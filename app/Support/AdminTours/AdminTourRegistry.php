<?php

namespace App\Support\AdminTours;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class AdminTourRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return config('admin_tours', []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function forUser(string $key, User $user, string $portal): ?array
    {
        if (! config('client.branding.admin_tours_enabled', false)) {
            return null;
        }

        $definition = $this->definitions()[$key] ?? null;
        if (! $definition || ! in_array($portal, $definition['portal'], true)) {
            return null;
        }

        $permission = $definition['required_permission'];
        if (! $user->isSuperAdmin() && ! $user->hasPermission($permission['feature'], $permission['action'])) {
            return null;
        }

        foreach ($definition['steps'] as $step) {
            if (! Route::has($step['route'])) {
                return null;
            }
        }

        return $definition;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    public function payload(array $definition): array
    {
        return [
            'key' => $definition['key'],
            'version' => $definition['version'],
            'title' => $definition['title'],
            'steps' => array_map(function (array $step): array {
                return [
                    'id' => $step['id'],
                    'route' => $step['route'],
                    'target' => $step['target'],
                    'type' => $step['type'],
                    'title' => $step['title'],
                    'body' => $step['body'],
                    'allowed_action' => $step['allowed_action'],
                    'next_route' => $step['next_route'] ?? null,
                ];
            }, $definition['steps']),
        ];
    }
}
