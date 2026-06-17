<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\GeneralPage;
use Illuminate\Http\Request;

class GeneralSettingController extends Controller
{
    public function edit()
    {
        $pages = $this->ensurePages();

        return view('super-admin.general-settings.edit', compact('pages'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'public_visibility' => ['nullable', 'array'],
            'public_visibility.*' => ['nullable', 'boolean'],
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
}
