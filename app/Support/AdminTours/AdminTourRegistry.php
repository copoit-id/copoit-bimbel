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
        return [
            'admin.tryout.create' => [
                'key' => 'admin.tryout.create',
                'version' => 1,
                'title' => 'Membuat Tryout',
                'portal' => ['admin'],
                'required_permission' => ['feature' => 'tryout', 'action' => 'create'],
                'steps' => [
                    [
                        'id' => 'open_create',
                        'route' => 'admin.tryout.index',
                        'target' => '[data-tour="tryout.create"]',
                        'type' => 'click_target',
                        'title' => 'Buat tryout baru',
                        'body' => 'Klik Tambah Tryout untuk membuka form pembuatan.',
                        'allowed_action' => 'click',
                        'next_route' => 'admin.tryout.create',
                    ],
                    [
                        'id' => 'fill_name',
                        'route' => 'admin.tryout.create',
                        'target' => '[data-tour="tryout.name"]',
                        'type' => 'input_target',
                        'title' => 'Isi nama tryout',
                        'body' => 'Masukkan nama yang mudah dikenali peserta. Setelah terisi, lanjutkan ke pengaturan jadwal.',
                        'allowed_action' => 'input',
                    ],
                    [
                        'id' => 'set_schedule',
                        'route' => 'admin.tryout.create',
                        'target' => '[data-tour="tryout.schedule"]',
                        'type' => 'explain',
                        'title' => 'Atur periode tryout',
                        'body' => 'Tentukan waktu mulai dan berakhir sesuai jadwal pelaksanaan. Periksa kembali sebelum menyimpan.',
                        'allowed_action' => 'none',
                    ],
                    [
                        'id' => 'complete',
                        'route' => 'admin.tryout.create',
                        'target' => '[data-tour="tryout.form"]',
                        'type' => 'complete',
                        'title' => 'Anda siap melanjutkan',
                        'body' => 'Lengkapi pengaturan lain yang diperlukan, lalu simpan tryout ketika sudah siap. Tour tidak menyimpan data untuk Anda.',
                        'allowed_action' => 'none',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function forUser(string $key, User $user, string $portal): ?array
    {
        if (! config('client.branding.admin_tours_enabled', true)) {
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
