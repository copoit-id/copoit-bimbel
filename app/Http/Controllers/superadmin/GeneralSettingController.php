<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\AiModelPricing;
use App\Models\ClientProfile;
use App\Models\GeneralPage;
use App\Services\AiGatewayCostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class GeneralSettingController extends Controller
{
    public function __construct(
        private readonly AiGatewayCostService $aiGatewayCostService,
    ) {
    }

    public function edit()
    {
        $pages = $this->ensurePages();
        $clientProfile = ClientProfile::query()->first();
        $openAiModels = $this->availableOpenAiDiscussionModels($clientProfile);
        $aiDiscussionModels = $this->aiDiscussionModels($openAiModels);
        $aiModelPricings = AiModelPricing::query()
            ->where('provider', 'openai')
            ->orderBy('model')
            ->get()
            ->keyBy('model');

        return view('super-admin.general-settings.edit', compact(
            'pages', 'clientProfile', 'aiDiscussionModels', 'openAiModels', 'aiModelPricings'
        ));
    }

    public function update(Request $request)
    {
        $profile = ClientProfile::query()->first();
        $openAiModels = $this->availableOpenAiDiscussionModels($profile);
        $aiDiscussionModels = $this->aiDiscussionModels($openAiModels);
        $submittedPricedModels = collect($request->input('ai_model_pricings', []))
            ->filter(fn ($pricing): bool => is_array($pricing)
                && $this->hasPricingRates($pricing)
                && $this->isSupportedOpenAiDiscussionModel((string) ($pricing['model'] ?? '')))
            ->pluck('model')
            ->map(fn ($model): string => strtolower(trim((string) $model)))
            ->all();
        $selectableModels = array_unique([...array_keys($aiDiscussionModels), ...$submittedPricedModels]);

        $validated = $request->validate([
            'public_visibility' => ['nullable', 'array'],
            'public_visibility.*' => ['nullable', 'boolean'],
            'admin_assistant_enabled' => ['nullable', 'boolean'],
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
            'ai_discussion_instruction' => ['nullable', 'string', 'max:2000'],
            'ai_model_pricings' => ['nullable', 'array'],
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
            'class_schedule_menu_enabled' => ['nullable', 'boolean'],
            'recurring_bill_menu_enabled' => ['nullable', 'boolean'],
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
                    'ai_discussion_feature_enabled' => $request->boolean('ai_discussion_feature_enabled'),
                    'ai_discussion_admin_configurable' => $request->boolean('ai_discussion_admin_configurable'),
                    'ai_discussion_settings' => $this->aiDiscussionSettings($request, $profile),
                    'ai_gateway_payment_settings' => $this->aiGatewayPaymentSettings($request, $profile),
                    'class_schedule_menu_enabled' => $request->boolean('class_schedule_menu_enabled'),
                    'recurring_bill_menu_enabled' => $request->boolean('recurring_bill_menu_enabled'),
                ]);
            }

            $this->syncAiModelPricings($validated['ai_model_pricings'] ?? []);
        });
        $this->aiGatewayCostService->forgetCachedPricing();

        return redirect()
            ->route('super-admin.general-settings.edit')
            ->with('success', 'Pengaturan General berhasil diperbarui.');
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
            'model' => (string) $request->input('ai_discussion_model', $existing['model'] ?? 'gemini-2.5-flash'),
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
            'instruction' => trim((string) $request->input('ai_discussion_instruction', $existing['instruction'] ?? '')),
            'monthly_token_limit' => max(0, (int) ($existing['monthly_token_limit'] ?? 0)),
            'models' => $existing['models'] ?? [],
        ];
    }

    private function aiDiscussionModels(array $openAiModels): array
    {
        $geminiModels = collect(config('services.gemini.question_models', []))
            ->map(fn (string $label, string $id) => [
                'id' => $id,
                'label' => $label,
                'provider' => 'Gemini',
            ]);

        return collect($openAiModels)
            ->filter(fn (array $model): bool => $this->aiGatewayCostService->hasPricing('openai', $model['id']))
            ->merge($geminiModels)
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
        $cacheKey = 'openai-discussion-models:' . hash('sha256', $baseUrl . '|' . $apiKey);
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
                'provider' => 'OpenAI',
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
                    'provider' => 'OpenAI',
                ])
                ->all();
        }

        $pricedModels = AiModelPricing::query()
            ->where('provider', 'openai')
            ->pluck('model')
            ->map(fn (string $model) => [
                'id' => $model,
                'label' => 'OpenAI - ' . $model,
                'provider' => 'OpenAI',
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
            if (! $this->isSupportedOpenAiDiscussionModel($model)) {
                continue;
            }

            AiModelPricing::query()->updateOrCreate(
                ['provider' => 'openai', 'model' => $model],
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
}
