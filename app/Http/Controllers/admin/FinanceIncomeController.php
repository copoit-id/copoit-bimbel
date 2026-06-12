<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Carbon\Carbon;

class FinanceIncomeController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['user', 'package'])
            ->where('status', 'success')
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->paginate(15);

        $totalIncome = Payment::where('status', 'success')->sum('total_amount');
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

        $items = Payment::query()
            ->where('status', 'success')
            ->whereBetween('created_at', [$from, $to])
            ->get(['total_amount', 'paid_at', 'created_at']);

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
                $buckets[$key]['total'] += (float) $item->total_amount;
            }
        }

        return [
            'labels' => array_values(array_map(fn ($b) => $b['label'], $buckets)),
            'values' => array_values(array_map(fn ($b) => (float) $b['total'], $buckets)),
        ];
    }
}
