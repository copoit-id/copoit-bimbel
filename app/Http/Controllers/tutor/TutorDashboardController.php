<?php

namespace App\Http\Controllers\tutor;

use App\Http\Controllers\Controller;
use App\Models\ClassAttendance;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\ScheduleBookingRequest;
use App\Models\TutorAttendance;
use App\Models\TutorPayroll;
use App\Models\TutorPayrollItem;
use App\Services\ClassAttendanceParticipantService;
use App\Services\PlanModuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TutorDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $tentor = $request->user()->tentorProfile;
        $planModules = app(PlanModuleService::class);
        $bookingEnabled = (bool) config('client.branding.booking_schedule_enabled', false)
            && $planModules->allows('booking');
        $payrollEnabled = $planModules->allows('tutor_payroll');
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthSessions = $this->sessionsFor($tentor->id, includeTutorAttendance: false)
            ->whereBetween('session_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->count();
        $upcomingSessions = $this->sessionsFor($tentor->id, includeTutorAttendance: false)
            ->where('start_at', '>=', now())
            ->limit(5)
            ->get();
        $attendanceSummary = TutorAttendance::query()
            ->where('tentor_id', $tentor->id)
            ->whereHas('session', fn ($query) => $query->whereBetween('session_date', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ]))
            ->selectRaw("SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as attended_count")
            ->selectRaw("SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending_count")
            ->first();
        $bookingSummary = [
            'waiting_count' => 0,
            'upcoming_count' => 0,
            'student_count' => 0,
        ];

        if ($bookingEnabled) {
            $bookingSummary = [
                'waiting_count' => ScheduleBookingRequest::query()
                    ->where('tentor_id', $tentor->id)
                    ->awaitingResponse()
                    ->count(),
                'upcoming_count' => ScheduleBookingRequest::query()
                    ->where('tentor_id', $tentor->id)
                    ->whereIn('status', [
                        ScheduleBookingRequest::STATUS_PENDING,
                        ScheduleBookingRequest::STATUS_COUNTER_PROPOSED,
                        ScheduleBookingRequest::STATUS_APPROVED,
                    ])
                    ->where('requested_start_at', '>=', now())
                    ->count(),
                'student_count' => ScheduleBookingRequest::query()
                    ->where('tentor_id', $tentor->id)
                    ->distinct('user_id')
                    ->count('user_id'),
            ];
        }

        $payrollSummary = [
            'current_amount' => 0,
            'paid_amount' => 0,
            'current_count' => 0,
        ];
        $recentPayrolls = collect();

        if ($payrollEnabled) {
            $currentPayrolls = TutorPayroll::query()
                ->where('tentor_id', $tentor->id)
                ->whereDate('period_start', '<=', $monthEnd->toDateString())
                ->whereDate('period_end', '>=', $monthStart->toDateString());
            $payrollSummary = [
                'current_amount' => (int) (clone $currentPayrolls)->sum('net_amount'),
                'paid_amount' => (int) (clone $currentPayrolls)->where('status', 'paid')->sum('net_amount'),
                'current_count' => (clone $currentPayrolls)->count(),
            ];
            $recentPayrolls = TutorPayroll::query()
                ->where('tentor_id', $tentor->id)
                ->latest('period_end')
                ->limit(4)
                ->get(['id', 'period_start', 'period_end', 'net_amount', 'status', 'paid_at']);
        }

        return view('tutor.dashboard', compact(
            'tentor',
            'bookingEnabled',
            'payrollEnabled',
            'monthSessions',
            'upcomingSessions',
            'attendanceSummary',
            'bookingSummary',
            'payrollSummary',
            'recentPayrolls',
        ));
    }

    public function schedule(Request $request): View
    {
        $tentor = $request->user()->tentorProfile;
        $schedule = $this->weeklyScheduleData($tentor->id);
        $canManageSchedule = app(PlanModuleService::class)->allows('schedule');

        return view('tutor.schedule', [
            'tentor' => $tentor,
            'canManageSchedule' => $canManageSchedule,
            ...$schedule,
        ]);
    }

    public function earnings(Request $request): View
    {
        $tentor = $request->user()->tentorProfile;
        $items = TutorPayrollItem::query()
            ->with(['payroll:id,tentor_id,status,paid_at', 'session:id,class_schedule_id,start_at', 'session.schedule:id,title'])
            ->whereHas('payroll', fn ($query) => $query->where('tentor_id', $tentor->id))
            ->latest('session_date')
            ->paginate(20);

        $summary = TutorPayroll::query()
            ->where('tentor_id', $tentor->id)
            ->selectRaw("SUM(CASE WHEN status = 'paid' THEN net_amount ELSE 0 END) as paid_amount")
            ->selectRaw("SUM(CASE WHEN status != 'paid' THEN net_amount ELSE 0 END) as pending_amount")
            ->first();

        return view('tutor.earnings', compact('items', 'summary'));
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
        abort_if($existingAttendance?->approval_status === 'approved', 422, 'Absensi Anda sudah disetujui admin dan tidak dapat diubah.');

        $photoPath = $validated['photo']->store('tutor-attendance-photos', 'public');

        if ($existingAttendance?->photo_path && Storage::disk('public')->exists($existingAttendance->photo_path)) {
            Storage::disk('public')->delete($existingAttendance->photo_path);
        }

        TutorAttendance::updateOrCreate(
            ['class_session_id' => $session->id, 'tentor_id' => $tentor->id],
            [
                'status' => now()->gt($session->start_at) ? 'late' : 'present',
                'approval_status' => 'pending',
                'check_in_at' => now(),
                'photo_path' => $photoPath,
                'source' => 'tutor',
                'marked_by' => $request->user()->id,
                'approved_by' => null,
                'approved_at' => null,
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

    private function weeklyScheduleData(int $tentorId): array
    {
        $weekStart = now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $weeklySessions = $this->sessionsFor($tentorId, includeTutorAttendance: false)
            ->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get()
            ->groupBy(fn (ClassSession $session) => $session->start_at->isoWeekday());

        if ($weeklySessions->isEmpty()) {
            $nextSession = $this->sessionsFor($tentorId, includeTutorAttendance: false)
                ->where('start_at', '>=', now()->startOfDay())
                ->first();

            if ($nextSession) {
                $weekStart = $nextSession->start_at->copy()->startOfWeek();
                $weekEnd = $weekStart->copy()->endOfWeek();
                $weeklySessions = $this->sessionsFor($tentorId, includeTutorAttendance: false)
                    ->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                    ->get()
                    ->groupBy(fn (ClassSession $session) => $session->start_at->isoWeekday());
            }
        }

        return [
            'weeklySessions' => $weeklySessions,
            'weekDates' => collect(range(1, 7))
                ->mapWithKeys(fn (int $day) => [$day => $weekStart->copy()->addDays($day - 1)]),
            'dayLabels' => [
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
                7 => 'Minggu',
            ],
        ];
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
