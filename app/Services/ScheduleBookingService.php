<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\PackageBookingRule;
use App\Models\ScheduleBookingRequest;
use App\Models\StudyGroup;
use App\Models\Tentor;
use App\Models\User;
use App\Models\UserPackageAcces;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleBookingService
{
    public function __construct(
        private ClassScheduleService $scheduleService
    ) {}

    /**
     * @param  array{location?: string|null, meeting_url?: string|null}  $sessionDetails
     */
    public function approve(
        ScheduleBookingRequest $booking,
        User $responder,
        ?Carbon $startAt = null,
        array $sessionDetails = []
    ): ScheduleBookingRequest {
        return DB::transaction(function () use ($booking, $responder, $startAt, $sessionDetails): ScheduleBookingRequest {
            $lockedBooking = ScheduleBookingRequest::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);

            $this->ensureStatus(
                $lockedBooking,
                [ScheduleBookingRequest::STATUS_PENDING, ScheduleBookingRequest::STATUS_COUNTER_PROPOSED],
                'Permintaan booking ini sudah diproses.'
            );

            $tentor = Tentor::query()
                ->whereKey($lockedBooking->tentor_id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();
            $access = $this->activeAccessFor($lockedBooking);
            $studyGroup = $this->activeStudyGroupFor($lockedBooking);
            $rule = PackageBookingRule::query()
                ->where('package_id', $lockedBooking->package_id)
                ->where('is_enabled', true)
                ->lockForUpdate()
                ->first();

            if (! $rule) {
                throw ValidationException::withMessages([
                    'booking' => 'Fitur booking pada paket ini sudah tidak aktif.',
                ]);
            }
            $tutorAllowed = $tentor && (
                $rule->allow_all_tutors
                || $rule->tutors()->whereKey($lockedBooking->tentor_id)->exists()
            );

            if (! $tutorAllowed) {
                throw ValidationException::withMessages([
                    'booking' => 'Tutor ini sudah tidak tersedia untuk paket tersebut.',
                ]);
            }

            $usedQuota = ScheduleBookingRequest::query()
                ->when(
                    $studyGroup,
                    fn ($query) => $query->where('study_group_id', $studyGroup->id),
                    fn ($query) => $query
                        ->where('user_package_access_id', $access->user_package_access_id)
                        ->whereNull('study_group_id')
                )
                ->where('id', '!=', $lockedBooking->id)
                ->consumesQuota()
                ->count();

            if ($usedQuota >= $rule->session_quota) {
                throw ValidationException::withMessages([
                    'booking' => 'Kuota sesi booking pada paket ini sudah habis.',
                ]);
            }

            $scheduledStart = ($startAt ?? $lockedBooking->scheduled_start_at ?? $lockedBooking->requested_start_at)
                ->copy()
                ->startOfMinute();
            $scheduledEnd = $scheduledStart->copy()->addMinutes($rule->duration_minutes);

            $this->ensureWithinBookingWindow($rule, $scheduledStart);
            $this->ensureAccessCoversSession($access, $scheduledStart);

            if ($scheduledStart->lte(now())) {
                throw ValidationException::withMessages([
                    'scheduled_start_at' => 'Waktu booking harus berada di masa mendatang.',
                ]);
            }

            $this->ensureTutorSlotAvailable(
                (int) $lockedBooking->tentor_id,
                $scheduledStart,
                $scheduledEnd,
                $lockedBooking->id
            );

            $schedule = $this->createSchedule(
                $lockedBooking,
                $rule,
                $responder,
                $scheduledStart,
                $scheduledEnd,
                $sessionDetails
            );
            $session = $schedule->sessions()
                ->where('start_at', $scheduledStart)
                ->first();

            if (! $session) {
                throw ValidationException::withMessages([
                    'booking' => 'Sesi booking gagal dibuat. Silakan coba kembali.',
                ]);
            }

            $lockedBooking->update([
                'status' => ScheduleBookingRequest::STATUS_APPROVED,
                'scheduled_start_at' => $scheduledStart,
                'scheduled_end_at' => $scheduledEnd,
                'class_schedule_id' => $schedule->id,
                'class_session_id' => $session->id,
                'responded_by' => $responder->id,
                'responded_at' => now(),
                'slot_key' => $this->slotKey(
                    (int) $lockedBooking->tentor_id,
                    $scheduledStart,
                    $scheduledEnd
                ),
            ]);

            return $lockedBooking->fresh([
                'user:id,name,email',
                'package:package_id,name',
                'tentor:id,name,expertise',
                'session',
            ]);
        }, 3);
    }

    public function reject(
        ScheduleBookingRequest $booking,
        User $responder,
        string $notes
    ): ScheduleBookingRequest {
        return DB::transaction(function () use ($booking, $responder, $notes): ScheduleBookingRequest {
            $lockedBooking = ScheduleBookingRequest::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);
            $this->ensureStatus(
                $lockedBooking,
                [ScheduleBookingRequest::STATUS_PENDING, ScheduleBookingRequest::STATUS_COUNTER_PROPOSED],
                'Permintaan booking ini sudah diproses.'
            );

            $lockedBooking->update([
                'status' => ScheduleBookingRequest::STATUS_REJECTED,
                'tutor_notes' => $notes,
                'responded_by' => $responder->id,
                'responded_at' => now(),
                'slot_key' => null,
            ]);

            return $lockedBooking->fresh();
        }, 3);
    }

    public function propose(
        ScheduleBookingRequest $booking,
        User $responder,
        Carbon $startAt,
        string $notes
    ): ScheduleBookingRequest {
        return DB::transaction(function () use ($booking, $responder, $startAt, $notes): ScheduleBookingRequest {
            $lockedBooking = ScheduleBookingRequest::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);
            $this->ensureStatus(
                $lockedBooking,
                [ScheduleBookingRequest::STATUS_PENDING],
                'Permintaan booking ini sudah diproses.'
            );
            $tentor = Tentor::query()
                ->whereKey($lockedBooking->tentor_id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();
            $access = $this->activeAccessFor($lockedBooking);
            $this->activeStudyGroupFor($lockedBooking);
            $rule = PackageBookingRule::query()
                ->where('package_id', $lockedBooking->package_id)
                ->where('is_enabled', true)
                ->lockForUpdate()
                ->first();

            if (! $rule || $startAt->lte(now())) {
                throw ValidationException::withMessages([
                    'scheduled_start_at' => 'Waktu usulan harus berada di masa mendatang.',
                ]);
            }
            $tutorAllowed = $tentor && (
                $rule->allow_all_tutors
                || $rule->tutors()->whereKey($lockedBooking->tentor_id)->exists()
            );

            if (! $tutorAllowed) {
                throw ValidationException::withMessages([
                    'booking' => 'Tutor ini sudah tidak tersedia untuk paket tersebut.',
                ]);
            }

            $this->ensureWithinBookingWindow($rule, $startAt);
            $this->ensureAccessCoversSession($access, $startAt);

            $lockedBooking->update([
                'status' => ScheduleBookingRequest::STATUS_COUNTER_PROPOSED,
                'scheduled_start_at' => $startAt->copy()->startOfMinute(),
                'scheduled_end_at' => $startAt->copy()->startOfMinute()->addMinutes($rule->duration_minutes),
                'tutor_notes' => $notes,
                'responded_by' => $responder->id,
                'responded_at' => now(),
            ]);

            return $lockedBooking->fresh();
        }, 3);
    }

    public function acceptCounter(
        ScheduleBookingRequest $booking,
        User $student
    ): ScheduleBookingRequest {
        if ((int) $booking->user_id !== (int) $student->id
            || $booking->status !== ScheduleBookingRequest::STATUS_COUNTER_PROPOSED
            || ! $booking->scheduled_start_at) {
            throw ValidationException::withMessages([
                'booking' => 'Usulan waktu ini sudah tidak dapat diterima.',
            ]);
        }

        return $this->approve(
            $booking,
            $booking->responder ?? $student,
            $booking->scheduled_start_at
        );
    }

    public function cancel(
        ScheduleBookingRequest $booking,
        User $student
    ): ScheduleBookingRequest {
        return DB::transaction(function () use ($booking, $student): ScheduleBookingRequest {
            $lockedBooking = ScheduleBookingRequest::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);

            if ((int) $lockedBooking->user_id !== (int) $student->id) {
                abort(403);
            }

            $this->ensureStatus(
                $lockedBooking,
                [ScheduleBookingRequest::STATUS_PENDING, ScheduleBookingRequest::STATUS_COUNTER_PROPOSED],
                'Booking yang sudah disetujui tidak dapat dibatalkan dari halaman ini.'
            );

            $lockedBooking->update([
                'status' => ScheduleBookingRequest::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'slot_key' => null,
            ]);

            return $lockedBooking->fresh();
        }, 3);
    }

    private function activeAccessFor(ScheduleBookingRequest $booking): UserPackageAcces
    {
        $access = UserPackageAcces::query()
            ->whereKey($booking->user_package_access_id)
            ->where('user_id', $booking->user_id)
            ->where('package_id', $booking->package_id)
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            })
            ->lockForUpdate()
            ->first();

        if (! $access) {
            throw ValidationException::withMessages([
                'booking' => 'Akses paket siswa sudah tidak aktif.',
            ]);
        }

        return $access;
    }

    private function activeStudyGroupFor(ScheduleBookingRequest $booking): ?StudyGroup
    {
        if (! $booking->study_group_id) {
            return null;
        }

        $studyGroup = StudyGroup::query()
            ->whereKey($booking->study_group_id)
            ->where('package_id', $booking->package_id)
            ->where('organizer_user_id', $booking->user_id)
            ->where('status', StudyGroup::STATUS_ACTIVE)
            ->lockForUpdate()
            ->first();

        if (! $studyGroup) {
            throw ValidationException::withMessages([
                'booking' => 'Rombel belum aktif untuk dijadwalkan.',
            ]);
        }

        return $studyGroup;
    }

    private function ensureTutorSlotAvailable(
        int $tentorId,
        Carbon $startAt,
        Carbon $endAt,
        int $exceptBookingId
    ): void {
        $hasBookingConflict = ScheduleBookingRequest::query()
            ->where('tentor_id', $tentorId)
            ->where('id', '!=', $exceptBookingId)
            ->where('status', ScheduleBookingRequest::STATUS_APPROVED)
            ->where('scheduled_start_at', '<', $endAt)
            ->where('scheduled_end_at', '>', $startAt)
            ->exists();
        $hasSessionConflict = ClassSession::query()
            ->where('tentor_id', $tentorId)
            ->where('status', 'scheduled')
            ->where('start_at', '<', $endAt)
            ->where(function ($query) use ($startAt): void {
                $query->where('end_at', '>', $startAt)
                    ->orWhere(function ($query) use ($startAt): void {
                        $query->whereNull('end_at')
                            ->where('start_at', '>=', $startAt->copy()->subHours(8));
                    });
            })
            ->exists();

        if ($hasBookingConflict || $hasSessionConflict) {
            throw ValidationException::withMessages([
                'scheduled_start_at' => 'Tutor sudah memiliki jadwal lain pada waktu tersebut.',
            ]);
        }
    }

    /**
     * @param  array{location?: string|null, meeting_url?: string|null}  $sessionDetails
     */
    private function createSchedule(
        ScheduleBookingRequest $booking,
        PackageBookingRule $rule,
        User $responder,
        Carbon $startAt,
        Carbon $endAt,
        array $sessionDetails
    ): ClassSchedule {
        $booking->loadMissing([
            'user:id,name',
            'package:package_id,name',
            'studyGroup:id,name,tentor_id',
        ]);
        $studyGroup = $booking->studyGroup;
        $class = $rule->class_id
            ? ClassModel::query()->find($rule->class_id)
            : null;

        if (! $class) {
            $class = ClassModel::query()->create([
                'title' => 'Booking - '.$booking->package->name,
                'schedule_time' => $startAt,
                'mentor' => null,
                'status' => 'upcoming',
                'is_for_sale' => false,
                'is_displayed' => false,
            ]);
            $rule->update(['class_id' => $class->class_id]);
        }

        $schedule = ClassSchedule::query()->create([
            'class_id' => $class->class_id,
            'study_group_id' => $studyGroup?->id,
            'tentor_id' => $booking->tentor_id,
            'title' => $studyGroup
                ? 'Booking '.$studyGroup->name
                : 'Booking '.$booking->package->name.' - '.$booking->user->name,
            'schedule_type' => 'single',
            'frequency' => null,
            'start_time' => $startAt->format('H:i'),
            'end_time' => $endAt->format('H:i'),
            'start_date' => $startAt->toDateString(),
            'end_date' => $startAt->toDateString(),
            'meeting_url' => $sessionDetails['meeting_url'] ?? null,
            'location' => ($sessionDetails['location'] ?? null) ?: $rule->default_location,
            'is_active' => true,
            'created_by' => $responder->id,
        ]);
        $schedule->attendanceSetting()->create([
            'mode' => 'button',
            'open_minutes_before' => 15,
            'close_minutes_after' => 30,
            'allow_admin_override' => true,
        ]);
        if ($studyGroup) {
            $studyGroup->update(['tentor_id' => $booking->tentor_id]);
        } else {
            $schedule->detailPackages()->create([
                'package_id' => $booking->package_id,
                'order' => 0,
            ]);
        }
        $daysAhead = max(
            1,
            (int) ceil(now()->startOfDay()->diffInDays($startAt->copy()->startOfDay())) + 1
        );
        $this->scheduleService->generateSessions($schedule, $daysAhead);

        return $schedule;
    }

    /**
     * @param  array<int, string>  $allowedStatuses
     */
    private function ensureStatus(
        ScheduleBookingRequest $booking,
        array $allowedStatuses,
        string $message
    ): void {
        if (! in_array($booking->status, $allowedStatuses, true)) {
            throw ValidationException::withMessages(['booking' => $message]);
        }
    }

    private function ensureWithinBookingWindow(
        PackageBookingRule $rule,
        Carbon $startAt
    ): void {
        $minimumStart = now()->addHours($rule->min_notice_hours);
        $maximumStart = now()->addDays($rule->max_advance_days)->endOfDay();

        if ($startAt->lt($minimumStart) || $startAt->gt($maximumStart)) {
            throw ValidationException::withMessages([
                'scheduled_start_at' => "Waktu booking harus antara {$rule->min_notice_hours} jam hingga {$rule->max_advance_days} hari dari sekarang.",
            ]);
        }
    }

    private function ensureAccessCoversSession(
        UserPackageAcces $access,
        Carbon $startAt
    ): void {
        if ($access->end_date && $startAt->gt($access->end_date)) {
            throw ValidationException::withMessages([
                'scheduled_start_at' => 'Waktu booking melewati masa aktif paket siswa.',
            ]);
        }
    }

    private function slotKey(int $tentorId, Carbon $startAt, Carbon $endAt): string
    {
        return hash('sha256', implode('|', [
            $tentorId,
            $startAt->toIso8601String(),
            $endAt->toIso8601String(),
        ]));
    }
}
