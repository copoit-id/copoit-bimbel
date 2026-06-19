<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\ParticipantDestinationCategory;
use App\Services\ClassScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassScheduleController extends Controller
{
    public function index(): View
    {
        $schedules = ClassSchedule::with(['class', 'attendanceSetting', 'destinationCategories.parent'])->get();

        $weeklySchedules = [];
        for ($i = 1; $i <= 7; $i++) {
            $weeklySchedules[$i] = $schedules->where('schedule_type', 'recurring')
                ->where('frequency', 'weekly')
                ->where('day_of_week', $i)
                ->sortBy('start_time');
        }

        $otherSchedules = $schedules->filter(function($s) {
            return $s->schedule_type !== 'recurring' || $s->frequency !== 'weekly';
        });

        return view('admin.pages.class-schedule.index', compact('weeklySchedules', 'otherSchedules'));
    }

    public function create(Request $request): View
    {
        $classes = ClassModel::orderBy('title')->get(['class_id', 'title']);
        $destinationCategories = ParticipantDestinationCategory::query()
            ->active()
            ->with('parent')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $preselectedDay = $request->query('day_of_week', 1);

        return view('admin.pages.class-schedule.create', compact('classes', 'destinationCategories', 'preselectedDay'));
    }

    public function store(Request $request, ClassScheduleService $scheduleService): RedirectResponse
    {
        // Auto-assign or create default class if class_id is missing
        if (!$request->has('class_id') || empty($request->input('class_id'))) {
            $defaultClass = ClassModel::first();
            if (!$defaultClass) {
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
        if (!$request->has('schedule_type')) {
            $request->merge(['schedule_type' => 'recurring']);
        }
        if (!$request->has('frequency')) {
            $request->merge(['frequency' => 'weekly']);
        }
        if (!$request->has('start_date')) {
            $request->merge(['start_date' => now()->toDateString()]);
        }

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,class_id'],
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
            'destination_category_ids' => ['required', 'array', 'min:1'],
            'destination_category_ids.*' => ['integer', 'exists:participant_destination_categories,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $schedule = ClassSchedule::create([
            'class_id' => $validated['class_id'],
            'title' => $validated['title'],
            'schedule_type' => $validated['schedule_type'],
            'frequency' => $validated['schedule_type'] === 'recurring' ? ($validated['frequency'] ?? null) : null,
            'day_of_week' => ($validated['frequency'] ?? null) === 'weekly' ? ($validated['day_of_week'] ?? null) : null,
            'day_of_month' => ($validated['frequency'] ?? null) === 'monthly' ? ($validated['day_of_month'] ?? null) : null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'meeting_url' => $validated['meeting_url'] ?? null,
            'location' => $validated['location'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()?->id,
        ]);

        $schedule->attendanceSetting()->create([
            'mode' => $validated['attendance_mode'],
            'open_minutes_before' => $validated['open_minutes_before'],
            'close_minutes_after' => $validated['close_minutes_after'],
            'allow_admin_override' => true,
        ]);
        $schedule->destinationCategories()->sync($validated['destination_category_ids']);

        $scheduleService->generateSessions($schedule);

        return redirect()
            ->route('admin.class-schedules.index')
            ->with('success', 'Jadwal kelas berhasil dibuat.');
    }

    public function show(ClassSchedule $classSchedule): View
    {
        $classSchedule->load(['class', 'attendanceSetting', 'destinationCategories.parent']);
        $sessions = $classSchedule->sessions()
            ->withCount('attendances')
            ->orderBy('start_at')
            ->paginate(20);

        return view('admin.pages.class-schedule.show', compact('classSchedule', 'sessions'));
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
            ->with('success', "Generate absen selesai. Data absen baru: {$created}.");
    }

    public function updateSession(Request $request, ClassSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:scheduled,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $session->update($validated);

        return back()->with('success', 'Data absen berhasil diperbarui.');
    }
}
