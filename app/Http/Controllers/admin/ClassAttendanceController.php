<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\ParticipantDestinationCategory;
use App\Models\User;
use App\Models\UserPackageAcces;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassAttendanceController extends Controller
{
    public function show(ClassSession $session): View
    {
        $session->load(['class.packages', 'schedule.attendanceSetting', 'schedule.destinationCategories.parent', 'schedule.destinationCategories.children', 'attendances.user']);
        $participants = $this->participants($session);
        $attendances = $session->attendances->keyBy('user_id');

        return view('admin.pages.class-attendance.show', compact('session', 'participants', 'attendances'));
    }

    public function mark(Request $request, ClassSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'status' => ['required', 'in:present,late,absent,excused'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        ClassAttendance::updateOrCreate(
            [
                'class_session_id' => $session->id,
                'user_id' => $validated['user_id'],
            ],
            [
                'status' => $validated['status'],
                'check_in_at' => now(),
                'source' => 'admin',
                'notes' => $validated['notes'] ?? null,
                'marked_by' => $request->user()?->id,
            ]
        );

        return back()->with('success', 'Absensi berhasil diperbarui.');
    }

    private function participants(ClassSession $session)
    {
        $categoryIds = $this->destinationCategoryIds($session);

        if (!empty($categoryIds)) {
            return User::query()
                ->where('role', 'user')
                ->whereIn('participant_destination_category_id', $categoryIds)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'participant_destination_category_id']);
        }

        $packageIds = $session->class->packages()->pluck('packages.package_id');

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
