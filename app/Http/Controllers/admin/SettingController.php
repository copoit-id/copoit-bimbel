<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use App\Services\ActivityLogger;
use App\Support\MailSafety;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function index()
    {
        $profile = ClientProfile::query()->first();
        $paymentGateways = config('payment_gateways.gateways', []);

        $branding = config('client.branding', [
            'name' => config('app.name'),
            'faq_label' => 'FAQ',
            'live_session_label' => 'Kelas Belajar',
            'logo_url' => asset('img/logo/logo-copoit.png'),
            'favicon_url' => asset('img/logo/logo-copoit.png'),
            'primary_color' => '#1C3259',
            'secondary_color' => '#F3F3F3',
            'header_primary_color' => false,
            'sidebar_primary_color' => false,
            'utbk_enabled' => true,
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
            'footer_links' => [],
            'ai_question_generator_enabled' => false,
            'ai_question_generator_settings' => [],
            'ai_discussion_feature_enabled' => false,
            'ai_discussion_settings' => [],
            'participant_destination_api_enabled' => false,
        ]);

        return view('admin.pages.settings.index', [
            'profile' => $profile,
            'branding' => $branding,
            'paymentGateways' => $paymentGateways,
        ]);
    }

    public function update(Request $request)
    {
        if ($request->user()?->isDemoAdmin()) {
            return redirect()
                ->route('admin.settings.index')
                ->with('error', 'Akun demo hanya dapat melihat pengaturan dan tidak dapat mengubahnya.');
        }

        $profile = ClientProfile::query()->first() ?? new ClientProfile;
        $paymentGatewayKeys = array_keys(config('payment_gateways.gateways', [
            'xendit' => [],
            'midtrans' => [],
            'ipaymu' => [],
            'interactive_qris' => [],
        ]));

        $rules = [
            'nama_bimbel' => ['required', 'string', 'max:255'],
            'faq_label' => ['required', 'string', 'max:80'],
            'live_session_label' => ['required', 'string', 'max:80'],
            'warna_primary' => ['required', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'warna_secondary' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            // SVG is executable XML in browsers. Do not place untrusted SVG
            // content in the public web root.
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'favicon' => ['nullable', 'mimes:ico,png,jpg,jpeg,webp', 'max:4096'],
            'payment_mode' => ['required', 'in:gateway,manual'],
            'payment_bank_name' => ['nullable', 'string', 'max:255'],
            'payment_account_number' => ['nullable', 'string', 'max:100'],
            'payment_account_holder' => ['nullable', 'string', 'max:255'],
            'payment_bank_note' => ['nullable', 'string'],
            'payment_unique_code_enabled' => ['nullable', 'boolean'],
            'payment_gateway' => ['nullable', 'in:'.implode(',', $paymentGatewayKeys)],
            'payment_gateway_mode' => ['nullable', 'in:sandbox,production'],
            'xendit_secret_key' => ['nullable', 'string', 'max:255'],
            'xendit_webhook_token' => ['nullable', 'string', 'max:255'],
            'midtrans_server_key' => ['nullable', 'string', 'max:255'],
            'midtrans_client_key' => ['nullable', 'string', 'max:255'],
            'interactive_qris_api_key' => ['nullable', 'string', 'max:500'],
            'interactive_qris_mid' => ['nullable', 'string', 'max:100'],
            'interactive_qris_use_tip' => ['nullable', 'boolean'],
            'ipaymu_api_key' => ['nullable', 'string', 'max:1000'],
            'ipaymu_va' => ['nullable', 'string', 'max:100'],
            'smtp_email' => ['nullable', 'email', 'max:255'],
            'smtp_app_password' => ['nullable', 'string', 'max:255'],
            'smtp_notification_email' => ['nullable', 'email', 'max:255'],
            'contact_whatsapp_number' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s.]+$/'],
            'contact_whatsapp_button_text' => ['nullable', 'string', 'max:80'],
            'concurrent_login_limit' => ['required', 'integer', 'min:1', 'max:20'],
            'footer_enabled' => ['nullable', 'boolean'],
            'footer_description' => ['nullable', 'string', 'max:1000'],
            'footer_copyright' => ['nullable', 'string', 'max:255'],
            'footer_links' => ['nullable', 'array', 'max:8'],
            'footer_links.*.label' => ['nullable', 'string', 'max:80'],
            'footer_links.*.url' => ['nullable', 'string', 'max:2048'],
            'footer_address' => ['nullable', 'string', 'max:1000'],
            'footer_phone' => ['nullable', 'string', 'max:32'],
            'footer_email' => ['nullable', 'email', 'max:255'],
            'footer_whatsapp' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s.]+$/'],
            'footer_facebook' => ['nullable', 'string', 'max:255'],
            'footer_instagram' => ['nullable', 'string', 'max:255'],
            'footer_twitter' => ['nullable', 'string', 'max:255'],
            'footer_youtube' => ['nullable', 'string', 'max:255'],
            'participant_destination_api_enabled' => ['nullable', 'boolean'],
            'ai_openai_api_key' => ['nullable', 'string', 'max:1000'],
            'ai_openai_base_url' => ['nullable', 'url', 'max:255'],
            'ai_openai_timeout' => ['nullable', 'integer', 'min:5', 'max:300'],
            'ai_gemini_api_key' => ['nullable', 'string', 'max:1000'],
            'ai_gemini_base_url' => ['nullable', 'url', 'max:255'],
            'ai_gemini_timeout' => ['nullable', 'integer', 'min:5', 'max:300'],
            'ai_question_default_model' => ['nullable', 'string', 'max:120'],
            'ai_question_models_json' => ['nullable', 'string'],
            'ai_discussion_enabled' => ['nullable', 'boolean'],
            'ai_discussion_credential_mode' => ['nullable', 'in:shared,custom'],
            'ai_discussion_model' => ['nullable', 'string', 'max:120'],
            'ai_discussion_openai_api_key' => ['nullable', 'string', 'max:1000'],
            'ai_discussion_openai_base_url' => ['nullable', 'url', 'max:255'],
            'ai_discussion_openai_timeout' => ['nullable', 'integer', 'min:5', 'max:300'],
            'ai_discussion_gemini_api_key' => ['nullable', 'string', 'max:1000'],
            'ai_discussion_gemini_base_url' => ['nullable', 'url', 'max:255'],
            'ai_discussion_gemini_timeout' => ['nullable', 'integer', 'min:5', 'max:300'],
            'ai_discussion_max_output_tokens' => ['nullable', 'integer', 'min:200', 'max:2000'],
            'ai_discussion_feature_token_limits' => ['nullable', 'array'],
            'ai_discussion_feature_token_limits.discussion' => ['nullable', 'integer', 'min:64', 'max:2000'],
            'ai_discussion_feature_token_limits.learning_note' => ['nullable', 'integer', 'min:64', 'max:2000'],
            'ai_discussion_feature_token_limits.learning_flashcard' => ['nullable', 'integer', 'min:64', 'max:2000'],
            'ai_discussion_feature_token_limits.learning_question' => ['nullable', 'integer', 'min:64', 'max:2000'],
            'ai_discussion_instruction' => ['nullable', 'string', 'max:2000'],
            'ai_admin_password' => ['nullable', 'string'],
        ];

        if ($request->input('payment_mode') === 'manual') {
            $rules['payment_bank_name'] = ['required', 'string', 'max:255'];
            $rules['payment_account_number'] = ['required', 'string', 'max:100'];
            $rules['payment_account_holder'] = ['required', 'string', 'max:255'];
        }
        if ($request->input('payment_mode') === 'gateway') {
            $rules['payment_gateway'] = ['required', 'in:'.implode(',', $paymentGatewayKeys)];
            $rules['payment_gateway_mode'] = ['required', 'in:sandbox,production'];
        }

        $validated = $request->validate($rules, [
            'warna_primary.regex' => 'Warna utama harus berupa kode hex valid.',
            'warna_secondary.regex' => 'Warna sekunder harus berupa kode hex valid.',
            'payment_bank_name.required' => 'Nama bank wajib diisi untuk pembayaran manual.',
            'payment_account_number.required' => 'Nomor rekening wajib diisi untuk pembayaran manual.',
            'payment_account_holder.required' => 'Nama pemilik rekening wajib diisi untuk pembayaran manual.',
        ]);

        if (
            ($validated['payment_mode'] ?? null) === 'gateway'
            && ($validated['payment_gateway'] ?? null) === 'interactive_qris'
        ) {
            try {
                $existingQrisApiKey = (string) ($profile->interactive_qris_api_key ?? '');
            } catch (DecryptException $e) {
                $existingQrisApiKey = '';
            }

            if (trim((string) ($validated['interactive_qris_mid'] ?? '')) === '') {
                return back()
                    ->withErrors(['interactive_qris_mid' => 'mID InterActive QRIS wajib diisi.'])
                    ->withInput($request->except(['admin_password', 'smtp_app_password', 'interactive_qris_api_key']))
                    ->with('active_tab', $request->input('settings_tab', 'payment'));
            }

            if ($existingQrisApiKey === '' && trim((string) ($validated['interactive_qris_api_key'] ?? '')) === '') {
                return back()
                    ->withErrors(['interactive_qris_api_key' => 'API key InterActive QRIS wajib diisi.'])
                    ->withInput($request->except(['admin_password', 'smtp_app_password', 'interactive_qris_api_key']))
                    ->with('active_tab', $request->input('settings_tab', 'payment'));
            }
        }
        if (
            ($validated['payment_mode'] ?? null) === 'gateway'
            && ($validated['payment_gateway'] ?? null) === 'ipaymu'
        ) {
            try {
                $existingIpaymuApiKey = (string) ($profile->ipaymu_api_key ?? '');
            } catch (DecryptException $e) {
                $existingIpaymuApiKey = '';
            }

            if (trim((string) ($validated['ipaymu_va'] ?? '')) === '') {
                return back()
                    ->withErrors(['ipaymu_va' => 'VA iPaymu wajib diisi.'])
                    ->withInput($request->except(['admin_password', 'smtp_app_password', 'ipaymu_api_key']))
                    ->with('active_tab', $request->input('settings_tab', 'payment'));
            }

            if ($existingIpaymuApiKey === '' && trim((string) ($validated['ipaymu_api_key'] ?? '')) === '') {
                return back()
                    ->withErrors(['ipaymu_api_key' => 'API key iPaymu wajib diisi.'])
                    ->withInput($request->except(['admin_password', 'smtp_app_password', 'ipaymu_api_key']))
                    ->with('active_tab', $request->input('settings_tab', 'payment'));
            }
        }

        $smtpHost = $profile->smtp_host ?: 'smtp.gmail.com';
        $smtpPort = (int) ($profile->smtp_port ?: 587);
        $smtpEncryption = $profile->smtp_encryption ?: 'tls';

        if (in_array($smtpHost, ['127.0.0.1', 'localhost'], true) && $smtpPort === 2525) {
            $smtpHost = 'smtp.gmail.com';
            $smtpPort = 587;
            $smtpEncryption = 'tls';
        }
        $validated['smtp_email'] = MailSafety::email($validated['smtp_email'] ?? null);
        $validated['smtp_notification_email'] = MailSafety::email($validated['smtp_notification_email'] ?? null);
        $smtpEmail = $validated['smtp_email'] ?? MailSafety::email($profile->smtp_email);

        $newPassword = trim((string) ($validated['smtp_app_password'] ?? ''));
        try {
            $existingSmtpPassword = $profile->smtp_app_password ?? null;
        } catch (DecryptException $e) {
            $existingSmtpPassword = null;
        }
        $smtpPassword = $newPassword !== '' ? $newPassword : $existingSmtpPassword;

        // Jika user mengosongkan email & password SMTP, hapus semua konfigurasi SMTP.
        $shouldClearSmtp = empty($validated['smtp_email'] ?? null) && $newPassword === '';
        if ($shouldClearSmtp) {
            $validated['smtp_email'] = null;
            $validated['smtp_app_password'] = null;
            $validated['smtp_notification_email'] = null;
            $validated['smtp_host'] = null;
            $validated['smtp_port'] = null;
            $validated['smtp_encryption'] = null;
        } elseif ($newPassword === '') {
            unset($validated['smtp_app_password']);
        }

        $smtpSettingsChanged = $newPassword !== ''
            || MailSafety::email($profile->smtp_email) !== ($validated['smtp_email'] ?? null)
            || MailSafety::email($profile->smtp_notification_email) !== ($validated['smtp_notification_email'] ?? null)
            || ($shouldClearSmtp && (
                ! empty($profile->smtp_host)
                || ! empty($profile->smtp_port)
                || ! empty($profile->smtp_encryption)
                || ! empty($existingSmtpPassword)
            ));

        $sensitiveKeys = [
            'xendit_secret_key',
            'xendit_webhook_token',
            'midtrans_server_key',
            'midtrans_client_key',
            'interactive_qris_api_key',
            'ipaymu_api_key',
        ];

        foreach ($sensitiveKeys as $key) {
            $newValue = trim((string) ($validated[$key] ?? ''));
            if ($newValue === '') {
                unset($validated[$key]);

                continue;
            }

            try {
                $existingValue = (string) ($profile->{$key} ?? '');
            } catch (DecryptException $e) {
                $existingValue = '';
            }

            if ($existingValue !== '' && hash_equals($existingValue, $newValue)) {
                unset($validated[$key]);
            } else {
                $validated[$key] = $newValue;
            }
        }

        $sensitiveChanged = false;
        foreach ($sensitiveKeys as $key) {
            if (array_key_exists($key, $validated)) {
                $sensitiveChanged = true;
                break;
            }
        }

        $sensitiveChanged = $sensitiveChanged || $smtpSettingsChanged;

        if ($sensitiveChanged) {
            if (! $request->filled('admin_password') && $request->filled('ai_admin_password')) {
                $request->merge(['admin_password' => $request->input('ai_admin_password')]);
            }

            $request->validate([
                'admin_password' => ['required', 'string'],
            ], [
                'admin_password.required' => 'Password admin wajib diisi untuk mengubah kredensial sensitif.',
            ]);

            $user = $request->user();
            if (! $user || ! Hash::check((string) $request->input('admin_password'), $user->password)) {
                return back()
                    ->withErrors(['admin_password' => 'Password admin tidak valid.'])
                    ->withInput($request->except([
                        'admin_password',
                        'smtp_app_password',
                        'ai_openai_api_key',
                        'ai_gemini_api_key',
                        'ai_discussion_openai_api_key',
                        'ai_discussion_gemini_api_key',
                        'ai_admin_password',
                        ...$sensitiveKeys,
                    ]))
                    ->with('active_tab', $request->input('settings_tab', 'payment'));
            }
        }

        // SMTP wajib lengkap hanya jika memang dikonfigurasi/diaktifkan.
        $smtpConfigured = ! empty($profile->smtp_email) || ! empty($existingSmtpPassword);
        $smtpRequested = $request->filled('smtp_email')
            || $request->filled('smtp_app_password')
            || $request->filled('smtp_notification_email');
        $shouldValidateSmtp = $smtpConfigured || $smtpRequested;

        if (! $shouldClearSmtp && $shouldValidateSmtp && (! $smtpEmail || ! $smtpPassword)) {
            return back()
                ->withErrors([
                    'smtp_email' => 'Konfigurasi SMTP belum lengkap. Isi email SMTP dan sandi aplikasi.',
                ])
                ->withInput($request->except('smtp_app_password'));
        }

        if ($shouldValidateSmtp && ! $shouldClearSmtp) {
            $validated['smtp_host'] = $smtpHost;
            $validated['smtp_port'] = $smtpPort;
            $validated['smtp_encryption'] = $smtpEncryption;
        }

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->storeBrandingImage(
                $request->file('logo'),
                $profile->logo ?? null,
                'brand-logo'
            );
        } else {
            unset($validated['logo']);
        }

        if ($request->hasFile('favicon')) {
            $validated['favicon'] = $this->storeBrandingImage(
                $request->file('favicon'),
                $profile->favicon ?? null,
                'brand-favicon'
            );
        } else {
            unset($validated['favicon']);
        }

        $validated['warna_secondary'] = $validated['warna_secondary'] ?? '#F3F3F3';
        $validated['faq_label'] = trim((string) ($validated['faq_label'] ?? '')) ?: 'FAQ';
        $validated['live_session_label'] = trim((string) ($validated['live_session_label'] ?? '')) ?: 'Kelas Belajar';
        $validated['contact_whatsapp_number'] = $this->normalizeWhatsappNumber($validated['contact_whatsapp_number'] ?? null);
        $validated['contact_whatsapp_button_text'] = trim((string) ($validated['contact_whatsapp_button_text'] ?? '')) ?: 'Chat Admin';
        $validated['concurrent_login_limit'] = max(1, (int) ($validated['concurrent_login_limit'] ?? 1));
        $validated['footer_enabled'] = $request->boolean('footer_enabled');
        $validated['footer_description'] = trim((string) ($validated['footer_description'] ?? '')) ?: null;
        $validated['footer_copyright'] = trim((string) ($validated['footer_copyright'] ?? '')) ?: null;
        $validated['footer_links'] = $this->normalizeFooterLinks($validated['footer_links'] ?? []);
        $validated['footer_address'] = trim((string) ($validated['footer_address'] ?? '')) ?: null;
        $validated['footer_phone'] = trim((string) ($validated['footer_phone'] ?? '')) ?: null;
        $validated['footer_email'] = trim((string) ($validated['footer_email'] ?? '')) ?: null;
        $validated['footer_whatsapp'] = $this->normalizeWhatsappNumber($validated['footer_whatsapp'] ?? null);
        $validated['footer_facebook'] = trim((string) ($validated['footer_facebook'] ?? '')) ?: null;
        $validated['footer_instagram'] = trim((string) ($validated['footer_instagram'] ?? '')) ?: null;
        $validated['footer_twitter'] = trim((string) ($validated['footer_twitter'] ?? '')) ?: null;
        $validated['footer_youtube'] = trim((string) ($validated['footer_youtube'] ?? '')) ?: null;
        $validated['enable_certificate_management'] = false;
        $validated['header_primary_color'] = $request->boolean('header_primary_color');
        $validated['sidebar_primary_color'] = $request->boolean('sidebar_primary_color');
        $validated['participant_destination_api_enabled'] = $request->boolean('participant_destination_api_enabled');
        $validated['enable_utbk_types'] = false;
        unset(
            $validated['ai_question_generator_enabled'],
            $validated['ai_question_generator_settings'],
            $validated['ai_discussion_settings'],
            $validated['ai_openai_api_key'],
            $validated['ai_openai_base_url'],
            $validated['ai_openai_timeout'],
            $validated['ai_gemini_api_key'],
            $validated['ai_gemini_base_url'],
            $validated['ai_gemini_timeout'],
            $validated['ai_question_default_model'],
            $validated['ai_question_models_json'],
            $validated['ai_discussion_enabled'],
            $validated['ai_discussion_credential_mode'],
            $validated['ai_discussion_model'],
            $validated['ai_discussion_openai_api_key'],
            $validated['ai_discussion_openai_base_url'],
            $validated['ai_discussion_openai_timeout'],
            $validated['ai_discussion_gemini_api_key'],
            $validated['ai_discussion_gemini_base_url'],
            $validated['ai_discussion_gemini_timeout'],
            $validated['ai_discussion_max_output_tokens'],
            $validated['ai_discussion_instruction'],
            $validated['ai_admin_password']
        );
        $validated['payment_mode'] = $validated['payment_mode'] ?? 'gateway';
        $validated['payment_unique_code_enabled'] = $request->boolean('payment_unique_code_enabled');
        $validated['payment_gateway'] = $validated['payment_gateway']
            ?? ($profile->payment_gateway ?? 'xendit');
        $validated['payment_gateway_mode'] = $validated['payment_gateway_mode']
            ?? ($profile->payment_gateway_mode ?? 'sandbox');
        $validated['interactive_qris_use_tip'] = $request->boolean('interactive_qris_use_tip');
        if (array_key_exists('smtp_notification_email', $validated) && $validated['smtp_notification_email'] === null) {
            // User sengaja mengosongkan field ini
            $validated['smtp_notification_email'] = null;
        } else {
            $validated['smtp_notification_email'] = $validated['smtp_notification_email']
                ?? ($validated['smtp_email'] ?? $profile->smtp_notification_email);
        }

        $changedFields = [];
        foreach ($validated as $key => $value) {
            if (in_array($key, $sensitiveKeys, true)) {
                $changedFields[] = $key.':updated';

                continue;
            }
            if ($key === 'smtp_app_password') {
                $changedFields[] = 'smtp_app_password:updated';

                continue;
            }
            $original = $profile->exists ? $profile->getOriginal($key) : null;
            if ($this->settingValueForComparison($original) !== $this->settingValueForComparison($value)) {
                $changedFields[] = $key;
            }
        }

        $profile->fill($validated);

        if (empty($profile->logo)) {
            $profile->logo = 'img/logo/logo-copoit.png';
        }

        try {
            $profile->save();
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan pengaturan', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['general' => 'Gagal menyimpan pengaturan: '.$e->getMessage()])
                ->withInput($request->except(['admin_password', 'smtp_app_password']))
                ->with('active_tab', $request->input('settings_tab', 'identity'));
        }

        ActivityLogger::log('settings_updated', 'success', $request->user(), [
            'changes' => $changedFields,
        ], $request);

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Pengaturan berhasil diperbarui.')
            ->with('active_tab', $request->input('settings_tab', 'identity'));
    }

    private function buildAiQuestionGeneratorSettings(Request $request, ClientProfile $profile): array
    {
        $existing = is_array($profile->ai_question_generator_settings ?? null)
            ? $profile->ai_question_generator_settings
            : [];
        $existingProviders = $existing['providers'] ?? [];

        $modelsJson = trim((string) $request->input('ai_question_models_json', ''));
        $models = $modelsJson !== ''
            ? json_decode($modelsJson, true)
            : ($existing['models'] ?? $this->defaultAiQuestionModels());

        if (! is_array($models)) {
            return [
                'settings' => $existing,
                'sensitive_changed' => false,
                'errors' => ['ai_question_models_json' => 'JSON model AI tidak valid.'],
            ];
        }

        $models = $this->normalizeAiQuestionModels($models);
        if (empty($models)) {
            return [
                'settings' => $existing,
                'sensitive_changed' => false,
                'errors' => ['ai_question_models_json' => 'Minimal isi 1 model AI yang aktif.'],
            ];
        }

        $openAiKey = trim((string) $request->input('ai_openai_api_key', ''));
        $geminiKey = trim((string) $request->input('ai_gemini_api_key', ''));
        $existingOpenAiKey = (string) ($existingProviders['openai']['api_key'] ?? '');
        $existingGeminiKey = (string) ($existingProviders['gemini']['api_key'] ?? '');
        $sensitiveChanged = ($openAiKey !== '' && ! hash_equals($existingOpenAiKey, $openAiKey))
            || ($geminiKey !== '' && ! hash_equals($existingGeminiKey, $geminiKey));

        $settings = [
            'default_model' => $request->input('ai_question_default_model')
                ?: ($existing['default_model'] ?? $models[0]['id']),
            'providers' => [
                'openai' => [
                    'api_key' => $openAiKey !== '' ? $openAiKey : ($existingProviders['openai']['api_key'] ?? null),
                    'base_url' => trim((string) $request->input('ai_openai_base_url', $existingProviders['openai']['base_url'] ?? 'https://api.openai.com/v1')),
                    'timeout' => max(5, min(300, (int) $request->input('ai_openai_timeout', $existingProviders['openai']['timeout'] ?? 90))),
                ],
                'gemini' => [
                    'api_key' => $geminiKey !== '' ? $geminiKey : ($existingProviders['gemini']['api_key'] ?? null),
                    'base_url' => trim((string) $request->input('ai_gemini_base_url', $existingProviders['gemini']['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta')),
                    'timeout' => max(5, min(300, (int) $request->input('ai_gemini_timeout', $existingProviders['gemini']['timeout'] ?? 90))),
                ],
            ],
            'models' => $models,
        ];

        $modelIds = collect($models)->pluck('id')->all();
        if (! in_array($settings['default_model'], $modelIds, true)) {
            $settings['default_model'] = $models[0]['id'];
        }

        return [
            'settings' => $settings,
            'sensitive_changed' => $sensitiveChanged,
            'errors' => [],
        ];
    }

    private function normalizeAiQuestionModels(array $models): array
    {
        return collect($models)
            ->map(function ($model) {
                return [
                    'id' => trim((string) ($model['id'] ?? '')),
                    'label' => trim((string) ($model['label'] ?? ($model['id'] ?? ''))),
                    'provider' => in_array(($model['provider'] ?? ''), ['openai', 'gemini'], true)
                        ? $model['provider']
                        : (str_starts_with((string) ($model['id'] ?? ''), 'gemini-') ? 'gemini' : 'openai'),
                    'enabled' => filter_var($model['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ];
            })
            ->filter(fn ($model) => $model['id'] !== '' && $model['label'] !== '')
            ->unique('id')
            ->values()
            ->all();
    }

    private function buildAiDiscussionSettings(Request $request, ClientProfile $profile, array $sharedAiSettings, bool $featureEnabled): array
    {
        $existing = is_array($profile->ai_discussion_settings ?? null)
            ? $profile->ai_discussion_settings
            : [];
        $existingProviders = $existing['providers'] ?? [];
        $credentialMode = $request->input('ai_discussion_credential_mode', $existing['credential_mode'] ?? 'shared');
        $credentialMode = in_array($credentialMode, ['shared', 'custom'], true) ? $credentialMode : 'shared';

        $openAiKey = trim((string) $request->input('ai_discussion_openai_api_key', ''));
        $geminiKey = trim((string) $request->input('ai_discussion_gemini_api_key', ''));
        $existingOpenAiKey = (string) ($existingProviders['openai']['api_key'] ?? '');
        $existingGeminiKey = (string) ($existingProviders['gemini']['api_key'] ?? '');
        $sensitiveChanged = $credentialMode === 'custom' && (
            ($openAiKey !== '' && ! hash_equals($existingOpenAiKey, $openAiKey))
            || ($geminiKey !== '' && ! hash_equals($existingGeminiKey, $geminiKey))
        );

        $sharedModel = (string) ($sharedAiSettings['default_model'] ?? 'gemini-2.5-flash');
        $model = trim((string) $request->input('ai_discussion_model', $existing['model'] ?? $sharedModel));

        if ($model === '') {
            $model = $sharedModel;
        }

        return [
            'settings' => [
                'enabled' => $featureEnabled && $request->boolean('ai_discussion_enabled'),
                'credential_mode' => $credentialMode,
                'model' => $model,
                'providers' => [
                    'openai' => [
                        'api_key' => $credentialMode === 'custom'
                            ? ($openAiKey !== '' ? $openAiKey : ($existingProviders['openai']['api_key'] ?? null))
                            : null,
                        'base_url' => trim((string) $request->input('ai_discussion_openai_base_url', $existingProviders['openai']['base_url'] ?? 'https://api.openai.com/v1')),
                        'timeout' => max(5, min(300, (int) $request->input('ai_discussion_openai_timeout', $existingProviders['openai']['timeout'] ?? 90))),
                    ],
                    'gemini' => [
                        'api_key' => $credentialMode === 'custom'
                            ? ($geminiKey !== '' ? $geminiKey : ($existingProviders['gemini']['api_key'] ?? null))
                            : null,
                        'base_url' => trim((string) $request->input('ai_discussion_gemini_base_url', $existingProviders['gemini']['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta')),
                        'timeout' => max(5, min(300, (int) $request->input('ai_discussion_gemini_timeout', $existingProviders['gemini']['timeout'] ?? 90))),
                    ],
                ],
                'max_output_tokens' => max(200, min(2000, (int) $request->input('ai_discussion_max_output_tokens', $existing['max_output_tokens'] ?? 700))),
                'feature_token_limits' => $this->featureTokenLimits($request, $existing),
                'instruction' => trim((string) $request->input('ai_discussion_instruction', $existing['instruction'] ?? '')),
                'models' => $sharedAiSettings['models'] ?? $this->defaultAiQuestionModels(),
            ],
            'sensitive_changed' => $sensitiveChanged,
            'errors' => [],
        ];
    }

    private function featureTokenLimits(Request $request, array $existing): array
    {
        $defaults = [
            'discussion' => 700,
            'learning_note' => 1200,
            'learning_flashcard' => 500,
            'learning_question' => 1800,
        ];
        $submitted = $request->input('ai_discussion_feature_token_limits', []);
        $saved = is_array($existing['feature_token_limits'] ?? null) ? $existing['feature_token_limits'] : [];

        return collect($defaults)->mapWithKeys(function (int $default, string $feature) use ($submitted, $saved): array {
            $value = is_array($submitted) && array_key_exists($feature, $submitted)
                ? $submitted[$feature]
                : ($saved[$feature] ?? $default);

            return [$feature => max(64, min(2000, (int) $value))];
        })->all();
    }

    private function defaultAiQuestionModels(): array
    {
        return [
            [
                'id' => 'gpt-5.4-mini',
                'label' => 'OpenAI - GPT-5.4 Mini',
                'provider' => 'openai',
                'enabled' => true,
            ],
            [
                'id' => 'gemini-2.5-flash',
                'label' => 'Gemini - 2.5 Flash',
                'provider' => 'gemini',
                'enabled' => true,
            ],
            [
                'id' => 'gemini-2.5-flash-lite',
                'label' => 'Gemini - 2.5 Flash-Lite',
                'provider' => 'gemini',
                'enabled' => true,
            ],
        ];
    }

    private function storeBrandingImage($file, ?string $existingPath = null, string $prefix = 'brand'): string
    {
        $directory = public_path('logo');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');
        $filename = $prefix.'-'.now()->format('YmdHis').'.'.$extension;

        $relativePath = 'logo/'.$filename;

        $this->deleteBrandingImage($existingPath);

        $file->move($directory, $filename);

        return $relativePath;
    }

    private function deleteBrandingImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        $normalized = ltrim($path, '/');

        if (Str::startsWith($normalized, 'storage/')) {
            $relativePath = Str::after($normalized, 'storage/');
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }

            return;
        }

        if (Str::startsWith($normalized, 'logo/')) {
            $fullPath = public_path($normalized);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    private function normalizeWhatsappNumber(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        return $digits;
    }

    private function normalizeFooterLinks(array $links): array
    {
        return collect($links)
            ->map(function ($link) {
                $label = trim((string) ($link['label'] ?? ''));
                $url = trim((string) ($link['url'] ?? ''));

                if ($label === '' || $url === '') {
                    return null;
                }

                if (! Str::startsWith($url, ['http://', 'https://', '/', 'mailto:', 'tel:'])) {
                    $url = '/'.ltrim($url, '/');
                }

                return [
                    'label' => $label,
                    'url' => $url,
                ];
            })
            ->filter()
            ->take(8)
            ->values()
            ->all();
    }

    private function settingValueForComparison(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }
}
