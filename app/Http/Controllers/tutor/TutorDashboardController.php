<?php

namespace App\Http\Controllers\tutor;

use App\Http\Controllers\Controller;
use App\Models\ClassAttendance;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\TutorAttendance;
use App\Services\ClassAttendanceParticipantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TutorDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $tentor = $request->user()->tentorProfile;
        $weekStart = now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $weeklySessions = $this->sessionsFor($tentor->id, includeTutorAttendance: false)
            ->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get()
            ->groupBy(fn (ClassSession $session) => $session->start_at->isoWeekday());

        if ($weeklySessions->isEmpty()) {
            $nextSession = $this->sessionsFor($tentor->id, includeTutorAttendance: false)
                ->where('start_at', '>=', now()->startOfDay())
                ->first();

            if ($nextSession) {
                $weekStart = $nextSession->start_at->copy()->startOfWeek();
                $weekEnd = $weekStart->copy()->endOfWeek();
                $weeklySessions = $this->sessionsFor($tentor->id, includeTutorAttendance: false)
                    ->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                    ->get()
                    ->groupBy(fn (ClassSession $session) => $session->start_at->isoWeekday());
            }
        }

        $weekDates = collect(range(1, 7))
            ->mapWithKeys(fn (int $day) => [$day => $weekStart->copy()->addDays($day - 1)]);
        $dayLabels = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        return view('tutor.dashboard', compact('tentor', 'weeklySessions', 'weekDates', 'dayLabels'));
    }

    public function attendanceIndex(Request $request): View
    {
        $tentor = $request->user()->tentorProfile;
        $activeTab = $request->string('tab')->toString();
        $activeTab = in_array($activeTab, ['schedule', 'latest'], true) ? $activeTab : 'schedule';

        $sessions = $this->sessionsFor($tentor->id)
            ->whereBetween('session_date', [now()->subDays(30)->toDateString(), now()->addDays(30)->toDateString()])
            ->get();

        $sessionsBySchedule = $sessions
            ->groupBy('class_schedule_id')
            ->sortBy(fn ($scheduleSessions) => $scheduleSessions->first()->schedule?->title ?? $scheduleSessions->first()->class?->title ?? '')
            ->map(fn ($scheduleSessions) => $scheduleSessions->sortBy('start_at')->values());

        $latestSessions = $sessions->sortByDesc('start_at')->values();

        $monthAttendances = TutorAttendance::query()
            ->where('tentor_id', $tentor->id)
            ->whereHas('session', fn ($query) => $query->whereBetween('session_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]))
            ->count();

        return view('tutor.attendance-index', compact(
            'tentor',
            'activeTab',
            'sessionsBySchedule',
            'latestSessions',
            'monthAttendances',
        ));
    }

    public function showAttendanceSchedule(Request $request, ClassSchedule $classSchedule): View
    {
        $tentor = $request->user()->tentorProfile;
        $assignedSessions = $this->sessionsFor($tentor->id)
            ->where('class_schedule_id', $classSchedule->id);

        abort_unless($assignedSessions->exists(), 403, 'Jadwal ini bukan tugas Anda.');

        $sessions = $this->sessionsFor($tentor->id)
            ->where('class_schedule_id', $classSchedule->id)
            ->whereBetween('session_date', [now()->subDays(30)->toDateString(), now()->addDays(30)->toDateString()])
            ->get();

        return view('tutor.attendance-schedule', compact('tentor', 'classSchedule', 'sessions'));
    }

    public function showSession(Request $request, ClassSession $session, ClassAttendanceParticipantService $participantService): View
    {
        $tentor = $request->user()->tentorProfile;
        $this->ensureAssignedSession($session, $tentor->id);

        $session->load([
            'class:class_id,title',
            'schedule.attendanceSetting',
            'studyGroup.users',
            'schedule.destinationCategories.children',
            'attendances.user:id,name,email',
            'tutorAttendance',
        ]);
        $participants = $participantService->participants($session);
        $attendances = $session->attendances->keyBy('user_id');
        $canManageStudentAttendance = $this->isAttendanceOpen($session);

        return view('tutor.session-attendance', compact(
            'tentor',
            'session',
            'participants',
            'attendances',
            'canManageStudentAttendance',
        ));
    }

    public function markOwnAttendance(Request $request, ClassSession $session): RedirectResponse
    {
        $tentor = $request->user()->tentorProfile;
        $this->ensureAssignedSession($session, $tentor->id);
        $this->ensureAttendanceOpen($session);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $existingAttendance = TutorAttendance::query()
            ->where('class_session_id', $session->id)
            ->where('tentor_id', $tentor->id)
            ->first();
        $photoPath = $validated['photo']->store('tutor-attendance-photos', 'public');

        if ($existingAttendance?->photo_path && Storage::disk('public')->exists($existingAttendance->photo_path)) {
            Storage::disk('public')->delete($existingAttendance->photo_path);
        }

        TutorAttendance::updateOrCreate(
            ['class_session_id' => $session->id, 'tentor_id' => $tentor->id],
            [
                'status' => now()->gt($session->start_at) ? 'late' : 'present',
                'check_in_at' => now(),
                'photo_path' => $photoPath,
                'source' => 'tutor',
                'marked_by' => $request->user()->id,
            ]
        );

        return redirect()
            ->route('tutor.attendance.schedule.show', $session->class_schedule_id)
            ->with('success', 'Kehadiran Anda berhasil dicatat.');
    }

    public function markStudentAttendance(Request $request, ClassSession $session, ClassAttendanceParticipantService $participantService): RedirectResponse
    {
        $tentor = $request->user()->tentorProfile;
        $this->ensureAssignedSession($session, $tentor->id);
        $this->ensureAttendanceOpen($session);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', 'in:present,late,absent,excused'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($participantService->participants($session)->contains('id', (int) $validated['user_id']), 403, 'Siswa tidak terdaftar pada sesi ini.');

        ClassAttendance::updateOrCreate(
            ['class_session_id' => $session->id, 'user_id' => $validated['user_id']],
            [
                'status' => $validated['status'],
                'check_in_at' => now(),
                'source' => 'tutor',
                'notes' => $validated['notes'] ?? null,
                'marked_by' => $request->user()->id,
            ]
        );

        return redirect()->route('tutor.attendance.show', $session)->with('success', 'Absensi siswa berhasil diperbarui.');
    }

    private function sessionsFor(int $tentorId, bool $includeTutorAttendance = true)
    {
        $relations = ['class:class_id,title', 'schedule:id,title', 'studyGroup:id,name'];

        if ($includeTutorAttendance) {
            $relations = [...$relations, 'tutorAttendance', 'schedule.attendanceSetting'];
        }

        return ClassSession::query()
            ->with($relations)
            ->where('tentor_id', $tentorId)
            ->orderBy('start_at');
    }

    private function ensureAssignedSession(ClassSession $session, int $tentorId): void
    {
        abort_unless((int) $session->tentor_id === $tentorId, 403, 'Sesi ini bukan tugas Anda.');
    }

    private function isAttendanceOpen(ClassSession $session): bool
    {
        [$openAt, $closeAt] = $this->attendanceWindow($session);

        return $session->status === 'scheduled' && now()->between($openAt, $closeAt);
    }

    private function ensureAttendanceOpen(ClassSession $session): void
    {
        abort_unless($session->status === 'scheduled', 422, 'Sesi kelas tidak dapat diabsen.');
        abort_unless($this->isAttendanceOpen($session), 422, 'Absensi belum dibuka atau sudah ditutup.');
    }

    private function attendanceWindow(ClassSession $session): array
    {
        $session->loadMissing('schedule.attendanceSetting');
        $setting = $session->schedule?->attendanceSetting;

        return [
            $session->start_at->copy()->subMinutes($setting?->open_minutes_before ?? 30),
            ($session->end_at ?? $session->start_at)->copy()->addMinutes($setting?->close_minutes_after ?? 60),
        ];
    }
}
