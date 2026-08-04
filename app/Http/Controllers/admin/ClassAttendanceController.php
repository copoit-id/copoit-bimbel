<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\TutorAttendance;
use App\Services\ClassAttendanceParticipantService;
use App\Services\TutorPayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassAttendanceController extends Controller
{
    public function show(ClassSession $session, ClassAttendanceParticipantService $participantService): View
    {
        $session->load(['class.packages', 'tentor', 'tutorAttendance', 'studyGroup.users', 'schedule.attendanceSetting', 'schedule.destinationCategories.parent', 'schedule.destinationCategories.children', 'attendances.user']);
        $participants = $participantService->participants($session);
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

    public function markTutor(Request $request, ClassSession $session, TutorPayrollService $tutorPayrollService): RedirectResponse
    {
        abort_unless($session->tentor_id, 422, 'Sesi ini belum memiliki Tutor.');

        $validated = $request->validate([
            'status' => ['required', 'in:present,late,absent,excused'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $existingAttendance = TutorAttendance::query()
            ->where('class_session_id', $session->id)
            ->where('tentor_id', $session->tentor_id)
            ->first();

        $attendance = TutorAttendance::updateOrCreate(
            ['class_session_id' => $session->id, 'tentor_id' => $session->tentor_id],
            [
                'status' => $validated['status'],
                'approval_status' => 'approved',
                'check_in_at' => in_array($validated['status'], ['present', 'late'], true) ? ($existingAttendance?->check_in_at ?? now()) : null,
                'source' => $existingAttendance?->source ?? 'admin',
                'notes' => $validated['notes'] ?? null,
                'marked_by' => $existingAttendance?->marked_by ?? $request->user()?->id,
                'approved_by' => $request->user()?->id,
                'approved_at' => now(),
            ]
        );
        $tutorPayrollService->syncApprovedAttendance($attendance, $request->user());

        return back()->with('success', 'Absensi Tutor disetujui dan penggajian otomatis diperbarui.');
    }
}
