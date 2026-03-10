<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogger
{
    public static function log(string $action, string $status, ?User $user = null, array $meta = [], ?Request $request = null, ?string $email = null): void
    {
        $request = $request ?: request();

        ActivityLog::create([
            'user_id' => $user?->id,
            'email' => $email ?? $user?->email ?? (string) ($request?->input('email') ?? ''),
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'action' => $action,
            'status' => $status,
            'meta' => $meta,
        ]);
    }
}
