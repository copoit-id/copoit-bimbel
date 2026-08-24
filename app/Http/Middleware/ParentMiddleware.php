<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ParentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->isParent(),
            403,
            'Akun ini bukan akun Orang Tua.'
        );

        return $next($request);
    }
}
