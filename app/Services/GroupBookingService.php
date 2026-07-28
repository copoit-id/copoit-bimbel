<?php

namespace App\Services;

use App\Models\BillInvoice;
use App\Models\BookingCohort;
use App\Models\BookingCohortParticipant;
use App\Models\Package;
use App\Models\PackageBookingPriceTier;
use App\Models\PackageBookingRule;
use App\Models\StudyGroup;
use App\Models\User;
use App\Models\UserPackageAcces;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GroupBookingService
{
    public function create(Package $package, User $organizer, int $participantCount): BookingCohort
    {
        return DB::transaction(function () use ($package, $organizer, $participantCount): BookingCohort {
            User::query()->whereKey($organizer->id)->lockForUpdate()->firstOrFail();
            $rule = PackageBookingRule::query()
                ->where('package_id', $package->package_id)
                ->where('is_enabled', true)
                ->lockForUpdate()
                ->first();

            if (! $rule || ! in_array($rule->learning_mode, ['group', 'both'], true)) {
                throw ValidationException::withMessages([
                    'package_id' => 'Paket ini belum menyediakan booking kelompok.',
                ]);
            }

            if ($participantCount < $rule->min_participants
                || $participantCount > $rule->max_participants) {
                throw ValidationException::withMessages([
                    'participant_count' => "Jumlah anggota harus {$rule->min_participants}–{$rule->max_participants} orang.",
                ]);
            }

            $tier = PackageBookingPriceTier::query()
                ->where('package_booking_rule_id', $rule->id)
                ->where('participant_count', $participantCount)
                ->lockForUpdate()
                ->first();

            if (! $tier) {
                throw ValidationException::withMessages([
                    'participant_count' => 'Harga untuk jumlah anggota tersebut belum diatur.',
                ]);
            }

            $hasActiveCohort = BookingCohortParticipant::query()
                ->where('user_id', $organizer->id)
                ->whereIn('status', [
                    BookingCohortParticipant::STATUS_AWAITING_PAYMENT,
                    BookingCohortParticipant::STATUS_PAID,
                ])
                ->whereHas('cohort', function ($query) use ($package): void {
                    $query->where('package_id', $package->package_id)
                        ->whereIn('status', [
                            BookingCohort::STATUS_FORMING,
                            BookingCohort::STATUS_READY,
                        ]);
                })
                ->exists();

            if ($hasActiveCohort) {
                throw ValidationException::withMessages([
                    'package_id' => 'Kamu sudah tergabung dalam kelompok aktif untuk paket ini.',
                ]);
            }

            $cohort = BookingCohort::query()->create([
                'package_id' => $package->package_id,
                'package_booking_rule_id' => $rule->id,
                'package_booking_price_tier_id' => $tier->id,
                'organizer_user_id' => $organizer->id,
                'invite_code' => $this->inviteCode(),
                'target_participants' => $participantCount,
                'unit_price_snapshot' => $tier->price_per_person,
                'status' => BookingCohort::STATUS_FORMING,
                'expires_at' => now()->addHours($rule->payment_deadline_hours),
            ]);

            $this->addParticipant($cohort, $organizer, 'organizer');
            $this->finalizeIfReady($cohort);

            return $cohort->fresh([
                'package:package_id,name',
                'participants.user:id,name,email',
                'participants.invoice',
                'studyGroup',
            ]);
        }, 3);
    }

    public function join(User $user, string $inviteCode): BookingCohort
    {
        return DB::transaction(function () use ($user, $inviteCode): BookingCohort {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $cohort = BookingCohort::query()
                ->with('package:package_id,name,access_duration_unit,access_duration_value')
                ->where('invite_code', Str::upper(trim($inviteCode)))
                ->lockForUpdate()
                ->first();

            if (! $cohort
                || $cohort->status !== BookingCohort::STATUS_FORMING
                || ($cohort->expires_at && $cohort->expires_at->isPast())) {
                throw ValidationException::withMessages([
                    'invite_code' => 'Kode kelompok tidak valid atau masa bergabung sudah berakhir.',
                ]);
            }

            if ($cohort->participants()
                ->whereIn('status', [
                    BookingCohortParticipant::STATUS_AWAITING_PAYMENT,
                    BookingCohortParticipant::STATUS_PAID,
                ])
                ->count() >= $cohort->target_participants) {
                throw ValidationException::withMessages([
                    'invite_code' => 'Kelompok ini sudah penuh.',
                ]);
            }

            if ($cohort->participants()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'invite_code' => 'Kamu sudah tergabung dalam kelompok ini.',
                ]);
            }

            $alreadyInPackageCohort = BookingCohortParticipant::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    BookingCohortParticipant::STATUS_AWAITING_PAYMENT,
                    BookingCohortParticipant::STATUS_PAID,
                ])
                ->whereHas('cohort', function ($query) use ($cohort): void {
                    $query->where('package_id', $cohort->package_id)
                        ->whereIn('status', [
                            BookingCohort::STATUS_FORMING,
                            BookingCohort::STATUS_READY,
                        ]);
                })
                ->exists();

            if ($alreadyInPackageCohort) {
                throw ValidationException::withMessages([
                    'invite_code' => 'Kamu sudah tergabung dalam kelompok aktif untuk paket ini.',
                ]);
            }

            $this->addParticipant($cohort, $user, 'member');
            $this->finalizeIfReady($cohort);

            return $cohort->fresh([
                'package:package_id,name',
                'participants.user:id,name,email',
                'participants.invoice',
                'studyGroup',
            ]);
        }, 3);
    }

    public function syncInvoice(BillInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $participantReference = BookingCohortParticipant::query()
                ->where('bill_invoice_id', $invoice->id)
                ->first(['id', 'user_id']);

            if (! $participantReference) {
                return;
            }

            User::query()
                ->whereKey($participantReference->user_id)
                ->lockForUpdate()
                ->firstOrFail();
            $participant = BookingCohortParticipant::query()
                ->whereKey($participantReference->id)
                ->lockForUpdate()
                ->first();

            if (! $participant) {
                return;
            }

            $cohort = BookingCohort::query()
                ->with('package:package_id,name,access_duration_unit,access_duration_value')
                ->lockForUpdate()
                ->findOrFail($participant->booking_cohort_id);
            $lockedInvoice = BillInvoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($lockedInvoice->status !== 'paid'
                || $participant->status === BookingCohortParticipant::STATUS_CANCELLED) {
                return;
            }

            $access = $this->grantAccess(
                $participant->user_id,
                $cohort->package,
                (int) $participant->unit_price_snapshot
            );
            $participant->update([
                'status' => BookingCohortParticipant::STATUS_PAID,
                'user_package_access_id' => $access->user_package_access_id,
                'paid_at' => $lockedInvoice->paid_at ?? now(),
            ]);

            $this->finalizeIfReady($cohort);
        }, 3);
    }

    private function addParticipant(BookingCohort $cohort, User $user, string $role): void
    {
        $activeAccess = UserPackageAcces::query()
            ->where('user_id', $user->id)
            ->where('package_id', $cohort->package_id)
            ->active()
            ->lockForUpdate()
            ->first();

        if ($activeAccess) {
            $cohort->participants()->create([
                'user_id' => $user->id,
                'user_package_access_id' => $activeAccess->user_package_access_id,
                'role' => $role,
                'status' => BookingCohortParticipant::STATUS_PAID,
                'unit_price_snapshot' => $cohort->unit_price_snapshot,
                'paid_at' => now(),
            ]);

            return;
        }

        if ((int) $cohort->unit_price_snapshot === 0) {
            $access = $this->grantAccess($user->id, $cohort->package, 0);
            $cohort->participants()->create([
                'user_id' => $user->id,
                'user_package_access_id' => $access->user_package_access_id,
                'role' => $role,
                'status' => BookingCohortParticipant::STATUS_PAID,
                'unit_price_snapshot' => 0,
                'paid_at' => now(),
            ]);

            return;
        }

        $participant = $cohort->participants()->create([
            'user_id' => $user->id,
            'role' => $role,
            'status' => BookingCohortParticipant::STATUS_AWAITING_PAYMENT,
            'unit_price_snapshot' => $cohort->unit_price_snapshot,
        ]);
        $invoice = BillInvoice::query()->create([
            'user_id' => $user->id,
            'invoice_number' => $this->invoiceNumber($cohort, $user),
            'title' => 'Paket kelompok '.$cohort->package->name,
            'amount' => $cohort->unit_price_snapshot,
            'due_date' => ($cohort->expires_at ?? now()->addDays(2))->toDateString(),
            'status' => 'unpaid',
            'notes' => "Pembayaran per anggota kelompok {$cohort->invite_code}.",
        ]);
        $participant->update(['bill_invoice_id' => $invoice->id]);
    }

    private function grantAccess(int $userId, Package $package, int $amount): UserPackageAcces
    {
        $startDate = now();

        return UserPackageAcces::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'package_id' => $package->package_id,
            ],
            [
                'start_date' => $startDate,
                'end_date' => PurchaseAccessDuration::expiresAt($package, $startDate),
                'status' => 'active',
                'payment_amount' => $amount,
                'payment_status' => $amount > 0 ? 'paid' : 'free',
                'notes' => 'Akses dari pembayaran paket kelompok.',
                'created_by' => null,
                'requirement_status' => 'none',
            ]
        );
    }

    private function finalizeIfReady(BookingCohort $cohort): void
    {
        $paidParticipants = $cohort->participants()
            ->where('status', BookingCohortParticipant::STATUS_PAID)
            ->get(['user_id']);

        if ($paidParticipants->count() < $cohort->target_participants) {
            return;
        }

        $group = $cohort->study_group_id
            ? StudyGroup::query()->lockForUpdate()->findOrFail($cohort->study_group_id)
            : StudyGroup::query()->create([
                'name' => "{$cohort->package->name} · {$cohort->invite_code}",
                'description' => "Rombel otomatis dari booking kelompok {$cohort->invite_code}.",
                'is_active' => true,
            ]);

        $group->users()->sync($paidParticipants->pluck('user_id')->all());
        $cohort->update([
            'study_group_id' => $group->id,
            'status' => BookingCohort::STATUS_READY,
        ]);
    }

    private function inviteCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (BookingCohort::query()->where('invite_code', $code)->exists());

        return $code;
    }

    private function invoiceNumber(BookingCohort $cohort, User $user): string
    {
        do {
            $number = "GRP-{$cohort->id}-{$user->id}-".Str::upper(Str::random(5));
        } while (BillInvoice::query()->where('invoice_number', $number)->exists());

        return $number;
    }
}
