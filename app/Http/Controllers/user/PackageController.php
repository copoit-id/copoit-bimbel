<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Payment;
use App\Models\UserPackageAcces;
use App\Models\ClassModel;
use App\Models\MaterialProgressLog;
use App\Models\UserAnswer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ActivityLogger;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'paid'); // paid or free
        
        // Get user's owned package IDs (cast to int for consistent comparison)
        $userOwnedPackageIds = [];
        if (Auth::check()) {
            $userOwnedPackageIds = UserPackageAcces::where('user_id', Auth::id())
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', Carbon::now());
                })
                ->pluck('package_id')
                ->map(fn($id) => (int) $id)
                ->toArray();
        }
        
        if ($tab === 'free') {
            // Free packages (gratis)
            $packages = Package::where('status', 'active')
                ->whereIn('type_price', ['free_unconditional', 'free_conditional'])
                ->withCount(['materials', 'tryouts'])
                ->get();
        } else {
            // Paid packages (berbayar)
            $packages = Package::where('status', 'active')
                ->where('type_price', 'paid')
                ->withCount(['materials', 'tryouts'])
                ->get();
        }
        
        return view('user.pages.package.new-index', compact('packages', 'tab', 'userOwnedPackageIds'));
    }

    public function buyPackage(Request $request, $package_id)
    {
        try {
            $package = Package::findOrFail($package_id);

            $existingAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $package_id)
                ->first();

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

                    $paymentMode = strtolower((string) config('client.branding.payment_mode', 'gateway'));
                    if ($paymentMode === 'manual') {
                        $validated = $request->validate([
                            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
                        ], [
                            'payment_proof.required' => 'Bukti pembayaran wajib diunggah.',
                            'payment_proof.mimes' => 'Format bukti harus berupa JPG, PNG, atau PDF.',
                            'payment_proof.max' => 'Ukuran bukti maksimal 20MB.',
                        ]);

                        $proofPath = $validated['payment_proof']->store('payment-proofs', 'public');
                        $transactionId = 'MANUAL-' . $package->package_id . '-' . Auth::id() . '-' . time();

                        Payment::create([
                            'transaction_id' => $transactionId,
                            'user_id' => Auth::id(),
                            'package_id' => $package->package_id,
                            'amount' => $package->price,
                            'admin_fee' => 0,
                            'total_amount' => $package->price,
                            'status' => Payment::STATUS_PENDING,
                            'payment_method' => 'manual',
                            'payment_details' => json_encode([
                                'proof_path' => $proofPath,
                                'proof_name' => $validated['payment_proof']->getClientOriginalName(),
                            ]),
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Bukti pembayaran berhasil dikirim. Mohon tunggu verifikasi admin.'
                        ]);
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

        ActivityLogger::log('class_list_opened', 'success', Auth::user(), [
            'package_id' => $id_package,
        ]);

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
            ->with([
                'tryoutDetails.questions',
                'userAnswers' => function ($query) {
                    $query->where('user_id', Auth::id());
                }
            ])->get();

        ActivityLogger::log('tryout_list_opened', 'success', Auth::user(), [
            'package_id' => $id_package,
        ]);

        return view('user.pages.package.tryout', compact('package', 'tryouts'));
    }

    public function openClassZoom(ClassModel $class)
    {
        $packageIds = $class->packages()->pluck('packages.package_id')->all();
        if (!empty($class->package_id)) {
            $packageIds[] = $class->package_id;
        }
        $packageIds = array_unique($packageIds);

        $hasAccess = UserPackageAcces::where('user_id', Auth::id())
            ->whereIn('package_id', $packageIds)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->exists();

        if (!$hasAccess) {
            return redirect()->route('user.package.index')
                ->with('error', 'Anda tidak memiliki akses ke kelas ini');
        }

        ActivityLogger::log('class_zoom_opened', 'success', Auth::user(), [
            'class_id' => $class->class_id,
            'package_ids' => $packageIds,
        ]);

        if (empty($class->zoom_link)) {
            return redirect()->back()->with('error', 'Link zoom tidak tersedia.');
        }

        return redirect()->away($class->zoom_link);
    }

    public function openClassMaterial(ClassModel $class)
    {
        $packageIds = $class->packages()->pluck('packages.package_id')->all();
        if (!empty($class->package_id)) {
            $packageIds[] = $class->package_id;
        }
        $packageIds = array_unique($packageIds);

        $hasAccess = UserPackageAcces::where('user_id', Auth::id())
            ->whereIn('package_id', $packageIds)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->exists();

        if (!$hasAccess) {
            return redirect()->route('user.package.index')
                ->with('error', 'Anda tidak memiliki akses ke materi ini');
        }

        ActivityLogger::log('class_material_opened', 'success', Auth::user(), [
            'class_id' => $class->class_id,
            'package_ids' => $packageIds,
        ]);

        if (empty($class->drive_link)) {
            return redirect()->back()->with('error', 'Link materi tidak tersedia.');
        }

        return redirect()->away($class->drive_link);
    }

    public function riwayatTryout($id_package, $id_tryout)
    {
        $package = Package::findOrFail($id_package);
        $tryout = \App\Models\Tryout::with('tryoutDetails')->findOrFail($id_tryout);

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

            if ($tryout->requiresIrtScoring()) {
                $userAnswers->loadMissing(['userAnswerDetails', 'tryoutDetail']);
                $questionCounts = \App\Models\Question::whereIn('tryout_detail_id', $userAnswers->pluck('tryout_detail_id'))
                    ->select('tryout_detail_id', \DB::raw('count(*) as total'))
                    ->groupBy('tryout_detail_id')
                    ->pluck('total', 'tryout_detail_id');

                $totalCorrect = 0;
                $totalWrong = 0;
                $totalUnanswered = 0;
                $isPassed = $userAnswers->every(function ($ua) {
                    return $this->isUtbkSubtestPassed($ua->tryoutDetail, (float) ($ua->score ?? 0));
                });

                foreach ($userAnswers as $ua) {
                    $answeredCount = $ua->userAnswerDetails->count();
                    $correctCount = $ua->userAnswerDetails->where('is_correct', true)->count();
                    $totalQuestions = (int) ($questionCounts[$ua->tryout_detail_id] ?? 0);

                    $totalCorrect += $correctCount;
                    $totalWrong += max(0, $answeredCount - $correctCount);
                    $totalUnanswered += max(0, $totalQuestions - $answeredCount);
                }

                $totalScore = (float) ($firstAnswer->utbk_total_score ?? 0);
                $finalPercentage = $totalScore / 10;
            } else
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
                    $isPassed = $this->isAttemptPassed($userAnswers, $tryout->tryoutDetails->count());
                } else {
                    // Single subtest
                    $singleAnswer = $userAnswers->first();
                    $rawScore = $this->calculateTotalScore($singleAnswer, $singleAnswer->tryoutDetail->type_subtest);
                    $maxScore = $this->getMaxPossibleScoreForDetail(
                        $singleAnswer->tryout_detail_id,
                        $singleAnswer->tryoutDetail->type_subtest
                    );
                    $finalPercentage = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;
                    $isPassed = $this->isAttemptPassed($userAnswers, 1);
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
                'score' => $userAnswers->count() > 1 ? round($totalScore, 0) : round($rawScore, 0),
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
            $question = $detail->question;
            if (!$question) {
                continue;
            }

            $questionType = $question->question_type ?? 'multiple_choice';
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
            $pendingReview = (bool) ($answerMeta['pending_review'] ?? false);

            switch ($questionType) {
                case 'multiple_answer':
                    $totalScore += $this->resolveMultipleAnswerAwardedScore($question, $detail);
                    break;
                case 'multiple_true_false':
                    $totalScore += $this->resolveMultipleTrueFalseAwardedScore($question, $detail);
                    break;

                case 'matching':
                    $totalScore += $this->resolveMatchingAwardedScore($question, $detail);
                    break;

                case 'short_answer':
                case 'essay':
                    if ($pendingReview) {
                        continue 2;
                    }
                    // Gunakan score_obtained dari answer_json (hasil koreksi AI/manual)
                    $scoreObtained = isset($answerMeta['score_obtained']) ? (float) $answerMeta['score_obtained'] : null;
                    if ($scoreObtained !== null) {
                        $totalScore += $scoreObtained;
                    } else {
                        // Fallback: gunakan essay_score_correct atau default_weight
                        $weight = (float) ($question->getEssayScoreCorrect() ?? $question->default_weight ?? 1);
                        $totalScore += $detail->is_correct ? ($weight > 0 ? $weight : 1) : 0;
                    }
                    break;

                case 'audio':
                    continue 2;

                default:
                    if ($detail->questionOption) {
                        switch ($type_subtest) {
                            case 'twk':
                            case 'tiu':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 5) : 0;
                                break;
                            case 'tkp':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $w > 0 ? $w : 1;
                                break;
                            case 'writing':
                            case 'reading':
                            case 'listening':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 10) : 0;
                                break;
                            default:
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 1) : 0;
                                break;
                        }
                    }
                    break;
            }
        }

        return $totalScore;
    }

    private function resolveMultipleAnswerAwardedScore($question, $detail): float
    {
        $defaultWeight = (float) ($question->default_weight ?? 1);
        $maxWeight = $defaultWeight > 0 ? $defaultWeight : 1;
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $selectedIds = collect($meta['selected_option_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (!empty($selectedIds)) {
            $correctIds = $question->questionOptions()
                ->where('is_correct', true)
                ->pluck('question_option_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            sort($selectedIds);
            sort($correctIds);
            $multipleAnswerMeta = is_array($question->metadata) ? ($question->metadata['multiple_answer'] ?? []) : [];
            $matchedCorrect = count(array_intersect($selectedIds, $correctIds));
            $wrongSelected = max(0, count($selectedIds) - $matchedCorrect);
            $scoreCorrect = (float) ($multipleAnswerMeta['score_correct'] ?? (($maxWeight > 0 && count($correctIds) > 0) ? ($maxWeight / count($correctIds)) : 1));
            $scoreWrong = (float) ($multipleAnswerMeta['score_wrong'] ?? 0);
            $scoringMode = in_array(($multipleAnswerMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
                ? $multipleAnswerMeta['scoring_mode']
                : 'fullscore';
            $totalCorrectCount = max(1, count($correctIds));
            $missedCorrect = max(0, $totalCorrectCount - $matchedCorrect);
            $wrongCount = $missedCorrect + $wrongSelected;
            $isExactCorrect = ($selectedIds === $correctIds);
            $fullScore = $scoreCorrect;
            $score = 0.0;

            if ($scoringMode === 'partial') {
                $score = $matchedCorrect > 0
                    ? ($matchedCorrect / $totalCorrectCount) * $fullScore
                    : $scoreWrong;
            } else {
                $score = $isExactCorrect ? $scoreCorrect : $scoreWrong;
            }

            return max(0, $score);
        }

        $storedScore = $meta['score_obtained'] ?? null;
        if (is_numeric($storedScore)) {
            return max(0, min((float) $storedScore, $maxWeight));
        }

        return $detail->is_correct ? $maxWeight : 0;
    }

    private function resolveMatchingAwardedScore($question, $detail): float
    {
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $questionMeta = is_array($question->metadata) ? $question->metadata : [];
        $matchingMeta = is_array($questionMeta['matching_scores'] ?? null) ? $questionMeta['matching_scores'] : [];
        $scoreCorrect = (float) ($matchingMeta['score_correct'] ?? 1);
        $scoreWrong = (float) ($matchingMeta['score_wrong'] ?? 0);
        $scoringMode = in_array(($matchingMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $matchingMeta['scoring_mode']
            : 'fullscore';

        $summary = is_array($meta['summary'] ?? null) ? $meta['summary'] : [];
        $correctCount = (int) ($summary['correct'] ?? 0);
        $totalCount = (int) ($summary['total'] ?? 0);
        $wrongCount = max(0, $totalCount - $correctCount);

        if ($totalCount > 0) {
            $fullScore = max(0, $scoreCorrect);
            $isExactCorrect = ($correctCount === $totalCount);
            $score = 0.0;
            if ($scoringMode === 'partial') {
                $score = $correctCount > 0
                    ? ($correctCount / $totalCount) * $fullScore
                    : $scoreWrong;
            } else {
                $score = $isExactCorrect ? $fullScore : $scoreWrong;
            }

            return max(0, $score);
        }

        $storedScore = $meta['score_obtained'] ?? null;
        if (is_numeric($storedScore)) {
            return max(0, (float) $storedScore);
        }

        $weight = (float) ($question->default_weight ?? 1);
        return $detail->is_correct ? max(0, $weight) : 0;
    }

    private function resolveMultipleTrueFalseAwardedScore($question, $detail): float
    {
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $questionMeta = is_array($question->metadata) ? ($question->metadata['multiple_true_false'] ?? []) : [];
        $scoreCorrect = (float) ($questionMeta['score_correct'] ?? ($question->default_weight ?? 1));
        $scoreWrong = (float) ($questionMeta['score_wrong'] ?? 0);
        $scoringMode = in_array(($questionMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $questionMeta['scoring_mode']
            : 'fullscore';

        $summary = is_array($meta['summary'] ?? null) ? $meta['summary'] : [];
        $correctCount = (int) ($summary['correct'] ?? 0);
        $totalCount = (int) ($summary['total'] ?? 0);

        if ($totalCount > 0) {
            $fullScore = max(0, $scoreCorrect);
            $isExactCorrect = ($correctCount === $totalCount);
            if ($scoringMode === 'partial') {
                return max(0, $correctCount > 0 ? ($correctCount / $totalCount) * $fullScore : $scoreWrong);
            }

            return max(0, $isExactCorrect ? $fullScore : $scoreWrong);
        }

        $storedScore = $meta['score_obtained'] ?? null;
        if (is_numeric($storedScore)) {
            return max(0, (float) $storedScore);
        }

        $weight = (float) ($question->default_weight ?? 1);
        return $detail->is_correct ? max(0, $weight) : 0;
    }

    private function isUtbkSubtestPassed($detail, float $score): bool
    {
        if (!$detail) {
            return false;
        }

        $passingScore = $detail->passing_score;
        if ($passingScore === null) {
            return false;
        }

        $passingType = $detail->passing_type ?? 'score';
        if ($passingType === 'percentage') {
            $percentage = ($score / 1000) * 100;
            return $percentage >= $passingScore;
        }

        return $score >= $passingScore;
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
        $questions = \App\Models\Question::where('tryout_detail_id', $tryoutDetailId)
            ->with('questionOptions')
            ->get();

        if ($questions->isEmpty()) {
            return 0;
        }

        $total = 0;

        foreach ($questions as $question) {
            $questionType = $question->question_type ?? 'multiple_choice';

            switch ($questionType) {
                case 'multiple_answer':
                    $weight = (float) ($question->default_weight ?? 1);
                    $total += $weight > 0 ? $weight : 1;
                    break;
                case 'multiple_true_false':
                    $mtfMeta = is_array($question->metadata['multiple_true_false'] ?? null) ? $question->metadata['multiple_true_false'] : [];
                    $weight = (float) ($mtfMeta['score_correct'] ?? ($question->default_weight ?? 0));
                    $total += $weight > 0 ? $weight : 1;
                    break;

                case 'matching':
                    $matchingMeta = is_array($question->metadata['matching_scores'] ?? null) ? $question->metadata['matching_scores'] : [];
                    $weight = (float) ($matchingMeta['score_correct'] ?? ($question->default_weight ?? 0));
                    if ($weight <= 0) {
                        $weight = 1;
                    }
                    if ($type_subtest === 'tkp') {
                        $weight = $weight > 0 ? $weight : 1;
                    }
                    $total += $weight;
                    break;

                case 'short_answer':
                case 'essay':
                    // Gunakan essay_score_correct (field "Benar") untuk max score
                    $weight = (float) ($question->getEssayScoreCorrect() ?? $question->default_weight ?? 1);
                    $total += $weight > 0 ? $weight : 1;
                    break;

                case 'audio':
                    break;

                default:
                    $options = $question->questionOptions;
                    switch ($type_subtest) {
                        case 'tkp':
                            $maxWeight = (float) ($options->max('weight') ?? 0);
                            $total += $maxWeight > 0 ? $maxWeight : 1;
                            break;
                        case 'twk':
                        case 'tiu':
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 5;
                            break;
                        case 'writing':
                        case 'reading':
                        case 'listening':
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 10;
                            break;
                        default:
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 1;
                            break;
                    }
                    break;
            }
        }

        return $total;
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

    private function isSubtestPassed($detail, float $rawScore, float $maxScore, string $type): bool
    {
        $passingScore = $detail?->passing_score ?? $this->getDefaultPassingScore($type);
        if (is_null($passingScore)) {
            return false;
        }

        $passingType = $detail?->passing_type ?? 'score';
        if ($passingType === 'percentage') {
            $percentage = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;
            return $percentage >= $passingScore;
        }

        return $rawScore >= $passingScore;
    }

    private function isToeflPassed(int $score): bool
    {
        return $score >= 217;
    }

    private function isAttemptPassed($userAnswers, int $expectedSubtests = 0): bool
    {
        if ($expectedSubtests > 0 && $userAnswers->count() < $expectedSubtests) {
            return false;
        }

        return $userAnswers->every(function ($userAnswer) {
            $detail = $userAnswer->tryoutDetail;
            $type = $detail->type_subtest;
            $rawScore = $this->calculateTotalScore($userAnswer, $type);
            $maxScore = $this->getMaxPossibleScoreForDetail($userAnswer->tryout_detail_id, $type);

            return $this->isSubtestPassed($detail, $rawScore, $maxScore, $type);
        });
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
        if (!Auth::user()->isAdmin()) {
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
            ->with([
                'package' => function ($query) {
                    $query->where('status', 'active');
                }
            ])
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
        if (!Auth::user()->isAdmin()) {
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

        $tryout = \App\Models\Tryout::with('tryoutDetails')->findOrFail($id_tryout);

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
                        $attempt->loadMissing('tryoutDetail');
                        $allPassed = $attempt->every(function ($ua) {
                            return $this->isUtbkSubtestPassed($ua->tryoutDetail, (float) ($ua->score ?? 0));
                        });

                        return [
                            'user' => $representative->user,
                            'raw_score' => $score,
                            'max_score' => 1000,
                            'percentage' => $score / 10,
                            'finished_at' => $attempt->max('finished_at'),
                            'correct_answers' => $attempt->sum('correct_answers'),
                            'wrong_answers' => $attempt->sum('wrong_answers'),
                            'unanswered' => $attempt->sum('unanswered'),
                            'is_passed' => $allPassed,
                        ];
                    })->filter()->sortByDesc('raw_score')->values()->first();

                    return $bestAttempt;
                }

                // Gabungkan skor dari semua subtest dengan group_by attempt_token
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
                            'started_at' => $attempt->min('started_at'),
                            'correct_answers' => $attempt->sum('correct_answers'),
                            'wrong_answers' => $attempt->sum('wrong_answers'),
                            'unanswered' => $attempt->sum('unanswered'),
                            'is_passed' => $this->isToeflPassed((int) $toeflScore),
                            'subtest_scores' => []
                        ];
                    } else {
                        // Regular scoring
                        $totalScore = 0;
                        $totalMaxScore = 0;
                        $allSubtestsPassed = true;
                        $subtestScores = [];

                        foreach ($attempt as $userAnswer) {
                            $subtestScore = $this->calculateTotalScore($userAnswer, $userAnswer->tryoutDetail->type_subtest);
                            $maxSubtestScore = $this->getMaxPossibleScoreForDetail(
                                $userAnswer->tryout_detail_id,
                                $userAnswer->tryoutDetail->type_subtest
                            );

                            $totalScore += $subtestScore;
                            $totalMaxScore += $maxSubtestScore;
                            $subtestScores[$userAnswer->tryout_detail_id] = $subtestScore;

                            $detail = $userAnswer->tryoutDetail;
                            if (!$this->isSubtestPassed($detail, $subtestScore, $maxSubtestScore, $detail->type_subtest)) {
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
                            'started_at' => $attempt->min('started_at'),
                            'correct_answers' => $attempt->sum('correct_answers'),
                            'wrong_answers' => $attempt->sum('wrong_answers'),
                            'unanswered' => $attempt->sum('unanswered'),
                            'is_passed' => $allSubtestsPassed,
                            'subtest_scores' => $subtestScores
                        ];
                    }
                })->filter()->sortByDesc('raw_score')->values()->first();

                return $bestAttempt;
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

        $pendingReviewCount = $allAnswerDetails->filter(function ($detail) {
            $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
            return !empty($meta['pending_review']);
        })->count();

        $latestUserAnswers->loadMissing(['userAnswerDetails', 'tryoutDetail']);
        $questionCounts = \App\Models\Question::whereIn('tryout_detail_id', $latestUserAnswers->pluck('tryout_detail_id'))
            ->select('tryout_detail_id', \DB::raw('count(*) as total'))
            ->groupBy('tryout_detail_id')
            ->pluck('total', 'tryout_detail_id');

        if ($tryout->requiresIrtScoring()) {
            $totalQuestions = $questionCounts->sum();
            $answeredCount = $latestUserAnswers->sum(fn($ua) => $ua->userAnswerDetails->count());
            $correctAnswers = $latestUserAnswers->sum(fn($ua) => $ua->userAnswerDetails->where('is_correct', true)->count());
            $wrongAnswers = max(0, $answeredCount - $correctAnswers);
            $unanswered = max(0, $totalQuestions - $answeredCount);

            $totalScore = (float) ($latestUserAnswers->first()->utbk_total_score ?? 0);
            $maxScore = 1000;
            $percentage = $totalScore / 10;
            $isPassed = $latestUserAnswers->every(function ($ua) {
                return $this->isUtbkSubtestPassed($ua->tryoutDetail, (float) ($ua->score ?? 0));
            });
        } else {
            // Calculate overall statistics
            $totalQuestions = $latestUserAnswers->sum(function ($ua) use ($questionCounts) {
                return (int) ($questionCounts[$ua->tryout_detail_id] ?? 0);
            });
            $answeredCount = 0;
            $correctAnswers = 0;
            $wrongAnswers = 0;
            foreach ($latestUserAnswers as $userAnswer) {
                $details = $userAnswer->userAnswerDetails;
                $answeredCount += $details->count();
                foreach ($details as $detail) {
                    $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
                    if (!empty($meta['pending_review'])) {
                        continue;
                    }

                    if ($detail->is_correct) {
                        $correctAnswers++;
                    } else {
                        $wrongAnswers++;
                    }
                }
            }
            $unanswered = max(0, $totalQuestions - $answeredCount);

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
            $isPassed = $this->isAttemptPassed($latestUserAnswers, $tryoutDetails->count());
        }

        $overallStats = [
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'wrong_answers' => $wrongAnswers,
            'unanswered' => $unanswered,
            'pending_review' => $pendingReviewCount,
            'total_score' => $totalScore,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'is_passed' => $isPassed
        ];

        $subtestSummaries = [];
        if ($tryoutDetails->count() > 1) {
            if ($tryout->requiresIrtScoring()) {
                $subtestSummaries = $latestUserAnswers->map(function ($userAnswer) {
                    $detail = $userAnswer->tryoutDetail;
                    $type = $detail->type_subtest;
                    $score = (float) ($userAnswer->score ?? 0);
                    $max = 1000;
                    $percentage = $score / 10;
                    $passingScore = $detail->passing_score ?? null;
                    $passingType = $detail->passing_type ?? 'score';

                    return [
                        'type' => $type,
                        'name' => $this->getSubtestName($type),
                        'score' => $score,
                        'max_score' => $max,
                        'percentage' => $percentage,
                        'passing_score' => $passingScore,
                        'passing_type' => $passingType,
                        'passing_percentage' => $passingType === 'percentage' ? $passingScore : null,
                        'is_passed' => $this->isUtbkSubtestPassed($detail, $score),
                        'correct_answers' => $userAnswer->userAnswerDetails->where('is_correct', true)->count(),
                        'wrong_answers' => max(0, $userAnswer->userAnswerDetails->count() - $userAnswer->userAnswerDetails->where('is_correct', true)->count()),
                    ];
                })->values();
            } else {
                $subtestSummaries = $latestUserAnswers->map(function ($userAnswer) {
                    $type = $userAnswer->tryoutDetail->type_subtest;
                    $score = $this->calculateTotalScore($userAnswer, $type);
                    $max = $this->getMaxPossibleScoreForDetail($userAnswer->tryout_detail_id, $type);
                    $percentage = $max > 0 ? ($score / $max) * 100 : 0;
                    $detail = $userAnswer->tryoutDetail;
                    $passingScore = $detail->passing_score ?? $this->getDefaultPassingScore($type);
                    $passingType = $detail->passing_type ?? 'score';
                    $passingPercentage = $passingType === 'percentage'
                        ? $passingScore
                        : ($max > 0 ? ($passingScore / $max) * 100 : null);
                    $correctCount = $userAnswer->userAnswerDetails->filter(function ($detail) {
                        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
                        return empty($meta['pending_review']) && $detail->is_correct;
                    })->count();
                    $wrongCount = $userAnswer->userAnswerDetails->filter(function ($detail) {
                        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
                        return empty($meta['pending_review']) && !$detail->is_correct;
                    })->count();

                    return [
                        'type' => $type,
                        'name' => $this->getSubtestName($type),
                        'score' => $score,
                        'max_score' => $max,
                        'percentage' => $percentage,
                        'passing_score' => $passingScore,
                        'passing_type' => $passingType,
                        'passing_percentage' => $passingPercentage,
                        'is_passed' => $this->isSubtestPassed($detail, $score, $max, $type),
                        'correct_answers' => $correctCount,
                        'wrong_answers' => $wrongCount,
                    ];
                })->values();
            }
        }

        $token = $token;
        return view('user.pages.package.tryout-pembahasan', compact(
            'package',
            'tryout',
            'tryoutDetails',
            'latestUserAnswers',
            'token',
            'allAnswerDetails',
            'overallStats',
            'subtestSummaries'
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

    /**
     * Display user's active packages with step by step view
     */
    public function myPackages()
    {
        $user = Auth::user();
        
        $activePackages = UserPackageAcces::where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->with('package')
            ->get();
        
        return view('user.pages.package.new-my-packages', compact('activePackages'));
    }
    
    /**
     * Show package roadmap (new gamified view)
     */
    public function showPackage($packageId)
    {
        $user = Auth::user();
        $package = Package::with(['materials', 'tryouts'])->findOrFail($packageId);
        
        // Check access
        $hasAccess = UserPackageAcces::where('user_id', $user->id)
            ->where('package_id', $packageId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->exists();
        
        if (!$hasAccess) {
            return redirect()->route('user.package.my')
                ->with('error', 'Anda tidak memiliki akses ke paket ini.');
        }
        
        // Prepare roadmap items with status
        $roadmapItems = collect();
        $completedCount = 0;
        $orderCounter = 1;
        
        // Process materials first (sorted by pivot order)
        $sortedMaterials = $package->materials->sortBy(function($material) {
            return $material->pivot->order_number ?? 0;
        });
        
        foreach ($sortedMaterials as $material) {
            $progress = MaterialProgressLog::where('user_id', $user->id)
                ->where('material_id', $material->material_id)
                ->first();
            $isCompleted = $progress && $progress->is_completed;
            $isInProgress = $progress && !$progress->is_completed;
            $itemProgress = $progress ? ($progress->progress_percent ?? 0) : 0;
            
            if ($isCompleted) {
                $completedCount++;
            }
            
            $roadmapItems->push([
                'order' => $orderCounter,
                'type' => 'material',
                'title' => $material->title,
                'subtitle' => $material->category?->name ?? 'Materi Belajar',
                'icon' => $material->type === 'video' ? 'ri-video-line' : ($material->type === 'document' ? 'ri-file-text-line' : 'ri-live-line'),
                'route' => route('user.material.show', $material->material_id),
                'is_completed' => $isCompleted,
                'is_in_progress' => $isInProgress,
                'progress_percent' => $itemProgress,
                'status_text' => $isCompleted ? 'Selesai' : ($isInProgress ? 'Berlangsung' : 'Mulai'),
                'is_left' => $orderCounter % 2 === 1,
            ]);
            
            $orderCounter++;
        }
        
        // Process tryouts (append at the end)
        foreach ($package->tryouts as $tryout) {
            $attempt = UserAnswer::where('user_id', $user->id)
                ->where('tryout_id', $tryout->tryout_id)
                ->where('status', 'completed')
                ->first();
            $isCompleted = $attempt !== null;
            
            if ($isCompleted) {
                $completedCount++;
            }
            
            $roadmapItems->push([
                'order' => $orderCounter,
                'type' => 'tryout',
                'title' => $tryout->name,
                'subtitle' => 'Tryout Latihan',
                'icon' => 'ri-file-list-3-line',
                'route' => route('user.tryout.lobby', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]),
                'is_completed' => $isCompleted,
                'is_in_progress' => false,
                'progress_percent' => 0,
                'status_text' => $isCompleted ? 'Selesai' : 'Mulai',
                'is_left' => $orderCounter % 2 === 1,
            ]);
            
            $orderCounter++;
        }
        
        $totalItems = $roadmapItems->count();
        $progressPercent = $totalItems > 0 ? round(($completedCount / $totalItems) * 100) : 0;
        
        // Find next item to start
        $nextItem = $roadmapItems->first(fn($item) => !$item['is_completed']) ?? $roadmapItems->first();
        
        return view('user.pages.package.roadmap', compact(
            'package',
            'roadmapItems',
            'completedCount',
            'totalItems',
            'progressPercent',
            'nextItem'
        ));
    }

    /**
     * List all tryouts - tampilkan SEMUA dengan status akses user
     */
    public function listTryout()
    {
        $user = Auth::user();
        
        // Get packages that user has access to
        $accessiblePackageIds = $user->userPackageAccess()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->pluck('package_id')
            ->toArray();
        
        // Get ALL active tryouts with their packages
        $tryouts = \App\Models\Tryout::with(['tryoutDetails', 'packages', 'userAnswers' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }])
        ->where('is_active', true)
        ->get();
        
        // Mark each tryout with access status
        foreach ($tryouts as $tryout) {
            $tryoutPackageIds = $tryout->packages->pluck('package_id')->toArray();
            $tryout->has_access = !empty(array_intersect($tryoutPackageIds, $accessiblePackageIds));
            $tryout->access_via_package = $tryout->packages->first();
        }
        
        return view('user.pages.tryout.new-list', compact('tryouts', 'accessiblePackageIds'));
    }
}
