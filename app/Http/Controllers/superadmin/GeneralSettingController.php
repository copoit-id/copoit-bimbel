<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\AiModelPricing;
use App\Models\ClientProfile;
use App\Models\GeneralPage;
use App\Services\AiGatewayCostService;
use App\Services\AiGatewayTelegramNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class GeneralSettingController extends Controller
{
    public function __construct(
        private readonly AiGatewayCostService $aiGatewayCostService,
        private readonly AiGatewayTelegramNotificationService $telegramNotificationService,
    ) {}

    public function edit(Request $request)
    {
        $activeSettingsTab = $this->activeSettingsTab($request->query('tab'));
        $pages = $this->ensurePages();
        $clientProfile = ClientProfile::query()->first();
        $openAiModels = $this->availableOpenAiDiscussionModels($clientProfile);
        $geminiModels = $this->availableGeminiDiscussionModels($clientProfile);
        $availableModels = [...$openAiModels, ...$geminiModels];
        $aiDiscussionModels = $this->aiDiscussionModels($availableModels);
        $aiModelPricings = AiModelPricing::query()
            ->orderBy('model')
            ->get()
            ->keyBy(fn (AiModelPricing $pricing): string => $pricing->provider . ':' . $pricing->model);

        return view('super-admin.general-settings.edit', compact(
            'pages', 'clientProfile', 'aiDiscussionModels', 'availableModels', 'aiModelPricings', 'activeSettingsTab'
        ));
    }

    public function update(Request $request)
    {
        $profile = ClientProfile::query()->first();
        $openAiModels = $this->availableOpenAiDiscussionModels($profile);
        $geminiModels = $this->availableGeminiDiscussionModels($profile);
        $aiDiscussionModels = $this->aiDiscussionModels([...$openAiModels, ...$geminiModels]);
        $submittedPricedModels = collect($request->input('ai_model_pricings', []))
            ->filter(fn ($pricing): bool => is_array($pricing)
                && $this->hasPricingRates($pricing)
                && $this->isSupportedDiscussionModel(
                    (string) ($pricing['provider'] ?? ''),
                    (string) ($pricing['model'] ?? ''),
                ))
            ->map(fn (array $pricing): string => strtolower(trim((string) $pricing['model'])))
            ->all();
        $pricedModels = collect($aiDiscussionModels)
            ->filter(fn (array $model): bool => (bool) ($model['has_pricing'] ?? false))
            ->keys()
            ->all();
        $selectableModels = array_unique([...$pricedModels, ...$submittedPricedModels]);

        $validated = $request->validate([
            'settings_tab' => ['nullable', Rule::in(['general', 'ai', 'pricing', 'payment', 'notification'])],
            'public_visibility' => ['nullable', 'array'],
            'public_visibility.*' => ['nullable', 'boolean'],
            'admin_assistant_enabled' => ['nullable', 'boolean'],
            'live_session_enabled' => ['nullable', 'boolean'],
            'ai_discussion_feature_enabled' => ['nullable', 'boolean'],
            'ai_discussion_admin_configurable' => ['nullable', 'boolean'],
            'ai_discussion_credential_mode' => ['nullable', 'in:custom'],
            'ai_discussion_model' => ['required', Rule::in($selectableModels)],
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
            'ai_model_pricings' => ['nullable', 'array'],
            'ai_model_pricings.*.provider' => ['required', Rule::in(['openai', 'gemini'])],
            'ai_model_pricings.*.model' => ['required', 'string', 'max:120'],
            'ai_model_pricings.*.input_per_million_usd' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'ai_model_pricings.*.output_per_million_usd' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'ai_model_pricings.*.usd_to_idr' => ['nullable', 'numeric', 'min:1', 'max:1000000'],
            'ai_gateway_payment_gateway' => ['required', 'in:xendit,midtrans,ipaymu,interactive_qris'],
            'ai_gateway_payment_gateway_mode' => ['required', 'in:sandbox,production'],
            'ai_gateway_xendit_secret_key' => ['nullable', 'string', 'max:255'],
            'ai_gateway_xendit_webhook_token' => ['nullable', 'string', 'max:255'],
            'ai_gateway_midtrans_server_key' => ['nullable', 'string', 'max:255'],
            'ai_gateway_midtrans_client_key' => ['nullable', 'string', 'max:255'],
            'ai_gateway_interactive_qris_api_key' => ['nullable', 'string', 'max:500'],
            'ai_gateway_interactive_qris_mid' => ['nullable', 'string', 'max:100'],
            'ai_gateway_interactive_qris_use_tip' => ['nullable', 'boolean'],
            'ai_gateway_ipaymu_api_key' => ['nullable', 'string', 'max:1000'],
            'ai_gateway_ipaymu_va' => ['nullable', 'string', 'max:100'],
            'ai_gateway_telegram_enabled' => ['nullable', 'boolean'],
            'ai_gateway_telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'ai_gateway_telegram_chat_id' => ['nullable', 'string', 'max:120'],
            'ai_gateway_telegram_message_thread_id' => ['nullable', 'integer', 'min:1'],
            'ai_gateway_telegram_notify_free' => ['nullable', 'boolean'],
            'ai_gateway_telegram_notify_paid' => ['nullable', 'boolean'],
            'class_schedule_menu_enabled' => ['nullable', 'boolean'],
            'recurring_bill_menu_enabled' => ['nullable', 'boolean'],
            'tutor_chat_enabled' => ['nullable', 'boolean'],
            'booking_schedule_enabled' => ['nullable', 'boolean'],
            'learning_progress_enabled' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $request, $profile): void {
            foreach ($this->pageLabels() as $pageKey => $label) {
                GeneralPage::query()->updateOrCreate(
                    ['page_key' => $pageKey],
                    [
                        'template_key' => 'default',
                        'is_active' => (bool) data_get($validated, "public_visibility.{$pageKey}", false),
                    ]
                );
            }

            if ($profile) {
                $profile->update([
                    'admin_assistant_enabled' => $request->boolean('admin_assistant_enabled'),
                    'live_session_enabled' => $request->boolean('live_session_enabled'),
                    'ai_discussion_feature_enabled' => $request->boolean('ai_discussion_feature_enabled'),
                    'ai_discussion_admin_configurable' => $request->boolean('ai_discussion_admin_configurable'),
                    'ai_discussion_settings' => $this->aiDiscussionSettings($request, $profile),
                    'ai_gateway_payment_settings' => $this->aiGatewayPaymentSettings($request, $profile),
                    'ai_gateway_telegram_settings' => $this->aiGatewayTelegramSettings($request, $profile),
                    'class_schedule_menu_enabled' => $request->boolean('class_schedule_menu_enabled'),
                    'recurring_bill_menu_enabled' => $request->boolean('recurring_bill_menu_enabled'),
                    'tutor_chat_enabled' => $request->boolean('tutor_chat_enabled'),
                    'booking_schedule_enabled' => $request->boolean('booking_schedule_enabled'),
                    'learning_progress_enabled' => $request->boolean('learning_progress_enabled'),
                ]);
            }

            $this->syncAiModelPricings($validated['ai_model_pricings'] ?? []);
        });
        $this->aiGatewayCostService->forgetCachedPricing();

        return redirect()
            ->route('super-admin.general-settings.edit', [
                'tab' => $this->activeSettingsTab($validated['settings_tab'] ?? null),
            ])
            ->with('success', 'Pengaturan General berhasil diperbarui.');
    }

    private function activeSettingsTab(?string $tab): string
    {
        return in_array($tab, ['general', 'ai', 'pricing', 'payment', 'notification'], true)
            ? $tab
            : 'general';
    }

    public function testTelegram(): RedirectResponse
    {
        try {
            $this->telegramNotificationService->sendTest();

            return redirect()
                ->route('super-admin.general-settings.edit', ['tab' => 'notification'])
                ->with('success', 'Tes notifikasi berhasil dikirim ke Telegram.');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('super-admin.general-settings.edit', ['tab' => 'notification'])
                ->with('error', $exception->getMessage());
        }
    }

    private function ensurePages()
    {
        foreach ($this->pageLabels() as $pageKey => $label) {
            GeneralPage::query()->firstOrCreate(
                ['page_key' => $pageKey],
                [
                    'template_key' => 'default',
                    'content' => null,
                    'settings' => null,
                    'seo' => null,
                    'is_active' => false,
                ]
            );
        }

        return GeneralPage::query()
            ->whereIn('page_key', array_keys($this->pageLabels()))
            ->get()
            ->keyBy('page_key');
    }

    private function pageLabels(): array
    {
        return [
            'landing' => 'Landing Page',
            'artikel' => 'Artikel',
            'statistik-ptn' => 'Statistik PTN',
        ];
    }

    private function aiDiscussionSettings(Request $request, ClientProfile $profile): array
    {
        $existing = is_array($profile->ai_discussion_settings) ? $profile->ai_discussion_settings : [];
        $providers = is_array($existing['providers'] ?? null) ? $existing['providers'] : [];

        return [
            'enabled' => $request->boolean('ai_discussion_feature_enabled'),
            'credential_mode' => 'custom',
            'model' => (string) $request->input('ai_discussion_model', $existing['model'] ?? 'gemini-3.1-flash-lite'),
            'providers' => [
                'openai' => [
                    'api_key' => trim((string) $request->input('ai_discussion_openai_api_key')) ?: ($providers['openai']['api_key'] ?? null),
                    'base_url' => trim((string) $request->input('ai_discussion_openai_base_url', $providers['openai']['base_url'] ?? 'https://api.openai.com/v1')),
                    'timeout' => max(5, min(300, (int) $request->input('ai_discussion_openai_timeout', $providers['openai']['timeout'] ?? 90))),
                ],
                'gemini' => [
                    'api_key' => trim((string) $request->input('ai_discussion_gemini_api_key')) ?: ($providers['gemini']['api_key'] ?? null),
                    'base_url' => trim((string) $request->input('ai_discussion_gemini_base_url', $providers['gemini']['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta')),
                    'timeout' => max(5, min(300, (int) $request->input('ai_discussion_gemini_timeout', $providers['gemini']['timeout'] ?? 90))),
                ],
            ],
            'max_output_tokens' => max(200, min(2000, (int) $request->input('ai_discussion_max_output_tokens', $existing['max_output_tokens'] ?? 700))),
            'feature_token_limits' => $this->featureTokenLimits($request, $existing),
            'instruction' => trim((string) $request->input('ai_discussion_instruction', $existing['instruction'] ?? '')),
            'monthly_token_limit' => max(0, (int) ($existing['monthly_token_limit'] ?? 0)),
            'models' => $existing['models'] ?? [],
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

    private function aiDiscussionModels(array $availableModels): array
    {
        return collect($availableModels)
            ->map(function (array $model): array {
                $model['has_pricing'] = $this->aiGatewayCostService->hasPricing(
                    (string) $model['provider'],
                    (string) $model['id'],
                );

                return $model;
            })
            ->keyBy('id')
            ->all();
    }

    /**
     * Retrieve the GPT models available to the saved OpenAI API key.
     *
     * The Models API also returns audio, image, realtime, and legacy models.
     * This screen only supports text GPT models through the Responses API.
     *
     * @return array<int, array{id: string, label: string, provider: string}>
     */
    private function openAiDiscussionModels(?ClientProfile $profile): array
    {
        $settings = is_array($profile?->ai_discussion_settings) ? $profile->ai_discussion_settings : [];
        $provider = is_array($settings['providers']['openai'] ?? null) ? $settings['providers']['openai'] : [];
        $apiKey = trim((string) ($provider['api_key'] ?? config('services.openai.api_key')));

        if ($apiKey === '') {
            return [];
        }

        $baseUrl = rtrim(trim((string) ($provider['base_url'] ?? config('services.openai.base_url'))), '/');
        $cacheKey = 'openai-discussion-models:v2:' . hash('sha256', $baseUrl . '|' . $apiKey);
        $cachedModels = Cache::get($cacheKey);

        if (is_array($cachedModels)) {
            return $cachedModels;
        }

        try {
            $response = Http::acceptJson()
                ->withToken($apiKey)
                ->timeout(8)
                ->get($baseUrl . '/models');
        } catch (\Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $models = collect($response->json('data', []))
            ->pluck('id')
            ->filter(fn ($id): bool => is_string($id) && $this->isSupportedOpenAiDiscussionModel($id))
            ->sort(SORT_NATURAL)
            ->values()
            ->map(fn (string $id) => [
                'id' => $id,
                'label' => 'OpenAI - ' . $id,
                'provider' => 'openai',
            ])
            ->all();

        if ($models !== []) {
            Cache::put($cacheKey, $models, now()->addMinutes(15));
        }

        return $models;
    }

    private function isSupportedOpenAiDiscussionModel(string $model): bool
    {
        $model = strtolower($model);

        return str_starts_with($model, 'gpt-')
            && ! str_contains($model, 'audio')
            && ! str_contains($model, 'realtime')
            && ! str_contains($model, 'transcribe')
            && ! str_contains($model, 'tts')
            && ! str_contains($model, 'image')
            && ! str_contains($model, 'search')
            && ! str_contains($model, 'codex');
    }

    private function isSupportedGeminiDiscussionModel(string $model): bool
    {
        $model = strtolower($model);

        return str_starts_with($model, 'gemini-')
            && ! str_contains($model, 'image')
            && ! str_contains($model, 'audio')
            && ! str_contains($model, 'live')
            && ! str_contains($model, 'tts');
    }

    private function isSupportedDiscussionModel(string $provider, string $model): bool
    {
        return match (strtolower(trim($provider))) {
            'openai' => $this->isSupportedOpenAiDiscussionModel($model),
            'gemini' => $this->isSupportedGeminiDiscussionModel($model),
            default => false,
        };
    }

    /**
     * @return array<int, array{id: string, label: string, provider: string}>
     */
    private function availableOpenAiDiscussionModels(?ClientProfile $profile): array
    {
        $models = $this->openAiDiscussionModels($profile);

        if ($models === []) {
            $models = collect(config('services.openai.question_models', []))
                ->map(fn (string $label, string $id) => [
                    'id' => $id,
                    'label' => $label,
                    'provider' => 'openai',
                ])
                ->all();
        }

        $pricedModels = AiModelPricing::query()
            ->where('provider', 'openai')
            ->pluck('model')
            ->map(fn (string $model) => [
                'id' => $model,
                'label' => 'OpenAI - ' . $model,
                'provider' => 'openai',
            ]);

        return collect($models)
            ->merge($pricedModels)
            ->keyBy('id')
            ->sortBy('id', SORT_NATURAL)
            ->values()
            ->all();
    }

    /**
     * Retrieve the text Gemini models available to the saved Gemini API key.
     *
     * @return array<int, array{id: string, label: string, provider: string}>
     */
    private function availableGeminiDiscussionModels(?ClientProfile $profile): array
    {
        $settings = is_array($profile?->ai_discussion_settings) ? $profile->ai_discussion_settings : [];
        $provider = is_array($settings['providers']['gemini'] ?? null) ? $settings['providers']['gemini'] : [];
        $apiKey = trim((string) ($provider['api_key'] ?? config('services.gemini.api_key')));
        $models = [];

        if ($apiKey !== '') {
            $baseUrl = rtrim(trim((string) ($provider['base_url'] ?? config('services.gemini.base_url'))), '/');
            $cacheKey = 'gemini-discussion-models:v2:' . hash('sha256', $baseUrl . '|' . $apiKey);
            $cachedModels = Cache::get($cacheKey);

            if (is_array($cachedModels)) {
                $models = $cachedModels;
            } else {
                try {
                    $response = Http::acceptJson()
                        ->timeout(8)
                        ->withQueryParameters(['key' => $apiKey])
                        ->get($baseUrl . '/models');
                } catch (\Throwable) {
                    $response = null;
                }

                if ($response?->successful()) {
                    $models = collect($response->json('models', []))
                        ->filter(fn ($model): bool => is_array($model)
                            && in_array('generateContent', $model['supportedGenerationMethods'] ?? [], true))
                        ->map(fn (array $model): string => str_replace('models/', '', (string) ($model['name'] ?? '')))
                        ->filter(fn (string $model): bool => $this->isSupportedGeminiDiscussionModel($model))
                        ->sort(SORT_NATURAL)
                        ->values()
                        ->map(fn (string $id): array => [
                            'id' => $id,
                            'label' => 'Gemini - ' . $id,
                            'provider' => 'gemini',
                        ])
                        ->all();

                    if ($models !== []) {
                        Cache::put($cacheKey, $models, now()->addMinutes(15));
                    }
                }
            }
        }

        if ($models === []) {
            $models = collect(config('services.gemini.question_models', []))
                ->map(fn (string $label, string $id): array => [
                    'id' => $id,
                    'label' => $label,
                    'provider' => 'gemini',
                ])
                ->all();
        }

        $pricedModels = AiModelPricing::query()
            ->where('provider', 'gemini')
            ->pluck('model')
            ->map(fn (string $model): array => [
                'id' => $model,
                'label' => 'Gemini - ' . $model,
                'provider' => 'gemini',
            ]);

        return collect($models)
            ->merge($pricedModels)
            ->keyBy('id')
            ->sortBy('id', SORT_NATURAL)
            ->values()
            ->all();
    }

    private function syncAiModelPricings(array $pricings): void
    {
        foreach ($pricings as $pricing) {
            if (! is_array($pricing)
                || ! $this->hasPricingRates($pricing)) {
                continue;
            }

            $model = strtolower(trim((string) ($pricing['model'] ?? '')));
            $provider = strtolower(trim((string) ($pricing['provider'] ?? '')));
            if (! $this->isSupportedDiscussionModel($provider, $model)) {
                continue;
            }

            AiModelPricing::query()->updateOrCreate(
                ['provider' => $provider, 'model' => $model],
                [
                    'input_per_million_usd' => (float) $pricing['input_per_million_usd'],
                    'output_per_million_usd' => (float) $pricing['output_per_million_usd'],
                    'usd_to_idr' => (float) ($pricing['usd_to_idr'] ?? 16000),
                    'is_active' => true,
                ],
            );
        }
    }

    private function hasPricingRates(array $pricing): bool
    {
        return array_key_exists('input_per_million_usd', $pricing)
            && array_key_exists('output_per_million_usd', $pricing)
            && $pricing['input_per_million_usd'] !== null
            && $pricing['input_per_million_usd'] !== ''
            && $pricing['output_per_million_usd'] !== null
            && $pricing['output_per_million_usd'] !== '';
    }

    private function aiGatewayPaymentSettings(Request $request, ClientProfile $profile): array
    {
        $existing = is_array($profile->ai_gateway_payment_settings) ? $profile->ai_gateway_payment_settings : [];

        return [
            'gateway' => $request->input('ai_gateway_payment_gateway', $existing['gateway'] ?? 'xendit'),
            'mode' => $request->input('ai_gateway_payment_gateway_mode', $existing['mode'] ?? 'sandbox'),
            'xendit_secret_key' => trim((string) $request->input('ai_gateway_xendit_secret_key')) ?: ($existing['xendit_secret_key'] ?? null),
            'xendit_webhook_token' => trim((string) $request->input('ai_gateway_xendit_webhook_token')) ?: ($existing['xendit_webhook_token'] ?? null),
            'midtrans_server_key' => trim((string) $request->input('ai_gateway_midtrans_server_key')) ?: ($existing['midtrans_server_key'] ?? null),
            'midtrans_client_key' => trim((string) $request->input('ai_gateway_midtrans_client_key')) ?: ($existing['midtrans_client_key'] ?? null),
            'interactive_qris_api_key' => trim((string) $request->input('ai_gateway_interactive_qris_api_key')) ?: ($existing['interactive_qris_api_key'] ?? null),
            'interactive_qris_mid' => trim((string) $request->input('ai_gateway_interactive_qris_mid')) ?: ($existing['interactive_qris_mid'] ?? null),
            'interactive_qris_use_tip' => $request->boolean('ai_gateway_interactive_qris_use_tip'),
            'ipaymu_api_key' => trim((string) $request->input('ai_gateway_ipaymu_api_key')) ?: ($existing['ipaymu_api_key'] ?? null),
            'ipaymu_va' => trim((string) $request->input('ai_gateway_ipaymu_va')) ?: ($existing['ipaymu_va'] ?? null),
        ];
    }

    private function aiGatewayTelegramSettings(Request $request, ClientProfile $profile): array
    {
        $existing = is_array($profile->ai_gateway_telegram_settings) ? $profile->ai_gateway_telegram_settings : [];

        return [
            'enabled' => $request->boolean('ai_gateway_telegram_enabled'),
            'bot_token' => trim((string) $request->input('ai_gateway_telegram_bot_token')) ?: ($existing['bot_token'] ?? null),
            'chat_id' => trim((string) $request->input('ai_gateway_telegram_chat_id')) ?: ($existing['chat_id'] ?? null),
            'message_thread_id' => $request->filled('ai_gateway_telegram_message_thread_id')
                ? (int) $request->input('ai_gateway_telegram_message_thread_id')
                : null,
            'notify_free' => $request->boolean('ai_gateway_telegram_notify_free'),
            'notify_paid' => $request->boolean('ai_gateway_telegram_notify_paid'),
        ];
    }
}
