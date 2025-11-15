<?php

namespace App\Providers;

use App\Models\ClientProfile;
use Illuminate\Support\Facades\Schema;
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
        ];

        $clientProfile = Schema::hasTable('client_profile')
            ? ClientProfile::query()->first()
            : null;

        if ($clientProfile) {
            $defaults['name'] = $clientProfile->nama_bimbel ?: $defaults['name'];
            $defaults['logo'] = $clientProfile->logo ?: $defaults['logo'];
            $defaults['primary_color'] = $clientProfile->warna_primary ?: $defaults['primary_color'];
            $defaults['secondary_color'] = $clientProfile->warna_secondary ?: $defaults['secondary_color'];
        }

        $branding = array_merge($defaults, [
            'logo_url' => asset($defaults['logo']),
        ]);

        config([
            'client.branding' => $branding,
            'app.name' => $branding['name'],
        ]);

        view()->share('clientProfile', $clientProfile);
        view()->share('clientBranding', $branding);
    }
}
