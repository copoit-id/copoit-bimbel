<?php

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\ClassSession;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ClassScheduleService
{
    public function generateSessions(ClassSchedule $schedule, int $daysAhead = 60): int
    {
        $schedule->loadMissing(['class', 'studyGroup']);

        $start = $schedule->start_date->copy()->startOfDay();
        $end = $schedule->end_date
            ? $schedule->end_date->copy()->endOfDay()
            : now()->addDays($daysAhead)->endOfDay();
        $end = $end->min(now()->addDays($daysAhead)->endOfDay());

        $created = 0;

        foreach (CarbonPeriod::create($start, $end) as $date) {
            if (!$this->matchesSchedule($schedule, $date)) {
                continue;
            }

            $startAt = Carbon::parse($date->toDateString() . ' ' . $schedule->start_time);
            $endAt = $schedule->end_time
                ? Carbon::parse($date->toDateString() . ' ' . $schedule->end_time)
                : null;

            $session = ClassSession::query()->firstOrCreate(
                [
                    'class_schedule_id' => $schedule->id,
                    'session_date' => $date->toDateString(),
                    'start_at' => $startAt,
                ],
                [
                    'class_id' => $schedule->class_id,
                    'study_group_id' => $schedule->study_group_id,
                    'tentor_id' => $schedule->tentor_id ?: $schedule->studyGroup?->tentor_id ?: $schedule->class?->tentor_id,
                    'end_at' => $endAt,
                    'status' => 'scheduled',
                    'meeting_url' => $schedule->meeting_url ?: $schedule->class?->zoom_link,
                    'location' => $schedule->location,
                ]
            );

            if ($session->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    private function matchesSchedule(ClassSchedule $schedule, Carbon $date): bool
    {
        if ($schedule->schedule_type === 'single') {
            return $date->isSameDay($schedule->start_date);
        }

        return match ($schedule->frequency) {
            'daily' => true,
            'weekly' => (int) $date->dayOfWeekIso === (int) $schedule->day_of_week,
            'monthly' => (int) $date->day === (int) $schedule->day_of_month,
            default => false,
        };
    }
}
