<?php

namespace App\Http\Controllers\tutor;

use App\Http\Controllers\Controller;
use App\Models\ScheduleBookingRequest;
use App\Services\ScheduleBookingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleBookingController extends Controller
{
    public function index(Request $request): View
    {
        $tentor = $request->user()->tentorProfile;
        $status = $request->string('status')->toString();
        $allowedStatuses = [
            ScheduleBookingRequest::STATUS_PENDING,
            ScheduleBookingRequest::STATUS_COUNTER_PROPOSED,
            ScheduleBookingRequest::STATUS_APPROVED,
            ScheduleBookingRequest::STATUS_REJECTED,
            ScheduleBookingRequest::STATUS_CANCELLED,
        ];
        $status = in_array($status, $allowedStatuses, true) ? $status : 'waiting';
        $bookings = ScheduleBookingRequest::query()
            ->with([
                'user:id,name,email,phone',
                'package:package_id,name',
                'session:id,start_at,end_at,location,meeting_url',
                'studyGroup:id,name,target_participants',
                'studyGroup.members.user:id,name',
            ])
            ->where('tentor_id', $tentor->id)
            ->when(
                $status === 'waiting',
                fn ($query) => $query->awaitingResponse(),
                fn ($query) => $query->where('status', $status)
            )
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'counter_proposed' THEN 1 ELSE 2 END")
            ->latest('requested_start_at')
            ->paginate(\App\Support\Pagination::perPage(15))
            ->withQueryString();
        $waitingCount = ScheduleBookingRequest::query()
            ->where('tentor_id', $tentor->id)
            ->awaitingResponse()
            ->count();

        return view('tutor.booking.index', compact(
            'tentor',
            'bookings',
            'status',
            'waitingCount',
        ));
    }

    public function approve(
        Request $request,
        ScheduleBookingRequest $booking,
        ScheduleBookingService $bookingService
    ): RedirectResponse {
        $this->ensureAssignedTutor($request, $booking);
        $validated = $request->validate([
            'scheduled_start_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'url', 'max:1000'],
        ]);
        $startAt = filled($validated['scheduled_start_at'] ?? null)
            ? Carbon::parse($validated['scheduled_start_at'])
            : null;

        $bookingService->approve($booking, $request->user(), $startAt, [
            'location' => $validated['location'] ?? null,
            'meeting_url' => $validated['meeting_url'] ?? null,
        ]);

        return redirect()
            ->route('tutor.booking.index')
            ->with('success', 'Booking disetujui dan jadwal berhasil dibuat.');
    }

    public function reject(
        Request $request,
        ScheduleBookingRequest $booking,
        ScheduleBookingService $bookingService
    ): RedirectResponse {
        $this->ensureAssignedTutor($request, $booking);
        $validated = $request->validate([
            'tutor_notes' => ['required', 'string', 'max:1000'],
        ]);
        $bookingService->reject($booking, $request->user(), $validated['tutor_notes']);

        return redirect()
            ->route('tutor.booking.index')
            ->with('success', 'Permintaan booking ditolak.');
    }

    public function propose(
        Request $request,
        ScheduleBookingRequest $booking,
        ScheduleBookingService $bookingService
    ): RedirectResponse {
        $this->ensureAssignedTutor($request, $booking);
        $validated = $request->validate([
            'scheduled_start_at' => ['required', 'date', 'after:now'],
            'tutor_notes' => ['required', 'string', 'max:1000'],
        ]);
        $bookingService->propose(
            $booking,
            $request->user(),
            Carbon::parse($validated['scheduled_start_at']),
            $validated['tutor_notes']
        );

        return redirect()
            ->route('tutor.booking.index')
            ->with('success', 'Usulan waktu baru berhasil dikirim kepada siswa.');
    }

    private function ensureAssignedTutor(Request $request, ScheduleBookingRequest $booking): void
    {
        abort_unless(
            (int) $request->user()->tentorProfile?->id === (int) $booking->tentor_id,
            403,
            'Permintaan booking ini bukan milik Anda.'
        );
    }
}
