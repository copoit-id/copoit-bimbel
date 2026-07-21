<?php

namespace App\Services;

use App\Models\Tentor;
use App\Models\TutorAttendance;
use App\Models\TutorPayroll;
use App\Models\TutorPayrollItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TutorPayrollService
{
    public function generate(Tentor $tentor, Carbon $periodStart, Carbon $periodEnd, ?User $generatedBy = null): TutorPayroll
    {
        return DB::transaction(function () use ($tentor, $periodStart, $periodEnd, $generatedBy): TutorPayroll {
            $payroll = TutorPayroll::query()->firstOrNew([
                'tentor_id' => $tentor->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ]);

            if ($payroll->exists && $payroll->status === 'paid') {
                return $payroll;
            }

            $attendances = TutorAttendance::query()
                ->with(['session.class:class_id,title', 'session.schedule:id,title', 'tentor:id,honor_per_attendance'])
                ->where('tentor_id', $tentor->id)
                ->whereIn('status', ['present', 'late'])
                ->where('approval_status', 'approved')
                ->whereHas('session', fn ($query) => $query->whereBetween('session_date', [
                    $periodStart->toDateString(),
                    $periodEnd->toDateString(),
                ]))
                ->get();

            $payroll->fill([
                'rate_per_attendance' => 0,
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
                    'description' => $session?->schedule?->title ?? $session?->class?->title ?? 'Sesi kelas',
                    'amount' => $this->honorFor($attendance),
                ]);
            }

            return $this->recalculate($payroll)->fresh(['tentor', 'items']);
        });
    }

    public function syncApprovedAttendance(TutorAttendance $attendance, ?User $generatedBy = null): void
    {
        DB::transaction(function () use ($attendance, $generatedBy): void {
            $attendance->loadMissing(['session.schedule:id,title', 'session.class:class_id,title', 'tentor:id,honor_per_attendance']);
            $existingItems = TutorPayrollItem::query()
                ->with('payroll')
                ->where('tutor_attendance_id', $attendance->id)
                ->get();

            if (! $this->isPayable($attendance)) {
                $existingItems
                    ->filter(fn (TutorPayrollItem $item) => $item->payroll && $item->payroll->status !== 'paid')
                    ->each(function (TutorPayrollItem $item): void {
                        $payroll = $item->payroll;
                        $item->delete();
                        $this->recalculate($payroll);
                    });

                return;
            }

            $sessionDate = $attendance->session->session_date;
            $periodStart = $sessionDate->copy()->startOfMonth();
            $periodEnd = $sessionDate->copy()->endOfMonth();
            $payroll = TutorPayroll::query()->firstOrNew([
                'tentor_id' => $attendance->tentor_id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ]);

            if ($payroll->exists && $payroll->status === 'paid') {
                return;
            }

            $payroll->fill([
                'rate_per_attendance' => 0,
                'status' => $payroll->status ?: 'draft',
                'generated_by' => $generatedBy?->id,
            ]);
            $payroll->save();

            $existingItems
                ->filter(fn (TutorPayrollItem $item) => $item->tutor_payroll_id !== $payroll->id && $item->payroll && $item->payroll->status !== 'paid')
                ->each(function (TutorPayrollItem $item): void {
                    $previousPayroll = $item->payroll;
                    $item->delete();
                    $this->recalculate($previousPayroll);
                });

            $payroll->items()->updateOrCreate(
                ['tutor_attendance_id' => $attendance->id],
                [
                    'class_session_id' => $attendance->session->id,
                    'session_date' => $attendance->session->session_date,
                    'description' => $attendance->session->schedule?->title ?? $attendance->session->class?->title ?? 'Sesi kelas',
                    'amount' => $this->honorFor($attendance),
                ]
            );

            $this->recalculate($payroll);
        });
    }

    public function syncApprovedAttendancesForTutor(Tentor $tentor, ?User $generatedBy = null): void
    {
        TutorAttendance::query()
            ->where('tentor_id', $tentor->id)
            ->where('approval_status', 'approved')
            ->whereIn('status', ['present', 'late'])
            ->orderBy('id')
            ->chunkById(100, function ($attendances) use ($generatedBy): void {
                $attendances->each(
                    fn (TutorAttendance $attendance) => $this->syncApprovedAttendance($attendance, $generatedBy)
                );
            });
    }

    private function isPayable(TutorAttendance $attendance): bool
    {
        return $attendance->approval_status === 'approved'
            && in_array($attendance->status, ['present', 'late'], true)
            && $attendance->session !== null;
    }

    private function honorFor(TutorAttendance $attendance): int
    {
        return (int) ($attendance->tentor?->honor_per_attendance ?? 0);
    }

    private function recalculate(TutorPayroll $payroll): TutorPayroll
    {
        if ($payroll->status === 'paid') {
            return $payroll;
        }

        $grossAmount = (int) $payroll->items()->sum('amount');
        $payroll->update([
            'gross_amount' => $grossAmount,
            'net_amount' => $grossAmount + (int) $payroll->adjustment_amount,
        ]);

        return $payroll;
    }
}
