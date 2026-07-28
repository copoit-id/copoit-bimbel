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
use App\Services\ClassScheduleService;
use App\Services\PlanModuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClassScheduleController extends Controller
{
    public function index(Request $request, PlanModuleService $planModules): View
    {
        $activeTab = $request->query('tab', 'schedules');
        $canUseClass = $planModules->allows('class');
        $activeTab = $activeTab === 'zoom' && $canUseClass ? 'zoom' : 'schedules';
        $filteredPackage = $request->integer('package_id')
            ? Package::query()
                ->whereKey($request->integer('package_id'))
                ->first(['package_id', 'name'])
            : null;
        $schedules = ClassSchedule::query()
            ->with([
                'class.tentor',
                'studyGroup.tentor',
                'tentor',
                'attendanceSetting',
                'destinationCategories.parent',
                'packages:package_id,name',
            ])
            ->when($request->integer('package_id'), fn ($query, int $packageId) => $query
                ->whereHas('packages', fn ($packageQuery) => $packageQuery
                    ->where('packages.package_id', $packageId)))
            ->get();

        $weeklySchedules = [];
        for ($i = 1; $i <= 7; $i++) {
            $weeklySchedules[$i] = $schedules->where('schedule_type', 'recurring')
                ->where('frequency', 'weekly')
                ->where('day_of_week', $i)
                ->sortBy('start_time');
        }

        $otherSchedules = $schedules->filter(function ($s) {
            return $s->schedule_type !== 'recurring' || $s->frequency !== 'weekly';
        });

        $liveClasses = $canUseClass
            ? ClassModel::with('tentor')
                ->orderBy('schedule_time', 'desc')
                ->paginate(10, ['*'], 'kelas_page')
                ->withQueryString()
            : null;

        return view('admin.pages.class-schedule.index', compact(
            'activeTab',
            'weeklySchedules',
            'otherSchedules',
            'liveClasses',
            'canUseClass',
            'filteredPackage',
        ));
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
        $preselectedDay = $request->query('day_of_week', 1);
        $preselectedPackageId = $request->integer('package_id') ?: null;
        $canUseClass = $planModules->allows('class');

        return view('admin.pages.class-schedule.create', compact(
            'classes',
            'packages',
            'studyGroups',
            'tentors',
            'preselectedDay',
            'preselectedPackageId',
            'canUseClass',
        ));
    }

    public function store(
        Request $request,
        ClassScheduleService $scheduleService,
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

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,class_id'],
            'study_group_id' => ['nullable', 'exists:study_groups,id'],
            'tentor_id' => ['nullable', 'exists:tentors,id'],
            'title' => ['required', 'string', 'max:255'],
            'schedule_type' => ['required', 'in:single,recurring'],
            'frequency' => ['nullable', 'required_if:schedule_type,recurring', 'in:daily,weekly,monthly'],
            'day_of_week' => ['nullable', 'required_if:frequency,weekly', 'integer', 'between:1,7'],
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
        ]);

        $canUseClass = $planModules->allows('class');

        DB::transaction(function () use (
            $request,
            $validated,
            $scheduleService,
            $canUseClass
        ): void {
            $schedule = ClassSchedule::create([
                'class_id' => $validated['class_id'],
                'study_group_id' => $validated['study_group_id'] ?? null,
                'tentor_id' => $validated['tentor_id'] ?? null,
                'title' => $validated['title'],
                'schedule_type' => $validated['schedule_type'],
                'frequency' => $validated['schedule_type'] === 'recurring' ? ($validated['frequency'] ?? null) : null,
                'day_of_week' => ($validated['frequency'] ?? null) === 'weekly' ? ($validated['day_of_week'] ?? null) : null,
                'day_of_month' => ($validated['frequency'] ?? null) === 'monthly' ? ($validated['day_of_month'] ?? null) : null,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'meeting_url' => $canUseClass ? ($validated['meeting_url'] ?? null) : null,
                'location' => $validated['location'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => $request->user()?->id,
            ]);

            $schedule->attendanceSetting()->create([
                'mode' => $canUseClass ? $validated['attendance_mode'] : 'button',
                'open_minutes_before' => $canUseClass ? $validated['open_minutes_before'] : 15,
                'close_minutes_after' => $canUseClass ? $validated['close_minutes_after'] : 30,
                'allow_admin_override' => true,
            ]);
            $schedule->destinationCategories()->sync($validated['destination_category_ids'] ?? []);
            $this->syncPackages($schedule, $validated['package_ids'] ?? []);

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
        $canUseClass = $planModules->allows('class');

        return view('admin.pages.class-schedule.edit', compact(
            'classSchedule',
            'classes',
            'packages',
            'studyGroups',
            'tentors',
            'preselectedDay',
            'canUseClass',
        ));
    }

    public function update(
        Request $request,
        ClassSchedule $classSchedule,
        ClassScheduleService $scheduleService,
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

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,class_id'],
            'study_group_id' => ['nullable', 'exists:study_groups,id'],
            'tentor_id' => ['nullable', 'exists:tentors,id'],
            'title' => ['required', 'string', 'max:255'],
            'schedule_type' => ['required', 'in:single,recurring'],
            'frequency' => ['nullable', 'required_if:schedule_type,recurring', 'in:daily,weekly,monthly'],
            'day_of_week' => ['nullable', 'required_if:frequency,weekly', 'integer', 'between:1,7'],
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
        ]);

        $canUseClass = $planModules->allows('class');

        DB::transaction(function () use ($request, $validated, $classSchedule, $scheduleService, $canUseClass): void {
            $classSchedule->update([
                'class_id' => $validated['class_id'],
                'study_group_id' => $validated['study_group_id'] ?? null,
                'tentor_id' => $validated['tentor_id'] ?? null,
                'title' => $validated['title'],
                'schedule_type' => $validated['schedule_type'],
                'frequency' => $validated['schedule_type'] === 'recurring' ? ($validated['frequency'] ?? null) : null,
                'day_of_week' => ($validated['frequency'] ?? null) === 'weekly' ? ($validated['day_of_week'] ?? null) : null,
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
            ]);

            $classSchedule->attendanceSetting()->updateOrCreate(
                ['class_schedule_id' => $classSchedule->id],
                [
                    'mode' => $canUseClass ? $validated['attendance_mode'] : 'button',
                    'open_minutes_before' => $canUseClass ? $validated['open_minutes_before'] : 15,
                    'close_minutes_after' => $canUseClass ? $validated['close_minutes_after'] : 30,
                    'allow_admin_override' => true,
                ]
            );

            $classSchedule->destinationCategories()->sync($validated['destination_category_ids'] ?? []);
            $this->syncPackages($classSchedule, $validated['package_ids'] ?? []);
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

    public function destroy(ClassSchedule $classSchedule): RedirectResponse
    {
        $classSchedule->delete();

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
