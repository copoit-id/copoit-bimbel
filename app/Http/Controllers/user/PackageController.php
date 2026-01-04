<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Payment;
use App\Models\UserPackageAcces;
use App\Models\ClassModel;
use App\Models\Tryout;
use App\Services\PracticeProgressService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    public function __construct(private PracticeProgressService $practiceProgress)
    {
        parent::__construct();
    }

    public function index()
    {
        $tryouts = Tryout::where('is_active', true)
            ->with(['tryoutDetails.questions', 'directPackage', 'packages'])
            ->orderBy('tryout_id')
            ->get();

        $tryouts = $tryouts->map(function ($tryout) {
            $primaryPackage = $tryout->directPackage ?: $tryout->packages->first();
            $tryout->setRelation('primaryPackage', $primaryPackage);
            return $tryout;
        });

        $practiceStats = $this->practiceProgress->getStatsForUser(Auth::id());

        return view('user.pages.package.index', compact(
            'tryouts',
            'practiceStats'
        ));
    }

    public function buyPackage(Request $request, $package_id)
    {
        try {
            $package = Package::findOrFail($package_id);

            $existingAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $package_id)
                ->first();

            $tryoutId = (int) $request->input('tryout_id');
            if (!$tryoutId) {
                $firstTryout = $package->tryouts()->first() ?: $package->directTryouts()->first();
                $tryoutId = $firstTryout?->tryout_id;
            }

            if ($tryoutId && !$this->practiceProgress->tryoutIsUnlocked(Auth::id(), $tryoutId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tryout ini masih terkunci. Selesaikan latihan soal untuk membukanya.'
                ], 403);
            }

            if ($existingAccess && $existingAccess->status === 'active' && $existingAccess->end_date && Carbon::parse($existingAccess->end_date)->greaterThan(Carbon::now())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah memiliki akses aktif ke paket ini'
                ], 400);
            }

            switch ($package->type_price) {
                case 'free_unconditional':
                    $this->grantFreeAccess($package_id);

                    return response()->json([
                        'success' => true,
                        'message' => 'Paket gratis berhasil diaktifkan!'
                    ]);

                case 'free_conditional':
                    $validated = $request->validate([
                        'requirement_proof' => 'required|file|mimes:jpg,jpeg,png,pdf,mp4,webm|max:20480',
                    ], [
                        'requirement_proof.required' => 'Bukti pemenuhan syarat wajib diunggah.',
                        'requirement_proof.mimes' => 'Format bukti harus berupa JPG, PNG, PDF, MP4, atau WEBM.',
                        'requirement_proof.max' => 'Ukuran bukti maksimal 20MB.',
                    ]);

                    $this->saveConditionalRequest($package, $existingAccess, $request->file('requirement_proof'));

                    return response()->json([
                        'success' => true,
                        'message' => 'Bukti berhasil dikirim. Mohon tunggu verifikasi admin.'
                    ]);

                case 'paid':
                default:
                    if ($package->price <= 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Paket berbayar memerlukan harga yang valid.'
                        ], 400);
                    }

                    $paymentResponse = $this->createPayment($package);

                    if ($paymentResponse['success']) {
                        return response()->json([
                            'success' => true,
                            'redirect_url' => $paymentResponse['redirect_url']
                        ]);
                    }

                    return response()->json([
                        'success' => false,
                        'message' => $paymentResponse['message']
                    ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    private function createPayment(Package $package)
    {
        $gateway = strtolower((string) config('services.payment_gateway', 'xendit'));

        if ($gateway === 'midtrans') {
            return $this->createMidtransPayment($package);
        }

        return $this->createXenditPayment($package);
    }

    private function createXenditPayment(Package $package)
    {
        $secretKey = config('services.xendit.secret_key');

        if (!$secretKey) {
            return [
                'success' => false,
                'message' => 'Xendit secret key tidak dikonfigurasi'
            ];
        }

        $transactionId = 'PKG-' . $package->package_id . '-' . Auth::id() . '-' . time();
        $baseUrl = rtrim(config('services.xendit.base_url', 'https://api.xendit.co'), '/');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/v2/invoices', [
                'external_id' => $transactionId,
                'amount' => $package->price,
                'description' => 'Pembelian ' . $package->name,
                'invoice_duration' => 86400, // 24 hours
                'customer' => [
                    'given_names' => Auth::user()->name,
                    'email' => Auth::user()->email,
                ],
                'customer_notification_preference' => [
                    'invoice_created' => ['email'],
                    'invoice_reminder' => ['email'],
                    'invoice_paid' => ['email'],
                ],
                'success_redirect_url' => route('user.package.payment.success'),
                'failure_redirect_url' => route('user.package.payment.failed'),
            ]);

            if ($response->successful()) {
                $invoiceData = $response->json();

                // Save payment record
                Payment::create([
                    'transaction_id' => $transactionId,
                    'user_id' => Auth::id(),
                    'package_id' => $package->package_id,
                    'amount' => $package->price,
                    'admin_fee' => 0,
                    'total_amount' => $package->price,
                    'status' => Payment::STATUS_PENDING,
                    'payment_method' => 'xendit',
                    'payment_details' => json_encode([
                        'invoice_id' => $invoiceData['id'],
                        'invoice_url' => $invoiceData['invoice_url'],
                        'external_id' => $transactionId
                    ]),
                ]);

                return [
                    'success' => true,
                    'redirect_url' => $invoiceData['invoice_url']
                ];
            } else {
                $errorMessage = 'Gagal membuat pembayaran';
                if ($response->json() && isset($response->json()['message'])) {
                    $errorMessage = $response->json()['message'];
                }

                return [
                    'success' => false,
                    'message' => $errorMessage
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error koneksi ke Xendit: ' . $e->getMessage()
            ];
        }
    }

    private function createMidtransPayment(Package $package)
    {
        $serverKey = config('services.midtrans.server_key');
        $snapUrl = config('services.midtrans.snap_url', 'https://app.sandbox.midtrans.com/snap/v1/transactions');

        if (!$serverKey) {
            return [
                'success' => false,
                'message' => 'Midtrans server key tidak dikonfigurasi'
            ];
        }

        $transactionId = 'PKG-' . $package->package_id . '-' . Auth::id() . '-' . time();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($serverKey . ':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($snapUrl, [
                'transaction_details' => [
                    'order_id' => $transactionId,
                    'gross_amount' => (int) round($package->price),
                ],
                'item_details' => [
                    [
                        'id' => (string) $package->package_id,
                        'price' => (int) round($package->price),
                        'quantity' => 1,
                        'name' => Str::limit($package->name, 50, ''),
                    ],
                ],
                'customer_details' => [
                    'first_name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                ],
                'callbacks' => [
                    'finish' => route('user.package.payment.success'),
                    'error' => route('user.package.payment.failed'),
                    'pending' => route('user.package.riwayatPembelian'),
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Payment::create([
                    'transaction_id' => $transactionId,
                    'user_id' => Auth::id(),
                    'package_id' => $package->package_id,
                    'amount' => $package->price,
                    'admin_fee' => 0,
                    'total_amount' => $package->price,
                    'status' => Payment::STATUS_PENDING,
                    'payment_method' => 'midtrans',
                    'payment_details' => json_encode([
                        'snap_token' => $data['token'] ?? null,
                        'redirect_url' => $data['redirect_url'] ?? null,
                        'external_id' => $transactionId,
                    ]),
                ]);

                return [
                    'success' => true,
                    'redirect_url' => $data['redirect_url'] ?? null,
                ];
            }

            $errorMessage = 'Gagal membuat pembayaran';
            if ($response->json() && isset($response->json()['status_message'])) {
                $errorMessage = $response->json()['status_message'];
            }

            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error koneksi ke Midtrans: ' . $e->getMessage(),
            ];
        }
    }

    private function grantFreeAccess(int $packageId): void
    {
        UserPackageAcces::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'package_id' => $packageId,
            ],
            [
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(30),
                'status' => 'active',
                'payment_amount' => 0,
                'payment_status' => 'free',
                'notes' => 'Free package access',
                'created_by' => Auth::id(),
                'requirement_proof_path' => null,
                'requirement_review_notes' => null,
                'requirement_status' => 'none',
            ]
        );
    }

    private function saveConditionalRequest(Package $package, ?UserPackageAcces $existingAccess, \Illuminate\Http\UploadedFile $proof): void
    {
        $proofPath = $proof->store('conditional-proofs', 'public');

        $access = $existingAccess ?: new UserPackageAcces([
            'user_id' => Auth::id(),
            'package_id' => $package->package_id,
        ]);

        if ($access->requirement_proof_path && Storage::disk('public')->exists($access->requirement_proof_path)) {
            Storage::disk('public')->delete($access->requirement_proof_path);
        }

        $access->fill([
            'start_date' => null,
            'end_date' => null,
            'status' => 'pending',
            'payment_amount' => 0,
            'payment_status' => 'conditional',
            'notes' => $package->conditional_requirement,
            'requirement_proof_path' => $proofPath,
            'requirement_review_notes' => null,
            'requirement_status' => 'pending',
            'created_by' => Auth::id(),
        ]);

        $access->save();
    }

    public function riwayatPembelian()
    {
        $payments = Payment::where('user_id', Auth::id())
            ->with('package')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('user.pages.package.riwayat-pembelian', compact('payments'));
    }

    public function riwayatPembelianAktif()
    {
        $activePackages = UserPackageAcces::where('user_id', Auth::id())
            ->where('status', 'active')
            ->where('end_date', '>', Carbon::now())
            ->with('package')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('user.pages.package.riwayat-pembelian-aktif', compact('activePackages'));
    }

    public function indexBimbel($id_package)
    {
        $package = Package::findOrFail($id_package);

        // Check if user has access - perbaiki query akses
        $hasAccess = UserPackageAcces::where('user_id', Auth::id())
            ->where('package_id', $id_package)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->exists();

        if (!$hasAccess) {
            return redirect()->route('user.package.index')
                ->with('error', 'Anda tidak memiliki akses ke paket ini');
        }

        // Get classes for this package
        $classes = ClassModel::whereHas('detailPackages', function ($query) use ($id_package) {
            $query->where('package_id', $id_package);
        })->orderBy('schedule_time', 'desc')->get();

        return view('user.pages.package.bimbel', compact('package', 'classes'));
    }

    public function indexTryout($id_package)
    {
        $package = Package::findOrFail($id_package);

        // Check if user has access - perbaiki query akses
        $hasAccess = UserPackageAcces::where('user_id', Auth::id())
            ->where('package_id', $id_package)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->exists();

        if (!$hasAccess) {
            return redirect()->route('user.package.index')
                ->with('error', 'Anda tidak memiliki akses ke paket ini');
        }

        // Get tryouts for this package with user attempts
        $tryouts = $package->tryouts()
            ->with(['tryoutDetails.questions', 'userAnswers' => function ($query) {
                $query->where('user_id', Auth::id());
            }])->get();

        return view('user.pages.package.tryout', compact('package', 'tryouts'));
    }

    public function riwayatTryout($id_package, $id_tryout)
    {
        $package = Package::findOrFail($id_package);
        $tryout = \App\Models\Tryout::findOrFail($id_tryout);

        // Check access - perbaiki query akses
        $hasAccess = UserPackageAcces::where('user_id', Auth::id())
            ->where('package_id', $id_package)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->exists();

        if (!$hasAccess) {
            return redirect()->route('user.package.index')
                ->with('error', 'Anda tidak memiliki akses ke paket ini');
        }

        // Get user attempts for this tryout dengan data yang lebih lengkap
        $attempts = \App\Models\UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->where('status', 'completed')
            ->with(['tryout', 'tryoutDetail'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Group attempts by attempt_token untuk SKD Full
        $groupedAttempts = $attempts->groupBy('attempt_token');

        // Prepare attempt data dengan perhitungan yang benar
        $attemptHistory = [];
        foreach ($groupedAttempts as $token => $userAnswers) {
            $firstAnswer = $userAnswers->first();
            $lastAnswer = $userAnswers->sortByDesc('finished_at')->first();

            // Calculate total score untuk attempt ini
            if ($userAnswers->count() > 1) {
                // SKD Full - hitung total dari semua subtest
                $totalScore = 0;
                $totalMaxScore = 0;
                $totalCorrect = 0;
                $totalWrong = 0;
                $totalUnanswered = 0;

                foreach ($userAnswers as $ua) {
                    $subtestScore = $this->calculateTotalScore($ua, $ua->tryoutDetail->type_subtest);
                    $maxSubtestScore = $this->getMaxPossibleScoreForDetail(
                        $ua->tryout_detail_id,
                        $ua->tryoutDetail->type_subtest
                    );

                    $totalScore += $subtestScore;
                    $totalMaxScore += $maxSubtestScore;
                    $totalCorrect += $ua->correct_answers ?? 0;
                    $totalWrong += $ua->wrong_answers ?? 0;
                    $totalUnanswered += $ua->unanswered ?? 0;
                }

                $finalPercentage = $totalMaxScore > 0 ? ($totalScore / $totalMaxScore) * 100 : 0;
                $isPassed = $finalPercentage >= 65; // SKD minimal 65%
            } else {
                // Single subtest
                $singleAnswer = $userAnswers->first();
                $finalPercentage = $singleAnswer->score ?? 0;
                $isPassed = $finalPercentage >= ($singleAnswer->tryoutDetail->passing_score ?? 60);
                $totalCorrect = $singleAnswer->correct_answers ?? 0;
                $totalWrong = $singleAnswer->wrong_answers ?? 0;
                $totalUnanswered = $singleAnswer->unanswered ?? 0;
            }

            // Calculate duration
            $startTime = Carbon::parse($firstAnswer->started_at);
            $endTime = Carbon::parse($lastAnswer->finished_at);
            $duration = $endTime->diff($startTime);

            $attemptHistory[] = [
                'id' => $token,
                'created_at' => $firstAnswer->created_at,
                'started_at' => $firstAnswer->started_at,
                'finished_at' => $lastAnswer->finished_at,
                'score' => round($finalPercentage, 0),
                'is_passed' => $isPassed,
                'duration' => $duration->format('%H:%I:%S'),
                'correct_answers' => $totalCorrect,
                'wrong_answers' => $totalWrong,
                'unanswered' => $totalUnanswered,
                'attempt_token' => $token
            ];
        }

        // Sort by newest first
        $attemptHistory = collect($attemptHistory)->sortByDesc('created_at')->values();

        return view('user.pages.package.tryout-riwayat', compact('package', 'tryout', 'attemptHistory'));
    }

    // Helper methods untuk calculation (tambahkan jika belum ada)
    private function calculateTotalScore($userAnswer, $type_subtest)
    {
        $totalScore = 0;

        $userAnswerDetails = \App\Models\UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->with(['questionOption', 'question'])
            ->get();

        foreach ($userAnswerDetails as $detail) {
            if ($detail->questionOption) {
                switch ($type_subtest) {
                    case 'twk':
                    case 'tiu':
                        $w = (float) ($detail->questionOption->weight ?? 0);
                        $totalScore += $detail->is_correct ? ($w > 0 ? $w : 5) : 0;
                        break;
                    case 'tkp':
                        $totalScore += (float) ($detail->questionOption->weight ?? 0);
                        break;
                    case 'writing':
                    case 'reading':
                    case 'listening':
                        // Gunakan bobot dari template jika ada; default 10
                        $w = (float) ($detail->questionOption->weight ?? 0);
                        $totalScore += $detail->is_correct ? ($w > 0 ? $w : 10) : 0;
                        break;
                    default:
                        // Default: jika ada bobot di template, pakai bobot untuk jawaban benar saja
                        $w = (float) ($detail->questionOption->weight ?? 0);
                        $totalScore += $detail->is_correct ? ($w > 0 ? $w : 1) : 0;
                        break;
                }
            }
        }

        return $totalScore;
    }

    private function getMaxPossibleScore($type_subtest, $totalQuestions)
    {
        switch ($type_subtest) {
            case 'twk':
            case 'tiu':
                return $totalQuestions * 5;
            case 'tkp':
                return $totalQuestions * 5;
            case 'writing':
            case 'reading':
            case 'listening':
                return $totalQuestions * 10; // 10 poin per soal untuk certification
            default:
                return $totalQuestions;
        }
    }

    // Versi dinamis: hitung maksimum skor berdasarkan bobot pada template
    private function getMaxPossibleScoreForDetail(int $tryoutDetailId, string $type_subtest)
    {
        // Ambil seluruh pertanyaan untuk detail ini
        $questionIds = \App\Models\Question::where('tryout_detail_id', $tryoutDetailId)
            ->pluck('question_id');

        if ($questionIds->isEmpty()) return 0;

        switch ($type_subtest) {
            case 'tkp':
                // TKP: per soal ambil bobot maksimum dari semua opsi
                $rows = \App\Models\QuestionOption::whereIn('question_id', $questionIds)
                    ->selectRaw('question_id, MAX(COALESCE(weight,0)) as mw')
                    ->groupBy('question_id')
                    ->get();
                return (float) $rows->sum('mw');
            case 'twk':
            case 'tiu':
                // Jika ada bobot pada opsi benar, gunakan itu; jika tidak, default 5 per soal
                $sum = 0;
                foreach ($questionIds as $qid) {
                    $w = (float) (\App\Models\QuestionOption::where('question_id', $qid)
                        ->where('is_correct', true)
                        ->value('weight') ?? 0);
                    $sum += ($w > 0 ? $w : 5);
                }
                return $sum;
            case 'writing':
            case 'reading':
            case 'listening':
                $sum = 0;
                foreach ($questionIds as $qid) {
                    $w = (float) (\App\Models\QuestionOption::where('question_id', $qid)
                        ->where('is_correct', true)
                        ->value('weight') ?? 0);
                    $sum += ($w > 0 ? $w : 10);
                }
                return $sum;
            default:
                $sum = 0;
                foreach ($questionIds as $qid) {
                    $w = (float) (\App\Models\QuestionOption::where('question_id', $qid)
                        ->where('is_correct', true)
                        ->value('weight') ?? 0);
                    $sum += ($w > 0 ? $w : 1);
                }
                return $sum;
        }
    }

    private function getDefaultPassingScore($type_subtest)
    {
        switch ($type_subtest) {
            case 'word':
            case 'excel':
            case 'ppt':
                return 70;
            case 'teknis':
            case 'social culture':
            case 'management':
            case 'interview':
                return 65;
            default:
                return 60;
        }
    }

    private function isToeflPassed(int $score): bool
    {
        return $score >= 217;
    }

    public function paymentSuccess()
    {
        // TEMPORARY: Auto-activate pending payments for development testing
        if (config('app.env') === 'local' || config('app.env') === 'production') {
            $this->activatePendingPayments(Auth::id());
        }

        return redirect()->route('user.package.riwayatPembelian')
            ->with('success', 'Pembayaran berhasil! Akses paket akan diaktifkan setelah konfirmasi.');
    }

    /**
     * TEMPORARY: Activate pending payments for development testing
     */
    private function activatePendingPayments($userId)
    {
        try {
            $pendingPayments = Payment::where('user_id', $userId)
                ->where('status', 'pending')
                ->where('created_at', '>', now()->subHours(2)) // Only payments from last 2 hours
                ->get();

            foreach ($pendingPayments as $payment) {
                // Update payment status
                $payment->update([
                    'status' => 'success',
                    'paid_at' => Carbon::now()
                ]);

                // Check if user already has access
                $existingAccess = UserPackageAcces::where('user_id', $payment->user_id)
                    ->where('package_id', $payment->package_id)
                    ->where('status', 'active')
                    ->where('end_date', '>', Carbon::now())
                    ->first();

                if (!$existingAccess) {
                    // Give user access to package
                    $userAccess = UserPackageAcces::create([
                        'user_id' => $payment->user_id,
                        'package_id' => $payment->package_id,
                        'start_date' => Carbon::now(),
                        'end_date' => Carbon::now()->addYear(),
                        'status' => 'active',
                        'payment_amount' => $payment->amount,
                        'payment_status' => 'paid',
                        'notes' => 'Auto-activated for development testing',
                        'created_by' => $payment->user_id
                    ]);
                }
            }
        } catch (\Exception $e) {
        }
    }

    public function paymentFailed()
    {
        return redirect()->route('user.package.index')
            ->with('error', 'Pembayaran gagal atau dibatalkan.');
    }

    // Webhook for Xendit payment callback
    public function xenditWebhook(Request $request)
    {

        $callbackToken = $request->header('X-CALLBACK-TOKEN');
        $expectedToken = config('services.xendit.webhook_token');

        if ($callbackToken !== $expectedToken) {
            return response()->json(['message' => 'Invalid callback token'], 401);
        }

        $payment = Payment::where('transaction_id', $request->external_id)->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if ($request->status === 'PAID') {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => Carbon::now()
            ]);

            $this->ensureUserPackageAccess($payment, 'Payment confirmed via Xendit - 1 year access');
        } elseif ($request->status === 'EXPIRED') {
            $payment->update(['status' => Payment::STATUS_EXPIRED]);
        } elseif ($request->status === 'FAILED') {
            $payment->update(['status' => Payment::STATUS_FAILED]);
        }

        return response()->json(['message' => 'OK']);
    }

    public function midtransWebhook(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');

        if (!$serverKey) {
            return response()->json(['message' => 'Midtrans server key tidak dikonfigurasi'], 500);
        }

        $orderId = (string) $request->input('order_id', '');
        $signature = (string) $request->input('signature_key', '');
        $statusCode = (string) $request->input('status_code', '');
        $grossAmount = (string) $request->input('gross_amount', '');
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if (!$signature || !hash_equals($expectedSignature, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        if (!$orderId) {
            return response()->json(['message' => 'Invalid payload'], 422);
        }

        $payment = Payment::where('transaction_id', $orderId)->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $transactionStatus = $request->input('transaction_status');
        $fraudStatus = $request->input('fraud_status');

        if (in_array($transactionStatus, ['capture', 'settlement'])) {
            if ($transactionStatus === 'capture' && $fraudStatus === 'challenge') {
                $payment->update(['status' => Payment::STATUS_PENDING]);
            } else {
                $payment->update([
                    'status' => Payment::STATUS_SUCCESS,
                    'paid_at' => Carbon::now(),
                ]);

                $this->ensureUserPackageAccess($payment, 'Payment confirmed via Midtrans - 1 year access');
            }
        } elseif ($transactionStatus === 'pending') {
            $payment->update(['status' => Payment::STATUS_PENDING]);
        } elseif ($transactionStatus === 'expire') {
            $payment->update(['status' => Payment::STATUS_EXPIRED]);
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'failure'])) {
            $payment->update(['status' => Payment::STATUS_FAILED]);
        }

        return response()->json(['message' => 'OK']);
    }

    private function ensureUserPackageAccess(Payment $payment, string $notes): void
    {
        $existingAccess = UserPackageAcces::where('user_id', $payment->user_id)
            ->where('package_id', $payment->package_id)
            ->where('status', 'active')
            ->where('end_date', '>', Carbon::now())
            ->first();

        if ($existingAccess) {
            return;
        }

        UserPackageAcces::create([
            'user_id' => $payment->user_id,
            'package_id' => $payment->package_id,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addYear(),
            'status' => 'active',
            'payment_amount' => $payment->amount,
            'payment_status' => 'paid',
            'notes' => $notes,
            'created_by' => $payment->user_id
        ]);
    }

    // Add method to manually check payment status (for testing)
    public function checkPaymentStatus($paymentId)
    {
        // Use 'role' instead of 'is_admin' based on migration
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $payment = Payment::findOrFail($paymentId);

        if (!$payment->payment_details && $payment->payment_method === 'xendit') {
            return response()->json(['error' => 'No payment details found']);
        }

        $paymentDetails = json_decode($payment->payment_details ?? '{}', true);

        if ($payment->payment_method === 'midtrans') {
            $orderId = $payment->transaction_id;
            if (!$orderId) {
                return response()->json(['error' => 'No order ID found']);
            }

            try {
                $serverKey = config('services.midtrans.server_key');

                if (!$serverKey) {
                    return response()->json(['error' => 'Midtrans server key is not configured']);
                }

                $statusUrl = rtrim(config('services.midtrans.status_url', 'https://api.sandbox.midtrans.com/v2'), '/');
                $response = Http::withHeaders([
                    'Authorization' => 'Basic ' . base64_encode($serverKey . ':'),
                    'Accept' => 'application/json',
                ])->get("{$statusUrl}/{$orderId}/status");

                if ($response->successful()) {
                    $midtransData = $response->json();

                    return response()->json([
                        'payment_status' => $payment->status,
                        'midtrans_status' => $midtransData['transaction_status'] ?? null,
                        'midtrans_data' => $midtransData,
                    ]);
                }

                return response()->json(['error' => 'Failed to fetch from Midtrans']);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }
        }

        $invoiceId = $paymentDetails['invoice_id'] ?? null;

        if (!$invoiceId) {
            return response()->json(['error' => 'No invoice ID found']);
        }

        try {
            $secretKey = config('services.xendit.secret_key');

            if (!$secretKey) {
                return response()->json(['error' => 'Xendit secret key is not configured']);
            }

            $baseUrl = rtrim(config('services.xendit.base_url', 'https://api.xendit.co'), '/');
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
            ])->get("{$baseUrl}/v2/invoices/{$invoiceId}");

            if ($response->successful()) {
                $invoiceData = $response->json();

                return response()->json([
                    'payment_status' => $payment->status,
                    'xendit_status' => $invoiceData['status'],
                    'xendit_data' => $invoiceData
                ]);
            }

            return response()->json(['error' => 'Failed to fetch from Xendit']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getUserActivePackagesForSidebar()
    {
        // Get user's active packages for sidebar
        $activePackages = UserPackageAcces::where('user_id', Auth::id())
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->with(['package' => function ($query) {
                $query->where('status', 'active');
            }])
            ->get()
            ->filter(function ($access) {
                return $access->package !== null;
            });

        return $activePackages;
    }

    // Add method for manual payment activation (admin only)
    public function manualActivatePayment(Request $request, $paymentId)
    {
        // Use 'role' instead of 'is_admin' based on migration
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Only admin can manually activate payments');
        }

        try {
            $payment = Payment::findOrFail($paymentId);

            if ($payment->status !== 'pending') {
                return response()->json([
                    'error' => 'Payment is not in pending status',
                    'current_status' => $payment->status
                ], 400);
            }

            // Update payment status
            $payment->update([
                'status' => 'success',
                'paid_at' => Carbon::now()
            ]);

            // Check if user already has access
            $existingAccess = UserPackageAcces::where('user_id', $payment->user_id)
                ->where('package_id', $payment->package_id)
                ->where('status', 'active')
                ->where('end_date', '>', Carbon::now())
                ->first();

            if (!$existingAccess) {
                // Give user access to package
                $userAccess = UserPackageAcces::create([
                    'user_id' => $payment->user_id,
                    'package_id' => $payment->package_id,
                    'start_date' => Carbon::now(),
                    'end_date' => Carbon::now()->addYear(),
                    'status' => 'active',
                    'payment_amount' => $payment->amount,
                    'payment_status' => 'paid',
                    'notes' => 'Manually activated by admin: ' . Auth::user()->name,
                    'created_by' => Auth::id()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment activated successfully',
                    'payment_id' => $payment->payment_id,
                    'access_id' => $userAccess->user_package_access_id
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment activated, user already had access',
                    'existing_access_id' => $existingAccess->user_package_access_id
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to activate payment',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function rankingTryout($id_package, $id_tryout)
    {
        $package = Package::findOrFail($id_package);

        // Check access - perbaiki query akses
        $hasAccess = UserPackageAcces::where('user_id', Auth::id())
            ->where('package_id', $id_package)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->exists();

        if (!$hasAccess) {
            return redirect()->route('user.package.index')
                ->with('error', 'Anda tidak memiliki akses ke paket ini');
        }

        $tryout = \App\Models\Tryout::findOrFail($id_tryout);

        // Get leaderboard for this tryout - ambil hasil terbaik per user
        $rankings = \App\Models\UserAnswer::where('tryout_id', $id_tryout)
            ->where('status', 'completed')
            ->with(['user', 'tryoutDetail'])
            ->get()
            ->groupBy('user_id')
            ->map(function ($userAnswers) use ($tryout) {
                $usesUtbkIrt = method_exists($tryout, 'requiresIrtScoring') && $tryout->requiresIrtScoring();

                if ($usesUtbkIrt) {
                    $attemptGroups = $userAnswers->groupBy('attempt_token');

                    $bestAttempt = $attemptGroups->map(function ($attempt) {
                        $representative = $attempt->first();
                        $score = (float) ($representative->utbk_total_score ?? 0);

                        return [
                            'user' => $representative->user,
                            'raw_score' => $score,
                            'max_score' => 1000,
                            'percentage' => $score / 10,
                            'finished_at' => $attempt->max('finished_at'),
                            'correct_answers' => $attempt->sum('correct_answers'),
                            'wrong_answers' => $attempt->sum('wrong_answers'),
                            'unanswered' => $attempt->sum('unanswered'),
                            'is_passed' => null,
                        ];
                    })->filter()->sortByDesc('raw_score')->values()->first();

                    return $bestAttempt;
                }

                // Untuk SKD Full, gabungkan skor dari semua subtest
                if ($userAnswers->count() > 1) {
                    // Group by attempt_token untuk mendapatkan attempt terbaik
                    $attemptGroups = $userAnswers->groupBy('attempt_token');

                    $bestAttempt = $attemptGroups->map(function ($attempt) use ($tryout) {
                        if ($tryout->is_toefl == 1) {
                            // For TOEFL, use the actual TOEFL total score
                            $toeflScore = $attempt->first()->toefl_total_score ?? $attempt->first()->score;

                            return [
                                'user' => $attempt->first()->user,
                                'raw_score' => $toeflScore,
                                'max_score' => 677, // TOEFL max score
                                'percentage' => $toeflScore,
                                'finished_at' => $attempt->max('finished_at'),
                                'correct_answers' => $attempt->sum('correct_answers'),
                                'wrong_answers' => $attempt->sum('wrong_answers'),
                                'unanswered' => $attempt->sum('unanswered'),
                                'is_passed' => $this->isToeflPassed((int) $toeflScore)
                            ];
                        } else {
                            // Regular scoring
                            $totalScore = 0;
                            $totalMaxScore = 0;
                            $allSubtestsPassed = true;

                            foreach ($attempt as $userAnswer) {
                                $subtestScore = $this->calculateTotalScore($userAnswer, $userAnswer->tryoutDetail->type_subtest);
                                $maxSubtestScore = $this->getMaxPossibleScoreForDetail(
                                    $userAnswer->tryout_detail_id,
                                    $userAnswer->tryoutDetail->type_subtest
                                );

                                $totalScore += $subtestScore;
                                $totalMaxScore += $maxSubtestScore;

                                $passingScore = $userAnswer->tryoutDetail->passing_score
                                    ?? $this->getDefaultPassingScore($userAnswer->tryoutDetail->type_subtest);

                                if (!is_null($passingScore) && $subtestScore < $passingScore) {
                                    $allSubtestsPassed = false;
                                }
                            }

                            $percentage = $totalMaxScore > 0 ? ($totalScore / $totalMaxScore) * 100 : 0;

                            return [
                                'user' => $attempt->first()->user,
                                'raw_score' => $totalScore,
                                'max_score' => $totalMaxScore,
                                'percentage' => $percentage,
                                'finished_at' => $attempt->max('finished_at'),
                                'correct_answers' => $attempt->sum('correct_answers'),
                                'wrong_answers' => $attempt->sum('wrong_answers'),
                                'unanswered' => $attempt->sum('unanswered'),
                                'is_passed' => $allSubtestsPassed
                            ];
                        }
                    })->filter()->sortByDesc('raw_score')->values()->first();

                    return $bestAttempt;
                } else {
                    // Single subtest - ambil yang terbaik
                    $bestAttempt = $userAnswers->sortByDesc(function ($answer) {
                        return $this->calculateTotalScore($answer, $answer->tryoutDetail->type_subtest);
                    })->first();

                    if ($tryout->is_toefl == 1) {
                        // For TOEFL single section (shouldn't happen but handle it)
                        $toeflScore = $bestAttempt->toefl_total_score ?? $bestAttempt->score;

                        return [
                            'user' => $bestAttempt->user,
                            'raw_score' => $toeflScore,
                            'max_score' => 677,
                            'percentage' => $toeflScore,
                            'finished_at' => $bestAttempt->finished_at,
                            'correct_answers' => $bestAttempt->correct_answers ?? 0,
                            'wrong_answers' => $bestAttempt->wrong_answers ?? 0,
                            'unanswered' => $bestAttempt->unanswered ?? 0,
                            'is_passed' => $this->isToeflPassed((int) $toeflScore)
                        ];
                    } else {
                        $rawScore = $this->calculateTotalScore($bestAttempt, $bestAttempt->tryoutDetail->type_subtest);
                        $maxScore = $this->getMaxPossibleScoreForDetail(
                            $bestAttempt->tryout_detail_id,
                            $bestAttempt->tryoutDetail->type_subtest
                        );
                        $percentage = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;
                        $passingScore = $bestAttempt->tryoutDetail->passing_score
                            ?? $this->getDefaultPassingScore($bestAttempt->tryoutDetail->type_subtest);
                        $isPassed = !is_null($passingScore) ? $rawScore >= $passingScore : $percentage >= 70;

                        return [
                            'user' => $bestAttempt->user,
                            'raw_score' => $rawScore,
                            'max_score' => $maxScore,
                            'percentage' => $percentage,
                            'finished_at' => $bestAttempt->finished_at,
                            'correct_answers' => $bestAttempt->correct_answers ?? 0,
                            'wrong_answers' => $bestAttempt->wrong_answers ?? 0,
                            'unanswered' => $bestAttempt->unanswered ?? 0,
                            'is_passed' => $isPassed
                        ];
                    }
                }
            })
            ->filter() // Remove null values
            ->sortByDesc('raw_score')
            ->values();

        return view('user.pages.package.tryout-rank', compact('package', 'tryout', 'rankings'));
    }

    public function pembahasanTryout($id_package, $id_tryout, $token)
    {
        $package = Package::findOrFail($id_package);
        $tryout = \App\Models\Tryout::findOrFail($id_tryout);

        // Check access
        $hasAccess = UserPackageAcces::where('user_id', Auth::id())
            ->where('package_id', $id_package)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->exists();

        if (!$hasAccess) {
            return redirect()->route('user.package.index')
                ->with('error', 'Anda tidak memiliki akses ke paket ini');
        }

        // Get user's latest completed answers for this tryout
        $userAnswers = \App\Models\UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->where('status', 'completed')
            ->where('attempt_token', $token)
            ->with(['tryout.tryoutDetails', 'userAnswerDetails.question.questionOptions', 'tryoutDetail'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($userAnswers->isEmpty()) {
            return redirect()->route('user.package.tryout', $id_package)
                ->with('error', 'Anda belum mengerjakan tryout ini');
        }

        // Group by attempt_token and get latest attempt
        $latestAttemptToken = $userAnswers->first()->attempt_token;
        $latestUserAnswers = $userAnswers->where('attempt_token', $latestAttemptToken);

        $tryoutDetails = $tryout->tryoutDetails;

        // Get all answer details with questions for pembahasan
        $allAnswerDetails = collect();
        foreach ($latestUserAnswers as $userAnswer) {
            $answerDetails = $userAnswer->userAnswerDetails()->with([
                'question.questionOptions',
                'questionOption'
            ])->get();

            foreach ($answerDetails as $detail) {
                $detail->subtest_type = $userAnswer->tryoutDetail->type_subtest;
                $detail->subtest_name = $this->getSubtestName($userAnswer->tryoutDetail->type_subtest);
            }

            $allAnswerDetails = $allAnswerDetails->concat($answerDetails);
        }

        // Calculate overall statistics
        $totalQuestions = $latestUserAnswers->sum(function ($ua) {
            return \App\Models\Question::where('tryout_detail_id', $ua->tryout_detail_id)->count();
        });
        $correctAnswers = $latestUserAnswers->sum('correct_answers');
        $wrongAnswers = $latestUserAnswers->sum('wrong_answers');
        $unanswered = $totalQuestions - $latestUserAnswers->sum(function ($ua) {
            return $ua->userAnswerDetails->count();
        });

        // Calculate total score
        if ($tryoutDetails->count() > 1) {
            // SKD Full calculation
            $totalScore = 0;
            $maxScore = 0;

            foreach ($latestUserAnswers as $userAnswer) {
                $subtestScore = $this->calculateTotalScore($userAnswer, $userAnswer->tryoutDetail->type_subtest);
                $maxSubtestScore = $this->getMaxPossibleScoreForDetail(
                    $userAnswer->tryout_detail_id,
                    $userAnswer->tryoutDetail->type_subtest
                );

                $totalScore += $subtestScore;
                $maxScore += $maxSubtestScore;
            }
        } else {
            // Single subtest
            $singleUserAnswer = $latestUserAnswers->first();
            $totalScore = $this->calculateTotalScore($singleUserAnswer, $singleUserAnswer->tryoutDetail->type_subtest);
            $maxScore = $this->getMaxPossibleScoreForDetail(
                $singleUserAnswer->tryout_detail_id,
                $singleUserAnswer->tryoutDetail->type_subtest
            );
        }

        $percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;
        $isPassed = $percentage >= 65; // SKD minimal 65%

        $overallStats = [
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'wrong_answers' => $wrongAnswers,
            'unanswered' => $unanswered,
            'total_score' => $totalScore,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'is_passed' => $isPassed
        ];

        $token = $token;
        return view('user.pages.package.tryout-pembahasan', compact(
            'package',
            'tryout',
            'tryoutDetails',
            'latestUserAnswers',
            'token',
            'allAnswerDetails',
            'overallStats'
        ));
    }

    private function getSubtestName($type)
    {
        switch ($type) {
            case 'twk':
                return 'Tes Wawasan Kebangsaan';
            case 'tiu':
                return 'Tes Intelegensi Umum';
            case 'tkp':
                return 'Tes Karakteristik Pribadi';
            case 'writing':
                return 'Writing Test';
            case 'reading':
                return 'Reading Comprehension';
            case 'listening':
                return 'Listening Test';
            case 'word':
                return 'Microsoft Word';
            case 'excel':
                return 'Microsoft Excel';
            case 'ppt':
                return 'Microsoft PowerPoint';
            default:
                return ucfirst($type);
        }
    }
}
