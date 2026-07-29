<?php

namespace Tests\Unit;

use App\Http\Controllers\GeneralPageController;
use Tests\TestCase;

class GeneralPageContentTest extends TestCase
{
    public function test_landing_defaults_keep_existing_partner_and_program_wording(): void
    {
        $content = GeneralPageController::defaultLandingContent();

        $this->assertSame(
            'Lembaga & Sekolah Mitra Kerja Sama',
            data_get($content, 'partners.eyebrow')
        );
        $this->assertSame(
            'Kami bekerjasama secara resmi dengan sekolah mitra dalam menyelenggarakan tryout nasional & sosialisasi PTN',
            data_get($content, 'partners.description')
        );
        $this->assertSame('Sekali Bayar', data_get($content, 'program.paid_suffix'));
        $this->assertSame('Kirim Bukti', data_get($content, 'program.conditional.submit_label'));
    }

    public function test_article_defaults_keep_existing_news_wording(): void
    {
        $content = GeneralPageController::defaultArticleContent();

        $this->assertSame('Bimbel News & Updates', data_get($content, 'index.badge'));
        $this->assertSame('Insight & Panduan Belajar', data_get($content, 'index.title'));
        $this->assertSame('Bimbel Insight', data_get($content, 'show.badge'));
        $this->assertSame(
            'Platform Persiapan Masuk Perguruan Tinggi',
            data_get($content, 'layout.tagline')
        );
    }

    public function test_custom_article_wording_is_preserved_while_missing_defaults_are_added(): void
    {
        $content = GeneralPageController::mergeArticleContentWithDefaults([
            'index' => [
                'badge' => 'Kabar Belajar',
            ],
        ]);

        $this->assertSame('Kabar Belajar', data_get($content, 'index.badge'));
        $this->assertSame('Semua Artikel', data_get($content, 'index.all_title'));
        $this->assertSame('Artikel Terpopuler', data_get($content, 'show.related_title'));
    }
}
