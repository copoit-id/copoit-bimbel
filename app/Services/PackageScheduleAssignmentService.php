<?php

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\DetailPackage;
use App\Models\Package;
use Illuminate\Support\Facades\DB;

class PackageScheduleAssignmentService
{
    public function __construct(
        private readonly ClassScheduleBookingConfigurator $bookingConfigurator
    ) {}

    public function setSelected(Package $package, ClassSchedule $schedule, bool $selected): bool
    {
        return DB::transaction(function () use ($package, $schedule, $selected): bool {
            $lockedPackage = Package::query()
                ->whereKey($package->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedSchedule = ClassSchedule::query()
                ->whereKey($schedule->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $previousPackageIds = $lockedSchedule->packages()
                ->pluck('packages.package_id')
                ->map(fn ($packageId): int => (int) $packageId)
                ->all();

            if ($selected && $lockedSchedule->allow_custom_booking) {
                $this->bookingConfigurator->ensurePackagesAvailable(
                    [$lockedPackage->package_id],
                    $lockedSchedule->id
                );
            }

            $attributes = [
                'package_id' => $lockedPackage->package_id,
                'detailable_type' => $lockedSchedule->getMorphClass(),
                'detailable_id' => $lockedSchedule->id,
            ];

            if ($selected) {
                DetailPackage::query()->insertOrIgnore([
                    ...$attributes,
                    'order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DetailPackage::query()
                    ->where($attributes)
                    ->delete();
            }

            $lockedSchedule->unsetRelation('packages');
            $this->bookingConfigurator->sync(
                $lockedSchedule,
                $previousPackageIds,
                (bool) $lockedSchedule->allow_custom_booking
            );

            return DetailPackage::query()
                ->where($attributes)
                ->exists();
        }, 3);
    }
}
