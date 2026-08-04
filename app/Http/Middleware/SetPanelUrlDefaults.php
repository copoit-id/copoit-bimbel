<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetPanelUrlDefaults
{
    /**
     * Use the authenticated user's portal when generating a shared panel URL.
     */
    public function handle(Request $request, Closure $next): Response
    {
        URL::defaults([
            'portal' => $request->user()?->isTutor() ? 'tutor' : 'admin',
        ]);

        return $next($request);
    }
}
