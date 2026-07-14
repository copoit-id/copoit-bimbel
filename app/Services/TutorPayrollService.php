<?php

namespace App\Services;

use App\Models\Tentor;
use App\Models\TutorAttendance;
use App\Models\TutorPayroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TutorPayrollService
{
    public function generate(Tentor $tentor, Carbon $periodStart, Carbon $periodEnd, int $ratePerAttendance, ?User $generatedBy = null): TutorPayroll
    {
        return DB::transaction(function () use ($tentor, $periodStart, $periodEnd, $ratePerAttendance, $generatedBy): TutorPayroll {
            $payroll = TutorPayroll::query()->firstOrNew([
                'tentor_id' => $tentor->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ]);

            if ($payroll->exists && $payroll->status === 'paid') {
                return $payroll;
            }

            $attendances = TutorAttendance::query()
                ->with('session.class:class_id,title')
                ->where('tentor_id', $tentor->id)
                ->whereIn('status', ['present', 'late'])
                ->whereHas('session', fn ($query) => $query->whereBetween('session_date', [
                    $periodStart->toDateString(),
                    $periodEnd->toDateString(),
                ]))
                ->get();

            $payroll->fill([
                'rate_per_attendance' => $ratePerAttendance,
                'gross_amount' => $attendances->count() * $ratePerAttendance,
                'net_amount' => ($attendances->count() * $ratePerAttendance) + (int) ($payroll->adjustment_amount ?? 0),
                'status' => 'draft',
                'generated_by' => $generatedBy?->id,
            ]);
            $payroll->save();

            $payroll->items()->delete();
            foreach ($attendances as $attendance) {
                $session = $attendance->session;
                $payroll->items()->create([
                    'tutor_attendance_id' => $attendance->id,
                    'class_session_id' => $session?->id,
                    'session_date' => $session?->session_date,
                    'description' => $session?->class?->title ?? 'Sesi kelas',
                    'amount' => $ratePerAttendance,
                ]);
            }

            return $payroll->fresh(['tentor', 'items']);
        });
    }
}
