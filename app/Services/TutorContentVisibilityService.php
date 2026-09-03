<?php

namespace App\Services;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TutorContentVisibilityService
{
    public const MODE_SHARED = 'shared';

    public const MODE_ISOLATED = 'isolated';

    public const MODE_TUTOR_ISOLATED = 'tutor_isolated';

    /** @var list<string> */
    private const ADMIN_ROLES = ['super_admin', 'admin', 'admin_demo'];

    private ?string $mode = null;

    private ?bool $enabled = null;

    public function isEnabled(): bool
    {
        if ($this->enabled !== null) {
            return $this->enabled;
        }

        return $this->enabled = (bool) ClientProfile::query()->value('tutor_content_enabled');
    }

    public function mode(): string
    {
        if ($this->mode !== null) {
            return $this->mode;
        }

        $mode = ClientProfile::query()->value('tutor_content_visibility');

        return $this->mode = in_array($mode, [self::MODE_SHARED, self::MODE_ISOLATED, self::MODE_TUTOR_ISOLATED], true)
            ? $mode
            : self::MODE_SHARED;
    }

    public function isIsolated(): bool
    {
        return $this->isEnabled() && $this->mode() !== self::MODE_SHARED;
    }

    public function shouldScopeToOwner(?User $user): bool
    {
        if (! $user || ! $this->isIsolated()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return false;
        }

        return $this->mode() === self::MODE_ISOLATED
            || ($this->mode() === self::MODE_TUTOR_ISOLATED && $user->isTutor());
    }

    public function applyContentVisibilityScope(Builder $query, User $user, string $createdByColumn): void
    {
        if ($this->mode() === self::MODE_ISOLATED) {
            $query->where($createdByColumn, $user->id);

            return;
        }

        $query->where(function (Builder $visibilityQuery) use ($createdByColumn, $user): void {
            $visibilityQuery->where($createdByColumn, $user->id)
                ->orWhereIn($createdByColumn, User::query()
                    ->select('id')
                    ->whereIn('role', self::ADMIN_ROLES));
        });
    }

    public function canTutorAccessContentOwner(?int $ownerId, User $user): bool
    {
        if ($ownerId === null) {
            return false;
        }

        if ($ownerId === (int) $user->id) {
            return true;
        }

        return $this->mode() === self::MODE_TUTOR_ISOLATED
            && User::query()
                ->whereKey($ownerId)
                ->whereIn('role', self::ADMIN_ROLES)
                ->exists();
    }

    public function canDeleteContentOwnedBy(?int $ownerId, User $user): bool
    {
        if ($this->mode() !== self::MODE_TUTOR_ISOLATED || ! $user->isTutor()) {
            return true;
        }

        return $ownerId === (int) $user->id;
    }

    private function isAdministrativeUser(User $user): bool
    {
        return in_array($user->role, self::ADMIN_ROLES, true);
    }
}
