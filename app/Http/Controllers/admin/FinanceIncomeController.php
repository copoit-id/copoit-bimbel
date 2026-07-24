<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\BillInvoicePayment;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class FinanceIncomeController extends Controller
{
    public function index(Request $request)
    {
        $legacyPackagePayments = Payment::with(['user', 'package'])
            ->where('status', 'success')
            ->doesntHave('installments')
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->get();
        $packageInstallments = PaymentInstallment::with(['payment.user', 'payment.package'])
            ->orderByDesc('paid_at')
            ->get();
        $billPayments = BillInvoicePayment::with(['invoice:id,recurring_bill_id,user_id,title,invoice_number', 'invoice.user:id,name,email'])
            ->orderByDesc('paid_at')
            ->get();

        $incomeRows = $legacyPackagePayments->map(fn (Payment $payment): array => [
            'transaction_id' => $payment->transaction_id,
            'user_name' => $payment->user?->name ?? '-',
            'user_email' => $payment->user?->email ?? '-',
            'item_name' => $payment->package?->name ?? '-',
            'item_type' => 'Paket',
            'amount' => (int) $payment->total_amount,
            'paid_at' => $payment->paid_at ?? $payment->created_at,
            'detail_route' => route('admin.pembayaran.show', $payment->payment_id),
        ])->concat($packageInstallments->map(fn (PaymentInstallment $installment): array => [
            'transaction_id' => $installment->receipt_number,
            'user_name' => $installment->payment?->user?->name ?? '-',
            'user_email' => $installment->payment?->user?->email ?? '-',
            'item_name' => $installment->payment?->package?->name ?? '-',
            'item_type' => 'Paket · Cicilan',
            'amount' => (int) $installment->amount,
            'paid_at' => $installment->paid_at,
            'detail_route' => $installment->payment
                ? route('admin.pembayaran.show', $installment->payment)
                : route('admin.finance.income.index'),
        ]))->concat($billPayments->map(fn (BillInvoicePayment $payment): array => [
            'transaction_id' => $payment->receipt_number,
            'user_name' => $payment->invoice?->user?->name ?? '-',
            'user_email' => $payment->invoice?->user?->email ?? '-',
            'item_name' => $payment->invoice?->title ?? 'Tagihan rutin',
            'item_type' => 'Tagihan Rutin',
            'amount' => (int) $payment->amount,
            'paid_at' => $payment->paid_at,
            'detail_route' => $payment->invoice?->recurring_bill_id
                ? route('admin.recurring-bills.show', $payment->invoice->recurring_bill_id)
                : route('admin.finance.income.index'),
        ]))->sortByDesc('paid_at')->values();

        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $payments = new LengthAwarePaginator(
            $incomeRows->forPage($page, $perPage)->values(),
            $incomeRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $totalIncome = (int) Payment::where('status', 'success')->doesntHave('installments')->sum('total_amount')
            + (int) PaymentInstallment::sum('amount')
            + (int) BillInvoicePayment::sum('amount');
        $period = request()->input('period', 'month');
        $chart = $this->buildChartData($period);

        return view('admin.pages.finance.income', compact('payments', 'totalIncome', 'chart', 'period'));
    }

    private function buildChartData(string $period): array
    {
        $period = in_array($period, ['day', 'week', 'month', 'year'], true) ? $period : 'month';

        $now = Carbon::now();
        $ranges = [
            'day' => 14,
            'week' => 12,
            'month' => 12,
            'year' => 5,
        ];
        $count = $ranges[$period];

        $buckets = [];
        for ($i = $count - 1; $i >= 0; $i--) {
            $bucketDate = match ($period) {
                'day' => $now->copy()->subDays($i)->startOfDay(),
                'week' => $now->copy()->subWeeks($i)->startOfWeek(),
                'month' => $now->copy()->subMonths($i)->startOfMonth(),
                'year' => $now->copy()->subYears($i)->startOfYear(),
            };
            $key = $bucketDate->toDateString();
            $label = match ($period) {
                'day' => $bucketDate->format('d M'),
                'week' => 'W'.$bucketDate->format('W').' '.$bucketDate->format('Y'),
                'month' => $bucketDate->format('M Y'),
                'year' => $bucketDate->format('Y'),
            };
            $buckets[$key] = [
                'label' => $label,
                'total' => 0,
                'start' => $bucketDate,
            ];
        }

        $from = reset($buckets)['start'];
        $to = $now->copy()->endOfDay();

        $legacyPackageItems = Payment::query()
            ->where('status', 'success')
            ->doesntHave('installments')
            ->whereBetween('created_at', [$from, $to])
            ->get(['total_amount as amount', 'paid_at', 'created_at']);
        $packageInstallmentItems = PaymentInstallment::query()
            ->whereBetween('paid_at', [$from, $to])
            ->get(['amount', 'paid_at', 'created_at']);
        $billItems = BillInvoicePayment::query()
            ->whereBetween('paid_at', [$from, $to])
            ->get(['amount', 'paid_at', 'created_at']);
        $items = $legacyPackageItems
            ->concat($packageInstallmentItems)
            ->concat($billItems);

        foreach ($items as $item) {
            $date = $item->paid_at ?? $item->created_at;
            $bucketDate = match ($period) {
                'day' => Carbon::parse($date)->startOfDay(),
                'week' => Carbon::parse($date)->startOfWeek(),
                'month' => Carbon::parse($date)->startOfMonth(),
                'year' => Carbon::parse($date)->startOfYear(),
            };
            $key = $bucketDate->toDateString();
            if (isset($buckets[$key])) {
                $buckets[$key]['total'] += (float) $item->amount;
            }
        }

        return [
            'labels' => array_values(array_map(fn ($b) => $b['label'], $buckets)),
            'values' => array_values(array_map(fn ($b) => (float) $b['total'], $buckets)),
        ];
    }
}
