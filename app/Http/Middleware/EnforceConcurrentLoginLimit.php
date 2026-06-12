<?php

namespace App\Http\Middleware;

use App\Services\ConcurrentLoginService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceConcurrentLoginLimit
{
    public function __construct(
        private ConcurrentLoginService $concurrentLoginService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $this->concurrentLoginService->enforce($request->user(), $request->session()->getId());
        }

        return $next($request);
    }
}
