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
            'schedule.destinationCategories.children',
        ]);

        $categoryIds = $this->destinationCategoryIds($session);

        if (!empty($categoryIds)) {
            return User::query()
                ->where('role', 'user')
                ->whereIn('participant_destination_category_id', $categoryIds)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'participant_destination_category_id']);
        }

        $packageIds = $session->class?->packages()->pluck('packages.package_id') ?? collect();

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
