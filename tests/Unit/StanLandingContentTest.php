<?php

namespace Tests\Unit;

use App\Http\Controllers\GeneralPageController;
use Tests\TestCase;

class StanLandingContentTest extends TestCase
{
    public function test_default_landing_content_is_focused_on_pkn_stan(): void
    {
        $content = GeneralPageController::defaultLandingContent();

        $this->assertStringContainsString('PKN STAN', data_get($content, 'meta.title'));
        $this->assertStringContainsString('TIU', data_get($content, 'hero.description'));
        $this->assertSame('img/stan-landing-hero.webp', data_get($content, 'hero.image'));
    }

    public function test_legacy_demo_copy_is_upgraded_without_losing_selected_packages_or_contacts(): void
    {
        $content = GeneralPageController::mergeLandingContentWithDefaults([
            'meta' => [
                'title' => 'Persiapan Ujian UTBK SNBT & SNBP Terbaik',
            ],
            'hero' => [
                'title_html' => 'Siap Tembus <br class="hidden sm:block"><span class="text-gradient">PTN Impian</span> Kamu?',
            ],
            'program' => [
                'title' => 'Program Bimbingan Belajar Pilihan',
                'package_ids' => [8, 3],
            ],
            'footer' => [
                'tagline' => 'Platform Sukses Tembus PTN Impian',
                'whatsapp_label' => '+62 812-3456-7890',
                'whatsapp_href' => 'https://wa.me/6281234567890',
            ],
        ]);

        $this->assertStringContainsString('PKN STAN', data_get($content, 'hero.title_html'));
        $this->assertSame([8, 3], data_get($content, 'program.package_ids'));
        $this->assertSame('+62 812-3456-7890', data_get($content, 'footer.whatsapp_label'));
        $this->assertSame('https://wa.me/6281234567890', data_get($content, 'footer.whatsapp_href'));
    }

    public function test_custom_landing_copy_is_not_overwritten(): void
    {
        $content = GeneralPageController::mergeLandingContentWithDefaults([
            'hero' => [
                'title_html' => 'Headline khusus tenant',
                'description' => 'Deskripsi khusus tenant.',
            ],
        ]);

        $this->assertSame('Headline khusus tenant', data_get($content, 'hero.title_html'));
        $this->assertSame('Deskripsi khusus tenant.', data_get($content, 'hero.description'));
    }
}
