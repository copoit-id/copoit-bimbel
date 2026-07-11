<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use App\Models\GeneralPage;
use Illuminate\Http\Request;

class GeneralSettingController extends Controller
{
    public function edit()
    {
        $pages = $this->ensurePages();
        $clientProfile = ClientProfile::query()->first();

        return view('super-admin.general-settings.edit', compact('pages', 'clientProfile'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'public_visibility' => ['nullable', 'array'],
            'public_visibility.*' => ['nullable', 'boolean'],
            'admin_assistant_enabled' => ['nullable', 'boolean'],
            'ai_discussion_feature_enabled' => ['nullable', 'boolean'],
            'ai_discussion_admin_configurable' => ['nullable', 'boolean'],
            'ai_discussion_credential_mode' => ['nullable', 'in:custom'],
            'ai_discussion_model' => ['nullable', 'string', 'max:120'],
            'ai_discussion_openai_api_key' => ['nullable', 'string', 'max:1000'],
            'ai_discussion_openai_base_url' => ['nullable', 'url', 'max:255'],
            'ai_discussion_openai_timeout' => ['nullable', 'integer', 'min:5', 'max:300'],
            'ai_discussion_gemini_api_key' => ['nullable', 'string', 'max:1000'],
            'ai_discussion_gemini_base_url' => ['nullable', 'url', 'max:255'],
            'ai_discussion_gemini_timeout' => ['nullable', 'integer', 'min:5', 'max:300'],
            'ai_discussion_max_output_tokens' => ['nullable', 'integer', 'min:200', 'max:2000'],
            'ai_discussion_instruction' => ['nullable', 'string', 'max:2000'],
            'class_schedule_menu_enabled' => ['nullable', 'boolean'],
            'recurring_bill_menu_enabled' => ['nullable', 'boolean'],
        ]);

        foreach ($this->pageLabels() as $pageKey => $label) {
            GeneralPage::query()->updateOrCreate(
                ['page_key' => $pageKey],
                [
                    'template_key' => 'default',
                    'is_active' => (bool) data_get($validated, "public_visibility.{$pageKey}", false),
                ]
            );
        }

        $profile = ClientProfile::query()->first();
        if ($profile) {
            $profile->update([
                'admin_assistant_enabled' => $request->boolean('admin_assistant_enabled'),
                'ai_discussion_feature_enabled' => $request->boolean('ai_discussion_feature_enabled'),
                'ai_discussion_admin_configurable' => $request->boolean('ai_discussion_admin_configurable'),
                'ai_discussion_settings' => $this->aiDiscussionSettings($request, $profile),
                'class_schedule_menu_enabled' => $request->boolean('class_schedule_menu_enabled'),
                'recurring_bill_menu_enabled' => $request->boolean('recurring_bill_menu_enabled'),
            ]);
        }

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
            'model' => trim((string) $request->input('ai_discussion_model', $existing['model'] ?? 'gemini-2.5-flash')) ?: 'gemini-2.5-flash',
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
}
