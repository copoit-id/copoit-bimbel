<?php

namespace App\Services;

use App\Models\IndividualPurchase;
use App\Models\Material;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionBankQuestion;
use App\Models\TesKoran;
use App\Models\TesKoranResult;
use App\Models\Tryout;
use App\Models\User;
use App\Models\UserAnswer;
use App\Models\UserMaterialAccess;
use App\Models\UserPackageAcces;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AdminAssistantService
{
    private const TIMEZONE = 'Asia/Jakarta';

    public function __construct(
        private readonly AiDiscussionService $aiDiscussionService,
        private readonly AdminAssistantProjectContextService $projectContextService,
        private readonly AdminAssistantHistoryService $historyService,
    ) {}

    /** @return array<int, array{role: string, text: string, created_at: string|null}> */
    public function history(User $user): array
    {
        return $this->historyService->recent($user);
    }

    public function chat(string $message): array
    {
        $message = trim($message);

        if ($message === '') {
            return $this->response(
                'Tulis pertanyaan singkat tentang data bimbel. Contoh: "pendapatan hari ini dibanding kemarin" atau "berapa pengajuan paket pending?".',
                'help'
            );
        }

        $period = $this->detectPeriod($message);
        $detection = $this->detectIntent($message);

        $intent = $detection['intent'] ?? null;

        $response = match ($intent) {
            'overview' => $this->overview($period),
            'revenue_summary' => $this->revenueSummary($period),
            'revenue_comparison' => $this->revenueComparison($period),
            'payment_summary' => $this->paymentSummary($period),
            'package_summary' => $this->packageSummary($period),
            'package_requests' => $this->packageRequests($period),
            'material_summary' => $this->materialSummary($period),
            'tryout_summary' => $this->tryoutSummary($period),
            'how_to' => $this->howTo($message),
            'admin_count' => $this->adminCount(),
            'student_registration' => $this->studentRegistration($period),
            'question_summary' => $this->questionSummary(),
            'tes_koran_summary' => $this->tesKoranSummary($period),
            'top_packages' => $this->topPackages($period),
            'pending_actions' => $this->pendingActions(),
            default => $this->fallback($message),
        };

        $user = auth()->user();
        if ($user && ! ($response['cache_hit'] ?? false)) {
            $context = $this->projectContextService->snapshot($user);
            $contextHash = hash('sha256', json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->historyService->store($user, $message, $response, $contextHash);
        }

        return $response;
    }

    public function suggestions(): array
    {
        return [
            'Pendapatan hari ini dibanding kemarin',
            'Ada berapa pembayaran pending?',
            'Berapa peserta yang daftar hari ini?',
            'Jumlah tryout dikerjakan hari ini',
            'Pengajuan paket pending ada berapa?',
            'Ringkasan paket aktif',
            'Materi aktif ada berapa?',
            'Top paket bulan ini',
        ];
    }

    private function detectIntent(string $message): array
    {
        $normalized = $this->normalize($message);

        if ($this->isHowToQuestion($normalized)) {
            return [
                'intent' => 'how_to',
                'confidence' => 1,
                'source' => 'local',
            ];
        }

        $intents = $this->intents();
        $bestIntent = null;
        $bestScore = 0;

        foreach ($intents as $intent => $keywords) {
            $score = 0;

            foreach ($keywords as $keyword => $weight) {
                if (str_contains($normalized, $keyword)) {
                    $score += $weight;
                }
            }

            if ($score > $bestScore || ($score === $bestScore && $intent === 'revenue_comparison')) {
                $bestScore = $score;
                $bestIntent = $intent;
            }
        }

        return [
            'intent' => $bestScore >= 3 ? $bestIntent : null,
            'confidence' => min(1, $bestScore / 8),
            'source' => 'local',
        ];
    }

    private function intents(): array
    {
        return [
            'overview' => [
                'ringkasan' => 4,
                'overview' => 4,
                'dashboard' => 3,
                'rekap' => 3,
                'semua data' => 3,
            ],
            'revenue_summary' => [
                'pendapatan' => 5,
                'revenue' => 5,
                'omzet' => 5,
                'pemasukan' => 5,
                'uang masuk' => 4,
                'total uang' => 3,
            ],
            'revenue_comparison' => [
                'banding' => 5,
                'perbandingan' => 5,
                'dibanding' => 5,
                'naik' => 2,
                'turun' => 2,
                'pendapatan' => 4,
                'revenue' => 4,
                'omzet' => 4,
            ],
            'payment_summary' => [
                'pembayaran' => 5,
                'payment' => 5,
                'transaksi' => 5,
                'bayar' => 4,
                'qris' => 2,
                'manual' => 2,
                'pending' => 2,
                'sukses' => 2,
            ],
            'package_summary' => [
                'paket' => 5,
                'package' => 5,
                'aktif' => 2,
                'gratis' => 2,
                'berbayar' => 2,
                'terjual' => 3,
                'akses paket' => 4,
            ],
            'package_requests' => [
                'pengajuan paket' => 6,
                'minta paket' => 5,
                'request paket' => 5,
                'verifikasi paket' => 5,
                'approval paket' => 4,
                'pending paket' => 4,
                'syarat paket' => 3,
            ],
            'material_summary' => [
                'materi' => 5,
                'material' => 5,
                'video' => 2,
                'dokumen' => 2,
                'document' => 2,
                'live session' => 3,
                'belajar' => 2,
            ],
            'tryout_summary' => [
                'tryout' => 5,
                'ujian' => 3,
                'latihan soal' => 4,
                'mengerjakan soal' => 5,
                'ngerjain soal' => 5,
                'attempt' => 3,
                'dikerjakan' => 4,
            ],
            'admin_count' => [
                'admin' => 5,
                'staff' => 3,
                'pengelola' => 3,
            ],
            'student_registration' => [
                'peserta' => 5,
                'siswa' => 5,
                'user baru' => 5,
                'daftar' => 4,
                'registrasi' => 4,
                'pendaftaran' => 4,
                'akun baru' => 4,
            ],
            'question_summary' => [
                'soal' => 5,
                'bank soal' => 5,
                'pertanyaan' => 3,
                'question' => 3,
            ],
            'tes_koran_summary' => [
                'tes koran' => 6,
                'koran' => 4,
                'pauli' => 3,
                'kraepelin' => 3,
            ],
            'top_packages' => [
                'top paket' => 6,
                'paket terlaris' => 6,
                'paket paling laku' => 6,
                'best seller' => 5,
                'ranking paket' => 5,
            ],
            'pending_actions' => [
                'pending' => 4,
                'menunggu' => 4,
                'perlu dicek' => 5,
                'perlu diproses' => 5,
                'tugas admin' => 5,
                'belum dikonfirmasi' => 4,
            ],
        ];
    }

    private function overview(array $period): array
    {
        $totalRevenue = $this->totalRevenue();
        $monthRevenue = $this->totalRevenue($period['start'], $period['end']);

        $answer = sprintf(
            "Ringkasan cepat:\n%s peserta terdaftar, %s paket aktif, %s tryout aktif, dan %s materi aktif.\nPendapatan %s tercatat %s. Total pendapatan sepanjang waktu: %s.",
            number_format(User::where('role', 'user')->count(), 0, ',', '.'),
            number_format(Package::where('status', 'active')->count(), 0, ',', '.'),
            number_format(Tryout::where('is_active', true)->count(), 0, ',', '.'),
            number_format(Material::where('is_active', true)->count(), 0, ',', '.'),
            $period['label'],
            $this->rupiah($monthRevenue),
            $this->rupiah($totalRevenue)
        );

        return $this->response($answer, 'overview');
    }

    private function revenueSummary(array $period): array
    {
        $packageRevenue = (float) Payment::where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->sum('total_amount');

        $itemRevenue = (float) IndividualPurchase::where('status', IndividualPurchase::STATUS_APPROVED)
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->sum('total_amount');

        $transactions = Payment::where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->count();

        $itemTransactions = IndividualPurchase::where('status', IndividualPurchase::STATUS_APPROVED)
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->count();

        $answer = sprintf(
            "Pendapatan %s: %s.\nRinciannya: %s dari %s pembayaran paket, dan %s dari %s pembelian item/materi/tryout.",
            $period['label'],
            $this->rupiah($packageRevenue + $itemRevenue),
            $this->rupiah($packageRevenue),
            number_format($transactions, 0, ',', '.'),
            $this->rupiah($itemRevenue),
            number_format($itemTransactions, 0, ',', '.')
        );

        return $this->response($answer, 'revenue_summary');
    }

    private function revenueComparison(array $period): array
    {
        $current = $this->totalRevenue($period['start'], $period['end']);
        $previous = $this->totalRevenue($period['previous_start'], $period['previous_end']);
        $delta = $current - $previous;
        $trend = $this->trendText($current, $previous);

        $answer = sprintf(
            "Perbandingan pendapatan %s: %s.\nPeriode pembanding: %s. Selisihnya %s (%s).",
            $period['label'],
            $this->rupiah($current),
            $this->rupiah($previous),
            $this->rupiah(abs($delta)),
            $trend
        );

        return $this->response($answer, 'revenue_comparison');
    }

    private function paymentSummary(array $period): array
    {
        $payments = Payment::select('status', DB::raw('COUNT(*) as total'), DB::raw('COALESCE(SUM(total_amount), 0) as amount'))
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->groupBy('status')
            ->pluck('total', 'status');

        $successRevenue = (float) Payment::where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->sum('total_amount');

        $answer = sprintf(
            "Pembayaran %s: %s sukses, %s pending, %s gagal, %s expired.\nNominal pembayaran sukses: %s.",
            $period['label'],
            number_format((int) ($payments[Payment::STATUS_SUCCESS] ?? 0), 0, ',', '.'),
            number_format((int) ($payments[Payment::STATUS_PENDING] ?? 0), 0, ',', '.'),
            number_format((int) ($payments[Payment::STATUS_FAILED] ?? 0), 0, ',', '.'),
            number_format((int) ($payments[Payment::STATUS_EXPIRED] ?? 0), 0, ',', '.'),
            $this->rupiah($successRevenue)
        );

        return $this->response($answer, 'payment_summary');
    }

    private function packageSummary(array $period): array
    {
        $total = Package::count();
        $active = Package::where('status', 'active')->count();
        $paid = Package::where('type_price', 'paid')->count();
        $free = Package::where('type_price', 'free')->count();
        $accesses = UserPackageAcces::whereBetween('created_at', [$period['start'], $period['end']])->count();
        $activeAccess = UserPackageAcces::active()->count();

        $answer = sprintf(
            "Data paket: %s total paket, %s aktif, %s berbayar, dan %s gratis.\nAkses paket aktif saat ini %s. Akses baru %s: %s.",
            number_format($total, 0, ',', '.'),
            number_format($active, 0, ',', '.'),
            number_format($paid, 0, ',', '.'),
            number_format($free, 0, ',', '.'),
            number_format($activeAccess, 0, ',', '.'),
            $period['label'],
            number_format($accesses, 0, ',', '.')
        );

        return $this->response($answer, 'package_summary');
    }

    private function packageRequests(array $period): array
    {
        $query = UserPackageAcces::query()
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere('payment_status', 'conditional');

                if (Schema::hasColumn('user_package_access', 'requirement_status')) {
                    $query->orWhere('requirement_status', 'pending');
                }
            });

        $total = (clone $query)->count();
        $allPending = UserPackageAcces::query()
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere('payment_status', 'conditional');

                if (Schema::hasColumn('user_package_access', 'requirement_status')) {
                    $query->orWhere('requirement_status', 'pending');
                }
            })
            ->count();

        $answer = sprintf(
            "Pengajuan paket %s ada %s. Total pengajuan yang masih perlu dicek saat ini: %s.",
            $period['label'],
            number_format($total, 0, ',', '.'),
            number_format($allPending, 0, ',', '.')
        );

        return $this->response($answer, 'package_requests');
    }

    private function materialSummary(array $period): array
    {
        $types = Material::select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $completed = UserMaterialAccess::where('status', 'completed')
            ->whereBetween('updated_at', [$period['start'], $period['end']])
            ->count();

        $answer = sprintf(
            "Materi saat ini: %s aktif dari %s total.\nKomposisi: %s video, %s dokumen, %s live session. Materi selesai dipelajari %s: %s.",
            number_format(Material::where('is_active', true)->count(), 0, ',', '.'),
            number_format(Material::count(), 0, ',', '.'),
            number_format((int) ($types['video'] ?? 0), 0, ',', '.'),
            number_format((int) ($types['document'] ?? 0), 0, ',', '.'),
            number_format((int) ($types['live_session'] ?? 0), 0, ',', '.'),
            $period['label'],
            number_format($completed, 0, ',', '.')
        );

        return $this->response($answer, 'material_summary');
    }

    private function tryoutSummary(array $period): array
    {
        $attempts = UserAnswer::whereBetween('started_at', [$period['start'], $period['end']])->count();
        $completed = UserAnswer::where('status', 'completed')
            ->whereBetween('finished_at', [$period['start'], $period['end']])
            ->count();
        $participants = UserAnswer::whereBetween('started_at', [$period['start'], $period['end']])
            ->distinct('user_id')
            ->count('user_id');

        $answer = sprintf(
            "Aktivitas tryout %s: %s attempt dari %s peserta unik.\nYang selesai pada periode ini: %s. Total tryout aktif di katalog: %s.",
            $period['label'],
            number_format($attempts, 0, ',', '.'),
            number_format($participants, 0, ',', '.'),
            number_format($completed, 0, ',', '.'),
            number_format(Tryout::where('is_active', true)->count(), 0, ',', '.')
        );

        return $this->response($answer, 'tryout_summary');
    }

    private function adminCount(): array
    {
        $admins = User::whereIn('role', ['admin', 'admin_demo', 'super_admin'])->count();
        $superAdmins = User::where('role', 'super_admin')->count();

        $answer = sprintf(
            "Jumlah admin saat ini %s akun. Dari jumlah itu, %s adalah super admin.",
            number_format($admins, 0, ',', '.'),
            number_format($superAdmins, 0, ',', '.')
        );

        return $this->response($answer, 'admin_count');
    }

    private function studentRegistration(array $period): array
    {
        $current = User::where('role', 'user')
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->count();
        $previous = User::where('role', 'user')
            ->whereBetween('created_at', [$period['previous_start'], $period['previous_end']])
            ->count();

        $answer = sprintf(
            "Peserta daftar %s: %s akun baru.\nPeriode pembanding: %s akun. Trennya %s.",
            $period['label'],
            number_format($current, 0, ',', '.'),
            number_format($previous, 0, ',', '.'),
            $this->trendText($current, $previous)
        );

        return $this->response($answer, 'student_registration');
    }

    private function questionSummary(): array
    {
        $answer = sprintf(
            "Bank soal saat ini berisi %s soal tryout dan %s soal di question bank.\nTotal bank soal: %s.",
            number_format(Question::count(), 0, ',', '.'),
            number_format(QuestionBankQuestion::count(), 0, ',', '.'),
            number_format(QuestionBank::count(), 0, ',', '.')
        );

        return $this->response($answer, 'question_summary');
    }

    private function tesKoranSummary(array $period): array
    {
        $results = TesKoranResult::whereBetween('created_at', [$period['start'], $period['end']])->count();
        $completed = TesKoranResult::where('status', 'completed')
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->count();

        $answer = sprintf(
            "Tes koran: %s aktif dari %s total.\nHasil masuk %s: %s, dengan %s selesai.",
            number_format(TesKoran::where('is_active', true)->count(), 0, ',', '.'),
            number_format(TesKoran::count(), 0, ',', '.'),
            $period['label'],
            number_format($results, 0, ',', '.'),
            number_format($completed, 0, ',', '.')
        );

        return $this->response($answer, 'tes_koran_summary');
    }

    private function topPackages(array $period): array
    {
        $rows = Payment::query()
            ->join('packages', 'payments.package_id', '=', 'packages.package_id')
            ->select('packages.name', DB::raw('COUNT(*) as transactions'), DB::raw('COALESCE(SUM(payments.total_amount), 0) as revenue'))
            ->where('payments.status', Payment::STATUS_SUCCESS)
            ->whereBetween('payments.created_at', [$period['start'], $period['end']])
            ->groupBy('packages.package_id', 'packages.name')
            ->orderByDesc('revenue')
            ->limit(3)
            ->get();

        if ($rows->isEmpty()) {
            return $this->response("Belum ada paket terjual pada {$period['label']}.", 'top_packages');
        }

        $lines = $rows->values()->map(function ($row, int $index) {
            return sprintf(
                "%s. %s - %s transaksi, %s",
                $index + 1,
                $row->name,
                number_format((int) $row->transactions, 0, ',', '.'),
                $this->rupiah((float) $row->revenue)
            );
        })->implode("\n");

        return $this->response("Top paket {$period['label']}:\n{$lines}", 'top_packages');
    }

    private function pendingActions(): array
    {
        $pendingPayments = Payment::where('status', Payment::STATUS_PENDING)->count();
        $pendingItems = IndividualPurchase::where('status', IndividualPurchase::STATUS_PENDING)->count();
        $pendingPackages = UserPackageAcces::query()
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere('payment_status', 'conditional');

                if (Schema::hasColumn('user_package_access', 'requirement_status')) {
                    $query->orWhere('requirement_status', 'pending');
                }
            })
            ->count();

        $answer = sprintf(
            "Yang perlu dicek: %s pembayaran pending, %s pengajuan paket, dan %s pembelian item pending.",
            number_format($pendingPayments, 0, ',', '.'),
            number_format($pendingPackages, 0, ',', '.'),
            number_format($pendingItems, 0, ',', '.')
        );

        return $this->response($answer, 'pending_actions');
    }

    private function fallback(string $message): array
    {
        $user = auth()->user();
        if ($user && filled(config('services.ai_gateway.url')) && filled(config('services.ai_gateway.key'))) {
            $projectContext = $this->projectContextService->snapshot(auth()->user());
            $contextHash = hash('sha256', json_encode($projectContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $cachedResponse = $this->historyService->reusable($user, $message, $contextHash);
            if ($cachedResponse) {
                return [
                    ...$cachedResponse,
                    'suggestions' => $this->suggestions(),
                ];
            }

            try {
                $result = $this->aiDiscussionService->chat(
                    $message,
                    [
                        'question_text' => $message,
                        'explanation' => json_encode(
                            $projectContext,
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                        ),
                    ],
                    feature: 'admin_assistant',
                );

                $response = [
                    'answer' => trim((string) ($result['message'] ?? '')),
                    'intent' => 'ai_assistant',
                    'source' => 'project_context',
                    'confidence' => 'verified',
                    'usage' => $result['usage'] ?? null,
                    'suggestions' => $this->suggestions(),
                ];
                return $response;
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $localGuide = $this->localProjectGuide($message);
        if ($localGuide) {
            return $localGuide;
        }

        return $this->response(
            'Aku belum bisa memverifikasi pertanyaan itu dari data dan struktur project yang tersedia. Coba tanyakan data seperti pendapatan, pembayaran, paket, peserta baru, tryout, materi, soal, admin, atau tes koran.',
            'fallback'
        );
    }

    private function howTo(string $message): array
    {
        return $this->fallback($message);
    }

    private function isHowToQuestion(string $normalized): bool
    {
        $normalized = preg_replace('/^ara\b/', 'cara', $normalized) ?? $normalized;

        foreach ([
            'cara', 'bagaimana', 'gimana', 'langkah', 'tutorial',
            'tambah', 'tambahkan', 'buat', 'membuat', 'atur', 'mengatur',
            'edit', 'ubah', 'mengubah', 'hapus', 'menghapus',
        ] as $keyword) {
            if (preg_match('/(?:^|\s)'.preg_quote($keyword, '/').'(?:\s|$)/', $normalized) === 1) {
                return true;
            }
        }

        return str_contains($normalized, 'gimana cara')
            || str_contains($normalized, 'bagaimana cara');
    }

    private function localProjectGuide(string $message): ?array
    {
        $normalized = $this->normalize($message);
        $subjectWords = collect(preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->reject(fn (string $word): bool => in_array($word, [
                'cara', 'bagaimana', 'gimana', 'langkah', 'tutorial', 'tambah', 'tambahkan',
                'buat', 'membuat', 'atur', 'mengatur', 'edit', 'ubah', 'mengubah', 'hapus',
                'menghapus', 'untuk', 'di', 'ke', 'pada', 'dengan',
            ], true))
            ->values();
        if ($subjectWords->isEmpty()) {
            return null;
        }

        $routes = $this->projectContextService->snapshot(auth()->user())['available_admin_routes'] ?? [];
        $matches = collect($routes)
            ->map(function (array $route) use ($subjectWords): array {
                $haystack = Str::lower($route['name'].' '.$route['uri']);
                $keywordScore = $subjectWords->sum(fn (string $word): int => str_contains($haystack, $word) ? 1 : 0);
                $isPage = in_array('GET', $route['methods'], true)
                    && ! str_contains(str_replace('{portal}', '', $route['uri']), '{');

                return [...$route, 'score' => $keywordScore + ($isPage ? 1 : 0), 'keyword_score' => $keywordScore, 'is_page' => $isPage];
            })
            ->filter(fn (array $route): bool => $route['keyword_score'] > 0 && $route['is_page'])
            ->sortByDesc('score')
            ->take(2)
            ->values();

        if ($matches->isEmpty()) {
            return null;
        }

        $subject = Str::ucfirst($subjectWords->implode(' '));
        $steps = $matches->map(function (array $route, int $index): string {
            $label = Str::of($route['uri'])
                ->replace(['{portal}', 'admin/', 'tutor/', '/'], ' ')
                ->replace('-', ' ')
                ->squish()
                ->title();

            return ($index + 1).'. Buka halaman '.$label.' (route '.$route['name'].').';
        })->implode("\n");

        return [
            ...$this->response(
                "Untuk menambahkan {$subject}:\n\n{$steps}\n".(count($matches) > 0 ? (count($matches) + 1).'. Gunakan tombol tambah pada halaman tersebut, isi data yang diminta, lalu simpan.' : ''),
                'how_to'
            ),
            'confidence' => 'partial',
            'source' => 'project_routes',
        ];
    }

    private function response(string $answer, string $intent): array
    {
        return [
            'answer' => $answer,
            'intent' => $intent,
            'suggestions' => $this->suggestions(),
        ];
    }

    private function detectPeriod(string $message): array
    {
        $normalized = $this->normalize($message);
        $now = Carbon::now(self::TIMEZONE);

        if (str_contains($normalized, 'hari ini') || str_contains($normalized, 'today')) {
            return $this->period('today', 'hari ini', $now->copy()->startOfDay(), $now->copy()->endOfDay());
        }

        if (str_contains($normalized, 'kemarin')) {
            return $this->period('yesterday', 'kemarin', $now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay());
        }

        if (str_contains($normalized, 'bulan lalu')) {
            return $this->period('last_month', 'bulan lalu', $now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth());
        }

        if (str_contains($normalized, 'bulan ini')) {
            return $this->period('this_month', 'bulan ini', $now->copy()->startOfMonth(), $now->copy()->endOfDay());
        }

        if (str_contains($normalized, 'minggu ini')) {
            return $this->period('this_week', 'minggu ini', $now->copy()->startOfWeek(), $now->copy()->endOfDay());
        }

        if (str_contains($normalized, '7 hari') || str_contains($normalized, 'seminggu')) {
            return $this->period('last_7_days', '7 hari terakhir', $now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay());
        }

        if (str_contains($normalized, '30 hari')) {
            return $this->period('last_30_days', '30 hari terakhir', $now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay());
        }

        return $this->period('today', 'hari ini', $now->copy()->startOfDay(), $now->copy()->endOfDay());
    }

    private function period(string $key, string $label, Carbon $start, Carbon $end): array
    {
        $durationSeconds = $start->diffInSeconds($end) + 1;

        return [
            'key' => $key,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'previous_start' => $start->copy()->subSeconds($durationSeconds),
            'previous_end' => $start->copy()->subSecond(),
        ];
    }

    private function totalRevenue(?Carbon $start = null, ?Carbon $end = null): float
    {
        $packageQuery = Payment::where('status', Payment::STATUS_SUCCESS);
        $itemQuery = IndividualPurchase::where('status', IndividualPurchase::STATUS_APPROVED);

        if ($start && $end) {
            $packageQuery->whereBetween('created_at', [$start, $end]);
            $itemQuery->whereBetween('created_at', [$start, $end]);
        }

        return (float) $packageQuery->sum('total_amount') + (float) $itemQuery->sum('total_amount');
    }

    private function trendText(float|int $current, float|int $previous): string
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? 'naik dari nol' : 'stabil';
        }

        $percent = (($current - $previous) / $previous) * 100;
        $direction = $percent >= 0 ? 'naik' : 'turun';

        return sprintf('%s %s%%', $direction, number_format(abs($percent), 1, ',', '.'));
    }

    private function rupiah(float|int $amount): string
    {
        return 'Rp' . number_format((float) $amount, 0, ',', '.');
    }

    private function normalize(string $message): string
    {
        $message = Str::lower($message);
        $message = preg_replace('/[^a-z0-9\s]+/u', ' ', $message) ?? $message;

        return trim(preg_replace('/\s+/', ' ', $message) ?? $message);
    }

}
