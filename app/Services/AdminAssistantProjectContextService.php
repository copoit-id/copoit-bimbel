<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AdminAssistantProjectContextService
{
    /** @return array<string, mixed> */
    public function snapshot(?User $user): array
    {
        $branding = (array) config('client.branding', []);
        $featureFlags = collect($branding)
            ->filter(fn ($value, $key): bool => is_bool($value) && Str::endsWith((string) $key, '_enabled'))
            ->map(fn (bool $value): bool => $value)
            ->all();

        $routes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route): array => [
                'name' => (string) $route->getName(),
                'uri' => (string) $route->uri(),
                'methods' => array_values(array_diff($route->methods(), ['HEAD'])),
            ])
            ->filter(fn (array $route): bool => filled($route['name']) && Str::startsWith($route['name'], 'admin.'))
            ->unique()
            ->sortBy('name')
            ->take(300)
            ->values()
            ->all();

        return [
            'project_name' => (string) ($branding['name'] ?? config('app.name', 'BIMBELHUB')),
            'portal' => (string) request()->route('portal', 'admin'),
            'current_route' => (string) request()->route()?->getName(),
            'user_role' => (string) ($user?->role ?? ''),
            'feature_flags' => $featureFlags,
            'available_admin_routes' => $routes,
            'verified_guides' => $this->guides(),
        ];
    }

    /** @return array<int, array<string, string>> */
    private function guides(): array
    {
        $directory = base_path('docs/admin-assistant');
        if (! is_dir($directory)) {
            return [];
        }

        return collect(glob($directory.'/*.md') ?: [])
            ->take(20)
            ->map(function (string $path): array {
                return [
                    'id' => Str::after($path, $directory.'/'),
                    'content' => Str::limit((string) file_get_contents($path), 12000, ''),
                ];
            })
            ->values()
            ->all();
    }
}
