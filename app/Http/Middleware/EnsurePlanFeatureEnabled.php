<?php

namespace App\Http\Middleware;

use App\Services\PlanModuleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeatureEnabled
{
    public function __construct(
        private PlanModuleService $planModules
    ) {}

    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        if ($request->user()?->isSuperAdmin()) {
            return $next($request);
        }

        $resolvedFeature = $feature ?: $this->planModules->featureForRoute($request->route()?->getName());

        if ($resolvedFeature && ! $this->planModules->allows($resolvedFeature)) {
            abort(403, 'Fitur ini tidak tersedia pada modul plan yang aktif.');
        }

        return $next($request);
    }
}
