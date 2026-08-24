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
        'ai-discussion' => 'ai_discussion_feature_enabled',
    ];

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $flag = self::FEATURE_FLAGS[$feature] ?? null;

        $enabled = $flag && (bool) config("client.branding.{$flag}", false);
        if ($feature === 'ai-discussion') {
            $enabled = $enabled && (bool) data_get(config('client.branding.ai_discussion_settings', []), 'enabled', false);
        }

        abort_unless($enabled, 404);

        return $next($request);
    }
}
