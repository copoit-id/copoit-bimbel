<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConcurrentLoginService
{
    public function enforce(User $user, ?string $currentSessionId = null): void
    {
        if ($user->role !== 'user') {
            return;
        }

        if (Config::get('session.driver') !== 'database') {
            return;
        }

        $table = (string) Config::get('session.table', 'sessions');
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'user_id')) {
            return;
        }

        $limit = max(1, (int) config('client.branding.concurrent_login_limit', 1));
        $currentSessionId = $currentSessionId ?: session()->getId();

        $otherSessionIds = DB::table($table)
            ->where('user_id', $user->id)
            ->when($currentSessionId, fn ($query) => $query->where('id', '!=', $currentSessionId))
            ->orderByDesc('last_activity')
            ->pluck('id');

        $allowedOtherSessions = max(0, $limit - 1);
        $sessionsToDelete = $otherSessionIds->slice($allowedOtherSessions);

        if ($sessionsToDelete->isNotEmpty()) {
            DB::table($table)
                ->whereIn('id', $sessionsToDelete->all())
                ->delete();
        }
    }
}
