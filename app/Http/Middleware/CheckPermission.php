<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        $route = $request->route();
        $routeName = $route?->getName();
        if (! $routeName) {
            return $next($request);
        }

        $feature = $this->resolveFeature($routeName);
        if (! $feature) {
            // Tutor must be explicitly granted every admin feature. This prevents access
            // to administrative routes that have not yet been mapped to a permission.
            if (method_exists($user, 'isTutor') && $user->isTutor()) {
                abort(403, 'Akses ditolak.');
            }

            return $next($request);
        }

        $action = $this->resolveAction($request->method(), $routeName);
        if (! $action) {
            return $next($request);
        }

        if (! $user->hasPermission($feature, $action)) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }

    private function resolveFeature(string $routeName): ?string
    {
        $features = config('permissions.features', []);
        $resolvedFeature = null;
        $bestMatchLength = -1;

        foreach ($features as $featureKey => $feature) {
            $prefixes = (array) ($feature['routes'] ?? []);
            foreach ($prefixes as $prefix) {
                if (Str::startsWith($routeName, $prefix) && strlen($prefix) > $bestMatchLength) {
                    $resolvedFeature = $featureKey;
                    $bestMatchLength = strlen($prefix);
                }
            }
        }

        return $resolvedFeature;
    }

    private function resolveAction(string $method, string $routeName): ?string
    {
        $method = strtoupper($method);

        if (in_array($method, ['GET', 'HEAD'], true)) {
            return 'view';
        }

        if ($method === 'DELETE') {
            return 'delete';
        }

        if (in_array($method, ['PUT', 'PATCH'], true)) {
            return 'update';
        }

        if ($method === 'POST') {
            if (Str::contains($routeName, ['destroy', 'bulk-destroy', 'delete'])) {
                return 'delete';
            }

            if (Str::contains($routeName, [
                'update',
                'toggle',
                'approve',
                'reject',
                'reset',
                'extend',
                'revoke',
                'confirm',
                'release',
                'add-time',
                'review',
                'activate',
            ])) {
                return 'update';
            }

            return 'create';
        }

        return null;
    }
}
