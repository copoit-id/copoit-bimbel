<?php

namespace App\Http\Controllers\tutor;

use App\Http\Controllers\Controller;
use App\Models\ClassAttendance;
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
        $sessionsByDate = $this->sessionsFor($tentor->id)
            ->whereBetween('session_date', [now()->subDay()->toDateString(), now()->addDays(30)->toDateString()])
            ->get()
            ->groupBy(fn (ClassSession $session) => $session->session_date->toDateString());

        $monthAttendances = TutorAttendance::query()
            ->where('tentor_id', $tentor->id)
            ->whereHas('session', fn ($query) => $query->whereBetween('session_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]))
            ->count();

        return view('tutor.attendance-index', compact('tentor', 'sessionsByDate', 'monthAttendances'));
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

        return view('tutor.session-attendance', compact('tentor', 'session', 'participants', 'attendances'));
    }

    public function markOwnAttendance(Request $request, ClassSession $session): RedirectResponse
    {
        $tentor = $request->user()->tentorProfile;
        $this->ensureAssignedSession($session, $tentor->id);
        abort_if($session->status === 'cancelled', 422, 'Sesi kelas dibatalkan.');

        $session->loadMissing('schedule.attendanceSetting');
        $setting = $session->schedule->attendanceSetting;
        $openAt = $session->start_at->copy()->subMinutes($setting?->open_minutes_before ?? 30);
        $closeAt = ($session->end_at ?? $session->start_at)->copy()->addMinutes($setting?->close_minutes_after ?? 60);
        abort_if(now()->lt($openAt) || now()->gt($closeAt), 422, 'Absensi Tutor belum dibuka atau sudah ditutup.');

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

        return redirect()->route('tutor.attendance.show', $session)->with('success', 'Kehadiran Anda berhasil dicatat.');
    }

    public function markStudentAttendance(Request $request, ClassSession $session, ClassAttendanceParticipantService $participantService): RedirectResponse
    {
        $tentor = $request->user()->tentorProfile;
        $this->ensureAssignedSession($session, $tentor->id);

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
        $relations = ['class:class_id,title', 'studyGroup:id,name'];

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
}
