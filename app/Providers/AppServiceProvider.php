<?php

namespace App\Providers;

use App\Models\ClientProfile;
use App\Models\Role;
use App\Services\PlanQuotaService;
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
            'faq_label' => 'FAQ',
            'live_session_label' => 'Kelas Belajar',
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
            'payment_unique_code_enabled' => true,
            'payment_gateway' => 'xendit',
            'payment_gateway_mode' => 'sandbox',
            'xendit_secret_key' => null,
            'xendit_webhook_token' => null,
            'midtrans_server_key' => null,
            'midtrans_client_key' => null,
            'interactive_qris_api_key' => null,
            'interactive_qris_mid' => null,
            'interactive_qris_use_tip' => false,
            'ipaymu_api_key' => null,
            'ipaymu_va' => null,
            'discount_menu_enabled' => (bool) config('settings.discount_menu_enabled', true),
            'affiliate_menu_enabled' => (bool) config('settings.affiliate_menu_enabled', false),
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_encryption' => null,
            'smtp_email' => null,
            'smtp_app_password' => null,
            'smtp_notification_email' => null,
            'contact_whatsapp_number' => null,
            'contact_whatsapp_button_text' => 'Chat Admin',
            'concurrent_login_limit' => 1,
            'footer_enabled' => true,
            'footer_description' => null,
            'footer_copyright' => null,
            'footer_links' => [
                ['label' => 'FAQ', 'url' => '/user/bantuan'],
                ['label' => 'Syarat dan Ketentuan', 'url' => '/terms-and-conditions'],
                ['label' => 'Kebijakan Pembayaran', 'url' => '/payment-policy'],
                ['label' => 'Refund Policy', 'url' => '/refund-policy'],
            ],
            'footer_address' => null,
            'footer_phone' => null,
            'footer_email' => null,
            'footer_whatsapp' => null,
            'footer_facebook' => null,
            'footer_instagram' => null,
            'footer_twitter' => null,
            'footer_youtube' => null,
            'tes_koran_enabled' => true,
            'ai_question_generator_enabled' => false,
            'ai_question_generator_settings' => [],
            'ai_discussion_feature_enabled' => false,
            'ai_discussion_settings' => [],
            'admin_assistant_enabled' => false,
            'class_schedule_menu_enabled' => false,
            'recurring_bill_menu_enabled' => false,
            'participant_destination_api_enabled' => false,
        ];

        $clientProfile = Schema::hasTable('client_profile')
            ? ClientProfile::query()->first()
            : null;

        if ($clientProfile) {
            $defaults['name'] = $clientProfile->nama_bimbel ?: $defaults['name'];
            $defaults['faq_label'] = $clientProfile->faq_label ?: $defaults['faq_label'];
            $defaults['live_session_label'] = $clientProfile->live_session_label ?: $defaults['live_session_label'];
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
            $defaults['payment_unique_code_enabled'] = (bool) ($clientProfile->payment_unique_code_enabled ?? $defaults['payment_unique_code_enabled']);
            $defaults['payment_gateway'] = $clientProfile->payment_gateway ?: $defaults['payment_gateway'];
            $defaults['payment_gateway_mode'] = $clientProfile->payment_gateway_mode ?: $defaults['payment_gateway_mode'];
            $defaults['xendit_secret_key'] = $clientProfile->xendit_secret_key ?? $defaults['xendit_secret_key'];
            $defaults['xendit_webhook_token'] = $clientProfile->xendit_webhook_token ?? $defaults['xendit_webhook_token'];
            $defaults['midtrans_server_key'] = $clientProfile->midtrans_server_key ?? $defaults['midtrans_server_key'];
            $defaults['midtrans_client_key'] = $clientProfile->midtrans_client_key ?? $defaults['midtrans_client_key'];
            $defaults['interactive_qris_api_key'] = $clientProfile->interactive_qris_api_key ?? $defaults['interactive_qris_api_key'];
            $defaults['interactive_qris_mid'] = $clientProfile->interactive_qris_mid ?? $defaults['interactive_qris_mid'];
            $defaults['interactive_qris_use_tip'] = (bool) ($clientProfile->interactive_qris_use_tip ?? $defaults['interactive_qris_use_tip']);
            $defaults['ipaymu_api_key'] = $clientProfile->ipaymu_api_key ?? $defaults['ipaymu_api_key'];
            $defaults['ipaymu_va'] = $clientProfile->ipaymu_va ?? $defaults['ipaymu_va'];
            $defaults['smtp_host'] = $clientProfile->smtp_host ?? $defaults['smtp_host'];
            $defaults['smtp_port'] = $clientProfile->smtp_port ?? $defaults['smtp_port'];
            $defaults['smtp_encryption'] = $clientProfile->smtp_encryption ?? $defaults['smtp_encryption'];
            $defaults['smtp_email'] = $clientProfile->smtp_email ?? $defaults['smtp_email'];
            $defaults['smtp_app_password'] = $clientProfile->smtp_app_password ?? $defaults['smtp_app_password'];
            $defaults['smtp_notification_email'] = $clientProfile->smtp_notification_email ?? $defaults['smtp_notification_email'];
            $defaults['contact_whatsapp_number'] = $clientProfile->contact_whatsapp_number ?? $defaults['contact_whatsapp_number'];
            $defaults['contact_whatsapp_button_text'] = $clientProfile->contact_whatsapp_button_text ?: $defaults['contact_whatsapp_button_text'];
            $defaults['concurrent_login_limit'] = max(1, (int) ($clientProfile->concurrent_login_limit ?? $defaults['concurrent_login_limit']));
            $defaults['footer_enabled'] = (bool) ($clientProfile->footer_enabled ?? $defaults['footer_enabled']);
            $defaults['footer_description'] = $clientProfile->footer_description ?? $defaults['footer_description'];
            $defaults['footer_copyright'] = $clientProfile->footer_copyright ?? $defaults['footer_copyright'];
            $defaults['footer_links'] = $clientProfile->footer_links ?: $defaults['footer_links'];
            $defaults['footer_address'] = $clientProfile->footer_address ?? $defaults['footer_address'];
            $defaults['footer_phone'] = $clientProfile->footer_phone ?? $defaults['footer_phone'];
            $defaults['footer_email'] = $clientProfile->footer_email ?? $defaults['footer_email'];
            $defaults['footer_whatsapp'] = $clientProfile->footer_whatsapp ?? $defaults['footer_whatsapp'];
            $defaults['footer_facebook'] = $clientProfile->footer_facebook ?? $defaults['footer_facebook'];
            $defaults['footer_instagram'] = $clientProfile->footer_instagram ?? $defaults['footer_instagram'];
            $defaults['footer_twitter'] = $clientProfile->footer_twitter ?? $defaults['footer_twitter'];
            $defaults['footer_youtube'] = $clientProfile->footer_youtube ?? $defaults['footer_youtube'];
            $defaults['ai_question_generator_settings'] = $clientProfile->ai_question_generator_settings ?: $defaults['ai_question_generator_settings'];
            $defaults['ai_discussion_feature_enabled'] = (bool) ($clientProfile->ai_discussion_feature_enabled ?? $defaults['ai_discussion_feature_enabled']);
            $defaults['ai_discussion_settings'] = $clientProfile->ai_discussion_settings ?: $defaults['ai_discussion_settings'];
            $defaults['admin_assistant_enabled'] = (bool) ($clientProfile->admin_assistant_enabled ?? $defaults['admin_assistant_enabled']);
            $defaults['class_schedule_menu_enabled'] = (bool) ($clientProfile->class_schedule_menu_enabled ?? $defaults['class_schedule_menu_enabled']);
            $defaults['recurring_bill_menu_enabled'] = (bool) ($clientProfile->recurring_bill_menu_enabled ?? $defaults['recurring_bill_menu_enabled']);
            $defaults['participant_destination_api_enabled'] = (bool) ($clientProfile->participant_destination_api_enabled ?? $defaults['participant_destination_api_enabled']);
        } else {
            $defaults['favicon'] = $defaults['favicon'] ?: $defaults['logo'];
        }

        if (
            Schema::hasTable('roles')
            && Schema::hasTable('permissions')
            && Schema::hasTable('permission_role')
        ) {
            $defaults['tes_koran_enabled'] = Role::adminCanViewFeature('tes_koran');
            $defaults['ai_question_generator_enabled'] = Role::adminCanViewFeature('ai_question_generator');
        }

        if (Schema::hasTable('client_plan_subscriptions') && Schema::hasTable('plans')) {
            $planFeatures = PlanQuotaService::getDefaultPlanFeatures();
            $defaults['affiliate_menu_enabled'] = $planFeatures['affiliate_enabled'] ?? false;
        }

        $defaults['footer_links'] = collect($defaults['footer_links'])
            ->map(function (array $link) use ($defaults) {
                if (($link['url'] ?? '') === '/user/bantuan' && ($link['label'] ?? '') === 'FAQ') {
                    $link['label'] = $defaults['faq_label'];
                }

                return $link;
            })
            ->values()
            ->all();

        $logoUrl = $this->makeBrandAssetUrl($defaults['logo'], $defaultAsset);
        $faviconUrl = $this->makeBrandAssetUrl($defaults['favicon'] ?? $defaults['logo'], $defaultAsset);

        $branding = array_merge($defaults, [
            'logo_url' => $logoUrl,
            'favicon_url' => $faviconUrl,
        ]);

        config([
            'client.branding' => $branding,
            'client.branding.payment_mode' => $branding['payment_mode'] ?? 'gateway',
            'app.name' => $branding['name'],
        ]);

        $this->applyDynamicPaymentConfiguration($branding);
        $this->applyDynamicMailConfiguration($branding);

        view()->share('clientProfile', $clientProfile);
        view()->share('clientBranding', $branding);
    }

    private function applyDynamicMailConfiguration(array $branding): void
    {
        $smtpHost = $branding['smtp_host'] ?: 'smtp.gmail.com';
        $smtpPort = (int) ($branding['smtp_port'] ?: 587);
        $smtpEmail = $branding['smtp_email'] ?? null;
        $smtpPassword = $branding['smtp_app_password'] ?? null;
        $smtpEncryption = $branding['smtp_encryption'] ?: 'tls';

        if (in_array($smtpHost, ['127.0.0.1', 'localhost'], true) && $smtpPort === 2525) {
            $smtpHost = 'smtp.gmail.com';
            $smtpPort = 587;
            $smtpEncryption = 'tls';
        }

        if (!$smtpHost || !$smtpPort || !$smtpEmail || !$smtpPassword) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $smtpHost,
            'mail.mailers.smtp.port' => (int) $smtpPort,
            'mail.mailers.smtp.username' => $smtpEmail,
            'mail.mailers.smtp.password' => $smtpPassword,
            'mail.mailers.smtp.scheme' => null,
            'mail.mailers.smtp.encryption' => $smtpEncryption ?: null,
            'mail.from.address' => $smtpEmail,
            'mail.from.name' => $branding['name'] ?? config('app.name'),
        ]);
    }

    private function applyDynamicPaymentConfiguration(array $branding): void
    {
        $gateway = $branding['payment_gateway'] ?: config('payment_gateways.default', 'xendit');
        $mode = Str::lower(trim((string) ($branding['payment_gateway_mode'] ?: (env('MIDTRANS_IS_PRODUCTION') ? 'production' : 'sandbox'))));
        $mode = $mode === 'production' ? 'production' : 'sandbox';

        $midtransSnapUrl = $mode === 'production'
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $midtransStatusUrl = $mode === 'production'
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';

        $xenditBaseUrl = 'https://api.xendit.co';
        $ipaymuBaseUrl = $mode === 'production'
            ? config('payment_gateways.gateways.ipaymu.production_url', 'https://my.ipaymu.com')
            : config('payment_gateways.gateways.ipaymu.sandbox_url', 'https://sandbox.ipaymu.com');

        if ($mode === 'production' && Str::contains($ipaymuBaseUrl, 'sandbox.ipaymu.com')) {
            $ipaymuBaseUrl = 'https://my.ipaymu.com';
        }

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
            'services.interactive_qris.api_key' => $branding['interactive_qris_api_key'] ?: env('INTERACTIVE_QRIS_API_KEY'),
            'services.interactive_qris.mid' => $branding['interactive_qris_mid'] ?: env('INTERACTIVE_QRIS_MID'),
            'services.interactive_qris.use_tip' => (bool) ($branding['interactive_qris_use_tip'] ?? env('INTERACTIVE_QRIS_USE_TIP', false)),
            'services.interactive_qris.base_url' => config('payment_gateways.gateways.interactive_qris.base_url', 'https://qris.interactive.co.id/restapi/qris'),
            'services.ipaymu.api_key' => $branding['ipaymu_api_key'] ?: env('IPAYMU_API_KEY'),
            'services.ipaymu.va' => $branding['ipaymu_va'] ?: env('IPAYMU_VA'),
            'services.ipaymu.base_url' => $ipaymuBaseUrl,
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
