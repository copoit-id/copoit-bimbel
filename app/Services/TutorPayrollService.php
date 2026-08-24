<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Package;
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
                ->with($this->payrollAttendanceRelations())
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
                $payroll->items()->create([
                    'tutor_attendance_id' => $attendance->id,
                    ...$this->payrollItemData($attendance),
                ]);
            }

            return $this->recalculate($payroll)->fresh(['tentor', 'items']);
        });
    }

    public function syncApprovedAttendance(TutorAttendance $attendance, ?User $generatedBy = null): void
    {
        DB::transaction(function () use ($attendance, $generatedBy): void {
            $attendance->loadMissing($this->payrollAttendanceRelations());
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
                    ...$this->payrollItemData($attendance),
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

    public function syncExpense(TutorPayroll $payroll, ?User $createdBy = null): void
    {
        if ($payroll->status !== 'paid') {
            $payroll->expense()->delete();

            return;
        }

        $payroll->load('tentor:id,name');

        Expense::query()->updateOrCreate(
            ['tutor_payroll_id' => $payroll->id],
            [
                'title' => 'Gaji tutor: ' . ($payroll->tentor?->name ?? 'Tutor'),
                'amount' => $payroll->net_amount,
                'spent_at' => $payroll->paid_at ?? now(),
                'notes' => "Otomatis dari penggajian tutor periode {$payroll->period_start->format('d/m/Y')} - {$payroll->period_end->format('d/m/Y')}",
                'created_by' => $createdBy?->id ?? $payroll->paid_by,
            ]
        );
    }

    private function isPayable(TutorAttendance $attendance): bool
    {
        return $attendance->approval_status === 'approved'
            && in_array($attendance->status, ['present', 'late'], true)
            && $attendance->session !== null;
    }

    private function payrollAttendanceRelations(): array
    {
        return [
            'session.schedule.packages:package_id,name',
            'session.class.packages:package_id,name',
            'tentor:id,honor_per_attendance',
            'tentor.packageRates:tentor_id,package_id,amount',
        ];
    }

    private function payrollItemData(TutorAttendance $attendance): array
    {
        $session = $attendance->session;
        $packages = collect($session?->schedule?->packages ?? [])
            ->concat($session?->class?->packages ?? [])
            ->unique('package_id')
            ->sortBy('package_id')
            ->values();
        $ratesByPackage = ($attendance->tentor?->packageRates ?? collect())
            ->keyBy('package_id');
        $package = $packages->first(fn (Package $package): bool => $ratesByPackage->has($package->package_id))
            ?? $packages->first();
        $amount = $package && $ratesByPackage->has($package->package_id)
            ? (int) $ratesByPackage->get($package->package_id)->amount
            : (int) ($attendance->tentor?->honor_per_attendance ?? 0);
        $description = $session?->schedule?->title ?? $session?->class?->title ?? 'Sesi kelas';

        if ($package) {
            $description .= ' · Paket: '.$package->name;
        }

        return [
            'class_session_id' => $session?->id,
            'package_id' => $package?->package_id,
            'session_date' => $session?->session_date,
            'description' => $description,
            'amount' => $amount,
        ];
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
