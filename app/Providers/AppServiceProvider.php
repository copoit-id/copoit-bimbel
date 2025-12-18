<?php

namespace App\Providers;

use App\Models\ClientProfile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        $defaultAsset = 'img/logo/logo-copoit.png';

        $defaults = [
            'name' => 'Copoit Academy',
            'logo' => $defaultAsset,
            'favicon' => null,
            'primary_color' => '#1C3259',
            'secondary_color' => '#F3F3F3',
            'certificate_management_enabled' => false,
            'header_primary_color' => false,
            'sidebar_primary_color' => false,
            'utbk_enabled' => false,
            'allow_video_thumbnail' => false,
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
            $defaults['certificate_management_enabled'] = (bool) ($clientProfile->enable_certificate_management ?? $defaults['certificate_management_enabled']);
            $defaults['header_primary_color'] = $clientProfile->header_primary_color ?? $defaults['header_primary_color'];
            $defaults['sidebar_primary_color'] = $clientProfile->sidebar_primary_color ?? $defaults['sidebar_primary_color'];
            $defaults['utbk_enabled'] = (bool) ($clientProfile->enable_utbk_types ?? $defaults['utbk_enabled']);
            $defaults['allow_video_thumbnail'] = $clientProfile->allow_video_thumbnail ?? $defaults['allow_video_thumbnail'];
        } else {
            $defaults['favicon'] = $defaults['favicon'] ?: $defaults['logo'];
        }

        // Force-hide certificate & UTBK modules per client request
        $defaults['certificate_management_enabled'] = false;
        $defaults['utbk_enabled'] = false;

        $logoUrl = $this->makeBrandAssetUrl($defaults['logo'], $defaultAsset);
        $faviconUrl = $this->makeBrandAssetUrl($defaults['favicon'] ?? $defaults['logo'], $defaultAsset);

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

    private function makeBrandAssetUrl(?string $path, string $fallback = 'img/logo/logo-copoit.png'): string
    {
        $target = $path ?: $fallback;

        if ($target && Str::startsWith($target, ['http://', 'https://', '//'])) {
            return $target;
        }

        $normalized = ltrim($target, '/');
        if (Str::startsWith($normalized, 'storage/')) {
            return asset($normalized);
        }

        if (file_exists(public_path($normalized))) {
            return asset($normalized);
        }

        if (Storage::disk('public')->exists($normalized)) {
            return Storage::disk('public')->url($normalized);
        }

        if (!Str::contains($normalized, '/')) {
            $normalized = 'img/logo/' . $normalized;
        }

        return asset($normalized);
    }
}
