<?php

namespace App\Services;

use App\Models\ScheduleBookingRequest;
use App\Models\TutorReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TutorReviewService
{
    public function save(
        ScheduleBookingRequest $booking,
        User $student,
        int $rating,
        ?string $comment
    ): TutorReview {
        return DB::transaction(function () use ($booking, $student, $rating, $comment): TutorReview {
            if ($rating < 1 || $rating > 5) {
                throw ValidationException::withMessages([
                    'rating' => 'Rating harus bernilai antara 1 sampai 5.',
                ]);
            }

            $lockedBooking = ScheduleBookingRequest::query()
                ->with('session:id,start_at,end_at,status')
                ->lockForUpdate()
                ->findOrFail($booking->id);

            if ((int) $lockedBooking->user_id !== (int) $student->id) {
                abort(403, 'Booking ini bukan milik Anda.');
            }

            $sessionEnd = $lockedBooking->session?->end_at
                ?? $lockedBooking->session?->start_at;
            $eligibleStatus = in_array($lockedBooking->status, [
                ScheduleBookingRequest::STATUS_APPROVED,
                ScheduleBookingRequest::STATUS_COMPLETED,
            ], true);
            $sessionIsValid = $lockedBooking->session
                && $lockedBooking->session->status !== 'cancelled';

            if (! $eligibleStatus
                || ! $sessionIsValid
                || ! $sessionEnd
                || $sessionEnd->isFuture()) {
                throw ValidationException::withMessages([
                    'rating' => 'Rating dapat diberikan setelah sesi booking selesai.',
                ]);
            }

            $review = TutorReview::query()->firstOrNew([
                'schedule_booking_request_id' => $lockedBooking->id,
            ]);
            $review->fill([
                'user_id' => $student->id,
                'tentor_id' => $lockedBooking->tentor_id,
                'rating' => $rating,
                'comment' => filled($comment) ? trim($comment) : null,
            ]);
            if (! $review->exists) {
                $review->is_visible = true;
            }
            $review->save();

            return $review->fresh();
        }, 3);
    }
}
