<?php

namespace App\Services;

use App\Models\ClassSession;
use App\Models\ParticipantDestinationCategory;
use App\Models\User;
use App\Models\UserPackageAcces;
use Illuminate\Support\Collection;

class ClassAttendanceParticipantService
{
    public function participants(ClassSession $session): Collection
    {
        $session->loadMissing([
            'class.packages',
            'studyGroup.users',
            'schedule.destinationCategories.children',
            'schedule.packages',
        ]);

        $categoryIds = $this->destinationCategoryIds($session);

        if (! empty($categoryIds)) {
            return User::query()
                ->where('role', 'user')
                ->whereIn('participant_destination_category_id', $categoryIds)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'participant_destination_category_id']);
        }

        $studyGroupUsers = $session->studyGroup?->users ?? collect();
        if ($studyGroupUsers->isNotEmpty()) {
            return $studyGroupUsers
                ->sortBy('name')
                ->values();
        }

        $schedulePackageIds = $session->schedule->packages->pluck('package_id');
        $packageIds = ($schedulePackageIds->isNotEmpty()
            ? $schedulePackageIds
            : ($session->class?->packages?->pluck('package_id') ?? collect()))
            ->unique()
            ->values();

        if ($packageIds->isEmpty()) {
            return collect();
        }

        return UserPackageAcces::query()
            ->with('user:id,name,email')
            ->whereIn('package_id', $packageIds)
            ->active()
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();
    }

    private function destinationCategoryIds(ClassSession $session): array
    {
        return $session->schedule->destinationCategories
            ->flatMap(function (ParticipantDestinationCategory $category) {
                return $category->children
                    ->pluck('id')
                    ->prepend($category->id);
            })
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
