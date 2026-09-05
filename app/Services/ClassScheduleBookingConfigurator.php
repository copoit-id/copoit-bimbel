<?php

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\PackageBookingRule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ClassScheduleBookingConfigurator
{
    /**
     * @param  array<int, int|string>  $packageIds
     */
    public function ensurePackagesAvailable(array $packageIds, ?int $exceptScheduleId = null): void
    {
        $normalizedIds = $this->normalizeIds($packageIds);
        if ($normalizedIds->isEmpty()) {
            throw ValidationException::withMessages([
                'package_ids' => 'Pilih minimal satu paket untuk mengaktifkan request jadwal custom.',
            ]);
        }

        $conflictingPackages = ClassSchedule::query()
            ->where('allow_custom_booking', true)
            ->when($exceptScheduleId, fn ($query) => $query->whereKeyNot($exceptScheduleId))
            ->whereHas(
                'packages',
                fn ($query) => $query->whereIn('packages.package_id', $normalizedIds)
            )
            ->with('packages:package_id,name')
            ->get()
            ->flatMap(fn (ClassSchedule $schedule) => $schedule->packages)
            ->whereIn('package_id', $normalizedIds)
            ->pluck('name')
            ->unique()
            ->values();

        if ($conflictingPackages->isNotEmpty()) {
            throw ValidationException::withMessages([
                'package_ids' => 'Paket berikut sudah memiliki kelas dengan request custom: '.$conflictingPackages->join(', ').'.',
            ]);
        }
    }

    /**
     * @param  array<int, int|string>  $previousPackageIds
     */
    public function sync(
        ClassSchedule $schedule,
        array $previousPackageIds = [],
        bool $wasCustom = false
    ): void {
        // Booking policy is now owned by the package. Existing callers remain
        // safe, while a tenant can explicitly opt into the former schedule-
        // driven synchronisation during a staged migration if ever required.
        if (! config('client.branding.legacy_schedule_booking_sync', false)) {
            return;
        }

        $schedule->loadMissing(['packages:package_id,name', 'studyGroup:id,tentor_id']);
        $currentPackageIds = $schedule->packages
            ->pluck('package_id')
            ->map(fn ($id): int => (int) $id)
            ->values();
        $previousIds = $this->normalizeIds($previousPackageIds);

        if ($wasCustom) {
            $this->disableRulesFor(
                $previousIds->diff($currentPackageIds)->merge(
                    $schedule->allow_custom_booking ? [] : $currentPackageIds
                ),
                $schedule
            );
        }

        if (! $schedule->allow_custom_booking) {
            return;
        }

        $tentorId = $schedule->tentor_id ?: $schedule->studyGroup?->tentor_id;
        if (! $tentorId) {
            throw ValidationException::withMessages([
                'tentor_id' => 'Tutor wajib dipilih jika siswa boleh request jadwal custom.',
            ]);
        }
        if (! $schedule->end_time) {
            throw ValidationException::withMessages([
                'end_time' => 'Jam selesai wajib diisi untuk menghitung durasi jadwal custom.',
            ]);
        }

        $durationMinutes = (int) Carbon::parse('2000-01-01 '.$schedule->start_time)
            ->diffInMinutes(Carbon::parse('2000-01-01 '.$schedule->end_time));

        foreach ($currentPackageIds as $packageId) {
            $rule = PackageBookingRule::query()->updateOrCreate(
                ['package_id' => $packageId],
                [
                    'class_id' => $schedule->class_id,
                    'is_enabled' => true,
                    'session_quota' => $schedule->booking_session_quota,
                    'duration_minutes' => $durationMinutes,
                    'min_notice_hours' => 0,
                    'max_advance_days' => 365,
                    'cancellation_hours' => 0,
                    'allow_custom_time' => true,
                    'allow_all_tutors' => false,
                    'default_location' => $schedule->location,
                ]
            );
            $rule->tutors()->sync([$tentorId]);
        }
    }

    /**
     * @param  Collection<int, int>  $packageIds
     */
    private function disableRulesFor(Collection $packageIds, ClassSchedule $schedule): void
    {
        foreach ($packageIds->unique() as $packageId) {
            $hasReplacement = ClassSchedule::query()
                ->whereKeyNot($schedule->id)
                ->where('allow_custom_booking', true)
                ->whereHas(
                    'packages',
                    fn ($query) => $query->where('packages.package_id', $packageId)
                )
                ->exists();

            if (! $hasReplacement) {
                PackageBookingRule::query()
                    ->where('package_id', $packageId)
                    ->where('class_id', $schedule->class_id)
                    ->update(['is_enabled' => false]);
            }
        }
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return Collection<int, int>
     */
    private function normalizeIds(array $ids): Collection
    {
        return collect($ids)
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }
}
