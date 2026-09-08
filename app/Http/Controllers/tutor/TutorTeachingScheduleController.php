<?php

namespace App\Http\Controllers\tutor;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Services\TutorTeachingScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutorTeachingScheduleController extends Controller
{
    public function create(Request $request): View
    {
        $tentor = $request->user()->tentorProfile;
        $schedules = ClassSchedule::query()
            ->where('tentor_id', $tentor->id)
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'location', 'meeting_url', 'start_time', 'end_time']);

        return view('tutor.schedule-create', compact('schedules'));
    }

    public function store(Request $request, TutorTeachingScheduleService $scheduleService): RedirectResponse
    {
        $validated = $request->validate([
            'class_schedule_id' => ['required', 'integer', 'exists:class_schedules,id'],
            'session_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'meeting_url' => ['nullable', 'url'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $tentor = $request->user()->tentorProfile;
        $schedule = ClassSchedule::query()->findOrFail($validated['class_schedule_id']);
        $session = $scheduleService->addSession($tentor, $schedule, $validated);

        return redirect()->route('tutor.schedule.index')
            ->with('success', $session->wasRecentlyCreated ? 'Sesi mengajar berhasil ditambahkan.' : 'Sesi pada waktu tersebut sudah ada.');
    }

    public function cancel(Request $request, ClassSession $session, TutorTeachingScheduleService $scheduleService): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $scheduleService->cancelSession($request->user()->tentorProfile, $session, $validated['reason'] ?? null);

        return redirect()->route('tutor.schedule.index')->with('success', 'Sesi mengajar dibatalkan.');
    }
}
