<?php

namespace App\Providers;

use App\Models\ClientProfile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $defaults = [
            'name' => 'Copoit Academy',
            'logo' => 'img/logo/logo.png',
            'primary_color' => '#1C3259',
            'secondary_color' => '#F3F3F3',
            'certificate_management_enabled' => true,
            'header_primary_color' => false,
            'sidebar_primary_color' => false,
        ];

        $clientProfile = Schema::hasTable('client_profile')
            ? ClientProfile::query()->first()
            : null;

        if ($clientProfile) {
            $defaults['name'] = $clientProfile->nama_bimbel ?: $defaults['name'];
            $defaults['logo'] = $clientProfile->logo ?: $defaults['logo'];
            $defaults['primary_color'] = $clientProfile->warna_primary ?: $defaults['primary_color'];
            $defaults['secondary_color'] = $clientProfile->warna_secondary ?: $defaults['secondary_color'];
            $defaults['certificate_management_enabled'] = $clientProfile->enable_certificate_management ?? $defaults['certificate_management_enabled'];
            $defaults['header_primary_color'] = $clientProfile->header_primary_color ?? $defaults['header_primary_color'];
            $defaults['sidebar_primary_color'] = $clientProfile->sidebar_primary_color ?? $defaults['sidebar_primary_color'];
        }

        $logoPath = $defaults['logo'] ?? 'img/logo/logo.png';
        if ($logoPath && Str::startsWith($logoPath, ['http://', 'https://', '//'])) {
            $logoUrl = $logoPath;
        } else {
            $normalized = ltrim($logoPath, '/');
            if (!Str::contains($normalized, '/')) {
                $normalized = 'img/logo/' . $normalized;
            }
            $logoUrl = asset($normalized);
        }

        $branding = array_merge($defaults, [
            'logo_url' => $logoUrl,
        ]);

        config([
            'client.branding' => $branding,
            'app.name' => $branding['name'],
        ]);

        view()->share('clientProfile', $clientProfile);
        view()->share('clientBranding', $branding);
    }
}
