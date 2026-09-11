<?php

namespace App\Http\Controllers\tutor;

use App\Http\Controllers\Controller;
use App\Models\ScheduleBookingRequest;
use App\Models\ClassSession;
use App\Models\StudentFeedback;
use App\Models\StudentProgressReport;
use App\Models\StudyGroup;
use App\Models\UserPackageAcces;
use App\Services\ClassAttendanceParticipantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentDevelopmentController extends Controller
{
    public function createForSession(
        Request $request,
        ClassSession $session,
        ClassAttendanceParticipantService $participantService
    ): View {
        $this->ensureAssignedSession($request, $session);
        $packageId = $this->sessionPackageId($session);
        abort_unless($packageId, 422, 'Paket sesi tidak tersedia untuk laporan perkembangan.');

        $students = $participantService->participants($session);
        $reports = StudentProgressReport::query()
            ->where('class_session_id', $session->id)
            ->where('tentor_id', $request->user()->tentorProfile->id)
            ->with('user:id,name')
            ->latest()
            ->get();

        return view('tutor.schedule-progress-form', compact('session', 'students', 'reports'));
    }

    public function storeForSession(
        Request $request,
        ClassSession $session,
        ClassAttendanceParticipantService $participantService
    ): RedirectResponse {
        $this->ensureAssignedSession($request, $session);
        $validated = $this->validateSessionProgress($request);
        $packageId = $this->sessionPackageId($session);
        abort_unless($packageId, 422, 'Paket sesi tidak tersedia untuk laporan perkembangan.');

        $student = $participantService->participants($session)
            ->firstWhere('id', (int) $validated['user_id']);
        abort_unless($student, 403, 'Siswa bukan peserta sesi ini.');

        $tentorId = $request->user()->tentorProfile->id;
        $existing = StudentProgressReport::query()
            ->where('class_session_id', $session->id)
            ->where('user_id', $student->id)
            ->where('tentor_id', $tentorId)
            ->first();
        if ($existing) {
            return redirect()->route('tutor.schedule.progress.edit', [$session, $existing])
                ->with('success', 'Laporan untuk siswa ini sudah ada. Silakan perbarui laporan tersebut.');
        }

        $accessId = UserPackageAcces::query()
            ->where('user_id', $student->id)
            ->where('package_id', $packageId)
            ->active()
            ->latest('user_package_access_id')
            ->value('user_package_access_id');

        StudentProgressReport::query()->create([
            'tentor_id' => $tentorId,
            'user_id' => $student->id,
            'package_id' => $packageId,
            'study_group_id' => $session->study_group_id,
            'user_package_access_id' => $accessId,
            'class_session_id' => $session->id,
            'period_start' => $session->session_date,
            'period_end' => $session->session_date,
            ...collect($validated)->except('user_id')->all(),
        ]);

        return redirect()->route('tutor.schedule.progress.create', $session)
            ->with('success', 'Laporan perkembangan berhasil disimpan.');
    }

    public function editForSession(Request $request, ClassSession $session, StudentProgressReport $report): View
    {
        $this->ensureAssignedSession($request, $session);
        $this->ensureSessionReport($request, $session, $report);

        return view('tutor.schedule-progress-form', [
            'session' => $session,
            'students' => collect(),
            'reports' => collect(),
            'report' => $report->load('user:id,name'),
        ]);
    }

    public function updateForSession(Request $request, ClassSession $session, StudentProgressReport $report): RedirectResponse
    {
        $this->ensureAssignedSession($request, $session);
        $this->ensureSessionReport($request, $session, $report);

        $report->update(collect($this->validateSessionProgress($request))->except('user_id')->all());

        return redirect()->route('tutor.schedule.progress.create', $session)
            ->with('success', 'Laporan perkembangan berhasil diperbarui.');
    }

    public function destroyForSession(Request $request, ClassSession $session, StudentProgressReport $report): RedirectResponse
    {
        $this->ensureAssignedSession($request, $session);
        $this->ensureSessionReport($request, $session, $report);
        $report->delete();

        return redirect()->route('tutor.schedule.progress.create', $session)
            ->with('success', 'Laporan perkembangan berhasil dihapus.');
    }

    public function index(Request $request): View
    {
        $tentor = $request->user()->tentorProfile;
        $groups = StudyGroup::query()
            ->where('tentor_id', $tentor->id)
            ->where('status', StudyGroup::STATUS_ACTIVE)
            ->with(['users:id,name,email', 'package:package_id,name'])
            ->orderBy('name')
            ->limit(25)
            ->get();
        $groupTargets = $groups->flatMap(function (StudyGroup $group): Collection {
            $package = $group->package;

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
            ->whereNull('study_group_id')
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
            ->paginate(\App\Support\Pagination::perPage(10), ['*'], 'feedback_page');
        $progressHistory = StudentProgressReport::query()
            ->where('tentor_id', $tentor->id)
            ->with(['user:id,name', 'package:package_id,name', 'studyGroup:id,name'])
            ->latest('period_end')
            ->paginate(\App\Support\Pagination::perPage(10), ['*'], 'progress_page');

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

    private function ensureAssignedSession(Request $request, ClassSession $session): void
    {
        abort_unless((int) $session->tentor_id === (int) $request->user()->tentorProfile?->id, 403);
    }

    private function ensureSessionReport(Request $request, ClassSession $session, StudentProgressReport $report): void
    {
        abort_unless(
            (int) $report->class_session_id === (int) $session->id
            && (int) $report->tentor_id === (int) $request->user()->tentorProfile?->id,
            404
        );
    }

    private function sessionPackageId(ClassSession $session): ?int
    {
        $session->loadMissing([
            'studyGroup:id,package_id',
            'schedule.packages:package_id',
            'class.packages:package_id',
        ]);

        return $session->studyGroup?->package_id
            ?? $session->schedule?->packages->first()?->package_id
            ?? $session->class?->packages->first()?->package_id;
    }

    private function validateSessionProgress(Request $request): array
    {
        return $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'summary' => ['required', 'string', 'max:5000'],
        ]);
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
                ->when($packageId, fn ($query) => $query->where('package_id', $packageId))
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
