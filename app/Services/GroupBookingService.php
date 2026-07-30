<?php

namespace App\Services;

use App\Models\BillInvoice;
use App\Models\Package;
use App\Models\PackageBookingPriceTier;
use App\Models\PackageBookingRule;
use App\Models\StudyGroup;
use App\Models\StudyGroupMember;
use App\Models\User;
use App\Models\UserPackageAcces;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GroupBookingService
{
    public function create(Package $package, User $organizer, int $participantCount): StudyGroup
    {
        return DB::transaction(function () use ($package, $organizer, $participantCount): StudyGroup {
            User::query()->whereKey($organizer->id)->lockForUpdate()->firstOrFail();
            $package = Package::query()
                ->whereKey($package->package_id)
                ->lockForUpdate()
                ->firstOrFail();
            $rule = $this->activeGroupRule($package);
            $this->ensureParticipantCount($rule, $participantCount);
            $tier = $this->resolvePriceTier($package, $rule, $participantCount);
            $this->ensureUserHasNoActiveGroup($organizer->id, $package->package_id);

            $inviteCode = $this->inviteCode();
            $group = StudyGroup::query()->create([
                'name' => $package->name.' · Pengajuan '.$inviteCode,
                'description' => 'Rombel pengajuan dari booking paket.',
                'package_id' => $package->package_id,
                'package_booking_rule_id' => $rule->id,
                'package_booking_price_tier_id' => $tier->id,
                'organizer_user_id' => $organizer->id,
                'invite_code' => $inviteCode,
                'target_participants' => $participantCount,
                'unit_price_snapshot' => $tier->price_per_person,
                'status' => StudyGroup::STATUS_PENDING_APPROVAL,
                'expires_at' => now()->addHours($rule->payment_deadline_hours),
                'is_active' => false,
            ]);

            $this->addMember($group, $organizer, 'organizer');

            return $group->fresh($this->groupRelations());
        }, 3);
    }

    public function join(User $user, string $inviteCode): StudyGroup
    {
        return DB::transaction(function () use ($user, $inviteCode): StudyGroup {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $group = StudyGroup::query()
                ->with('package:package_id,name,access_duration_unit,access_duration_value')
                ->where('invite_code', Str::upper(trim($inviteCode)))
                ->lockForUpdate()
                ->first();

            if (! $group
                || $group->status !== StudyGroup::STATUS_PENDING_APPROVAL
                || ($group->expires_at && $group->expires_at->isPast())) {
                throw ValidationException::withMessages([
                    'invite_code' => 'Kode rombel tidak valid atau masa bergabung sudah berakhir.',
                ]);
            }

            $activeMemberCount = $group->members()
                ->whereIn('status', [
                    StudyGroupMember::STATUS_AWAITING_APPROVAL,
                    StudyGroupMember::STATUS_AWAITING_PAYMENT,
                    StudyGroupMember::STATUS_PAID,
                ])
                ->count();
            if ($activeMemberCount >= $group->target_participants) {
                throw ValidationException::withMessages([
                    'invite_code' => 'Rombel ini sudah penuh.',
                ]);
            }
            if ($group->members()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'invite_code' => 'Kamu sudah tergabung dalam rombel ini.',
                ]);
            }

            $this->ensureUserHasNoActiveGroup($user->id, $group->package_id);
            $this->addMember($group, $user, 'member');

            return $group->fresh($this->groupRelations());
        }, 3);
    }

    public function approve(StudyGroup $studyGroup): StudyGroup
    {
        return DB::transaction(function () use ($studyGroup): StudyGroup {
            $group = StudyGroup::query()
                ->with('package:package_id,name,access_duration_unit,access_duration_value')
                ->lockForUpdate()
                ->findOrFail($studyGroup->id);

            if ($group->status !== StudyGroup::STATUS_PENDING_APPROVAL) {
                throw ValidationException::withMessages([
                    'rombel' => 'Pengajuan rombel ini sudah diproses.',
                ]);
            }
            if ($group->expires_at && $group->expires_at->isPast()) {
                $group->update(['status' => StudyGroup::STATUS_EXPIRED]);
                throw ValidationException::withMessages([
                    'rombel' => 'Masa pengajuan rombel sudah berakhir.',
                ]);
            }

            $memberCount = $group->members()
                ->where('status', StudyGroupMember::STATUS_AWAITING_APPROVAL)
                ->count();
            if ($memberCount !== $group->target_participants) {
                throw ValidationException::withMessages([
                    'rombel' => 'Rombel harus terisi lengkap sebelum dapat disetujui.',
                ]);
            }

            $group->update([
                'status' => StudyGroup::STATUS_PENDING_PAYMENT,
                'expires_at' => now()->addHours($this->paymentDeadlineHours($group)),
            ]);
            $group->members()
                ->where('status', StudyGroupMember::STATUS_AWAITING_APPROVAL)
                ->update(['status' => StudyGroupMember::STATUS_AWAITING_PAYMENT]);

            $group->load('members');
            foreach ($group->members as $member) {
                $this->prepareMemberPayment($group, $member);
            }

            $this->finalizeIfPaid($group);

            return $group->fresh($this->groupRelations());
        }, 3);
    }

    public function syncInvoice(BillInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $memberReference = StudyGroupMember::query()
                ->where('bill_invoice_id', $invoice->id)
                ->first(['id', 'user_id', 'study_group_id']);
            if (! $memberReference) {
                return;
            }

            User::query()->whereKey($memberReference->user_id)->lockForUpdate()->firstOrFail();
            $member = StudyGroupMember::query()->lockForUpdate()->find($memberReference->id);
            $lockedInvoice = BillInvoice::query()->lockForUpdate()->find($invoice->id);
            $group = StudyGroup::query()
                ->with('package:package_id,name,access_duration_unit,access_duration_value')
                ->lockForUpdate()
                ->find($memberReference->study_group_id);

            if (! $member || ! $lockedInvoice || ! $group
                || $lockedInvoice->status !== 'paid'
                || $member->status === StudyGroupMember::STATUS_CANCELLED) {
                return;
            }

            $access = $this->grantAccess(
                $member->user_id,
                $group->package,
                (int) $member->unit_price_snapshot
            );
            $member->update([
                'status' => StudyGroupMember::STATUS_PAID,
                'user_package_access_id' => $access->user_package_access_id,
                'paid_at' => $lockedInvoice->paid_at ?? now(),
            ]);

            $this->finalizeIfPaid($group);
        }, 3);
    }

    private function activeGroupRule(Package $package): PackageBookingRule
    {
        $rule = PackageBookingRule::query()
            ->where('package_id', $package->package_id)
            ->where('is_enabled', true)
            ->lockForUpdate()
            ->first();

        if (! $rule || ! in_array($rule->learning_mode, ['group', 'both'], true)) {
            throw ValidationException::withMessages([
                'package_id' => 'Paket ini belum menyediakan rombel booking.',
            ]);
        }

        return $rule;
    }

    private function ensureParticipantCount(PackageBookingRule $rule, int $participantCount): void
    {
        if ($participantCount < $rule->min_participants
            || $participantCount > $rule->max_participants) {
            throw ValidationException::withMessages([
                'participant_count' => "Jumlah anggota harus {$rule->min_participants}–{$rule->max_participants} orang.",
            ]);
        }
    }

    private function resolvePriceTier(
        Package $package,
        PackageBookingRule $rule,
        int $participantCount
    ): PackageBookingPriceTier {
        $tier = PackageBookingPriceTier::query()
            ->where('package_booking_rule_id', $rule->id)
            ->where('participant_count', $participantCount)
            ->lockForUpdate()
            ->first();

        if ($tier) {
            return $tier;
        }

        if (($rule->group_pricing_mode ?? 'same') === 'same') {
            return PackageBookingPriceTier::query()->create([
                'package_booking_rule_id' => $rule->id,
                'participant_count' => $participantCount,
                'price_per_person' => (int) $package->price,
            ]);
        }

        throw ValidationException::withMessages([
            'participant_count' => 'Harga untuk jumlah anggota tersebut belum diatur.',
        ]);
    }

    private function ensureUserHasNoActiveGroup(int $userId, int $packageId): void
    {
        $exists = StudyGroupMember::query()
            ->where('user_id', $userId)
            ->whereIn('status', [
                StudyGroupMember::STATUS_AWAITING_APPROVAL,
                StudyGroupMember::STATUS_AWAITING_PAYMENT,
                StudyGroupMember::STATUS_PAID,
            ])
            ->whereHas('studyGroup', function ($query) use ($packageId): void {
                $query->where('package_id', $packageId)
                    ->whereIn('status', [
                        StudyGroup::STATUS_PENDING_APPROVAL,
                        StudyGroup::STATUS_PENDING_PAYMENT,
                        StudyGroup::STATUS_ACTIVE,
                    ]);
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'package_id' => 'Kamu sudah tergabung dalam rombel aktif untuk paket ini.',
            ]);
        }
    }

    private function addMember(StudyGroup $group, User $user, string $role): void
    {
        $group->members()->create([
            'user_id' => $user->id,
            'role' => $role,
            'status' => StudyGroupMember::STATUS_AWAITING_APPROVAL,
            'unit_price_snapshot' => $group->unit_price_snapshot,
        ]);
    }

    private function prepareMemberPayment(StudyGroup $group, StudyGroupMember $member): void
    {
        $activeAccess = UserPackageAcces::query()
            ->where('user_id', $member->user_id)
            ->where('package_id', $group->package_id)
            ->active()
            ->lockForUpdate()
            ->first();

        if ($activeAccess) {
            $member->update([
                'status' => StudyGroupMember::STATUS_PAID,
                'user_package_access_id' => $activeAccess->user_package_access_id,
                'paid_at' => now(),
            ]);

            return;
        }

        if ((int) $member->unit_price_snapshot === 0) {
            $access = $this->grantAccess($member->user_id, $group->package, 0);
            $member->update([
                'status' => StudyGroupMember::STATUS_PAID,
                'user_package_access_id' => $access->user_package_access_id,
                'paid_at' => now(),
            ]);

            return;
        }

        $invoice = BillInvoice::query()->create([
            'user_id' => $member->user_id,
            'invoice_number' => $this->invoiceNumber($group, $member),
            'title' => 'Rombel '.$group->package->name,
            'amount' => $member->unit_price_snapshot,
            'due_date' => ($group->expires_at ?? now()->addDays(2))->toDateString(),
            'status' => 'unpaid',
            'notes' => 'Pembayaran anggota rombel '.$group->invite_code.'.',
        ]);
        $member->update(['bill_invoice_id' => $invoice->id]);
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
                'notes' => 'Akses dari pembayaran rombel.',
                'created_by' => null,
                'requirement_status' => 'none',
            ]
        );
    }

    private function finalizeIfPaid(StudyGroup $group): void
    {
        $paidCount = $group->members()
            ->where('status', StudyGroupMember::STATUS_PAID)
            ->count();
        if ($paidCount < $group->target_participants) {
            return;
        }

        $group->update([
            'status' => StudyGroup::STATUS_ACTIVE,
            'is_active' => true,
            'expires_at' => null,
        ]);
    }

    private function paymentDeadlineHours(StudyGroup $group): int
    {
        return max(1, (int) ($group->rule?->payment_deadline_hours ?? 48));
    }

    /**
     * @return array<int, string>
     */
    private function groupRelations(): array
    {
        return [
            'package:package_id,name',
            'organizer:id,name,email',
            'members.user:id,name,email',
            'members.invoice',
        ];
    }

    private function inviteCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (StudyGroup::query()->where('invite_code', $code)->exists());

        return $code;
    }

    private function invoiceNumber(StudyGroup $group, StudyGroupMember $member): string
    {
        do {
            $number = "RMB-{$group->id}-{$member->user_id}-".Str::upper(Str::random(5));
        } while (BillInvoice::query()->where('invoice_number', $number)->exists());

        return $number;
    }
}
