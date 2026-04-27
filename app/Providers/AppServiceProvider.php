<?php

namespace App\Providers;

use App\Models\ClientProfile;
use Illuminate\Support\Facades\Blade;
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
        Blade::anonymousComponentNamespace(resource_path('views/components/ui'), 'ui');
        Blade::componentNamespace('App\\View\\Components\\Ui', 'ui');
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
            'payment_mode' => 'gateway',
            'payment_bank_name' => null,
            'payment_account_number' => null,
            'payment_account_holder' => null,
            'payment_bank_note' => null,
            'payment_gateway' => 'xendit',
            'payment_gateway_mode' => 'sandbox',
            'xendit_secret_key' => null,
            'xendit_webhook_token' => null,
            'midtrans_server_key' => null,
            'midtrans_client_key' => null,
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_encryption' => null,
            'smtp_email' => null,
            'smtp_app_password' => null,
            'smtp_notification_email' => null,
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
            $defaults['payment_mode'] = $clientProfile->payment_mode ?: $defaults['payment_mode'];
            $defaults['payment_bank_name'] = $clientProfile->payment_bank_name ?? $defaults['payment_bank_name'];
            $defaults['payment_account_number'] = $clientProfile->payment_account_number ?? $defaults['payment_account_number'];
            $defaults['payment_account_holder'] = $clientProfile->payment_account_holder ?? $defaults['payment_account_holder'];
            $defaults['payment_bank_note'] = $clientProfile->payment_bank_note ?? $defaults['payment_bank_note'];
            $defaults['payment_gateway'] = $clientProfile->payment_gateway ?: $defaults['payment_gateway'];
            $defaults['payment_gateway_mode'] = $clientProfile->payment_gateway_mode ?: $defaults['payment_gateway_mode'];
            $defaults['xendit_secret_key'] = $clientProfile->xendit_secret_key ?? $defaults['xendit_secret_key'];
            $defaults['xendit_webhook_token'] = $clientProfile->xendit_webhook_token ?? $defaults['xendit_webhook_token'];
            $defaults['midtrans_server_key'] = $clientProfile->midtrans_server_key ?? $defaults['midtrans_server_key'];
            $defaults['midtrans_client_key'] = $clientProfile->midtrans_client_key ?? $defaults['midtrans_client_key'];
            $defaults['smtp_host'] = $clientProfile->smtp_host ?? $defaults['smtp_host'];
            $defaults['smtp_port'] = $clientProfile->smtp_port ?? $defaults['smtp_port'];
            $defaults['smtp_encryption'] = $clientProfile->smtp_encryption ?? $defaults['smtp_encryption'];
            $defaults['smtp_email'] = $clientProfile->smtp_email ?? $defaults['smtp_email'];
            $defaults['smtp_app_password'] = $clientProfile->smtp_app_password ?? $defaults['smtp_app_password'];
            $defaults['smtp_notification_email'] = $clientProfile->smtp_notification_email ?? $defaults['smtp_notification_email'];
        } else {
            $defaults['favicon'] = $defaults['favicon'] ?: $defaults['logo'];
        }

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

        $this->applyDynamicPaymentConfiguration($branding);
        $this->applyDynamicMailConfiguration($branding);

        view()->share('clientProfile', $clientProfile);
        view()->share('clientBranding', $branding);
    }

    private function applyDynamicMailConfiguration(array $branding): void
    {
        $smtpHost = $branding['smtp_host'] ?: env('MAIL_HOST', 'smtp.gmail.com');
        $smtpPort = $branding['smtp_port'] ?: (int) env('MAIL_PORT', 587);
        $smtpEmail = $branding['smtp_email'] ?? null;
        $smtpPassword = $branding['smtp_app_password'] ?? null;
        $smtpEncryption = $branding['smtp_encryption'] ?: env('MAIL_SCHEME', 'tls');

        if (!$smtpHost || !$smtpPort || !$smtpEmail || !$smtpPassword) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $smtpHost,
            'mail.mailers.smtp.port' => (int) $smtpPort,
            'mail.mailers.smtp.username' => $smtpEmail,
            'mail.mailers.smtp.password' => $smtpPassword,
            'mail.mailers.smtp.scheme' => $smtpEncryption ?: null,
            'mail.from.address' => $smtpEmail,
            'mail.from.name' => $branding['name'] ?? config('app.name'),
        ]);
    }

    private function applyDynamicPaymentConfiguration(array $branding): void
    {
        $gateway = $branding['payment_gateway'] ?: env('PAYMENT_GATEWAY', 'xendit');
        $mode = $branding['payment_gateway_mode'] ?: (env('MIDTRANS_IS_PRODUCTION') ? 'production' : 'sandbox');
        $mode = $mode === 'production' ? 'production' : 'sandbox';

        $midtransSnapUrl = $mode === 'production'
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $midtransStatusUrl = $mode === 'production'
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';

        $xenditBaseUrl = 'https://api.xendit.co';

        config([
            'services.payment_gateway' => $gateway,
            'services.xendit.secret_key' => $branding['xendit_secret_key'] ?: env('XENDIT_SECRET_KEY'),
            'services.xendit.webhook_token' => $branding['xendit_webhook_token'] ?: env('XENDIT_WEBHOOK_TOKEN'),
            'services.xendit.base_url' => $xenditBaseUrl,
            'services.midtrans.server_key' => $branding['midtrans_server_key'] ?: env('MIDTRANS_SERVER_KEY'),
            'services.midtrans.client_key' => $branding['midtrans_client_key'] ?: env('MIDTRANS_CLIENT_KEY'),
            'services.midtrans.is_production' => $mode === 'production',
            'services.midtrans.snap_url' => $midtransSnapUrl,
            'services.midtrans.status_url' => $midtransStatusUrl,
        ]);
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
