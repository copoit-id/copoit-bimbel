<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\UserPackageAcces;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserClassScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->loadMissing('participantDestinationCategory');
        $destinationCategoryIds = collect([
            $user->participant_destination_category_id,
            $user->participantDestinationCategory?->parent_id,
        ])->filter()->map(fn ($id) => (int) $id)->values();

        $packageIds = UserPackageAcces::query()
            ->where('user_id', $user->id)
            ->active()
            ->pluck('package_id');

        $sessions = ClassSession::with(['class', 'schedule.attendanceSetting', 'schedule.destinationCategories', 'attendances' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }])
            ->where(function ($query) use ($destinationCategoryIds, $packageIds) {
                if ($destinationCategoryIds->isNotEmpty()) {
                    $query->whereHas('schedule.destinationCategories', function ($categoryQuery) use ($destinationCategoryIds) {
                        $categoryQuery->whereIn('participant_destination_categories.id', $destinationCategoryIds);
                    });
                }

                $query->orWhere(function ($fallbackQuery) use ($packageIds) {
                    $fallbackQuery
                        ->whereDoesntHave('schedule.destinationCategories')
                        ->whereHas('class.packages', fn ($packageQuery) => $packageQuery->whereIn('packages.package_id', $packageIds));
                });
            })
            ->whereDate('session_date', '>=', now()->subDay()->toDateString())
            ->orderBy('start_at')
            ->paginate(15);

        return view('user.pages.class-schedule.index', compact('sessions'));
    }

    public function attend(Request $request, ClassSession $session): RedirectResponse
    {
        abort_unless($this->canAccessSession($request, $session), 403);

        $session->loadMissing('schedule.attendanceSetting');
        $setting = $session->schedule->attendanceSetting;
        $openAt = $session->start_at->copy()->subMinutes($setting?->open_minutes_before ?? 15);
        $closeAt = ($session->end_at ?? $session->start_at)->copy()->addMinutes($setting?->close_minutes_after ?? 30);

        if (now()->lt($openAt) || now()->gt($closeAt)) {
            return back()->with('error', 'Absensi belum dibuka atau sudah ditutup.');
        }

        $rules = [];
        if (($setting?->mode ?? 'button') === 'photo') {
            $rules['photo'] = ['required', 'image', 'max:4096'];
        }

        $validated = $request->validate($rules);
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendance-photos', 'public');
        }

        ClassAttendance::updateOrCreate(
            [
                'class_session_id' => $session->id,
                'user_id' => $request->user()->id,
            ],
            [
                'status' => now()->gt($session->start_at) ? 'late' : 'present',
                'check_in_at' => now(),
                'photo_path' => $photoPath,
                'source' => 'user',
            ]
        );

        return back()->with('success', 'Absensi berhasil dikirim.');
    }

    private function canAccessSession(Request $request, ClassSession $session): bool
    {
        $session->loadMissing('schedule.destinationCategories');
        $user = $request->user()->loadMissing('participantDestinationCategory');
        $destinationCategoryIds = collect([
            $user->participant_destination_category_id,
            $user->participantDestinationCategory?->parent_id,
        ])->filter()->map(fn ($id) => (int) $id);

        if ($session->schedule->destinationCategories->isNotEmpty()) {
            return $session->schedule->destinationCategories
                ->pluck('id')
                ->intersect($destinationCategoryIds)
                ->isNotEmpty();
        }

        $packageIds = $session->class->packages()->pluck('packages.package_id');

        return UserPackageAcces::query()
            ->where('user_id', $user->id)
            ->whereIn('package_id', $packageIds)
            ->active()
            ->exists();
    }
}
