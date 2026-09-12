<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

class TutorSchedulePeriodService
{
    /**
     * @return array{start: Carbon, end: Carbon, selected_year: int, selected_month: int, selected_week: int|null, month_label: string, week_options: array<int, array{value: int, label: string}>}
     */
    public function resolve(Request $request, string $range): array
    {
        $now = now();
        $year = $request->integer('period_year');
        $year = $year >= $now->year - 10 && $year <= $now->year + 10 ? $year : $now->year;
        $month = $request->integer('period_month');
        $month = $month >= 1 && $month <= 12 ? $month : $now->month;
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();

        if ($range === 'month') {
            return [
                'start' => $monthStart,
                'end' => $monthStart->copy()->endOfMonth(),
                'selected_year' => $year,
                'selected_month' => $month,
                'selected_week' => null,
                'month_label' => $monthStart->locale('id')->translatedFormat('F Y'),
                'week_options' => [],
            ];
        }

        $calendarStart = $monthStart->copy()->startOfWeek();
        $calendarEnd = $monthStart->copy()->endOfMonth()->endOfWeek();
        $weekCount = (int) ceil(($calendarStart->diffInDays($calendarEnd) + 1) / 7);
        $week = $request->integer('period_week');
        $week = $week >= 1 && $week <= $weekCount ? $week : (int) ceil($now->day / 7);
        $week = min($week, $weekCount);
        $start = $calendarStart->copy()->addWeeks($week - 1);
        $end = $start->copy()->endOfWeek();
        $weekOptions = collect(range(1, $weekCount))
            ->map(function (int $number) use ($calendarStart): array {
                $weekStart = $calendarStart->copy()->addWeeks($number - 1);

                return [
                    'value' => $number,
                    'label' => 'Minggu ke-'.$number.' · '.$weekStart->locale('id')->translatedFormat('d M').' – '.$weekStart->copy()->endOfWeek()->locale('id')->translatedFormat('d M'),
                ];
            })
            ->all();

        return [
            'start' => $start,
            'end' => $end,
            'selected_year' => $year,
            'selected_month' => $month,
            'selected_week' => $week,
            'month_label' => $monthStart->locale('id')->translatedFormat('F Y'),
            'week_options' => $weekOptions,
        ];
    }

    /** @return array<int, int> */
    public function yearOptions(): array
    {
        return range(now()->year - 5, now()->year + 5);
    }
}
