<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralPage;
use Illuminate\Http\Request;

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
        $visibilityPages = $this->ensureVisibilityPages();

        $defaultContent = \App\Http\Controllers\GeneralPageController::defaultLandingContent();

        return view('admin.pages.general.pages.landing', [
            'page' => $page,
            'content' => $page->content ?? $defaultContent,
            'seo' => $page->seo ?? [],
            'visibilityPages' => $visibilityPages,
        ]);
    }

    public function updateLanding(Request $request)
    {
        $validated = $request->validate([
            'template_key' => ['required', 'string', 'max:255'],
            'content' => ['required', 'array'],
            'seo' => ['nullable', 'array'],
            'seo.*' => ['nullable', 'string'],
            'landing_images.hero_image' => ['nullable', 'image', 'max:10240'],
            'landing_images.logo_stack.*.src' => ['nullable', 'image', 'max:10240'],
            'landing_images.testimonials.*.image' => ['nullable', 'image', 'max:10240'],
            'landing_images.seo_image' => ['nullable', 'image', 'max:10240'],
            'public_visibility' => ['nullable', 'array'],
            'public_visibility.*' => ['nullable', 'boolean'],
        ]);

        $seo = $this->cleanArray($validated['seo'] ?? []);
        $content = $validated['content'];
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
                'is_active' => $request->boolean('public_visibility.landing'),
            ]
        );
        $this->updatePublicVisibility($request);

        return redirect()
            ->route('admin.general-pages.landing.edit')
            ->with('success', 'Landing page berhasil diperbarui.');
    }

    private function ensureVisibilityPages()
    {
        foreach (['statistik-ptn', 'artikel'] as $pageKey) {
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
            ->whereIn('page_key', ['landing', 'statistik-ptn', 'artikel'])
            ->get()
            ->keyBy('page_key');
    }

    private function updatePublicVisibility(Request $request): void
    {
        foreach (['statistik-ptn', 'artikel'] as $pageKey) {
            GeneralPage::query()->updateOrCreate(
                ['page_key' => $pageKey],
                [
                    'template_key' => 'default',
                    'is_active' => $request->boolean("public_visibility.{$pageKey}"),
                ]
            );
        }
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

        foreach (['hero', 'program', 'community', 'testimonials', 'achievements', 'faq', 'footer'] as $section) {
            if (!isset($content[$section]) || !is_array($content[$section])) {
                $content[$section] = [];
            }
        }

        $content['hero']['logo_stack'] = array_values(array_filter(
            data_get($content, 'hero.logo_stack', []),
            fn ($item) => is_array($item) && (trim((string) ($item['src'] ?? '')) !== '' || trim((string) ($item['alt'] ?? '')) !== '')
        ));

        $content['program']['cards'] = array_values(array_map(function (array $card) {
            $card['features'] = array_values(array_filter(
                data_get($card, 'features', []),
                fn ($feature) => is_array($feature) && (
                    trim((string) ($feature['label'] ?? '')) !== ''
                    || trim((string) ($feature['label_html'] ?? '')) !== ''
                )
            ));

            $card['cta'] = $this->cleanArray(data_get($card, 'cta', []));

            return $card;
        }, array_filter(
            data_get($content, 'program.cards', []),
            fn ($item) => is_array($item) && trim((string) ($item['title'] ?? '')) !== ''
        )));

        $content['testimonials']['items'] = array_values(array_filter(
            data_get($content, 'testimonials.items', []),
            fn ($item) => is_array($item) && (trim((string) ($item['name'] ?? '')) !== '' || trim((string) ($item['quote'] ?? '')) !== '')
        ));

        $content['achievements']['items'] = array_values(array_filter(
            data_get($content, 'achievements.items', []),
            fn ($item) => is_array($item) && trim((string) ($item['value'] ?? '')) !== ''
        ));

        $content['faq']['items'] = array_values(array_filter(
            data_get($content, 'faq.items', []),
            fn ($item) => is_array($item) && (trim((string) ($item['question'] ?? '')) !== '' || trim((string) ($item['answer'] ?? '')) !== '')
        ));

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
