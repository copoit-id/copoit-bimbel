<?php

namespace App\Http\Controllers\tutor;

use App\Http\Controllers\Controller;
use App\Models\ScheduleBookingRequest;
use App\Models\StudentFeedback;
use App\Models\StudentProgressReport;
use App\Models\StudyGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentDevelopmentController extends Controller
{
    public function index(Request $request): View
    {
        $tentor = $request->user()->tentorProfile;
        $groups = StudyGroup::query()
            ->where('tentor_id', $tentor->id)
            ->with(['users:id,name,email', 'bookingCohort.package:package_id,name'])
            ->orderBy('name')
            ->limit(25)
            ->get();
        $groupTargets = $groups->flatMap(function (StudyGroup $group): Collection {
            $package = $group->bookingCohort?->package;

            return $group->users->map(fn ($user): array => [
                'value' => implode(':', [
                    $user->id,
                    $package?->package_id ?? 0,
                    0,
                    $group->id,
                ]),
                'label' => "{$user->name} · {$group->name}",
                'can_report_progress' => (bool) $package,
            ]);
        });
        $personalTargets = ScheduleBookingRequest::query()
            ->where('tentor_id', $tentor->id)
            ->whereNull('booking_cohort_id')
            ->whereIn('status', [
                ScheduleBookingRequest::STATUS_APPROVED,
                ScheduleBookingRequest::STATUS_COMPLETED,
            ])
            ->with([
                'user:id,name,email',
                'package:package_id,name',
            ])
            ->latest()
            ->limit(25)
            ->get()
            ->unique(fn ($booking) => "{$booking->user_id}:{$booking->package_id}")
            ->map(fn ($booking): array => [
                'value' => implode(':', [
                    $booking->user_id,
                    $booking->package_id,
                    $booking->user_package_access_id,
                    0,
                ]),
                'label' => "{$booking->user->name} · Personal · {$booking->package->name}",
                'can_report_progress' => true,
            ]);
        $studentTargets = $groupTargets->merge($personalTargets)->values();
        $feedbackHistory = StudentFeedback::query()
            ->where('tentor_id', $tentor->id)
            ->with(['user:id,name', 'studyGroup:id,name'])
            ->latest()
            ->paginate(10, ['*'], 'feedback_page');
        $progressHistory = StudentProgressReport::query()
            ->where('tentor_id', $tentor->id)
            ->with(['user:id,name', 'package:package_id,name', 'studyGroup:id,name'])
            ->latest('period_end')
            ->paginate(10, ['*'], 'progress_page');

        return view('tutor.development.index', compact(
            'groups',
            'studentTargets',
            'feedbackHistory',
            'progressHistory',
        ));
    }

    public function storeFeedback(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:personal,group'],
            'student_target' => ['nullable', 'string', 'max:100'],
            'study_group_id' => ['nullable', 'integer', 'exists:study_groups,id'],
            'title' => ['required', 'string', 'max:255'],
            'feedback' => ['required', 'string', 'max:5000'],
            'is_visible_to_student' => ['nullable', 'boolean'],
        ]);
        $tentor = $request->user()->tentorProfile;

        if ($validated['scope'] === 'group') {
            $group = StudyGroup::query()
                ->whereKey($validated['study_group_id'] ?? null)
                ->where('tentor_id', $tentor->id)
                ->first();
            if (! $group) {
                throw ValidationException::withMessages([
                    'study_group_id' => 'Rombel tidak valid untuk Tutor ini.',
                ]);
            }
            $userId = null;
            $groupId = $group->id;
        } else {
            [$userId, , , $groupId] = $this->parseTarget($validated['student_target'] ?? '');
            $this->authorizeStudentTarget($tentor->id, $userId, $groupId ?: null);
        }

        StudentFeedback::query()->create([
            'tentor_id' => $tentor->id,
            'user_id' => $userId,
            'study_group_id' => $groupId ?: null,
            'scope' => $validated['scope'],
            'title' => $validated['title'],
            'feedback' => $validated['feedback'],
            'is_visible_to_student' => $request->boolean('is_visible_to_student', true),
        ]);

        return back()->with('success', 'Feedback siswa berhasil disimpan.');
    }

    public function storeProgress(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_target' => ['required', 'string', 'max:100'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'progress_percent' => ['nullable', 'integer', 'between:0,100'],
            'mastery_score' => ['nullable', 'integer', 'between:0,100'],
            'discipline_score' => ['nullable', 'integer', 'between:0,100'],
            'participation_score' => ['nullable', 'integer', 'between:0,100'],
            'summary' => ['required', 'string', 'max:5000'],
            'strengths' => ['nullable', 'string', 'max:3000'],
            'improvements' => ['nullable', 'string', 'max:3000'],
            'next_target' => ['nullable', 'string', 'max:3000'],
        ]);
        $tentor = $request->user()->tentorProfile;
        [$userId, $packageId, $accessId, $groupId] = $this->parseTarget($validated['student_target']);
        if (! $packageId) {
            throw ValidationException::withMessages([
                'student_target' => 'Paket target belum tersedia untuk laporan progres.',
            ]);
        }
        $this->authorizeStudentTarget($tentor->id, $userId, $groupId ?: null, $packageId);

        StudentProgressReport::query()->create([
            'tentor_id' => $tentor->id,
            'user_id' => $userId,
            'package_id' => $packageId,
            'study_group_id' => $groupId ?: null,
            'user_package_access_id' => $accessId ?: null,
            ...collect($validated)->except('student_target')->all(),
        ]);

        return back()->with('success', 'Laporan progres siswa berhasil disimpan.');
    }

    /**
     * @return array{int, int, int, int}
     */
    private function parseTarget(string $target): array
    {
        $parts = array_map('intval', explode(':', $target));
        if (count($parts) !== 4 || $parts[0] < 1) {
            throw ValidationException::withMessages([
                'student_target' => 'Target siswa tidak valid.',
            ]);
        }

        return [$parts[0], $parts[1], $parts[2], $parts[3]];
    }

    private function authorizeStudentTarget(
        int $tentorId,
        int $userId,
        ?int $groupId = null,
        ?int $packageId = null
    ): void {
        $allowed = $groupId
            ? StudyGroup::query()
                ->whereKey($groupId)
                ->where('tentor_id', $tentorId)
                ->whereHas('users', fn ($query) => $query->where('users.id', $userId))
                ->when($packageId, fn ($query) => $query->whereHas(
                    'bookingCohort',
                    fn ($cohortQuery) => $cohortQuery->where('package_id', $packageId)
                ))
                ->exists()
            : ScheduleBookingRequest::query()
                ->where('tentor_id', $tentorId)
                ->where('user_id', $userId)
                ->when($packageId, fn ($query) => $query->where('package_id', $packageId))
                ->whereIn('status', [
                    ScheduleBookingRequest::STATUS_APPROVED,
                    ScheduleBookingRequest::STATUS_COMPLETED,
                ])
                ->exists();

        if (! $allowed) {
            throw ValidationException::withMessages([
                'student_target' => 'Siswa tidak berada dalam tanggung jawab Tutor ini.',
            ]);
        }
    }
}
