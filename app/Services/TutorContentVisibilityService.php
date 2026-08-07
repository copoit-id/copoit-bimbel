<?php

namespace App\Services;

use App\Models\ClientProfile;
use App\Models\User;

class TutorContentVisibilityService
{
    public const MODE_SHARED = 'shared';

    public const MODE_ISOLATED = 'isolated';

    private ?string $mode = null;

    public function mode(): string
    {
        if ($this->mode !== null) {
            return $this->mode;
        }

        $mode = ClientProfile::query()->value('tutor_content_visibility');

        return $this->mode = in_array($mode, [self::MODE_SHARED, self::MODE_ISOLATED], true)
            ? $mode
            : self::MODE_SHARED;
    }

    public function isIsolated(): bool
    {
        return $this->mode() === self::MODE_ISOLATED;
    }

    public function shouldScopeToOwner(?User $user): bool
    {
        if (! $user || ! $this->isIsolated()) {
            return false;
        }

        return ! in_array($user->role, ['super_admin', 'admin', 'admin_demo'], true);
    }
}
