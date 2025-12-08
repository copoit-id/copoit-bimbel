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
            'favicon' => null,
            'primary_color' => '#1C3259',
            'secondary_color' => '#F3F3F3',
            'certificate_management_enabled' => true,
            'header_primary_color' => false,
            'sidebar_primary_color' => false,
            'utbk_enabled' => true,
        ];

        $clientProfile = Schema::hasTable('client_profile')
            ? ClientProfile::query()->first()
            : null;

        if ($clientProfile) {
            $defaults['name'] = $clientProfile->nama_bimbel ?: $defaults['name'];
            $defaults['logo'] = $clientProfile->logo ?: $defaults['logo'];
            $defaults['favicon'] = $clientProfile->favicon ?: $defaults['logo'];
            $defaults['primary_color'] = $clientProfile->warna_primary ?: $defaults['primary_color'];
            $defaults['secondary_color'] = $clientProfile->warna_secondary ?: $defaults['secondary_color'];
            $defaults['certificate_management_enabled'] = $clientProfile->enable_certificate_management ?? $defaults['certificate_management_enabled'];
            $defaults['header_primary_color'] = $clientProfile->header_primary_color ?? $defaults['header_primary_color'];
            $defaults['sidebar_primary_color'] = $clientProfile->sidebar_primary_color ?? $defaults['sidebar_primary_color'];
            $defaults['utbk_enabled'] = $clientProfile->enable_utbk_types ?? $defaults['utbk_enabled'];
        } else {
            $defaults['favicon'] = $defaults['favicon'] ?: $defaults['logo'];
        }

        $logoUrl = $this->makeBrandAssetUrl($defaults['logo']);
        $faviconUrl = $this->makeBrandAssetUrl($defaults['favicon'] ?? $defaults['logo']);

        $branding = array_merge($defaults, [
            'logo_url' => $logoUrl,
            'favicon_url' => $faviconUrl,
        ]);

        config([
            'client.branding' => $branding,
            'app.name' => $branding['name'],
        ]);

        view()->share('clientProfile', $clientProfile);
        view()->share('clientBranding', $branding);
    }

    private function makeBrandAssetUrl(?string $path, string $fallback = 'img/logo/logo.png'): string
    {
        $target = $path ?: $fallback;

        if ($target && Str::startsWith($target, ['http://', 'https://', '//'])) {
            return $target;
        }

        $normalized = ltrim($target, '/');
        if (!Str::contains($normalized, '/')) {
            $normalized = 'img/logo/' . $normalized;
        }

        return asset($normalized);
    }
}
