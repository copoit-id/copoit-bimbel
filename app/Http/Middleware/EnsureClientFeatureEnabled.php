<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientFeatureEnabled
{
    /**
     * @var array<string, string>
     */
    private const FEATURE_FLAGS = [
        'schedule-booking' => 'booking_schedule_enabled',
        'learning-progress' => 'learning_progress_enabled',
    ];

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $flag = self::FEATURE_FLAGS[$feature] ?? null;

        abort_unless($flag && (bool) config("client.branding.{$flag}", false), 404);

        return $next($request);
    }
}
