<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\GeneralPage;
use App\Models\Package;
use App\Models\PtnSupportingSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class GeneralPageController extends Controller
{
    public function landing()
    {
        $page = GeneralPage::findActiveByKey('landing');

        if (! $page) {
            return redirect()->route('login');
        }

        $content = self::mergeLandingContentWithDefaults($page->content ?: []);
        $selectedPackageIds = collect(data_get($content, 'program.package_ids', []))
            ->map(fn ($packageId) => (int) $packageId)
            ->filter()
            ->unique()
            ->take(3)
            ->values();
        $landingPackagesById = Package::query()
            ->whereIn('package_id', $selectedPackageIds)
            ->where('status', 'active')
            ->where('is_displayed', true)
            ->get()
            ->keyBy('package_id');
        $landingPackages = $selectedPackageIds
            ->map(fn (int $packageId) => $landingPackagesById->get($packageId))
            ->filter()
            ->values();

        return view($this->resolveTemplateView('landing', $page, 'general.landing'), [
            'title' => $content['title'] ?? 'Landing Page',
            'page' => $page,
            'content' => $content,
            'settings' => $page?->settings ?? [],
            'seo' => $page?->seo ?? [],
            'landingPackages' => $landingPackages,
        ]);
    }

    public function statistics()
    {
        return $this->statisticsView('snbp');
    }

    public function statisticsSnbt()
    {
        return $this->statisticsView('snbt');
    }

    private function statisticsView(string $selectionPath)
    {
        $page = GeneralPage::findActiveByKey('statistik-ptn');

        abort_unless($page, 404);

        $content = $page->content ?? [];
        $isSnbt = $selectionPath === 'snbt';

        return view($this->resolveTemplateView('statistik-ptn', $page, 'general.statistics'), [
            'title' => $content['title'] ?? ($isSnbt ? 'Statistik PTN SNBT' : 'Statistik PTN SNBP'),
            'page' => $page,
            'content' => $content,
            'settings' => $page?->settings ?? [],
            'seo' => $page?->seo ?? [],
            'selectionPath' => $selectionPath,
            'selectionLabel' => strtoupper($selectionPath),
            'ptnDataUrl' => $isSnbt ? route('statistics.snbt.proxy.ptn') : route('statistics.proxy.ptn'),
            'prodiDataUrl' => $isSnbt ? route('statistics.snbt.proxy.prodi') : route('statistics.proxy.prodi'),
            'quotaField' => $isSnbt ? 'daya_tampung_snbt' : 'daya_tampung_snbp',
        ]);
    }

    public function proxyPtnList()
    {
        return $this->proxyPtnSelectionList('snbp');
    }

    public function proxyPtnListSnbt()
    {
        return $this->proxyPtnSelectionList('snbt');
    }

    public function proxyProdiList(Request $request)
    {
        return $this->proxyProdiSelectionList($request, 'snbp');
    }

    public function proxyProdiListSnbt(Request $request)
    {
        return $this->proxyProdiSelectionList($request, 'snbt');
    }

    private function proxyPtnSelectionList(string $selectionPath)
    {
        abort_unless(GeneralPage::findActiveByKey('statistik-ptn'), 404);

        $endpoint = $selectionPath === 'snbt'
            ? 'https://snpmb.id/proxy-ptn-sb.php'
            : 'https://snpmb.id/proxy-ptn-sn.php';

        $data = Cache::remember("snpmb_{$selectionPath}_ptn_list", 3600 * 6, function () use ($endpoint, $selectionPath) {
            try {
                $response = Http::timeout(10)->get($endpoint);
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                logger()->error("Error fetching PTN list {$selectionPath} from SNPMB: ".$e->getMessage());
            }

            return null;
        });

        if ($data === null) {
            return response()->json(['error' => 'Gagal mengambil data PTN dari server pusat.'], 502);
        }

        return response()->json($data);
    }

    private function proxyProdiSelectionList(Request $request, string $selectionPath)
    {
        abort_unless(GeneralPage::findActiveByKey('statistik-ptn'), 404);

        $ptnId = $request->query('ptn');
        if (! $ptnId) {
            return response()->json(['error' => 'Parameter ptn wajib diisi.'], 400);
        }

        // Validate parameter to prevent directory traversal or arbitrary requests
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $ptnId)) {
            return response()->json(['error' => 'Parameter ptn tidak valid.'], 400);
        }

        $endpoint = $selectionPath === 'snbt'
            ? 'https://snpmb.id/proxy-prodi-sb.php'
            : 'https://snpmb.id/proxy-prodi-sn.php';

        $cacheKey = "snpmb_{$selectionPath}_prodi_list_".$ptnId;
        $data = Cache::remember($cacheKey, 3600 * 6, function () use ($ptnId, $endpoint, $selectionPath) {
            try {
                $response = Http::timeout(10)->get($endpoint, [
                    'ptn' => $ptnId,
                ]);
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                logger()->error("Error fetching Prodi list {$selectionPath} for PTN {$ptnId} from SNPMB: ".$e->getMessage());
            }

            return null;
        });

        if ($data === null) {
            return response()->json(['error' => 'Gagal mengambil data Program Studi dari server pusat.'], 502);
        }

        return response()->json($this->appendSupportingSubjectsToProdiList($data));
    }

    private function appendSupportingSubjectsToProdiList(array $prodiList): array
    {
        if (! Schema::hasTable('ptn_supporting_subjects')) {
            return $prodiList;
        }

        $kodeProdiList = collect($prodiList)
            ->pluck('kode_prodi')
            ->map(fn ($kodeProdi) => (string) $kodeProdi)
            ->filter()
            ->unique()
            ->values();

        if ($kodeProdiList->isEmpty()) {
            return $prodiList;
        }

        $subjectsByKode = PtnSupportingSubject::query()
            ->whereIn('kode_prodi', $kodeProdiList)
            ->get(['kode_prodi', 'mapel_pendukung'])
            ->keyBy('kode_prodi');

        return collect($prodiList)
            ->map(function (array $prodi) use ($subjectsByKode): array {
                $subject = $subjectsByKode->get((string) ($prodi['kode_prodi'] ?? ''));

                $prodi['mapel_pendukung'] = $subject?->mapel_pendukung ?? [];

                return $prodi;
            })
            ->all();
    }

    public function articles()
    {
        abort_unless(GeneralPage::findActiveByKey('artikel'), 404);

        $articles = Article::query()
            ->with('author:id,name')
            ->published()
            ->latest('published_at')
            ->paginate(9);

        $featuredArticle = Article::query()
            ->with('author:id,name')
            ->published()
            ->latest('published_at')
            ->first();

        return view('general.articles.index', compact('articles', 'featuredArticle'));
    }

    public function showArticle(string $slug)
    {
        abort_unless(GeneralPage::findActiveByKey('artikel'), 404);

        $article = Article::query()
            ->with('author:id,name')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedArticles = Article::query()
            ->published()
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('general.articles.show', compact('article', 'relatedArticles'));
    }

    private function resolveTemplateView(string $pageKey, ?GeneralPage $page, string $defaultView): string
    {
        $templateKey = $page?->template_key ?: 'default';

        if ($templateKey === 'default') {
            return $defaultView;
        }

        $templateView = 'general.templates.'.$pageKey.'.'.$templateKey;

        return view()->exists($templateView) ? $templateView : $defaultView;
    }

    public static function defaultLandingContent(): array
    {
        return [
            'meta' => [
                'title' => 'Persiapan Ujian UTBK SNBT & SNBP Terbaik',
            ],
            'hero' => [
                'badge' => 'Bimbel Persiapan UTBK 2026 #1',
                'title_html' => 'Siap Tembus <br class="hidden sm:block"><span class="text-gradient">PTN Impian</span> Kamu?',
                'description' => 'Belajar UTBK/SNBT lebih terarah dengan materi, tryout IRT nasional, Kak AI, dan bimbingan mentor alumni.',
                'primary_cta' => [
                    'label' => 'Mulai Belajar Sekarang',
                    'href' => route('login'),
                ],
                'secondary_cta' => [
                    'label' => 'Hubungi Admin',
                    'href' => 'https://wa.me/628561078411?text=Halo%20Admin%20saya%20Ingin%20Tanya%20Program%20Bimbel',
                ],
                'logo_stack' => [
                    ['src' => 'img/logo_kampus.png', 'alt' => 'UI Logo'],
                    ['src' => 'img/logo_kampus.png', 'alt' => 'ITB Logo'],
                    ['src' => 'img/logo_kampus.png', 'alt' => 'UGM Logo'],
                    ['src' => 'img/logo_kampus.png', 'alt' => 'ITS Logo'],
                ],
                'social_proof_html' => 'Bergabung bersama <span class="text-slate-900 font-extrabold">10.000+ Pejuang UTBK & SNBP</span> tahun ini!',
                'image' => 'img/hero_study.png',
                'image_alt' => 'Siswa Belajar UTBK Online',
            ],
            'program' => [
                'eyebrow' => 'Investasi Masa Depan',
                'title' => 'Program Bimbingan Belajar Pilihan',
                'description' => 'Pilih paket belajar persiapan ujian yang sesuai dengan kriteria target jurusan dan kampus favoritmu.',
                'package_ids' => [],
            ],
            'community' => [
                'badge' => 'Support System Pejuang PTN',
                'title' => 'Komunitas Pejuang PTN '.config('client.branding.name', 'Copoit Academy'),
                'description' => 'Jangan berjuang sendirian! Bergabunglah di grup WhatsApp diskusi kami untuk berbagi soal, info pendaftaran PTN, konsultasi, serta webinar gratis bersama alumni terkemuka.',
                'cta' => [
                    'label' => 'Gabung Grup Sekarang',
                    'href' => 'https://chat.whatsapp.com/DO0KNXJVyoyAWK31EOoo3H',
                ],
            ],
            'testimonials' => [
                'eyebrow' => 'Kisah Sukses Pejuang',
                'title' => 'Apa Kata Alumni Kami?',
                'description' => 'Mereka telah membuktikan keakuratan data dan bimbingan kami, kini berhasil lolos ke prodi impian.',
                'items' => [
                    [
                        'quote' => 'Tryout IRT di sini bener-bener mirip dengan ujian UTBK aslinya. Ranking nasionalnya bikin aku termotivasi untuk terus mengejar ketertinggalan materi.',
                        'image' => 'img/student_rian.png',
                        'name' => 'Rian H.',
                        'result' => 'Lolos Teknik Sipil ITB',
                    ],
                    [
                        'quote' => 'Fitur Kak AI ngebantu aku banget saat ngerjain soal fisika malam-malam. Penjelasan langkah demi langkahnya mudah dipahami dan cepat responnya!',
                        'image' => 'img/student_nanda.png',
                        'name' => 'Nanda P.',
                        'result' => 'Lolos Farmasi UI',
                    ],
                    [
                        'quote' => 'Terima kasih program bimbingan rapot SNBP-nya. Penjelasan mentor tentang strategi memilih prodi di UI dan UGM bikin aku mantap melangkah.',
                        'image' => 'img/student_farah.png',
                        'name' => 'Farah D.',
                        'result' => 'Lolos Psikologi UGM',
                    ],
                    [
                        'quote' => 'Sebagai siswa dari luar Jawa, akses materi UTBK premium di sini terjangkau dan sangat berkualitas dibandingkan bimbel tatap muka biasa.',
                        'image' => 'img/student_alvin.png',
                        'name' => 'Alvin K.',
                        'result' => 'Lolos Matematika ITS',
                    ],
                ],
            ],
            'achievements' => [
                'eyebrow' => 'Pencapaian Terbaik Kami',
                'title' => 'Bukti Nyata Kualitas Pendampingan BimbelHub',
                'items' => [
                    [
                        'value' => '92,4%',
                        'label' => 'Tingkat Kelolosan Ujian',
                        'description' => '9.240 dari total 10.000 siswa bimbingan kami berhasil lolos ke program studi & PTN pilihan ke-1 dan ke-2.',
                    ],
                    [
                        'value' => '10.000+',
                        'label' => 'Pejuang PTN Aktif',
                        'description' => 'Siswa terdaftar aktif berasal dari sekolah-sekolah unggulan mitra kami di seluruh wilayah Indonesia.',
                    ],
                    [
                        'value' => '50.000+',
                        'label' => 'Bank Soal & Pembahasan',
                        'description' => 'Koleksi bank soal terlengkap dari subtest TPS, Literasi Bahasa, dan Penalaran Matematika terupdate.',
                    ],
                ],
            ],
            'partners' => [
                'eyebrow' => 'Lembaga & Sekolah Mitra Kerja Sama',
                'description' => 'Kami bekerjasama secara resmi dengan sekolah mitra dalam menyelenggarakan tryout nasional & sosialisasi PTN',
                'items' => [
                    [
                        'logo' => 'img/logo_kampus.png',
                        'alt' => 'Logo SMAN 8 Jakarta',
                        'name' => 'SMAN 8 Jakarta',
                        'location' => 'DKI Jakarta',
                    ],
                    [
                        'logo' => 'img/logo_kampus.png',
                        'alt' => 'Logo SMAN 3 Bandung',
                        'name' => 'SMAN 3 Bandung',
                        'location' => 'Jawa Barat',
                    ],
                    [
                        'logo' => 'img/logo_kampus.png',
                        'alt' => 'Logo SMAN 1 Yogyakarta',
                        'name' => 'SMAN 1 Yogya',
                        'location' => 'DI Yogyakarta',
                    ],
                    [
                        'logo' => 'img/logo_kampus.png',
                        'alt' => 'Logo SMAN 5 Surabaya',
                        'name' => 'SMAN 5 Surabaya',
                        'location' => 'Jawa Timur',
                    ],
                    [
                        'logo' => 'img/logo_kampus.png',
                        'alt' => 'Logo SMA Labschool',
                        'name' => 'SMA Labschool',
                        'location' => 'DKI Jakarta',
                    ],
                    [
                        'logo' => 'img/logo_kampus.png',
                        'alt' => 'Logo SMA Kristen Yusuf',
                        'name' => 'SMA K. Yusuf',
                        'location' => 'DKI Jakarta',
                    ],
                ],
            ],
            'faq' => [
                'eyebrow' => 'Pertanyaan Umum',
                'title' => 'FAQ (Frequently Asked Questions)',
                'items' => [
                    [
                        'question' => 'Apakah saya bisa menggunakan platform ini secara gratis?',
                        'answer' => 'Ya, tentu saja! Kamu bisa menggunakan akun gratis untuk melihat data statistik program studi PTN se-Indonesia serta menguji coba 1x sistem simulasi tryout awal yang kami miliki.',
                    ],
                    [
                        'question' => 'Bagaimana sistem penilaian di simulasi Tryout UTBK?',
                        'answer' => 'Sistem penilaian tryout kami menggunakan algoritma Item Response Theory (IRT) yang disesuaikan dengan aturan penilaian resmi dari panitia pelaksana seleksi SNPMB BP3 Kemendikbud. Bobot nilai setiap soal dihitung berdasarkan tingkat kesulitan riil soal tersebut.',
                    ],
                    [
                        'question' => 'Apa itu fitur Rasionalisasi Rapor SNBP?',
                        'answer' => 'Rasionalisasi Rapor SNBP adalah fitur analisis kelayakan nilai rapor semester 1 sampai 5. Nilai rapor kamu akan dikalkulasikan dengan bobot mata pelajaran pendukung prodi yang dituju, dipetakan secara statistik, lalu dibandingkan dengan jutaan histori pendaftar lain di PTN pilihan Anda.',
                    ],
                    [
                        'question' => 'Apakah pembayaran paket berlaku langganan bulanan?',
                        'answer' => 'Tidak. Pembayaran paket bimbingan belajar (Silver maupun Gold) bersifat sekali bayar (*One-Time Payment*) di awal dan langsung aktif untuk masa kepesertaan penuh selama satu tahun penuh hingga seleksi ujian mandiri selesai.',
                    ],
                    [
                        'question' => 'Bagaimana asisten cerdas Kak AI membantu saya?',
                        'answer' => 'Kak AI terintegrasi dengan model AI canggih. Kamu cukup mengetik pertanyaan atau mengunggah gambar/foto soal latihan yang sulit, dan Kak AI akan memberikan panduan penjelasan langkah-demi-langkah, rumus pelengkap, serta tips cepat mengerjakannya.',
                    ],
                ],
            ],
            'footer' => [
                'tagline' => 'Platform Sukses Tembus PTN Impian',
                'description' => 'Penyedia layanan bimbingan belajar, tryout IRT online nasional, pendampingan konsultasi jurusan, serta rasionalisasi rapor seleksi SNBP/SNBT terpercaya di Indonesia.',
                'navigation_title' => 'Navigasi',
                'nav_landing_label' => 'Home Landing',
                'nav_statistics_snbp_label' => 'Statistik PTN SNBP',
                'nav_statistics_snbt_label' => 'Statistik PTN SNBT',
                'nav_articles_label' => 'Insight & Artikel',
                'nav_login_label' => 'Daftar / Login Akun',
                'contact_title' => 'Hubungi Kami',
                'instagram_label' => '@naufalacademy',
                'instagram_href' => 'https://instagram.com/naufalacademy',
                'whatsapp_label' => '+62 856-1078-411',
                'whatsapp_href' => 'https://wa.me/628561078411?text=Halo%2520Admin%2520saya%2520Ingin%2520Bertanya',
                'email_label' => 'team.naufalacademy@gmail.com',
                'email_href' => 'mailto:team.naufalacademy@gmail.com',
                'terms_label' => 'Syarat & Ketentuan',
                'terms_href' => '#',
                'privacy_label' => 'Kebijakan Privasi',
                'privacy_href' => '#',
                'copyright_suffix' => 'Hak cipta dilindungi undang-undang.',
            ],
            'sections' => [
                'testimonials' => [
                    'template_note' => 'Field lanjutan untuk testimoni mengikuti layout landing.blade.php.',
                ],
                'partners' => [
                    'template_note' => 'Field lanjutan untuk logo mitra mengikuti layout landing.blade.php.',
                ],
            ],
        ];
    }

    public static function mergeLandingContentWithDefaults(array $content): array
    {
        return self::mergeMissingLandingDefaults(self::defaultLandingContent(), $content);
    }

    private static function mergeMissingLandingDefaults(array $defaults, array $content): array
    {
        foreach ($defaults as $key => $defaultValue) {
            if (! array_key_exists($key, $content)) {
                $content[$key] = $defaultValue;
                continue;
            }

            if (
                is_array($defaultValue)
                && is_array($content[$key])
                && ! array_is_list($defaultValue)
                && ! array_is_list($content[$key])
            ) {
                $content[$key] = self::mergeMissingLandingDefaults($defaultValue, $content[$key]);
            }
        }

        return $content;
    }
}
