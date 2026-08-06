<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralPage;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class GeneralPageController extends Controller
{
    public function editLanding()
    {
        $page = GeneralPage::query()->firstOrCreate(
            ['page_key' => 'landing'],
            [
                'template_key' => 'default',
                'content' => null,
                'settings' => null,
                'seo' => null,
                'is_active' => false,
            ]
        );
        $content = \App\Http\Controllers\GeneralPageController::mergeLandingContentWithDefaults($page->content ?? []);

        return view('admin.pages.general.pages.landing', [
            'page' => $page,
            'content' => $content,
            'seo' => $page->seo ?? [],
            'packages' => Package::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function updateLanding(Request $request)
    {
        $validated = $request->validate([
            'template_key' => ['required', 'string', 'max:255'],
            'content' => ['required', 'array'],
            'content.program.package_ids' => ['nullable', 'array', 'max:3'],
            'content.program.package_ids.*' => ['integer', 'distinct', 'exists:packages,package_id'],
            'seo' => ['nullable', 'array'],
            'seo.*' => ['nullable', 'string'],
            'landing_images.hero_image' => ['nullable', 'image', 'max:10240'],
            'landing_images.logo_stack.*.src' => ['nullable', 'image', 'max:10240'],
            'landing_images.testimonials.*.image' => ['nullable', 'image', 'max:10240'],
            'landing_images.partners.*.logo' => ['nullable', 'image', 'max:10240'],
            'landing_images.seo_image' => ['nullable', 'image', 'max:10240'],
        ]);

        $seo = $this->cleanArray($request->input('seo', []));
        $content = $request->input('content', []);
        $this->applyUploadedImages($request, $content, $seo);
        $content = $this->normalizeLandingContent($content);
        $existingPage = GeneralPage::query()->where('page_key', 'landing')->first();

        GeneralPage::query()->updateOrCreate(
            ['page_key' => 'landing'],
            [
                'template_key' => $validated['template_key'],
                'content' => $content,
                'settings' => $existingPage?->settings ?? [],
                'seo' => $seo,
                'is_active' => $existingPage?->is_active ?? false,
            ]
        );

        Artisan::call('view:clear');

        return redirect()
            ->route('admin.general-pages.landing.edit')
            ->with('success', 'Landing page berhasil diperbarui pada ' . now()->format('d M Y H:i:s') . '.');
    }

    private function applyUploadedImages(Request $request, array &$content, array &$seo): void
    {
        if ($request->hasFile('landing_images.hero_image')) {
            data_set($content, 'hero.image', $this->storeLandingImage($request->file('landing_images.hero_image')));
        }

        foreach (data_get($request->file('landing_images', []), 'logo_stack', []) as $index => $item) {
            $file = data_get($item, 'src');

            if ($file) {
                data_set($content, "hero.logo_stack.{$index}.src", $this->storeLandingImage($file));
            }
        }

        foreach (data_get($request->file('landing_images', []), 'testimonials', []) as $index => $item) {
            $file = data_get($item, 'image');

            if ($file) {
                data_set($content, "testimonials.items.{$index}.image", $this->storeLandingImage($file));
            }
        }

        foreach (data_get($request->file('landing_images', []), 'partners', []) as $index => $item) {
            $file = data_get($item, 'logo');

            if ($file) {
                data_set($content, "partners.items.{$index}.logo", $this->storeLandingImage($file));
            }
        }

        if ($request->hasFile('landing_images.seo_image')) {
            $seo['image'] = $this->storeLandingImage($request->file('landing_images.seo_image'));
        }
    }

    private function storeLandingImage($file): string
    {
        return $file->store('general/landing', 'public');
    }

    private function normalizeLandingContent(array $content): array
    {
        $content['meta']['title'] = trim((string) data_get($content, 'meta.title', ''));

        foreach (['hero', 'program', 'community', 'testimonials', 'achievements', 'marquee', 'features', 'dashboard', 'roadmap', 'ai_learning', 'partners', 'faq', 'cta', 'footer'] as $section) {
            if (! isset($content[$section]) || ! is_array($content[$section])) {
                $content[$section] = [];
            }
        }

        $content['program']['package_ids'] = collect(data_get($content, 'program.package_ids', []))
            ->map(fn ($packageId) => (int) $packageId)
            ->filter()
            ->unique()
            ->take(3)
            ->values()
            ->all();
        unset($content['program']['cards']);

        $content['hero']['logo_stack'] = array_values(array_filter(
            data_get($content, 'hero.logo_stack', []),
            fn ($item) => is_array($item) && trim((string) ($item['src'] ?? '')) !== ''
        ));

        $content['hero']['score_card']['items'] = array_values(array_filter(
            data_get($content, 'hero.score_card.items', []),
            fn ($item) => is_array($item) && trim((string) ($item['label'] ?? '')) !== ''
        ));
        $content['hero']['score_card']['items'] = array_map(function (array $item): array {
            $item['score'] = min(100, max(0, (int) ($item['score'] ?? 0)));

            return $item;
        }, $content['hero']['score_card']['items']);

        $content['testimonials']['items'] = array_values(array_filter(
            data_get($content, 'testimonials.items', []),
            fn ($item) => is_array($item) && (trim((string) ($item['name'] ?? '')) !== '' || trim((string) ($item['quote'] ?? '')) !== '')
        ));

        $content['achievements']['items'] = array_values(array_filter(
            data_get($content, 'achievements.items', []),
            fn ($item) => is_array($item) && trim((string) ($item['value'] ?? '')) !== ''
        ));

        $content['partners']['items'] = array_values(array_filter(
            data_get($content, 'partners.items', []),
            fn ($item) => is_array($item) && (trim((string) ($item['name'] ?? '')) !== '' || trim((string) ($item['logo'] ?? '')) !== '')
        ));

        $content['faq']['items'] = array_values(array_filter(
            data_get($content, 'faq.items', []),
            fn ($item) => is_array($item) && (trim((string) ($item['question'] ?? '')) !== '' || trim((string) ($item['answer'] ?? '')) !== '')
        ));

        foreach (['marquee.items', 'features.items', 'roadmap.items', 'ai_learning.items', 'dashboard.stats'] as $key) {
            data_set($content, $key, array_values(array_filter(
                data_get($content, $key, []),
                fn ($item) => is_array($item) && collect($item)->contains(fn ($value) => trim((string) $value) !== '')
            )));
        }

        foreach (['dashboard.benefits', 'ai_learning.chips', 'ai_learning.workspace.output_items'] as $key) {
            data_set($content, $key, array_values(array_filter(
                data_get($content, $key, []),
                fn ($item) => trim((string) $item) !== ''
            )));
        }

        $content['dashboard']['target_percentage'] = min(100, max(0, (int) data_get($content, 'dashboard.target_percentage', 0)));

        return $content;
    }


    private function cleanArray(array $items): array
    {
        return array_filter(
            array_map(fn ($item) => is_string($item) ? trim($item) : $item, $items),
            fn ($item) => $item !== null && $item !== ''
        );
    }
}
