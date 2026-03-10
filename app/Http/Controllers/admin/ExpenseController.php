<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::with('author')
            ->orderByDesc('spent_at')
            ->orderByDesc('created_at')
            ->paginate(15);

        $totalExpense = Expense::sum('amount');
        $period = request()->input('period', 'month');
        $chart = $this->buildChartData($period);

        return view('admin.pages.finance.expenses', compact('expenses', 'totalExpense', 'chart', 'period'));
    }

    public function create()
    {
        return view('admin.pages.finance.expenses-create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'spent_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        Expense::create([
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'spent_at' => $validated['spent_at'] ? Carbon::parse($validated['spent_at']) : null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('admin.finance.expenses.index')
            ->with('success', 'Pengeluaran berhasil ditambahkan.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('admin.finance.expenses.index')
            ->with('success', 'Pengeluaran berhasil dihapus.');
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

        $items = Expense::query()
            ->whereBetween('created_at', [$from, $to])
            ->get(['amount', 'spent_at', 'created_at']);

        foreach ($items as $item) {
            $date = $item->spent_at ?? $item->created_at;
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
