<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\BillInvoicePayment;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class FinanceIncomeController extends Controller
{
    public function index(Request $request)
    {
        $packagePayments = Payment::with(['user', 'package'])
            ->where('status', 'success')
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->get();
        $billPayments = BillInvoicePayment::with(['invoice:id,recurring_bill_id,user_id,title,invoice_number', 'invoice.user:id,name,email'])
            ->orderByDesc('paid_at')
            ->get();

        $incomeRows = $packagePayments->map(fn (Payment $payment): array => [
            'transaction_id' => $payment->transaction_id,
            'user_name' => $payment->user?->name ?? '-',
            'user_email' => $payment->user?->email ?? '-',
            'item_name' => $payment->package?->name ?? '-',
            'item_type' => 'Paket',
            'amount' => (int) $payment->total_amount,
            'paid_at' => $payment->paid_at ?? $payment->created_at,
            'detail_route' => route('admin.pembayaran.show', $payment->payment_id),
        ])->concat($billPayments->map(fn (BillInvoicePayment $payment): array => [
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

        $totalIncome = (int) Payment::where('status', 'success')->sum('total_amount')
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
                'week' => 'W' . $bucketDate->format('W') . ' ' . $bucketDate->format('Y'),
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

        $packageItems = Payment::query()
            ->where('status', 'success')
            ->whereBetween('created_at', [$from, $to])
            ->get(['total_amount as amount', 'paid_at', 'created_at']);
        $billItems = BillInvoicePayment::query()
            ->whereBetween('paid_at', [$from, $to])
            ->get(['amount', 'paid_at', 'created_at']);
        $items = $packageItems->concat($billItems);

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
