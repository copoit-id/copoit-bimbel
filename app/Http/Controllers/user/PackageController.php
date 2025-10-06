<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Payment;
use App\Models\UserPackageAcces;
use App\Models\ClassModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    public function index()
    {
        // Get packages by type with user access information - HANYA PAKET BERBAYAR
        $kelasPackages = Package::where('type_package', 'bimbel')
            ->where('status', 'active')
            ->where('price', '>', 0) // Hanya paket berbayar
            ->withCount(['userAccess' => function ($query) {
                $query->where('user_id', Auth::id())
                    ->where('status', 'active')
                    ->where('end_date', '>', Carbon::now());
            }])
            ->get();

        $tryoutPackages = Package::where('type_package', 'tryout')
            ->where('status', 'active')
            ->where('price', '>', 0) // Hanya paket berbayar
            ->withCount(['userAccess' => function ($query) {
                $query->where('user_id', Auth::id())
                    ->where('status', 'active')
                    ->where('end_date', '>', Carbon::now());
            }])
            ->get();

        $sertifikasiPackages = Package::where('type_package', 'sertifikasi')
            ->where('status', 'active')
            ->where('price', '>', 0) // Hanya paket berbayar
            ->withCount(['userAccess' => function ($query) {
                $query->where('user_id', Auth::id())
                    ->where('status', 'active')
                    ->where('end_date', '>', Carbon::now());
            }])
            ->get();

        return view('user.pages.package.index', compact(
            'kelasPackages',
            'tryoutPackages',
            'sertifikasiPackages'
        ));
    }

    public function buyPackage(Request $request, $package_id)
    {
        try {
            $package = Package::findOrFail($package_id);

            // Check if user already has active access
            $existingAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $package_id)
                ->where('status', 'active')
                ->where('end_date', '>', Carbon::now())
                ->first();

            if ($existingAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah memiliki akses aktif ke paket ini'
                ], 400);
            }

            // If package is free, give direct access
            if ($package->price == 0) {
                UserPackageAcces::create([
                    'user_id' => Auth::id(),
                    'package_id' => $package_id,
                    'start_date' => Carbon::now(),
                    'end_date' => Carbon::now()->addDays(30), // Default 30 days for free packages
                    'status' => 'active',
                    'payment_amount' => 0,
                    'payment_status' => 'free',
                    'notes' => 'Free package access',
                    'created_by' => Auth::id()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Paket gratis berhasil diaktifkan!'
                ]);
            }

            // Create payment for paid packages using Xendit
            $paymentResponse = $this->createXenditPayment($package);

            if ($paymentResponse['success']) {
                return response()->json([
                    'success' => true,
                    'redirect_url' => $paymentResponse['invoice_url']
                ]);
            } else {
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

    private function createXenditPayment($package)
    {
        // Pastikan Xendit secret key tersedia
        if (!config('services.xendit.secret_key')) {
            return [
                'success' => false,
                'message' => 'Xendit secret key tidak dikonfigurasi'
            ];
        }

        $transactionId = 'PKG-' . $package->package_id . '-' . Auth::id() . '-' . time();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode(config('services.xendit.secret_key') . ':'),
                'Content-Type' => 'application/json',
            ])->post('https://api.xendit.co/v2/invoices', [
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
                    'status' => 'pending',
                    'payment_method' => 'xendit',
                    'payment_details' => json_encode([
                        'invoice_id' => $invoiceData['id'],
                        'invoice_url' => $invoiceData['invoice_url'],
                        'external_id' => $transactionId
                    ]),
                ]);

                return [
                    'success' => true,
                    'invoice_url' => $invoiceData['invoice_url']
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
            // Update payment status to success
            $payment->update([
                'status' => 'success',
                'paid_at' => Carbon::now()
            ]);

            // Check if user already has access to prevent duplicate entries
            $existingAccess = UserPackageAcces::where('user_id', $payment->user_id)
                ->where('package_id', $payment->package_id)
                ->where('status', 'active')
                ->where('end_date', '>', Carbon::now())
                ->first();

            if (!$existingAccess) {
                // Give user access to package for 1 year
                $userAccess = UserPackageAcces::create([
                    'user_id' => $payment->user_id,
                    'package_id' => $payment->package_id,
                    'start_date' => Carbon::now(),
                    'end_date' => Carbon::now()->addYear(), // 1 year access
                    'status' => 'active',
                    'payment_amount' => $payment->amount,
                    'payment_status' => 'paid',
                    'notes' => 'Payment confirmed via Xendit - 1 year access',
                    'created_by' => $payment->user_id
                ]);
            }
        } elseif ($request->status === 'EXPIRED') {
            $payment->update(['status' => 'expired']);
        } elseif ($request->status === 'FAILED') {
            $payment->update(['status' => 'failed']);
        }

        return response()->json(['message' => 'OK']);
    }

    // Add method to manually check payment status (for testing)
    public function checkPaymentStatus($paymentId)
    {
        // Use 'role' instead of 'is_admin' based on migration
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $payment = Payment::findOrFail($paymentId);

        if (!$payment->payment_details) {
            return response()->json(['error' => 'No payment details found']);
        }

        $paymentDetails = json_decode($payment->payment_details, true);
        $invoiceId = $paymentDetails['invoice_id'] ?? null;

        if (!$invoiceId) {
            return response()->json(['error' => 'No invoice ID found']);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode(config('services.xendit.secret_key') . ':'),
            ])->get("https://api.xendit.co/v2/invoices/{$invoiceId}");

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
