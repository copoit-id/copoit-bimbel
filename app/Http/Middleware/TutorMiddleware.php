<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TutorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user?->isTutor() && $user->tentorProfile()->where('is_active', true)->exists(),
            403,
            'Akun ini bukan akun Tutor yang aktif.'
        );

        return $next($request);
    }
}
