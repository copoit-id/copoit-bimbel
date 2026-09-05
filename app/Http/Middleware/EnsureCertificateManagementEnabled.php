<?php

namespace App\Http\Middleware;

use App\Services\PlanModuleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCertificateManagementEnabled
{
    public function __construct(
        private PlanModuleService $planModules
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            ! config('client.branding.certificate_management_enabled', true)
            || ! $this->planModules->allows('certificate')
        ) {
            abort(404);
        }

        return $next($request);
    }
}
