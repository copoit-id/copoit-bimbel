<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\GeneralPage;
use App\Models\Package;
use App\Models\PtnSupportingSubject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class GeneralPageController extends Controller
{
    public function landing(): RedirectResponse|View
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
            ->select([
                'package_id',
                'name',
                'description',
                'features',
                'image',
                'type_price',
                'price',
                'conditional_requirement',
            ])
            ->whereIn('package_id', $selectedPackageIds)
            ->where('status', 'active')
            ->where('is_displayed', true)
            ->get()
            ->keyBy('package_id');
        $landingPackages = $selectedPackageIds
            ->map(fn (int $packageId) => $landingPackagesById->get($packageId))
            ->filter()
            ->values();

        return view($this->resolveTemplateView('landing', $page, 'general.templates.landing.stan'), [
            'title' => data_get($content, 'meta.title', 'Landing Page'),
            'page' => $page,
            'content' => $content,
            'settings' => $page?->settings ?? [],
            'seo' => $page?->seo ?? [],
            'landingPackages' => $landingPackages,
        ]);
    }

    /**
     * Display the public introduction to the AI Learning Tools.
     */
    public function aiLearningTools(): View
    {
        return view('general.ai-learning-tools');
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

        $endpoints = $selectionPath === 'snbt'
            ? [
                'https://snpmb.id/proxy-ptn-sb.php',
                'https://snpmb.id/proxy-ptn-sn.php',
            ]
            : [
                'https://snpmb.id/proxy-ptn-sn.php',
                'https://snpmb.id/proxy-ptn-sb.php',
            ];

        $data = $this->fetchSnpmbData("snpmb_{$selectionPath}_ptn_list", $endpoints, [], $selectionPath, 'PTN');

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

        $cacheKey = "snpmb_{$selectionPath}_prodi_list_".$ptnId;
        $endpoints = $selectionPath === 'snbt'
            ? [
                'https://snpmb.id/proxy-prodi-sb.php',
                'https://snpmb.id/proxy-prodi-sn.php',
            ]
            : [
                'https://snpmb.id/proxy-prodi-sn.php',
                'https://snpmb.id/proxy-prodi-sb.php',
            ];
        $data = $this->fetchSnpmbData(
            $cacheKey,
            $endpoints,
            ['ptn' => $ptnId],
            $selectionPath,
            "Prodi for PTN {$ptnId}"
        );

        if ($data === null) {
            return response()->json(['error' => 'Gagal mengambil data Program Studi dari server pusat.'], 502);
        }

        return response()->json($this->appendSupportingSubjectsToProdiList($data));
    }

    /**
     * Fetch SNPMB data with a same-format endpoint failover and a durable last-successful cache.
     * The SNPMB proxy endpoints occasionally return 502 while the other selection endpoint remains available.
     */
    private function fetchSnpmbData(
        string $cacheKey,
        array $endpoints,
        array $query,
        string $selectionPath,
        string $resource
    ): ?array {
        $lastSuccessfulCacheKey = "{$cacheKey}_last_successful";

        $data = Cache::remember($cacheKey, now()->addHours(6), function () use (
            $endpoints,
            $query,
            $selectionPath,
            $resource,
            $lastSuccessfulCacheKey
        ) {
            foreach ($endpoints as $endpoint) {
                try {
                    $response = Http::acceptJson()
                        ->timeout(15)
                        ->connectTimeout(5)
                        ->retry(2, 500, throw: false)
                        ->get($endpoint, $query);

                    $data = $response->json();
                    if ($response->successful() && is_array($data) && $data !== []) {
                        Cache::forever($lastSuccessfulCacheKey, $data);

                        return $data;
                    }

                    logger()->warning("SNPMB {$resource} endpoint failed", [
                        'selection' => $selectionPath,
                        'endpoint' => $endpoint,
                        'status' => $response->status(),
                    ]);
                } catch (\Throwable $e) {
                    logger()->warning("Error fetching {$resource} {$selectionPath} from SNPMB", [
                        'endpoint' => $endpoint,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }

            return Cache::get($lastSuccessfulCacheKey);
        });

        return is_array($data) && $data !== [] ? $data : null;
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
            ->paginate(\App\Support\Pagination::perPage(9));

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
        $whatsappNumber = preg_replace(
            '/\D+/',
            '',
            (string) config('client.branding.contact_whatsapp_number', '')
        ) ?: '628561078411';
        $whatsappHref = "https://wa.me/{$whatsappNumber}?text=Halo%20Admin%2C%20saya%20ingin%20konsultasi%20program%20persiapan%20PKN%20STAN.";
        $contactEmail = (string) config('client.branding.footer_email', '');

        return [
            'meta' => [
                'title' => 'Bimbel Persiapan PKN STAN & Sekolah Kedinasan',
            ],
            'hero' => [
                'badge' => 'Persiapan PKN STAN 2026',
                'title_html' => 'Langkah lebih terarah untuk <span class="stan-highlight">tembus PKN STAN.</span>',
                'description' => 'Persiapkan TIU, TWK, dan TKP lewat kelas terstruktur, drilling soal, serta tryout CAT yang membantu kamu membaca progres dan memperbaiki strategi.',
                'primary_cta' => [
                    'label' => 'Mulai Persiapan',
                    'href' => route('register'),
                ],
                'secondary_cta' => [
                    'label' => 'Konsultasi Gratis',
                    'href' => $whatsappHref,
                ],
                'logo_stack' => [
                    ['src' => 'img/student_rian.png', 'alt' => 'Peserta program'],
                    ['src' => 'img/student_nanda.png', 'alt' => 'Peserta program'],
                    ['src' => 'img/student_farah.png', 'alt' => 'Peserta program'],
                ],
                'social_proof_html' => 'Belajar bareng komunitas <strong>pejuang PKN STAN</strong> dari seluruh Indonesia.',
                'image' => 'img/stan-landing-hero.webp',
                'image_alt' => 'Siswa mempersiapkan seleksi masuk PKN STAN',
                'live_card' => [
                    'title' => 'Kelas interaktif',
                    'description' => 'Belajar & tanya langsung',
                ],
                'score_card' => [
                    'eyebrow' => 'Progress latihan',
                    'title' => 'Makin konsisten',
                    'items' => [
                        ['label' => 'TIU', 'score' => 84],
                        ['label' => 'TWK', 'score' => 72],
                        ['label' => 'TKP', 'score' => 90],
                    ],
                ],
            ],
            'program' => [
                'eyebrow' => 'Program Belajar',
                'title' => 'Pilih ritme belajar yang paling cocok',
                'description' => 'Mulai dari tryout mandiri hingga pendampingan intensif. Data paket, harga, fasilitas, dan thumbnail selalu mengikuti pengaturan admin.',
                'package_ids' => [],
            ],
            'community' => [
                'badge' => 'Teman Seperjuangan',
                'title' => 'Konsisten lebih mudah saat tidak sendirian',
                'description' => 'Masuk ke komunitas belajar untuk diskusi soal, pengingat jadwal, informasi seleksi, dan sesi berbagi strategi bersama mentor serta peserta lain.',
                'cta' => [
                    'label' => 'Gabung Komunitas',
                    'href' => $whatsappHref,
                ],
            ],
            'testimonials' => [
                'eyebrow' => 'Cerita Peserta',
                'title' => 'Progres terasa ketika belajarnya terukur',
                'description' => 'Pengalaman peserta selama menggunakan kelas, latihan, dan tryout di platform kami.',
                'items' => [
                    [
                        'quote' => 'Setelah tryout, aku langsung tahu bagian TIU mana yang masih lemah. Belajarnya jadi tidak asal banyak, tapi lebih fokus.',
                        'image' => 'img/student_rian.png',
                        'name' => 'Raka A.',
                        'result' => 'Peserta Program Intensif',
                    ],
                    [
                        'quote' => 'Pembahasan TWK-nya runtut dan mudah diingat. Jadwal latihan juga membuat aku lebih konsisten walaupun masih sekolah.',
                        'image' => 'img/student_nanda.png',
                        'name' => 'Nadia P.',
                        'result' => 'Peserta Kelas Online',
                    ],
                    [
                        'quote' => 'Simulasi CAT dan timer membantu aku membangun tempo. Sekarang lebih tenang ketika bertemu soal yang panjang.',
                        'image' => 'img/student_farah.png',
                        'name' => 'Farhan D.',
                        'result' => 'Peserta Tryout CAT',
                    ],
                ],
            ],
            'achievements' => [
                'eyebrow' => 'Fokus Materi',
                'title' => 'Satu sistem untuk latihan yang lebih terarah',
                'items' => [
                    [
                        'value' => 'TIU',
                        'label' => 'Tes Intelegensia Umum',
                        'description' => 'Latihan kemampuan verbal, numerik, figural, dan penalaran dengan pembahasan yang mudah diikuti.',
                    ],
                    [
                        'value' => 'TWK',
                        'label' => 'Tes Wawasan Kebangsaan',
                        'description' => 'Materi inti, rangkuman, dan drilling soal untuk memperkuat pemahaman wawasan kebangsaan.',
                    ],
                    [
                        'value' => 'TKP',
                        'label' => 'Tes Karakteristik Pribadi',
                        'description' => 'Pahami pola penilaian dan latih pengambilan keputusan melalui studi kasus yang relevan.',
                    ],
                ],
            ],
            'marquee' => [
                'items' => [
                    ['icon' => 'ri-brain-line', 'label' => 'Penalaran numerik'],
                    ['icon' => 'ri-book-2-line', 'label' => 'Wawasan kebangsaan'],
                    ['icon' => 'ri-user-heart-line', 'label' => 'Karakteristik pribadi'],
                    ['icon' => 'ri-timer-flash-line', 'label' => 'Simulasi CAT'],
                    ['icon' => 'ri-bar-chart-grouped-line', 'label' => 'Analisis nilai'],
                    ['icon' => 'ri-discuss-line', 'label' => 'Diskusi mentor'],
                ],
            ],
            'features' => [
                'eyebrow' => 'Sistem Belajar',
                'title_html' => 'Bukan sekadar banyak soal.<br>Belajar harus punya arah.',
                'description' => 'Setiap aktivitas dirancang agar kamu tahu apa yang sudah kuat, apa yang harus diperbaiki, dan langkah berikutnya.',
                'items' => [
                    ['icon' => 'ri-computer-line', 'title' => 'Tryout rasa ujian', 'description' => 'Simulasi CAT dengan timer membantu kamu membangun fokus dan tempo pengerjaan.'],
                    ['icon' => 'ri-pie-chart-2-line', 'title' => 'Analisis progres', 'description' => 'Baca hasil per materi dan gunakan datanya untuk menentukan prioritas belajar.'],
                    ['icon' => 'ri-book-open-line', 'title' => 'Pembahasan runtut', 'description' => 'Pahami cara berpikir di balik jawaban, bukan sekadar menghafal opsi benar.'],
                    ['icon' => 'ri-team-line', 'title' => 'Support system', 'description' => 'Tetap konsisten bersama mentor dan komunitas peserta seperjuangan.'],
                ],
            ],
            'dashboard' => [
                'badge' => 'Dashboard Belajar',
                'title' => 'Semua progresmu dalam satu tempat.',
                'description' => 'Akses paket, jadwal, materi, tryout, riwayat nilai, dan pembahasan tanpa alur yang membingungkan.',
                'benefits' => [
                    'Riwayat latihan tersimpan rapi',
                    'Bisa diakses dari HP maupun laptop',
                    'Materi mengikuti fasilitas paket',
                ],
                'summary_label' => 'Ringkasan belajar',
                'greeting' => 'Halo, Pejuang STAN!',
                'stats' => [
                    ['icon' => 'ri-file-list-3-line', 'label' => 'Tryout', 'value' => '6 aktif'],
                    ['icon' => 'ri-time-line', 'label' => 'Belajar', 'value' => '12 jam'],
                    ['icon' => 'ri-award-line', 'label' => 'Target', 'value' => 'Terukur'],
                ],
                'target_label' => 'Target pekan ini',
                'target_description' => '4 dari 5 latihan selesai',
                'target_percentage' => 80,
            ],
            'roadmap' => [
                'badge' => 'Roadmap Persiapan',
                'title_html' => 'Dari “mulai dari mana?”<br>jadi siap menghadapi ujian.',
                'description' => 'Alur belajar dibuat sederhana supaya setiap pekan punya fokus, target, dan evaluasi yang jelas.',
                'items' => [
                    ['icon' => 'ri-focus-2-line', 'title' => 'Ukur kemampuan', 'description' => 'Mulai dari tryout diagnostik untuk membaca posisi awalmu.'],
                    ['icon' => 'ri-route-line', 'title' => 'Susun prioritas', 'description' => 'Fokuskan latihan pada materi yang paling perlu dikuatkan.'],
                    ['icon' => 'ri-repeat-2-line', 'title' => 'Latihan konsisten', 'description' => 'Jalankan drilling, kelas, dan pembahasan secara bertahap.'],
                    ['icon' => 'ri-flag-2-line', 'title' => 'Simulasi & evaluasi', 'description' => 'Uji strategi, tempo, lalu perbaiki sebelum hari seleksi.'],
                ],
            ],
            'ai_learning' => [
                'badge' => 'AI Learning Tools',
                'title' => 'Belajar lebih cerdas, bukan sekadar lebih lama.',
                'description' => 'Ubah materi dan soal seleksi STAN menjadi catatan, flashcard, serta latihan yang membantu kamu memahami pola soal dengan lebih terarah.',
                'chips' => ['Catatan cerdas', 'Flashcard aktif', 'Soal serupa'],
                'items' => [
                    ['icon' => 'ri-sticky-note-line', 'title' => 'Ringkas konsep penting', 'description' => 'Jadikan materi panjang sebagai catatan belajar yang runtut dan mudah diulang.'],
                    ['icon' => 'ri-stack-line', 'title' => 'Latih ingatan aktif', 'description' => 'Buat flashcard dari rumus, istilah, atau pola soal yang perlu kamu kuasai.'],
                    ['icon' => 'ri-file-add-line', 'title' => 'Perbanyak latihan terarah', 'description' => 'Dapatkan soal serupa untuk menguji pemahaman sebelum tryout berikutnya.'],
                ],
                'primary_cta' => ['label' => 'Coba AI Learning', 'href' => route('user.ai-learning.index', ['tool' => 'note'])],
                'secondary_cta' => ['label' => 'Lihat cara kerjanya', 'href' => route('ai-learning-tools')],
                'workspace' => [
                    'title' => 'AI Study Station',
                    'subtitle' => 'Ruang belajar personalmu',
                    'status' => 'Siap belajar',
                    'input_label' => 'Masukkan materi atau soal',
                    'input_text' => '“Jelaskan strategi menyelesaikan soal deret angka secara cepat dan sistematis.”',
                    'output_label' => 'Pilih hasil yang kamu butuhkan',
                    'output_items' => ['Catatan', 'Flashcard', 'Soal'],
                    'result_label' => 'Hasilmu siap',
                    'result_text' => 'Belajar jadi lebih terstruktur',
                ],
            ],
            'partners' => [
                'eyebrow' => 'Dipercaya Komunitas Belajar',
                'description' => 'Logo sekolah, komunitas, atau lembaga mitra dapat dikelola langsung dari halaman admin.',
                'items' => [],
            ],
            'faq' => [
                'eyebrow' => 'Pertanyaan Umum',
                'title' => 'Sebelum mulai, mungkin ini yang ingin kamu tahu',
                'items' => [
                    [
                        'question' => 'Apakah program ini khusus untuk persiapan PKN STAN?',
                        'answer' => 'Fokus utama program adalah persiapan seleksi PKN STAN dan materi SKD yang relevan. Detail cakupan setiap program dapat dilihat pada halaman paket.',
                    ],
                    [
                        'question' => 'Apakah tryout bisa dikerjakan lewat HP?',
                        'answer' => 'Bisa. Platform dapat dibuka melalui HP, tablet, maupun laptop. Untuk pengalaman simulasi yang paling nyaman, gunakan perangkat dengan koneksi internet stabil.',
                    ],
                    [
                        'question' => 'Apa yang didapat setelah mengerjakan tryout?',
                        'answer' => 'Kamu dapat melihat hasil, pembahasan, riwayat pengerjaan, dan fitur lain sesuai fasilitas paket yang dipilih. Informasi lengkap selalu tercantum di detail paket.',
                    ],
                    [
                        'question' => 'Apakah ada kelas dan pendampingan mentor?',
                        'answer' => 'Ketersediaan kelas, rekaman, grup diskusi, dan pendampingan mengikuti paket yang kamu pilih. Konsultasikan kebutuhanmu dengan admin bila masih ragu.',
                    ],
                    [
                        'question' => 'Bagaimana cara memilih paket yang tepat?',
                        'answer' => 'Pilih berdasarkan waktu persiapan dan intensitas pendampingan yang kamu butuhkan. Kamu juga bisa menghubungi admin untuk konsultasi awal tanpa biaya.',
                    ],
                ],
            ],
            'footer' => [
                'tagline' => 'Teman Bertumbuh Pejuang PKN STAN',
                'description' => 'Platform belajar untuk membantu persiapan seleksi PKN STAN menjadi lebih terarah, konsisten, dan terukur.',
                'navigation_title' => 'Navigasi',
                'nav_landing_label' => 'Beranda',
                'nav_statistics_snbp_label' => 'Program',
                'nav_statistics_snbt_label' => 'Keunggulan',
                'nav_articles_label' => 'Artikel',
                'nav_login_label' => 'Masuk Akun',
                'nav_features_label' => 'Keunggulan',
                'nav_roadmap_label' => 'Roadmap',
                'nav_program_label' => 'Program',
                'nav_testimonials_label' => 'Testimoni',
                'nav_faq_label' => 'FAQ',
                'contact_title' => 'Hubungi Kami',
                'instagram_label' => 'Instagram',
                'instagram_href' => config('client.branding.footer_instagram') ?: '#',
                'whatsapp_label' => config('client.branding.footer_whatsapp') ?: 'WhatsApp Admin',
                'whatsapp_href' => $whatsappHref,
                'email_label' => $contactEmail ?: 'Email Admin',
                'email_href' => $contactEmail ? "mailto:{$contactEmail}" : '#',
                'terms_label' => 'Syarat & Ketentuan',
                'terms_href' => '#',
                'privacy_label' => 'Kebijakan Privasi',
                'privacy_href' => '#',
                'copyright_suffix' => 'Hak cipta dilindungi undang-undang.',
            ],
            'cta' => [
                'badge' => 'Mulai Hari Ini',
                'title' => 'Target besar dimulai dari latihan pertama yang konsisten.',
                'description' => 'Buat akun, pilih program, dan mulai ukur progres persiapan PKN STAN-mu.',
                'primary_cta' => ['label' => 'Mulai Persiapan', 'href' => route('register')],
                'secondary_cta' => ['label' => 'Lihat program', 'href' => '#program'],
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
        return self::mergeMissingLandingDefaults(
            self::defaultLandingContent(),
            self::upgradeLegacyLandingContent($content)
        );
    }

    /**
     * Keep existing tenant customizations while upgrading untouched PTN demo copy
     * to the STAN landing experience. This does not mutate persisted JSON.
     */
    private static function upgradeLegacyLandingContent(array $content): array
    {
        $defaults = self::defaultLandingContent();

        if (data_get($content, 'hero.title_html') === 'Siap Tembus <br class="hidden sm:block"><span class="text-gradient">PTN Impian</span> Kamu?') {
            $content['hero'] = $defaults['hero'];
        }

        if (data_get($content, 'program.title') === 'Program Bimbingan Belajar Pilihan') {
            $selectedPackageIds = data_get($content, 'program.package_ids', []);
            $content['program'] = $defaults['program'];
            $content['program']['package_ids'] = $selectedPackageIds;
        }

        if (data_get($content, 'community.badge') === 'Support System Pejuang PTN') {
            $content['community'] = $defaults['community'];
        }

        if (data_get($content, 'testimonials.title') === 'Apa Kata Alumni Kami?') {
            $content['testimonials'] = $defaults['testimonials'];
        }

        if (data_get($content, 'achievements.title') === 'Bukti Nyata Kualitas Pendampingan BimbelHub') {
            $content['achievements'] = $defaults['achievements'];
        }

        if (data_get($content, 'partners.eyebrow') === 'Lembaga & Sekolah Mitra Kerja Sama') {
            $partnerItems = data_get($content, 'partners.items', []);
            $content['partners'] = $defaults['partners'];
            $content['partners']['items'] = $partnerItems;
        }

        if (data_get($content, 'faq.title') === 'FAQ (Frequently Asked Questions)') {
            $content['faq'] = $defaults['faq'];
        }

        if (data_get($content, 'footer.tagline') === 'Platform Sukses Tembus PTN Impian') {
            $legacyFooter = $content['footer'] ?? [];
            $content['footer'] = $defaults['footer'];
            $legacyContactDefaults = [
                'instagram_label' => '@naufalacademy',
                'instagram_href' => 'https://instagram.com/naufalacademy',
                'whatsapp_label' => '+62 856-1078-411',
                'whatsapp_href' => 'https://wa.me/628561078411?text=Halo%2520Admin%2520saya%2520Ingin%2520Bertanya',
                'email_label' => 'team.naufalacademy@gmail.com',
                'email_href' => 'mailto:team.naufalacademy@gmail.com',
            ];

            foreach ($legacyContactDefaults as $contactKey => $legacyDefault) {
                if (
                    ! empty($legacyFooter[$contactKey])
                    && $legacyFooter[$contactKey] !== $legacyDefault
                ) {
                    $content['footer'][$contactKey] = $legacyFooter[$contactKey];
                }
            }

            foreach ([
                'terms_label',
                'terms_href',
                'privacy_label',
                'privacy_href',
                'copyright_suffix',
            ] as $contactKey) {
                if (! empty($legacyFooter[$contactKey])) {
                    $content['footer'][$contactKey] = $legacyFooter[$contactKey];
                }
            }
        }

        if (data_get($content, 'meta.title') === 'Persiapan Ujian UTBK SNBT & SNBP Terbaik') {
            $content['meta'] = $defaults['meta'];
        }

        return $content;
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
