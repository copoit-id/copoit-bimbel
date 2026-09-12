<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\DetailPackage;
use App\Models\Package;
use App\Models\StudyGroup;
use App\Models\Tentor;
use App\Services\ClassAttendanceParticipantService;
use App\Services\ClassScheduleBookingConfigurator;
use App\Services\ClassScheduleService;
use App\Services\PackageScheduleAssignmentService;
use App\Services\PlanModuleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClassScheduleController extends Controller
{
    public function index(Request $request, PlanModuleService $planModules): View
    {
        $activeTab = $request->query('tab', 'schedules');
        $scheduleRange = $request->string('range')->toString();
        $scheduleRange = in_array($scheduleRange, ['today', 'week', 'month', 'year', 'all'], true)
            ? $scheduleRange
            : 'all';
        $canUseClass = $planModules->allows('class');
        $canUseAttendance = $planModules->allows('attendance');
        $activeTab = $activeTab === 'zoom' && $canUseClass ? 'zoom' : 'schedules';
        $filteredPackage = $request->integer('package_id')
            ? Package::query()
                ->whereKey($request->integer('package_id'))
                ->first(['package_id', 'name'])
            : null;
        $packageOptions = Package::query()
            ->where(function ($query) use ($filteredPackage): void {
                $query->where('status', 'active');
                if ($filteredPackage) {
                    $query->orWhere('package_id', $filteredPackage->package_id);
                }
            })
            ->orderBy('name')
            ->get(['package_id', 'name']);
        $scheduleMorphType = (new ClassSchedule)->getMorphClass();
        $selectedScheduleIds = $filteredPackage
            ? DetailPackage::query()
                ->where('package_id', $filteredPackage->package_id)
                ->where('detailable_type', $scheduleMorphType)
                ->pluck('detailable_id')
                ->map(fn ($id): int => (int) $id)
            : collect();
        $selectedClassIds = $filteredPackage
            ? DetailPackage::query()
                ->where('package_id', $filteredPackage->package_id)
                ->where('detailable_type', ClassModel::class)
                ->pluck('detailable_id')
                ->map(fn ($id): int => (int) $id)
            : collect();
        $allSchedules = null;
        if ($activeTab === 'schedules' && $scheduleRange === 'all') {
            $allSchedules = ClassSchedule::query()
                ->with([
                    'studyGroup:id,name',
                    'tentor:id,name',
                    'packages:package_id,name',
                ])
                ->orderByDesc('is_active')
                ->orderBy('start_time')
                ->orderBy('title')
                ->paginate(\App\Support\Pagination::perPage(20), ['*'], 'schedule_page')
                ->withQueryString()
                ->through(fn (ClassSchedule $schedule): ClassSchedule => $this->presentScheduleListRow(
                    $schedule,
                    $selectedScheduleIds,
                ));
        }

        $scheduleSessions = null;
        $scheduleSessionDays = collect();
        $rangeLabel = null;
        if ($activeTab === 'schedules' && $scheduleRange !== 'all') {
            $now = now();
            [$rangeStart, $rangeEnd, $rangeLabel] = match ($scheduleRange) {
                'week' => [
                    $now->copy()->startOfWeek(),
                    $now->copy()->endOfWeek(),
                    'Minggu ini',
                ],
                'month' => [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth(),
                    'Bulan ini',
                ],
                'year' => [
                    $now->copy()->startOfYear(),
                    $now->copy()->endOfYear(),
                    'Tahun ini',
                ],
                default => [
                    $now->copy()->startOfDay(),
                    $now->copy()->endOfDay(),
                    'Hari ini',
                ],
            };

            $scheduleSessions = ClassSession::query()
                ->with([
                    'schedule:id,title,location,meeting_url',
                    'class:class_id,title',
                    'studyGroup:id,name',
                    'tentor:id,name',
                ])
                ->whereBetween('session_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                ->orderBy('start_at')
                ->paginate(50, ['*'], 'session_page')
                ->withQueryString()
                ->through(fn (ClassSession $session): ClassSession => $this->presentScheduleSessionRow($session));
            $scheduleSessionDays = $scheduleSessions->getCollection()
                ->groupBy(fn (ClassSession $session): string => $session->start_at->toDateString())
                ->map(function (Collection $sessions, string $date): array {
                    $day = Carbon::parse($date);

                    return [
                        ...$this->presentScheduleDay($day),
                        'sessions' => $sessions->values(),
                    ];
                })
                ->values();
        }

        $liveClasses = $canUseClass
            ? ClassModel::with('tentor')
                ->orderBy('schedule_time', 'desc')
                ->paginate(\App\Support\Pagination::perPage(10), ['*'], 'kelas_page')
                ->withQueryString()
            : null;

        $tabParameters = array_filter([
            'package_id' => $filteredPackage?->package_id,
            'range' => $scheduleRange,
        ]);
        $scheduleTabs = [
            [
                'id' => 'schedules',
                'label' => 'Kelas Terjadwal',
                'active' => $activeTab === 'schedules',
                'href' => route('admin.class-schedules.index', ['tab' => 'schedules', ...$tabParameters]),
            ],
        ];
        if ($canUseClass) {
            $scheduleTabs[] = [
                'id' => 'zoom',
                'label' => 'Kelas Zoom',
                'active' => $activeTab === 'zoom',
                'href' => route('admin.class-schedules.index', ['tab' => 'zoom', ...$tabParameters]),
            ];
        }

        $scheduleRangeTabs = collect([
            'all' => 'Semua',
            'today' => 'Hari ini',
            'week' => 'Minggu',
            'month' => 'Bulan',
            'year' => 'Tahun',
        ])->map(fn (string $label, string $range): array => [
            'id' => $range,
            'label' => $label,
            'active' => $scheduleRange === $range,
            'href' => route('admin.class-schedules.index', array_filter([
                'tab' => 'schedules',
                'package_id' => $filteredPackage?->package_id,
                'range' => $range,
            ])),
        ])->values()->all();

        return view('admin.pages.class-schedule.index', compact(
            'activeTab',
            'scheduleRange',
            'scheduleSessions',
            'scheduleSessionDays',
            'rangeLabel',
            'scheduleTabs',
            'scheduleRangeTabs',
            'allSchedules',
            'liveClasses',
            'canUseClass',
            'canUseAttendance',
            'filteredPackage',
            'packageOptions',
            'selectedScheduleIds',
            'selectedClassIds',
        ));
    }

    /**
     * Add display-ready session data for the admin schedule agenda.
     */
    private function presentScheduleSessionRow(ClassSession $session): ClassSession
    {
        $session->setAttribute('agenda_title', $session->schedule?->title ?? $session->class?->title ?? 'Sesi belajar');
        $session->setAttribute(
            'agenda_time_label',
            $session->start_at->format('H:i')
                . ($session->end_at ? ' – ' . $session->end_at->format('H:i') : '')
                . ' WIB',
        );
        $session->setAttribute('agenda_group_label', $session->studyGroup?->name ?? 'Sesi personal');
        $session->setAttribute('agenda_tutor_label', $session->tentor?->name ?? 'Belum ditetapkan');
        $session->setAttribute('agenda_location', $session->schedule?->location);
        $session->setAttribute('agenda_meeting_url', $session->schedule?->meeting_url);
        $session->setAttribute(
            'agenda_status_label',
            match ($session->status) {
                'completed' => 'Selesai',
                'cancelled' => 'Dibatalkan',
                default => 'Terjadwal',
            },
        );
        $session->setAttribute(
            'agenda_status_class',
            match ($session->status) {
                'completed' => 'bg-emerald-50 text-emerald-700',
                'cancelled' => 'bg-rose-50 text-rose-700',
                default => 'bg-primary/10 text-primary',
            },
        );

        return $session;
    }

    /**
     * @return array{day_number: string, day_name: string, date_label: string, state_label: string, state_class: string, date_class: string, timeline_class: string}
     */
    private function presentScheduleDay(Carbon $date): array
    {
        $base = [
            'day_number' => $date->format('d'),
            'day_name' => $date->locale('id')->translatedFormat('l'),
            'date_label' => $date->locale('id')->translatedFormat('d F Y'),
        ];

        if ($date->isToday()) {
            return [...$base,
                'state_label' => 'Hari ini',
                'state_class' => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
                'date_class' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'timeline_class' => 'bg-emerald-400 ring-4 ring-emerald-50',
            ];
        }

        if ($date->isFuture()) {
            return [...$base,
                'state_label' => 'Mendatang',
                'state_class' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-100',
                'date_class' => 'border-blue-100 bg-blue-50/70 text-blue-700',
                'timeline_class' => 'bg-blue-400 ring-4 ring-blue-50',
            ];
        }

        return [...$base,
            'state_label' => 'Lewat',
            'state_class' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-100',
            'date_class' => 'border-rose-100 bg-rose-50/70 text-rose-700',
            'timeline_class' => 'bg-rose-400 ring-4 ring-rose-50',
        ];
    }

    private function presentScheduleListRow(ClassSchedule $schedule, Collection $selectedScheduleIds): ClassSchedule
    {
        $weekdayLabels = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        $recurrenceLabel = match ($schedule->schedule_type) {
            'single' => $schedule->start_date
                ? 'Sekali jalan · ' . $schedule->start_date->locale('id')->translatedFormat('d M Y')
                : 'Sekali jalan',
            default => match ($schedule->frequency) {
                'daily' => 'Setiap hari',
                'weekly' => 'Setiap ' . collect($schedule->weeklyDays())
                    ->map(fn (int $day): string => $weekdayLabels[$day])
                    ->join(', '),
                'monthly' => 'Setiap tanggal ' . $schedule->day_of_month,
                default => 'Jadwal rutin',
            },
        };

        $schedule->setAttribute('list_recurrence_label', $recurrenceLabel);
        $schedule->setAttribute(
            'list_time_label',
            substr((string) $schedule->start_time, 0, 5)
                . ($schedule->end_time ? ' – ' . substr((string) $schedule->end_time, 0, 5) : '')
                . ' WIB',
        );
        $schedule->setAttribute('list_package_label', $schedule->packages->pluck('name')->join(', ') ?: '—');
        $schedule->setAttribute('list_is_selected', $selectedScheduleIds->contains((int) $schedule->id));
        $schedule->setAttribute('list_status_label', $schedule->is_active ? 'Aktif' : 'Nonaktif');
        $schedule->setAttribute(
            'list_status_class',
            $schedule->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600',
        );

        return $schedule;
    }

    public function togglePackage(
        Request $request,
        Package $package,
        ClassSchedule $classSchedule,
        PackageScheduleAssignmentService $assignmentService
    ): JsonResponse {
        $validated = $request->validate([
            'selected' => ['required', 'boolean'],
        ]);

        $isSelected = $assignmentService->setSelected(
            $package,
            $classSchedule,
            (bool) $validated['selected']
        );

        return response()->json([
            'success' => true,
            'selected' => $isSelected,
            'message' => $isSelected
                ? 'Jadwal berhasil ditambahkan ke paket.'
                : 'Jadwal berhasil dilepas dari paket.',
        ]);
    }

    public function create(Request $request, PlanModuleService $planModules): View
    {
        $classes = ClassModel::orderBy('title')->get(['class_id', 'title']);
        $packages = Package::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['package_id', 'name']);
        $studyGroups = StudyGroup::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'tentor_id']);
        $tentors = Tentor::active()->orderBy('name')->get(['id', 'name', 'expertise']);
        $preselectedDay = $request->integer('day_of_week') ?: now()->dayOfWeekIso;
        $preselectedDays = collect(old('weekly_days', [$preselectedDay]))
            ->map(static fn ($day): int => (int) $day)
            ->filter(static fn (int $day): bool => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $preselectedPackageId = $request->integer('package_id') ?: null;
        $selectedPackageIds = collect(old('package_ids', $preselectedPackageId ? [$preselectedPackageId] : []))
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $canUseClass = $planModules->allows('class');
        $canUseAttendance = $planModules->allows('attendance');
        $bookingScheduleEnabled = (bool) config('client.branding.legacy_schedule_booking_sync', false);

        return view('admin.pages.class-schedule.create', compact(
            'classes',
            'packages',
            'studyGroups',
            'tentors',
            'preselectedDay',
            'preselectedDays',
            'preselectedPackageId',
            'selectedPackageIds',
            'canUseClass',
            'canUseAttendance',
            'bookingScheduleEnabled',
        ));
    }

    public function store(
        Request $request,
        ClassScheduleService $scheduleService,
        ClassScheduleBookingConfigurator $bookingConfigurator,
        PlanModuleService $planModules
    ): RedirectResponse {
        // Auto-assign or create default class if class_id is missing
        if (! $request->has('class_id') || empty($request->input('class_id'))) {
            $defaultClass = ClassModel::first();
            if (! $defaultClass) {
                $defaultClass = ClassModel::create([
                    'title' => 'Kelas Umum',
                    'schedule_time' => now(),
                    'mentor' => null,
                    'status' => 'upcoming',
                ]);
            }
            $request->merge(['class_id' => $defaultClass->class_id]);
        }

        // Auto-assign default schedule values for weekly recurring
        if (! $request->has('schedule_type')) {
            $request->merge(['schedule_type' => 'recurring']);
        }
        if (! $request->has('frequency')) {
            $request->merge(['frequency' => 'weekly']);
        }
        if (! $request->has('start_date')) {
            $request->merge(['start_date' => now()->toDateString()]);
        }
        $this->ensureWeeklyDaysInput($request);

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,class_id'],
            'study_group_id' => ['nullable', 'exists:study_groups,id'],
            'tentor_id' => ['nullable', 'exists:tentors,id'],
            'title' => ['required', 'string', 'max:255'],
            'schedule_type' => ['required', 'in:single,recurring'],
            'frequency' => ['nullable', 'required_if:schedule_type,recurring', 'in:daily,weekly,monthly'],
            'weekly_days' => ['nullable', 'required_if:frequency,weekly', 'array', 'min:1', 'max:7'],
            'weekly_days.*' => ['integer', 'distinct', 'between:1,7'],
            'day_of_month' => ['nullable', 'required_if:frequency,monthly', 'integer', 'between:1,31'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'meeting_url' => ['nullable', 'url'],
            'location' => ['nullable', 'string', 'max:255'],
            'attendance_mode' => ['required', 'in:button,photo'],
            'open_minutes_before' => ['required', 'integer', 'min:0', 'max:1440'],
            'close_minutes_after' => ['required', 'integer', 'min:0', 'max:1440'],
            'destination_category_ids' => ['nullable', 'array'],
            'destination_category_ids.*' => ['integer', 'exists:participant_destination_categories,id'],
            'package_ids' => ['nullable', 'array'],
            'package_ids.*' => ['integer', 'distinct', 'exists:packages,package_id'],
            'is_active' => ['nullable', 'boolean'],
            'allow_custom_booking' => ['nullable', 'boolean'],
            'booking_session_quota' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);
        $weeklyDays = $this->weeklyDaysFor($validated);

        $canUseClass = $planModules->allows('class');
        $canUseAttendance = $planModules->allows('attendance');
        $bookingScheduleEnabled = (bool) config('client.branding.legacy_schedule_booking_sync', false);
        $allowCustomBooking = $bookingScheduleEnabled && $request->boolean('allow_custom_booking');

        DB::transaction(function () use (
            $request,
            $validated,
            $scheduleService,
            $bookingConfigurator,
            $canUseClass,
            $canUseAttendance,
            $allowCustomBooking,
            $weeklyDays
        ): void {
            $packageIds = $validated['package_ids'] ?? [];
            if ($allowCustomBooking) {
                Package::query()
                    ->whereKey($packageIds)
                    ->orderBy('package_id')
                    ->lockForUpdate()
                    ->get(['package_id']);
                $bookingConfigurator->ensurePackagesAvailable($packageIds);
            }
            $schedule = ClassSchedule::create([
                'class_id' => $validated['class_id'],
                'study_group_id' => $validated['study_group_id'] ?? null,
                'tentor_id' => $validated['tentor_id'] ?? null,
                'title' => $validated['title'],
                'schedule_type' => $validated['schedule_type'],
                'frequency' => $validated['schedule_type'] === 'recurring' ? ($validated['frequency'] ?? null) : null,
                'day_of_week' => ($validated['frequency'] ?? null) === 'weekly' ? $weeklyDays[0] : null,
                'weekly_days' => ($validated['frequency'] ?? null) === 'weekly' ? $weeklyDays : null,
                'day_of_month' => ($validated['frequency'] ?? null) === 'monthly' ? ($validated['day_of_month'] ?? null) : null,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'meeting_url' => $canUseClass ? ($validated['meeting_url'] ?? null) : null,
                'location' => $validated['location'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => $request->user()?->id,
                'allow_custom_booking' => $allowCustomBooking,
                'booking_session_quota' => $allowCustomBooking
                    ? (int) ($validated['booking_session_quota'] ?? 1)
                    : 1,
            ]);

            $schedule->attendanceSetting()->create([
                'mode' => $canUseAttendance ? $validated['attendance_mode'] : 'button',
                'open_minutes_before' => $canUseAttendance ? $validated['open_minutes_before'] : 15,
                'close_minutes_after' => $canUseAttendance ? $validated['close_minutes_after'] : 30,
                'allow_admin_override' => true,
            ]);
            $schedule->destinationCategories()->sync($validated['destination_category_ids'] ?? []);
            $this->syncPackages($schedule, $validated['package_ids'] ?? []);
            $bookingConfigurator->sync($schedule->fresh(['packages', 'studyGroup']));

            $scheduleService->generateSessions($schedule);
        });

        return redirect()
            ->route('admin.class-schedules.index')
            ->with('success', 'Jadwal kelas berhasil dibuat.');
    }

    public function show(Request $request, ClassSchedule $classSchedule, ClassAttendanceParticipantService $participantService): View
    {
        $activeTab = $request->string('tab')->toString();
        $activeTab = in_array($activeTab, ['tutor', 'participants'], true) ? $activeTab : 'tutor';
        $classSchedule->load(['class.tentor', 'studyGroup.tentor', 'studyGroup.users', 'tentor', 'attendanceSetting', 'destinationCategories.parent']);
        $sessionOptions = $classSchedule->sessions()
            ->orderByDesc('session_date')
            ->limit(120)
            ->get();

        $selectedSession = null;
        if ($request->filled('session_id')) {
            $selectedSession = $classSchedule->sessions()
                ->whereKey($request->integer('session_id'))
                ->first();
        }

        if (! $selectedSession && $request->filled('date') && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->input('date'))) {
            $selectedSession = $classSchedule->sessions()
                ->whereDate('session_date', $request->input('date'))
                ->orderBy('start_at')
                ->first();
        }

        if (! $selectedSession) {
            $selectedSession = $classSchedule->sessions()
                ->whereDate('session_date', now()->toDateString())
                ->orderBy('start_at')
                ->first()
                ?: $classSchedule->sessions()
                    ->where('start_at', '>=', now())
                    ->orderBy('start_at')
                    ->first()
                ?: $classSchedule->sessions()
                    ->latest('start_at')
                    ->first();
        }

        $participants = collect();
        $attendances = collect();
        if ($selectedSession) {
            $selectedSession->load(['class.packages', 'studyGroup.users', 'tentor', 'tutorAttendance', 'schedule.tentor', 'schedule.destinationCategories.parent', 'schedule.destinationCategories.children', 'attendances.user']);
            $participants = $participantService->participants($selectedSession);
            $attendances = $selectedSession->attendances->keyBy('user_id');
        }

        return view('admin.pages.class-schedule.show', compact(
            'classSchedule',
            'sessionOptions',
            'selectedSession',
            'participants',
            'attendances',
            'activeTab',
        ));
    }

    public function edit(ClassSchedule $classSchedule, PlanModuleService $planModules): View
    {
        $classSchedule->load(['class', 'tentor', 'attendanceSetting', 'destinationCategories', 'packages']);
        $classes = ClassModel::orderBy('title')->get(['class_id', 'title']);
        $selectedPackageIds = $classSchedule->packages->pluck('package_id');
        $selectedPackageIds = collect(old('package_ids', $selectedPackageIds->all()))
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $packages = Package::query()
            ->where(function ($query) use ($selectedPackageIds): void {
                $query->where('status', 'active')
                    ->orWhereIn('package_id', $selectedPackageIds);
            })
            ->orderBy('name')
            ->get(['package_id', 'name']);
        $studyGroups = StudyGroup::query()
            ->where('is_active', true)
            ->orWhere('id', $classSchedule->study_group_id)
            ->orderBy('name')
            ->get(['id', 'name', 'tentor_id']);
        $tentors = Tentor::query()
            ->where('is_active', true)
            ->orWhere('id', $classSchedule->tentor_id)
            ->orderBy('name')
            ->get(['id', 'name', 'expertise']);
        $preselectedDay = $classSchedule->day_of_week ?: 1;
        $preselectedDays = collect(old('weekly_days', $classSchedule->weeklyDays()))
            ->map(static fn ($day): int => (int) $day)
            ->filter(static fn (int $day): bool => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $canUseClass = $planModules->allows('class');
        $canUseAttendance = $planModules->allows('attendance');
        $bookingScheduleEnabled = (bool) config('client.branding.legacy_schedule_booking_sync', false);

        return view('admin.pages.class-schedule.edit', compact(
            'classSchedule',
            'classes',
            'packages',
            'studyGroups',
            'tentors',
            'preselectedDay',
            'preselectedDays',
            'canUseClass',
            'canUseAttendance',
            'bookingScheduleEnabled',
            'selectedPackageIds',
        ));
    }

    public function update(
        Request $request,
        ClassSchedule $classSchedule,
        ClassScheduleService $scheduleService,
        ClassScheduleBookingConfigurator $bookingConfigurator,
        PlanModuleService $planModules
    ): RedirectResponse {
        if (! $request->has('class_id') || empty($request->input('class_id'))) {
            $request->merge(['class_id' => $classSchedule->class_id]);
        }
        if (! $request->has('schedule_type')) {
            $request->merge(['schedule_type' => $classSchedule->schedule_type ?: 'recurring']);
        }
        if (! $request->has('frequency')) {
            $request->merge(['frequency' => $classSchedule->frequency ?: 'weekly']);
        }
        if (! $request->has('start_date')) {
            $request->merge(['start_date' => $classSchedule->start_date?->toDateString() ?: now()->toDateString()]);
        }
        $this->ensureWeeklyDaysInput($request);

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,class_id'],
            'study_group_id' => ['nullable', 'exists:study_groups,id'],
            'tentor_id' => ['nullable', 'exists:tentors,id'],
            'title' => ['required', 'string', 'max:255'],
            'schedule_type' => ['required', 'in:single,recurring'],
            'frequency' => ['nullable', 'required_if:schedule_type,recurring', 'in:daily,weekly,monthly'],
            'weekly_days' => ['nullable', 'required_if:frequency,weekly', 'array', 'min:1', 'max:7'],
            'weekly_days.*' => ['integer', 'distinct', 'between:1,7'],
            'day_of_month' => ['nullable', 'required_if:frequency,monthly', 'integer', 'between:1,31'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'meeting_url' => ['nullable', 'url'],
            'location' => ['nullable', 'string', 'max:255'],
            'attendance_mode' => ['required', 'in:button,photo'],
            'open_minutes_before' => ['required', 'integer', 'min:0', 'max:1440'],
            'close_minutes_after' => ['required', 'integer', 'min:0', 'max:1440'],
            'destination_category_ids' => ['nullable', 'array'],
            'destination_category_ids.*' => ['integer', 'exists:participant_destination_categories,id'],
            'package_ids' => ['nullable', 'array'],
            'package_ids.*' => ['integer', 'distinct', 'exists:packages,package_id'],
            'is_active' => ['nullable', 'boolean'],
            'allow_custom_booking' => ['nullable', 'boolean'],
            'booking_session_quota' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);
        $weeklyDays = $this->weeklyDaysFor($validated);

        $canUseClass = $planModules->allows('class');
        $canUseAttendance = $planModules->allows('attendance');
        $bookingScheduleEnabled = (bool) config('client.branding.legacy_schedule_booking_sync', false);
        $allowCustomBooking = $bookingScheduleEnabled
            ? $request->boolean('allow_custom_booking')
            : (bool) $classSchedule->allow_custom_booking;

        $previousPackageIds = $classSchedule->packages()->pluck('packages.package_id')->all();
        $wasCustom = (bool) $classSchedule->allow_custom_booking;

        DB::transaction(function () use (
            $request,
            $validated,
            $classSchedule,
            $scheduleService,
            $bookingConfigurator,
            $canUseClass,
            $canUseAttendance,
            $allowCustomBooking,
            $previousPackageIds,
            $wasCustom,
            $weeklyDays
        ): void {
            $packageIds = $validated['package_ids'] ?? [];
            Package::query()
                ->whereKey(array_values(array_unique([...$previousPackageIds, ...$packageIds])))
                ->orderBy('package_id')
                ->lockForUpdate()
                ->get(['package_id']);
            if ($allowCustomBooking) {
                $bookingConfigurator->ensurePackagesAvailable($packageIds, $classSchedule->id);
            }
            $classSchedule->update([
                'class_id' => $validated['class_id'],
                'study_group_id' => $validated['study_group_id'] ?? null,
                'tentor_id' => $validated['tentor_id'] ?? null,
                'title' => $validated['title'],
                'schedule_type' => $validated['schedule_type'],
                'frequency' => $validated['schedule_type'] === 'recurring' ? ($validated['frequency'] ?? null) : null,
                'day_of_week' => ($validated['frequency'] ?? null) === 'weekly' ? $weeklyDays[0] : null,
                'weekly_days' => ($validated['frequency'] ?? null) === 'weekly' ? $weeklyDays : null,
                'day_of_month' => ($validated['frequency'] ?? null) === 'monthly' ? ($validated['day_of_month'] ?? null) : null,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'meeting_url' => $canUseClass
                    ? ($validated['meeting_url'] ?? null)
                    : $classSchedule->meeting_url,
                'location' => $validated['location'] ?? null,
                'is_active' => $request->boolean('is_active'),
                'allow_custom_booking' => $allowCustomBooking,
                'booking_session_quota' => $allowCustomBooking
                    ? (int) ($validated['booking_session_quota'] ?? $classSchedule->booking_session_quota ?? 1)
                    : 1,
            ]);

            $classSchedule->attendanceSetting()->updateOrCreate(
                ['class_schedule_id' => $classSchedule->id],
                [
                    'mode' => $canUseAttendance ? $validated['attendance_mode'] : 'button',
                    'open_minutes_before' => $canUseAttendance ? $validated['open_minutes_before'] : 15,
                    'close_minutes_after' => $canUseAttendance ? $validated['close_minutes_after'] : 30,
                    'allow_admin_override' => true,
                ]
            );

            $classSchedule->destinationCategories()->sync($validated['destination_category_ids'] ?? []);
            $this->syncPackages($classSchedule, $validated['package_ids'] ?? []);
            $bookingConfigurator->sync(
                $classSchedule->fresh(['packages', 'studyGroup']),
                $previousPackageIds,
                $wasCustom
            );
            $classSchedule->sessions()
                ->where('start_at', '>=', now())
                ->whereDoesntHave('attendances')
                ->delete();

            $scheduleService->generateSessions($classSchedule->fresh());
        });

        return redirect()
            ->route('admin.class-schedules.index')
            ->with('success', 'Jadwal kelas berhasil diperbarui.');
    }

    public function destroy(
        ClassSchedule $classSchedule,
        ClassScheduleBookingConfigurator $bookingConfigurator
    ): RedirectResponse {
        DB::transaction(function () use ($classSchedule, $bookingConfigurator): void {
            $previousPackageIds = $classSchedule->packages()
                ->pluck('packages.package_id')
                ->all();
            $wasCustom = (bool) $classSchedule->allow_custom_booking;
            $classSchedule->update(['allow_custom_booking' => false]);
            $bookingConfigurator->sync($classSchedule, $previousPackageIds, $wasCustom);
            $classSchedule->delete();
        });

        return redirect()
            ->route('admin.class-schedules.index')
            ->with('success', 'Jadwal kelas berhasil dihapus.');
    }

    public function generate(ClassSchedule $classSchedule, ClassScheduleService $scheduleService): RedirectResponse
    {
        $created = $scheduleService->generateSessions($classSchedule);

        return redirect()
            ->route('admin.class-schedules.show', $classSchedule)
            ->with('success', "Generate sesi kelas selesai. Sesi baru: {$created}.");
    }

    public function updateSession(Request $request, ClassSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:scheduled,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $session->update($validated);

        return back()->with('success', 'Data sesi kelas berhasil diperbarui.');
    }

    private function ensureWeeklyDaysInput(Request $request): void
    {
        if (
            $request->input('schedule_type') !== 'recurring'
            || $request->input('frequency') !== 'weekly'
            || $request->has('weekly_days')
        ) {
            return;
        }

        $day = $request->integer('day_of_week');
        if ($day >= 1 && $day <= 7) {
            $request->merge(['weekly_days' => [$day]]);
        }
    }

    /** @param array<string, mixed> $validated
     *  @return array<int, int>
     */
    private function weeklyDaysFor(array $validated): array
    {
        return collect($validated['weekly_days'] ?? [])
            ->map(static fn ($day): int => (int) $day)
            ->filter(static fn (int $day): bool => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int|string>  $packageIds
     */
    private function syncPackages(ClassSchedule $schedule, array $packageIds): void
    {
        $normalizedIds = collect($packageIds)
            ->map(fn ($packageId): int => (int) $packageId)
            ->filter()
            ->unique()
            ->values();
        $type = $schedule->getMorphClass();

        $existingQuery = DetailPackage::query()
            ->where('detailable_type', $type)
            ->where('detailable_id', $schedule->id);

        if ($normalizedIds->isEmpty()) {
            $existingQuery->delete();

            return;
        }

        (clone $existingQuery)
            ->whereNotIn('package_id', $normalizedIds)
            ->delete();

        $existingPackageIds = $existingQuery
            ->whereIn('package_id', $normalizedIds)
            ->pluck('package_id');
        $now = now();
        $rows = $normalizedIds
            ->diff($existingPackageIds)
            ->map(fn (int $packageId): array => [
                'package_id' => $packageId,
                'detailable_type' => $type,
                'detailable_id' => $schedule->id,
                'order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DetailPackage::query()->insertOrIgnore($rows);
        }
    }
}
