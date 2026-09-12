<?php

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Tentor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TutorTeachingScheduleService
{
    public function addSession(Tentor $tentor, ClassSchedule $schedule, array $data): ClassSession
    {
        abort_unless((int) $schedule->tentor_id === (int) $tentor->id, 403);

        return DB::transaction(function () use ($tentor, $schedule, $data): ClassSession {
            $schedule = ClassSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            $startAt = Carbon::parse($data['session_date'].' '.$data['start_time']);
            abort_unless($startAt->isFuture(), 422, 'Waktu sesi harus di masa mendatang.');
            $endAt = filled($data['end_time'])
                ? Carbon::parse($data['session_date'].' '.$data['end_time'])
                : null;

            return ClassSession::query()->firstOrCreate(
                [
                    'class_schedule_id' => $schedule->id,
                    'session_date' => $startAt->toDateString(),
                    'start_at' => $startAt,
                ],
                [
                    'class_id' => $schedule->class_id,
                    'study_group_id' => $schedule->study_group_id,
                    'tentor_id' => $tentor->id,
                    'end_at' => $endAt,
                    'status' => 'scheduled',
                    'meeting_url' => $data['meeting_url'] ?: $schedule->meeting_url,
                    'location' => $data['location'] ?: $schedule->location,
                    'notes' => $data['notes'] ?? null,
                ],
            );
        });
    }

    public function cancelSession(Tentor $tentor, ClassSession $session, ?string $reason): void
    {
        abort_unless((int) $session->tentor_id === (int) $tentor->id, 403);
        abort_unless($session->status === 'scheduled' && $session->start_at->isFuture(), 422, 'Hanya sesi mendatang yang dapat dibatalkan.');
        abort_if($session->tutorAttendance()->where('approval_status', 'approved')->exists(), 422, 'Sesi dengan absensi tutor yang sudah disetujui tidak dapat dibatalkan.');

        $session->update([
            'status' => 'cancelled',
            'notes' => trim(implode("\n", array_filter([
                $session->notes,
                'Dibatalkan tutor: '.($reason ?: 'tanpa keterangan'),
            ]))),
        ]);
    }
}
