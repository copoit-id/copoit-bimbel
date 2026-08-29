@extends('user.layout.new-user')
@section('title', 'Pembahasan Tryout')
@section('content')
<div class="package-bimbel flex flex-col gap-4 pb-28">
    @php
        $formatScore = function ($value) {
            return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        };
        $showPassingGrade = $tryout->shouldShowPassingGrade();
        $showScoreMaximum = $tryout->shouldShowScoreMaximum();
        $primaryColor = $clientBranding['primary_color'] ?? '#10b981';
        $aiDiscussionEnabled = (bool) ($clientBranding['ai_discussion_feature_enabled'] ?? false)
            && (bool) data_get($clientBranding, 'ai_discussion_settings.enabled', false);
        $packageRouteId = $packageRouteId ?? ($package->package_id ?? 'free');
        $aiDiscussionEndpointUrl = route('user.package.tryout.pembahasan.ai-chat', [$packageRouteId, $tryout->tryout_id, $token]);
        $aiLearningToolEndpointUrl = route('user.package.tryout.pembahasan.ai-tools', [$packageRouteId, $tryout->tryout_id, $token]);
        $aiLearningHistoryEndpointUrl = route('user.package.tryout.pembahasan.ai-tools.history', [$packageRouteId, $tryout->tryout_id, $token]);
        $aiSpeechEndpointUrl = route('user.package.tryout.pembahasan.ai-speech', [$packageRouteId, $tryout->tryout_id, $token]);
        $downloadExplanationsUrl = route('user.package.tryout.pembahasan.download', [$packageRouteId, $tryout->tryout_id, $token, 'pembahasan']);
        $aiGatewaySubscriptionsCollection = collect($aiGatewaySubscriptions ?? ($aiGatewaySubscription ? [$aiGatewaySubscription] : []));
        $activeAiGatewaySubscriptions = $aiGatewaySubscriptionsCollection->filter(fn ($subscription) => data_get($subscription, 'status') === 'active');
        $hasAnyActiveAiGatewayPackage = $activeAiGatewaySubscriptions->isNotEmpty();
        $activeAiGatewayTokenLimit = $activeAiGatewaySubscriptions->sum(function ($subscription) {
            $plan = data_get($subscription, 'plan', []);
            return (int) (data_get($subscription, 'token_limit') ?: data_get($plan, 'token_limit', 0));
        });
        $activeAiGatewayTokensUsed = $activeAiGatewaySubscriptions->sum(fn ($subscription) => (int) data_get($subscription, 'tokens_used', 0));
        $activeAiGatewayChatLimit = $activeAiGatewaySubscriptions->sum(function ($subscription) {
            $plan = data_get($subscription, 'plan', []);
            return (int) (data_get($subscription, 'chat_limit') ?: data_get($plan, 'chat_limit', 0));
        });
        $activeAiGatewayChatsUsed = $activeAiGatewaySubscriptions->sum(fn ($subscription) => (int) data_get($subscription, 'chats_used', 0));
        $aiGatewayQuota = $hasAnyActiveAiGatewayPackage ? [] : ($aiGatewayTrial ?? []);
        $aiGatewayTokenLimit = $hasAnyActiveAiGatewayPackage ? $activeAiGatewayTokenLimit : (int) data_get($aiGatewayQuota, 'token_limit', 0);
        $aiGatewayTokensUsed = $hasAnyActiveAiGatewayPackage ? $activeAiGatewayTokensUsed : (int) data_get($aiGatewayQuota, 'tokens_used', 0);
        $aiGatewayRemainingTokens = $aiGatewayTokenLimit > 0 ? max(0, $aiGatewayTokenLimit - $aiGatewayTokensUsed) : null;
        $aiGatewayTokenPercentage = $aiGatewayTokenLimit > 0 ? min(100, ($aiGatewayRemainingTokens / $aiGatewayTokenLimit) * 100) : null;
        $aiGatewayUsedPercentage = $aiGatewayTokenPercentage !== null ? 100 - $aiGatewayTokenPercentage : null;
        $aiGatewayChatLimit = $hasAnyActiveAiGatewayPackage ? $activeAiGatewayChatLimit : (int) data_get($aiGatewayQuota, 'chat_limit', 0);
        $aiGatewayChatsUsed = $hasAnyActiveAiGatewayPackage ? $activeAiGatewayChatsUsed : (int) data_get($aiGatewayQuota, 'chats_used', 0);
        $aiGatewayRemainingChats = $aiGatewayChatLimit > 0 ? max(0, $aiGatewayChatLimit - $aiGatewayChatsUsed) : null;
        $hasActiveAiGatewayPackage = $hasAnyActiveAiGatewayPackage
            && ($aiGatewayTokenLimit <= 0 || $aiGatewayRemainingTokens > 0)
            && ($aiGatewayChatLimit <= 0 || $aiGatewayRemainingChats > 0);
        $isAiGatewayPackageExhausted = $hasAnyActiveAiGatewayPackage
            && (($aiGatewayTokenLimit > 0 && $aiGatewayRemainingTokens <= 0)
                || ($aiGatewayChatLimit > 0 && $aiGatewayRemainingChats <= 0));
        $shouldOpenAiGatewayBuyModal = !$hasActiveAiGatewayPackage;
        $hasAiGatewayTrial = !$hasAnyActiveAiGatewayPackage && (bool) data_get($aiGatewayTrial, 'available', false);
        $aiGatewayReturnUrl = request()->fullUrlWithoutQuery('payment');
        $aiGatewayPendingInvoiceUrl = data_get($aiGatewayPendingPayment ?? [], 'invoice_url');
        $aiGatewayBadgeTitle = $hasActiveAiGatewayPackage
            ? 'Lihat Paket & Penggunaan AI'
            : ($isAiGatewayPackageExhausted ? 'Kuota paket AI habis. Beli paket baru.' : ($hasAiGatewayTrial ? 'Coba gratis Diskusi AI tersedia' : 'Belum membeli paket pembahasan AI'));
        $aiGatewayFeatureTitle = $isAiGatewayPackageExhausted ? 'Kuota AI habis' : 'Fitur baru';
        $aiGatewayFeatureButtonText = $isAiGatewayPackageExhausted ? 'Beli paket lagi' : 'Beli paket AI';
        $aiLearningTokensPerGeneratedQuestion = 900;
        $aiLearningMaxQuestionCount = $aiGatewayRemainingTokens === null
            ? 5
            : min(5, intdiv(max(0, (int) $aiGatewayRemainingTokens), $aiLearningTokensPerGeneratedQuestion));
    @endphp
    <style>
        .discussion-nav-btn:hover,
        .discussion-nav-active {
            border-color: {{ $primaryColor }} !important;
            background-color: {{ $primaryColor }} !important;
            color: #ffffff !important;
        }

        .discussion-nav-active {
            box-shadow: 0 0 0 3px {{ $primaryColor }}33;
            transform: translateY(-1px);
        }

        .ai-flashcard-study {
            perspective: 1200px;
        }

        .ai-flashcard {
            will-change: transform, opacity;
        }

        .ai-flashcard-inner {
            position: relative;
            transform-style: preserve-3d;
            transition: transform 620ms cubic-bezier(.22, 1, .36, 1);
            will-change: transform;
        }

        .ai-flashcard.is-showing-back .ai-flashcard-inner {
            transform: rotateY(180deg);
        }

        .ai-flashcard-face {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }

        .ai-flashcard-back {
            transform: rotateY(180deg);
        }

        .ai-flashcard.is-entering {
            animation: ai-flashcard-enter 360ms cubic-bezier(.22, 1, .36, 1);
        }

        .ai-flashcard.is-exiting-remembered {
            animation: ai-flashcard-exit-right 320ms ease-in forwards;
        }

        .ai-flashcard.is-exiting-forgotten {
            animation: ai-flashcard-exit-left 320ms ease-in forwards;
        }

        @keyframes ai-flashcard-enter {
            from { opacity: 0; transform: translateY(22px) scale(.94) rotateY(-10deg); }
            to { opacity: 1; transform: translateY(0) scale(1) rotateY(0); }
        }

        @keyframes ai-flashcard-exit-right {
            to { opacity: 0; transform: translateX(56px) rotate(4deg) scale(.94); }
        }

        @keyframes ai-flashcard-exit-left {
            to { opacity: 0; transform: translateX(-56px) rotate(-4deg) scale(.94); }
        }

        @media (prefers-reduced-motion: reduce) {
            .ai-flashcard.is-entering,
            .ai-flashcard.is-exiting-remembered,
            .ai-flashcard.is-exiting-forgotten {
                animation-duration: 1ms;
            }

            .ai-flashcard-inner {
                transition-duration: 1ms;
            }
        }
    </style>
    @if(request('payment') === 'success')
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">Pembayaran paket AI berhasil diterima. Status paket akan tersinkron otomatis dari gateway pusat.</div>
    @endif
    @if(request('payment') === 'failed')
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Pembayaran paket AI belum berhasil. Kamu bisa coba beli lagi dari modal Diskusi AI.</div>
    @endif
    <div class="bg-white px-4 py-10 rounded-lg border border-border flex flex-col md:flex-row gap-4 text-dark">
        <div class="flex order-2 md:order-1 flex-col items-center gap-4 w-full">
            <div class="flex flex-wrap items-center justify-center gap-2">
                <p class="font-semibold">Pembahasan - {{ $tryout->name }}</p>
            </div>
            <p class="text-5xl font-medium">{{ $overallStats['display_score'] ?? $formatScore($overallStats['total_score']) }}</p>
            @if($showPassingGrade)
                <span
                    class="flex items-center gap-1 border px-6 py-0.5 rounded-lg {{ $overallStats['is_passed'] ? 'border-green bg-green-light text-green' : 'border-red bg-red-light text-red' }}">
                    <i class="ri-checkbox-circle-fill text-lg"></i>
                    <span>{{ $overallStats['is_passed'] ? 'Lulus' : 'Tidak Lulus' }}</span>
                </span>
            @endif
            @if(isset($tryoutDetails) && $tryoutDetails->count() > 1)
            <div class="mt-2">
                <span class="inline-flex px-3 py-1 bg-primary/10 text-primary text-sm font-medium rounded-full">
                    {{ $subtestGroupLabel }}
                </span>
            </div>
            @endif
        </div>
        <span class="self-strech hidden md:block md:order-2 w-px border-l border-dashed border-gray-400"></span>
        <div class="grid order-1 md:order-3 grid-cols-2 gap-2 w-full">
            <div class="flex w-full items-center gap-3 bg-white p-4 rounded-lg border border-border">
                <i
                    class="ri-question-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
                <div>
                    <p class="text-[24px] font-bold">{{ $overallStats['total_questions'] }}</p>
                    <p class="text-[12px] mt-[-6px] font-light">Total Soal</p>
                </div>
            </div>
            <div class="flex w-full items-center gap-3 bg-white p-4 rounded-lg border border-border">
                <i
                    class="ri-check-line text-[20px] flex items-center justify-center text-white font-medium bg-green w-10 h-10 rounded-lg"></i>
                <div>
                    <p class="text-[24px] font-bold">{{ $overallStats['correct_answers'] }}</p>
                    <p class="text-[12px] mt-[-6px] font-light">Jawaban Benar</p>
                </div>
            </div>
            <div class="flex w-full items-center gap-3 bg-white p-4 rounded-lg border border-border">
                <i
                    class="ri-close-line text-[20px] flex items-center justify-center text-white font-medium bg-red w-10 h-10 rounded-lg"></i>
                <div>
                    <p class="text-[24px] font-bold">{{ $overallStats['wrong_answers'] }}</p>
                    <p class="text-[12px] mt-[-6px] font-light">Jawaban Salah</p>
                </div>
            </div>
            <div class="flex w-full items-center gap-3 bg-white p-4 rounded-lg border border-border">
                <i
                    class="ri-question-mark-line text-[20px] flex items-center justify-center text-white font-medium bg-gray-500 w-10 h-10 rounded-lg"></i>
                <div>
                    <p class="text-[24px] font-bold">{{ $overallStats['unanswered'] }}</p>
                    <p class="text-[12px] mt-[-6px] font-light">Tidak Dijawab</p>
                </div>
            </div>
            @if(!empty($overallStats['pending_review']))
            <div class="flex w-full items-center gap-3 bg-white p-4 rounded-lg border border-border">
                <i
                    class="ri-time-line text-[20px] flex items-center justify-center text-white font-medium bg-amber-500 w-10 h-10 rounded-lg"></i>
                <div>
                    <p class="text-[24px] font-bold">{{ $overallStats['pending_review'] }}</p>
                    <p class="text-[12px] mt-[-6px] font-light">Belum Dikoreksi</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Subtest Summary (if multiple subtests) -->
    @if(!empty($subtestSummaries))
    <div class="bg-white px-4 py-6 rounded-lg border border-border">
        <h3 class="text-lg font-bold mb-4 text-gray-800">Ringkasan Per Subtest</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($subtestSummaries as $summary)
            <div
                class="p-4 border rounded-lg {{ $showPassingGrade ? ($summary['is_passed'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50') : 'border-gray-200 bg-white' }}">
                <div class="text-center mb-3">
                    <h4 class="font-semibold text-gray-800">{{ $summary['abbreviation'] ?? \App\Models\TryoutDetail::abbreviationFromName($summary['name'] ?? '') }}
                    </h4>
                    <p class="text-sm text-gray-600">{{ $summary['name'] }}</p>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold {{ $showPassingGrade ? ($summary['is_passed'] ? 'text-green-600' : 'text-red-600') : 'text-gray-800' }}">
                        {{ $summary['display_score'] ?? $formatScore($summary['score']) }}@if($showScoreMaximum)/{{ $summary['display_maximum'] ?? $formatScore($summary['max_score']) }}@endif
                    </div>
                    <div class="text-sm {{ $showPassingGrade ? ($summary['is_passed'] ? 'text-green-600' : 'text-red-600') : 'text-gray-600' }}">
                        {{ number_format($summary['percentage'], 1) }}%
                        @if($showPassingGrade)
                            - {{ $summary['is_passed'] ? 'LULUS' : 'TIDAK LULUS' }}
                        @endif
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-600 text-center">
                    {{ $summary['correct_answers'] }} benar, {{ $summary['wrong_answers'] }} salah
                </div>
                @if($showPassingGrade)
                    <div class="mt-1 text-xs text-gray-500 text-center">
                        Passing grade:
                        @if(($summary['passing_type'] ?? 'score') === 'percentage')
                            {{ number_format($summary['passing_score'] ?? 0, 1) }}%
                        @else
                            {{ $summary['passing_score'] ?? '-' }}
                            @if(!is_null($summary['passing_percentage'] ?? null))
                                ({{ number_format($summary['passing_percentage'], 1) }}%)
                            @endif
                        @endif
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($allAnswerDetails->isNotEmpty())
    <div class="bg-white px-4 py-6 rounded-lg border border-border">
        <div class="flex flex-col gap-2 mb-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Navigasi Soal</h3>
                <p class="text-sm text-gray-500">Pilih nomor untuk melihat pembahasan soal tertentu.</p>
            </div>
            <span id="discussion-nav-status" class="text-sm text-gray-500">Menampilkan semua soal</span>
        </div>
        <div class="grid grid-cols-4 gap-2 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 xl:grid-cols-12">
            <button type="button"
                class="discussion-nav-btn discussion-nav-active col-span-2 rounded-xl border px-4 py-3 text-sm font-semibold transition-colors"
                data-question-target="all">
                Semua
            </button>
            @foreach($allAnswerDetails as $navIndex => $navDetail)
            @php
                $navIsUnanswered = (bool) ($navDetail->is_unanswered ?? false);
                $navAnswerMeta = is_array($navDetail->answer_json) ? $navDetail->answer_json : [];
                $navIsPending = ($navAnswerMeta['pending_review'] ?? false) === true;
                $navSelectedOption = $navDetail->questionOption;
                $navIsTkp = ($navDetail->subtest_type ?? '') === 'tkp';
                $navMaxWeight = (float) ($navDetail->question->questionOptions->max('weight') ?? 0);
                $navIsCorrect = !$navIsUnanswered && ($navIsTkp
                    ? ($navSelectedOption && (float) ($navSelectedOption->weight ?? 0) >= $navMaxWeight)
                    : (bool) $navDetail->is_correct);
                $navStateClass = $navIsPending
                    ? 'border-amber-200 bg-amber-50 text-amber-700'
                    : ($navIsUnanswered
                        ? 'border-gray-200 bg-gray-50 text-gray-500'
                        : ($navIsCorrect
                            ? 'border-green-200 bg-green-50 text-green-700'
                            : 'border-red-200 bg-red-50 text-red-700'));
            @endphp
            <button type="button"
                class="discussion-nav-btn rounded-xl border px-3 py-3 text-sm font-semibold transition-colors {{ $navStateClass }}"
                data-question-target="{{ $navIndex + 1 }}">
                {{ $navIndex + 1 }}
            </button>
            @endforeach
        </div>
        <div class="mt-5 border-t border-gray-200 pt-4">
            <p class="text-sm font-semibold text-gray-700">Unduh Materi</p>
            <p class="mt-1 text-xs text-gray-500">Simpan soal beserta pembahasannya untuk dipelajari kembali.</p>
            <div class="mt-3">
                <a href="{{ $downloadExplanationsUrl }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 sm:w-auto">
                    <i class="ri-download-2-line"></i>
                    Unduh Soal & Pembahasan
                </a>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-white px-4 py-10 rounded-lg border border-border flex flex-col gap-8 text-dark">
        @php $currentSubtest = null; @endphp
        @foreach($allAnswerDetails as $index => $detail)
        @php
        $question = $detail->question;
        $isUnanswered = (bool) ($detail->is_unanswered ?? false);
        $isTkp = ($detail->subtest_type ?? '') === 'tkp';
        $maxOptionWeight = (float) ($question->questionOptions->max('weight') ?? 0);
        $correctOption = $isTkp
            ? $question->questionOptions->sortByDesc(fn ($option) => (float) ($option->weight ?? 0))->first()
            : $question->questionOptions->where('is_correct', true)->first();
        $selectedOption = $detail->questionOption;
        $isCorrect = !$isUnanswered && ($isTkp
            ? ($selectedOption && (float) ($selectedOption->weight ?? 0) >= $maxOptionWeight)
            : (bool) $detail->is_correct);
        $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $isPendingReview = ($answerMeta['pending_review'] ?? false) === true;
        $questionMeta = is_array($question->metadata ?? null) ? $question->metadata : [];
        $multipleAnswerMeta = is_array($questionMeta['multiple_answer'] ?? null) ? $questionMeta['multiple_answer'] : [];
        $multipleAnswerScoringMode = in_array(($multipleAnswerMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $multipleAnswerMeta['scoring_mode']
            : 'fullscore';
        $multipleAnswerTotalScore = (float) ($multipleAnswerMeta['score_correct'] ?? ($question->default_weight ?? 1));
        $multipleAnswerCorrectCount = max(1, $question->questionOptions->where('is_correct', true)->count());
        $multipleAnswerPerCorrectScore = $multipleAnswerCorrectCount > 0
            ? ($multipleAnswerTotalScore / $multipleAnswerCorrectCount)
            : $multipleAnswerTotalScore;
        $selectedOptionIds = collect($answerMeta['selected_option_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->all();
        $multipleAnswerResult = null;
        $isPartiallyCorrect = false;
        if (($question->question_type ?? '') === 'multiple_answer' && ! $isUnanswered) {
            $multipleAnswerResult = app(\App\Services\MultipleAnswerScoringService::class)
                ->evaluateDetail($question, $detail);
            $isCorrect = $multipleAnswerResult['is_correct'];
            $isPartiallyCorrect = app(\App\Services\MultipleAnswerScoringService::class)
                ->isPartiallyCorrect($multipleAnswerResult);
        }
        $matchingPairs = isset($questionMeta['matching_pairs']) && is_array($questionMeta['matching_pairs'])
            ? $questionMeta['matching_pairs']
            : [];
        $userMatches = isset($answerMeta['matches']) && is_array($answerMeta['matches'])
            ? $answerMeta['matches']
            : [];
        $mtfMeta = is_array($questionMeta['multiple_true_false'] ?? null) ? $questionMeta['multiple_true_false'] : [];
        $mtfStatements = is_array($mtfMeta['statements'] ?? null) ? $mtfMeta['statements'] : [];
        $mtfUserAnswers = is_array($answerMeta['answers'] ?? null) ? $answerMeta['answers'] : [];
        $mtfTrueLabel = trim((string) ($mtfMeta['true_label'] ?? 'Benar'));
        $mtfFalseLabel = trim((string) ($mtfMeta['false_label'] ?? 'Salah'));
        $mtfScoringMode = in_array(($mtfMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $mtfMeta['scoring_mode']
            : 'fullscore';
        $mtfScoreCorrect = (float) ($mtfMeta['score_correct'] ?? ($question->default_weight ?? 1));
        $mtfScoreWrong = (float) ($mtfMeta['score_wrong'] ?? 0);
        $mtfPerStatementScore = count($mtfStatements) > 0 ? ($mtfScoreCorrect / count($mtfStatements)) : $mtfScoreCorrect;
        $scoringModeInfo = null;
        if (($question->question_type ?? '') === 'multiple_answer') {
            $scoringModeInfo = [
                'type' => 'Multiple Answer',
                'mode' => $multipleAnswerScoringMode === 'partial' ? 'Partial' : 'Full Score',
            ];
        } elseif (($question->question_type ?? '') === 'multiple_true_false') {
            $scoringModeInfo = [
                'type' => 'Multiple True/False',
                'mode' => $mtfScoringMode === 'partial' ? 'Partial' : 'Full Score',
            ];
        }
        @endphp

        {{-- Subtest Header --}}
        @if($currentSubtest !== $detail->subtest_type)
        @php $currentSubtest = $detail->subtest_type; @endphp
        <div class="discussion-subtest-header border-t-2 border-primary pt-6 -mt-2">
            <div class="flex items-center gap-3 mb-4">
                <div
                    class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-bold text-sm">
                    <i class="ri-book-open-line"></i>
                </div>
                <h3 class="text-xl font-bold text-primary">{{ $detail->subtest_name }}</h3>
            </div>
        </div>
        @endif

        @php
            // Tentukan border color berdasarkan status
            if ($isPendingReview) {
                $cardBorderClass = 'border-amber-400 bg-amber-50/30';
            } elseif ($isUnanswered) {
                $cardBorderClass = 'border-red bg-red-light/30';
            } elseif ($isPartiallyCorrect) {
                $cardBorderClass = 'border-amber-400 bg-amber-50/30';
            } elseif ($isCorrect) {
                $cardBorderClass = 'border-green bg-green-light/30';
            } else {
                $cardBorderClass = 'border-red bg-red-light/30';
            }
        @endphp
        <div class="card-pembahasan essay-card w-full border border-dashed p-4 rounded-lg {{ $cardBorderClass }} {{ ($isPendingReview && ($answerMeta['evaluation_mode'] ?? 'manual') === 'auto') ? 'essay-pending-ai' : '' }}"
             data-question-number="{{ $index + 1 }}"
             data-question-id="{{ $question->question_id }}" 
             data-pending="{{ $isPendingReview ? 'true' : 'false' }}">
            <div class="flex flex-wrap items-center justify-start gap-4">
                <p class="font-semibold">Soal {{ $index + 1 }}</p>
                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                    {{ $detail->subtest_name }}
                </span>
                @if($scoringModeInfo)
                <span class="inline-flex items-center gap-1 rounded-full border border-primary/20 bg-primary/5 px-2 py-1 text-xs font-semibold text-primary">
                    <i class="ri-calculator-line"></i>
                    {{ $scoringModeInfo['type'] }} · {{ $scoringModeInfo['mode'] }}
                </span>
                @endif
                @php
                    $evalMode = $answerMeta['evaluation_mode'] ?? 'manual';
                    if ($isPendingReview && $evalMode === 'auto') {
                        $statusBadgeClass = 'bg-blue-100 text-blue-700 border-blue-200';
                        $statusText = 'Sedang diproses AI';
                        $statusIcon = 'ri-loader-4-line';
                    } elseif ($isPendingReview) {
                        $statusBadgeClass = 'bg-amber-100 text-amber-700 border-amber-200';
                        $statusText = 'Belum Dikoreksi';
                        $statusIcon = 'ri-time-line';
                    } elseif ($isUnanswered) {
                        $statusBadgeClass = 'bg-red text-white';
                        $statusText = 'Tidak Dijawab';
                        $statusIcon = 'ri-close-circle-fill';
                    } elseif ($isPartiallyCorrect) {
                        $statusBadgeClass = 'bg-amber-100 text-amber-700 border-amber-200';
                        $statusText = 'Sebagian Benar';
                        $statusIcon = 'ri-checkbox-circle-line';
                    } elseif ($isCorrect) {
                        $statusBadgeClass = 'bg-green text-white';
                        $statusText = 'Benar';
                        $statusIcon = 'ri-checkbox-circle-fill';
                    } else {
                        $statusBadgeClass = 'bg-red text-white';
                        $statusText = 'Salah';
                        $statusIcon = 'ri-close-circle-fill';
                    }
                @endphp
                <span class="flex items-center gap-1 border px-4 py-1 rounded-lg {{ $statusBadgeClass }}">
                    <i class="{{ $statusIcon }}{{ $isPendingReview && $evalMode === 'auto' ? ' animate-spin' : '' }}"></i>
                    <p class="text-sm">{{ $statusText }}</p>
                </span>
                @php
                // Calculate score earned for this question
                $scoreEarned = 0;
                if (($question->question_type ?? '') === 'multiple_answer') {
                    $scoreEarned = $multipleAnswerResult['score_obtained']
                        ?? app(\App\Services\MultipleAnswerScoringService::class)->scoreForDetail($question, $detail);
                } elseif (($question->question_type ?? '') === 'multiple_true_false') {
                    $summary = is_array($answerMeta['summary'] ?? null) ? $answerMeta['summary'] : [];
                    $correctCount = (int) ($summary['correct'] ?? 0);
                    $totalCount = (int) ($summary['total'] ?? count($mtfStatements));
                    $isExactCorrect = $totalCount > 0 && $correctCount === $totalCount;

                    if ($totalCount > 0) {
                        if ($mtfScoringMode === 'partial') {
                            $scoreEarned = $correctCount > 0
                                ? ($correctCount / $totalCount) * $mtfScoreCorrect
                                : $mtfScoreWrong;
                        } else {
                            $scoreEarned = $isExactCorrect ? $mtfScoreCorrect : $mtfScoreWrong;
                        }
                    } else {
                        $scoreEarned = (float) ($answerMeta['score_obtained'] ?? 0);
                    }
                    $scoreEarned = max(0, $scoreEarned);
                } else {
                    // Essay dan tipe lainnya
                    if ($isPendingReview) {
                        $scoreEarned = 0; // Jangan tampilkan score kalau masih pending
                    } else {
                        $storedScore = $answerMeta['score_obtained'] ?? null;
                        if(is_numeric($storedScore)) {
                            $scoreEarned = (float) $storedScore;
                        } elseif($selectedOption) {
                            switch($detail->subtest_type) {
                                case 'twk':
                                case 'tiu':
                                    $scoreEarned = $isCorrect ? 5 : 0;
                                    break;
                                case 'tkp':
                                    $scoreEarned = $selectedOption->weight ?? 0;
                                    break;
                                default:
                                    $scoreEarned = $selectedOption->weight ?? ($isCorrect ? 1 : 0);
                                    break;
                            }
                        }
                    }
                }
                @endphp
                @if($isPendingReview)
                    {{-- Belum dikoreksi - tampilkan MENUNGGU --}}
                    <span class="essay-status-badge flex items-center gap-1 border border-amber-200 bg-amber-50 text-amber-700 px-3 py-1 rounded-lg text-sm">
                        <i class="ri-time-line"></i>
                        Menunggu
                    </span>
                @elseif($isUnanswered)
                    <span class="essay-score-badge flex items-center gap-1 border border-red-200 bg-red-50 text-red-700 px-3 py-1 rounded-lg text-sm">
                        <i class="ri-close-line"></i>
                        Kosong
                    </span>
                @else
                    {{-- Sudah dikoreksi - tampilkan nilai --}}
                    <span class="essay-score-badge flex items-center gap-1 border {{ $isCorrect ? 'border-green-200 bg-green-50 text-green-700' : ($isPartiallyCorrect ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-red-200 bg-red-50 text-red-700') }} px-3 py-1 rounded-lg text-sm">
                        <i class="{{ $isCorrect ? 'ri-check-line' : ($isPartiallyCorrect ? 'ri-checkbox-circle-line' : 'ri-close-line') }}"></i>
                        {{ $isCorrect ? 'Benar' : ($isPartiallyCorrect ? 'Sebagian Benar' : 'Salah') }}
                        @if(($question->question_type ?? '') === 'multiple_answer' || $scoreEarned > 0)
                            ({{ $scoreEarned }} poin)
                        @endif
                    </span>
                @endif
            </div>

            <div class="question-rich-text mt-2 font-light">
                {!! $question->question_text !!}
            </div>

            @if($question->sound)
            <div class="mt-4">
                <audio controls class="w-full">
                    <source src="{{ Storage::url($question->sound) }}" type="audio/mpeg">
                    Browser Anda tidak mendukung audio.
                </audio>
            </div>
            @endif

            @if(in_array($question->question_type ?? '', ['short_answer', 'essay']))
            <div class="mt-4 p-3 bg-white border border-gray-200 rounded-lg">
                <p class="font-semibold text-gray-800 mb-1">Jawaban Peserta:</p>
                <p class="text-gray-700">{!! nl2br(e($detail->answer_text ?? '')) ?: '-' !!}</p>
                @php
                    $aiSimilarity = $detail->answer_json['ai_similarity'] ?? null;
                    $aiCorrectedAt = $detail->answer_json['ai_corrected_at'] ?? null;
                @endphp
                <div class="essay-ai-info">
                    @if($detail->answer_json['pending_review'] ?? false)
                        @php
                            $evalMode = $detail->answer_json['evaluation_mode'] ?? 'manual';
                        @endphp
                        @if($evalMode === 'auto')
                            <div class="flex items-center gap-2 mt-2 text-blue-600">
                                <i class="ri-loader-4-line animate-spin"></i>
                                <p class="text-xs font-medium">Sedang diproses AI...</p>
                            </div>
                        @else
                            <p class="text-xs text-gray-500 mt-2">Menunggu koreksi admin.</p>
                        @endif
                    @elseif($aiCorrectedAt)
                        <div class="flex items-center gap-2 mt-2">
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 rounded text-xs">
                                <i class="ri-robot-line"></i>
                                Dikoreksi AI
                            </span>
                            @if($aiSimilarity !== null)
                                <span class="text-xs text-gray-500">
                                    Similarity: {{ round($aiSimilarity * 100, 1) }}%
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            @elseif(($question->question_type ?? '') === 'matching')
            <div class="mt-4 p-3 bg-white border border-gray-200 rounded-lg">
                <p class="font-semibold text-gray-800 mb-2">Jawaban Peserta:</p>
                @if(empty($matchingPairs))
                <p class="text-sm text-gray-500">Belum ada pasangan pencocokan untuk soal ini.</p>
                @else
                <div class="space-y-2">
                    @foreach($matchingPairs as $pair)
                    @php
                        $leftText = trim((string) ($pair['left'] ?? ''));
                        $rightText = trim((string) ($pair['right'] ?? ''));
                        $userRight = trim((string) ($userMatches[$leftText] ?? ''));
                        $isPairCorrect = $userRight !== '' && $userRight === $rightText;
                    @endphp
                    <div class="flex items-center gap-2 text-sm {{ $isPairCorrect ? 'text-green' : 'text-gray-700' }}">
                        <span class="font-medium text-gray-800">{{ $leftText !== '' ? $leftText : '-' }}</span>
                        <i class="ri-arrow-right-line text-gray-400"></i>
                        <span>{{ $userRight !== '' ? $userRight : '-' }}</span>
                        @if($isPairCorrect)
                        <i class="ri-check-line text-green"></i>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            <div class="mt-3 p-3 bg-green border border-green rounded-lg text-white">
                <p class="font-semibold text-white mb-2">Jawaban Yang Benar:</p>
                @if(empty($matchingPairs))
                <p class="text-sm text-white">Belum ada pasangan pencocokan yang tersimpan.</p>
                @else
                <div class="space-y-2 text-sm text-white">
                    @foreach($matchingPairs as $pair)
                    @php
                        $leftText = trim((string) ($pair['left'] ?? ''));
                        $rightText = trim((string) ($pair['right'] ?? ''));
                    @endphp
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-white">{{ $leftText !== '' ? $leftText : '-' }}</span>
                        <i class="ri-arrow-right-line text-white/90"></i>
                        <span>{{ $rightText !== '' ? $rightText : '-' }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @elseif(($question->question_type ?? '') === 'multiple_true_false')
            <div class="mt-4">
                @if(empty($mtfStatements))
                <p class="text-sm text-gray-500">Belum ada pernyataan Multiple True/False untuk soal ini.</p>
                @else
                <div class="overflow-x-auto border border-gray-200 rounded-lg bg-gray-50/70">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100/80">
                            <tr>
                                <th class="px-5 py-3.5 text-left font-semibold text-gray-800 w-[63%]">Pernyataan</th>
                                <th class="px-5 py-3.5 text-center font-semibold text-gray-800 whitespace-nowrap w-[9%]">{{ $mtfTrueLabel !== '' ? $mtfTrueLabel : 'Benar' }}</th>
                                <th class="px-5 py-3.5 text-center font-semibold text-gray-800 whitespace-nowrap w-[9%]">{{ $mtfFalseLabel !== '' ? $mtfFalseLabel : 'Salah' }}</th>
                                <th class="px-5 py-3.5 text-center font-semibold text-gray-800 whitespace-nowrap w-[9%]">Status</th>
                                <th class="px-5 py-3.5 text-right font-semibold text-gray-800 whitespace-nowrap w-[10%]">Kunci</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mtfStatements as $stmt)
                            @php
                                $statementId = (string) ($stmt['id'] ?? '');
                                $userAnswer = strtolower((string) ($mtfUserAnswers[$statementId] ?? ''));
                                $correctAnswer = strtolower((string) ($stmt['correct'] ?? 'true'));
                                $isStmtCorrect = $userAnswer !== '' && $userAnswer === $correctAnswer;
                                $correctLabel = $correctAnswer === 'true'
                                    ? ($mtfTrueLabel !== '' ? $mtfTrueLabel : 'Benar')
                                    : ($mtfFalseLabel !== '' ? $mtfFalseLabel : 'Salah');
                            @endphp
                            <tr class="border-t border-gray-200">
                                <td class="px-5 py-3.5 text-gray-800 align-top">{{ $stmt['text'] ?? '-' }}</td>
                                <td class="px-5 py-3.5 text-center align-middle">
                                    @if($userAnswer === 'true')
                                    <i class="ri-checkbox-circle-fill {{ $isStmtCorrect ? 'text-green' : 'text-red' }} text-lg"></i>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center align-middle">
                                    @if($userAnswer === 'false')
                                    <i class="ri-checkbox-circle-fill {{ $isStmtCorrect ? 'text-green' : 'text-red' }} text-lg"></i>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center align-middle">
                                    @if($isStmtCorrect)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green text-white">Benar</span>
                                    @elseif($userAnswer === '')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-200 text-gray-700">Kosong</span>
                                    @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-red text-white">Salah</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right text-gray-700 whitespace-nowrap align-middle">
                                    {{ $correctLabel }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            <div class="mt-3 p-3 bg-white border border-gray-300 rounded-lg text-gray-800 text-sm">
                @if($mtfScoringMode === 'partial')
                Setiap pernyataan benar bernilai {{ number_format($mtfPerStatementScore, 2) }} poin. Jika semua salah, nilai mengikuti skor salah.
                @else
                Nilai penuh diberikan jika semua pernyataan benar: {{ rtrim(rtrim(number_format($mtfScoreCorrect, 2, '.', ''), '0'), '.') }} poin.
                @endif
            </div>
            @else
            <div class="flex flex-col gap-2 mt-4 w-full">
                @foreach($question->questionOptions as $option)
                @php
                $isMultipleAnswerQuestion = ($question->question_type ?? '') === 'multiple_answer';
                $isSelected = $isMultipleAnswerQuestion
                    ? in_array((int) $option->question_option_id, $selectedOptionIds, true)
                    : ($detail->question_option_id === $option->question_option_id);
                $isCorrectOption = $isTkp
                    ? ((float) ($option->weight ?? 0) >= $maxOptionWeight)
                    : (bool) $option->is_correct;
                $storedOptionKey = trim((string) ($option->option_key ?? ''));
                $optionKey = $storedOptionKey !== '' ? $storedOptionKey : chr(65 + $loop->index);
                $choiceInputType = $isMultipleAnswerQuestion ? 'checkbox' : 'radio';
                @endphp

                @if($isCorrectOption)
                <!-- Correct answer - always GREEN -->
                <div
                    class="flex w-full items-center gap-1 font-light border px-4 py-2 rounded-lg transition-colors bg-green text-white border-green">
                    <input type="{{ $choiceInputType }}" disabled class="mr-2" {{ $isSelected ? 'checked' : '' }}>
                    <span class="font-medium mr-2">{{ $optionKey }}.</span>
                    <p>{!! $option->option_text !!}</p>
                    @if($isMultipleAnswerQuestion)
                        @if($multipleAnswerScoringMode === 'partial')
                        <span class="text-xs bg-white/20 px-2 py-1 rounded">
                            {{ number_format($multipleAnswerPerCorrectScore, 2) }} poin
                        </span>
                        @else
                        <span class="text-xs bg-white/20 px-2 py-1 rounded">
                            Benar semua : {{ rtrim(rtrim(number_format($multipleAnswerTotalScore, 2, '.', ''), '0'), '.') }} poin
                        </span>
                        @endif
                    @endif
                    <i class="ri-check-line text-lg"></i>
                    @if($detail->subtest_type === 'tkp')
                    <span class="text-xs bg-white/20 px-2 py-1 rounded">Bobot: {{ $option->weight }}</span>
                    @endif
                </div>
                @elseif($isSelected && !$isCorrectOption)
                <!-- User's wrong answer - RED -->
                <div
                    class="flex w-full items-center gap-1 font-light border px-4 py-2 rounded-lg transition-colors bg-red text-white border-red">
                    <input type="{{ $choiceInputType }}" disabled class="mr-2" checked>
                    <span class="font-medium mr-2">{{ $optionKey }}.</span>
                    <p>{!! $option->option_text !!}</p>
                    @if($isMultipleAnswerQuestion && $multipleAnswerScoringMode === 'partial')
                    <span class="text-xs bg-white/20 px-2 py-1 rounded">0.00 poin</span>
                    @endif
                    <i class="ri-close-line text-lg"></i>
                    @if($detail->subtest_type === 'tkp')
                    <span class="text-xs bg-white/20 px-2 py-1 rounded">Bobot: {{ $option->weight }}</span>
                    @endif
                </div>
                @else
                <!-- All other options - NEUTRAL -->
                <div
                    class="flex w-full items-center gap-1 font-light border px-4 py-2 rounded-lg transition-colors border-gray-900/10 hover:bg-gray-50">
                    <input type="{{ $choiceInputType }}" disabled class="mr-2" {{ $isSelected ? 'checked' : '' }}>
                    <span class="font-medium mr-2">{{ $optionKey }}.</span>
                    <p>{!! $option->option_text !!}</p>
                    @if($isMultipleAnswerQuestion && $multipleAnswerScoringMode === 'partial')
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                        {{ $isCorrectOption ? number_format($multipleAnswerPerCorrectScore, 2) : '0.00' }} poin
                    </span>
                    @endif
                    @if($detail->subtest_type === 'tkp')
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">Bobot: {{ $option->weight
                        }}</span>
                    @endif
                </div>
                @endif
                @endforeach
            </div>

            @if($isUnanswered)
            <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p class="font-semibold text-red-800 mb-1">Status Jawaban:</p>
                <p class="text-red-700">Soal ini tidak dijawab.</p>
            </div>
            @endif

            @if(!$isCorrect && $correctOption && in_array($detail->subtest_type, ['twk', 'tiu']))
            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="font-semibold text-green-800 mb-1">Jawaban Yang Benar:</p>
                @php
                    $correctOptionIndex = $question->questionOptions
                        ->values()
                        ->search(fn ($option) => (int) $option->question_option_id === (int) $correctOption->question_option_id);
                    $correctOptionIndex = $correctOptionIndex === false ? 0 : (int) $correctOptionIndex;
                    $storedCorrectOptionKey = trim((string) ($correctOption->option_key ?? ''));
                    $correctOptionKey = $storedCorrectOptionKey !== '' ? $storedCorrectOptionKey : chr(65 + $correctOptionIndex);
                @endphp
                <p class="text-green-700">{{ $correctOptionKey }}. {!! $correctOption->option_text !!}</p>
            </div>
            @endif
            @endif

            @if($detail->subtest_type === 'tkp')
            <div class="mt-4 p-3 bg-primary/10 border border-primary/50 rounded-lg">
                <p class="font-semibold text-primary mb-1">Info TKP:</p>
                <p class="text-primary text-sm">Untuk TKP, setiap pilihan memiliki bobot nilai. Pilih jawaban yang
                    paling mencerminkan karakter positif.</p>
            </div>
            @endif

            @if($question->explanation)
            <div class="mt-4">
                <p class="font-semibold text-gray-800 mb-2"># Pembahasan</p>
                <div class="font-light text-gray-700 bg-gray-50 p-3 rounded-lg">
                    {{-- {!! nl2br(e($question->explanation)) !!} --}}
                    {!! $question->explanation !!}
                </div>
            </div>
            @endif

            @if($aiDiscussionEnabled)
            <div class="ai-discussion mt-4 rounded-xl border border-gray-200 bg-white p-3"
                data-question-id="{{ $question->question_id }}">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <i class="ri-robot-2-line text-lg"></i>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Diskusi dengan AI</p>
                            <p class="text-xs text-gray-500">Tanyakan langkah, konsep, atau kenapa jawabanmu salah.</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button"
                            class="ai-learning-open inline-flex items-center gap-1 rounded-lg border border-primary/25 bg-primary/5 px-3 py-2 text-xs font-semibold text-primary hover:bg-primary/10">
                            <i class="ri-sparkling-2-line"></i>
                            <span class="hidden sm:inline">AI Learning Tools</span>
                            <span class="sm:hidden">AI Tools</span>
                        </button>
                        <button type="button"
                            class="ai-discussion-toggle inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                            <i class="ri-message-3-line"></i>
                            Buka
                        </button>
                    </div>
                </div>
                <div class="ai-discussion-body mt-3 hidden space-y-3">
                    <div class="ai-discussion-messages max-h-72 space-y-2 overflow-y-auto rounded-lg bg-gray-50 p-3 text-sm">
                        <div class="max-w-[90%] rounded-lg bg-white border border-gray-200 px-3 py-2 text-gray-700">
                            Mau bahas bagian mana dari soal ini?
                        </div>
                    </div>
                    <form class="ai-discussion-form flex flex-col gap-2 sm:flex-row">
                        <input type="text"
                            class="ai-discussion-input min-w-0 flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                            maxlength="1200"
                            placeholder="Contoh: jelaskan kenapa opsi B benar">
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60">
                            <i class="ri-send-plane-2-line"></i>
                            Kirim
                        </button>
                    </form>
                    <p class="ai-discussion-error hidden text-xs text-red-600"></p>
                </div>
            </div>
            @endif
        </div>
        @endforeach

        @if($allAnswerDetails->isEmpty())
        <div class="text-center py-8">
            <i class="ri-file-list-line text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500">Tidak ada data jawaban ditemukan.</p>
        </div>
        @endif
    </div>

    <!-- Summary Statistics -->
    <div class="bg-white px-4 py-6 rounded-lg border border-border">
        @php
            $summaryNames = collect($subtestSummaries ?? [])->pluck('name')->filter()->unique()->values();
            $summaryTitle = $summaryNames->isNotEmpty()
                ? 'Ringkasan Hasil ' . $summaryNames->implode(' - ')
                : 'Ringkasan Hasil ' . ($tryout->title ?? $tryout->name ?? 'Tryout');
        @endphp
        <h3 class="text-lg font-bold mb-4 text-gray-800">{{ $summaryTitle }}</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-gray-700 mb-3">Detail Skor</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Skor:</span>
                        <span class="font-semibold">{{ $overallStats['display_score'] ?? number_format($overallStats['total_score'], 0) }}@if($showScoreMaximum)/{{ $overallStats['display_maximum'] ?? number_format($overallStats['max_score'], 0) }}@endif</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Persentase:</span>
                        <span class="font-semibold {{ $showPassingGrade ? ($overallStats['is_passed'] ? 'text-green' : 'text-red') : 'text-gray-800' }}">
                            {{ number_format($overallStats['percentage'], 1) }}%
                        </span>
                    </div>
                    @if($showPassingGrade)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-semibold {{ $overallStats['is_passed'] ? 'text-green' : 'text-red' }}">
                            {{ $overallStats['is_passed'] ? 'LULUS' : 'TIDAK LULUS' }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            <div>
                <h4 class="font-semibold text-dark mb-3">Statistik Jawaban</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="">Benar:</span>
                        <span class="font-semibold">{{ $overallStats['correct_answers'] }} soal</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="">Salah:</span>
                        <span class="font-semibold">{{ $overallStats['wrong_answers'] }} soal</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="">Tidak Dijawab:</span>
                        <span class="font-semibold">{{ $overallStats['unanswered'] }} soal</span>
                    </div>
                    @if(!empty($overallStats['pending_review']))
                    <div class="flex justify-between">
                        <span class="">Belum Dikoreksi:</span>
                        <span class="font-semibold">{{ $overallStats['pending_review'] }} soal</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-6">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>Progress Pengerjaan</span>
                <span>{{ $overallStats['total_questions'] - $overallStats['unanswered'] }}/{{
                    $overallStats['total_questions'] }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-green h-3 rounded-full transition-all duration-500"
                    style="width: {{ $overallStats['total_questions'] > 0 ? (($overallStats['total_questions'] - $overallStats['unanswered']) / $overallStats['total_questions']) * 100 : 100 }}%">
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Buttons -->
    <div class="fixed inset-x-3 bottom-3 z-40 flex justify-center sm:inset-x-6 sm:bottom-5">
    <div class="flex max-w-4xl flex-wrap justify-center gap-2 rounded-2xl border border-gray-200 bg-white/95 p-2 shadow-xl backdrop-blur sm:gap-3 sm:p-3">
        @if($package)
            <x-ui.history-back :fallback="route('user.package.tryout', $package->package_id)" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                <i class="ri-arrow-left-line"></i>
                Kembali ke Tryout
            </x-ui.history-back>
        @else
            <x-ui.history-back :fallback="route('user.tryout.result', [$packageRouteId, $tryout->tryout_id])" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                <i class="ri-arrow-left-line"></i>
                Kembali ke Hasil
            </x-ui.history-back>
        @endif

        @if($package)
        <x-ui.button :href="route('user.package.tryout.riwayat', [$package->package_id, $tryout->tryout_id])" variant="outline" size="md" icon="ri-history-line">
            Lihat Riwayat
        </x-ui.button>
        @endif

        @if($tryout->show_leaderboard)
        <x-ui.button :href="route('user.package.tryout.ranking', [$packageRouteId, $tryout->tryout_id])" variant="outline" size="md" icon="ri-trophy-line">
            Lihat Ranking
        </x-ui.button>
        @endif

        @if(($clientBranding['certificate_management_enabled'] ?? true) && $tryout->is_certification)
        <x-ui.button :href="route('user.certificate.preview', [$packageRouteId, $tryout->tryout_id, 'token' => $token])" variant="outline" size="md" icon="ri-award-line">
            Unduh Sertifikat
        </x-ui.button>
        @endif

        <x-ui.button :href="route('user.tryout.lobby', [$packageRouteId, $tryout->tryout_id])" variant="outline" size="md" icon="ri-refresh-line">
            Coba Lagi
        </x-ui.button>
    </div>
    </div>

    @if(filled(config('services.ai_gateway.url')) && filled(config('services.ai_gateway.key')) && $aiDiscussionEnabled)
    <a id="ai-gateway-usage-badge" href="{{ $hasActiveAiGatewayPackage ? route('user.ai-gateway.index') : '#' }}" @if($shouldOpenAiGatewayBuyModal) onclick="event.preventDefault(); openAiDiscussionFeatureModal();" @endif class="group fixed bottom-24 left-4 z-40 flex h-20 w-20 items-center justify-center rounded-full p-1 shadow-lg transition hover:-translate-y-1 hover:shadow-xl md:bottom-6" style="background: {{ $aiGatewayUsedPercentage !== null ? 'conic-gradient(' . $primaryColor . ' ' . $aiGatewayUsedPercentage . '%, #e5e7eb 0)' : '#e5e7eb' }}" title="{{ $aiGatewayBadgeTitle }}">
        <span class="flex h-full w-full flex-col items-center justify-center rounded-full bg-white text-center text-gray-700">
        @if($hasActiveAiGatewayPackage)
            <i class="ri-robot-2-line mb-1 text-xs text-primary"></i><span id="ai-gateway-used-percentage" class="text-base font-bold leading-none">{{ $aiGatewayUsedPercentage !== null ? number_format($aiGatewayUsedPercentage, 0) . '%' : '∞' }}</span>
            <span id="ai-gateway-used-label" class="mt-1 text-[9px] font-medium leading-none text-gray-500">terpakai</span>
        @elseif($isAiGatewayPackageExhausted)
            <i class="ri-robot-2-line text-lg text-amber-500"></i><span class="mt-1 text-[9px] font-semibold leading-none text-amber-700">Kuota habis</span>
        @elseif($hasAiGatewayTrial)
            <i class="ri-sparkling-2-line text-lg text-primary"></i><span class="mt-1 text-[9px] font-semibold leading-none">Coba gratis</span>
        @else
            <i class="ri-robot-2-line text-lg text-gray-400"></i><span class="mt-1 text-[9px] font-medium leading-none text-gray-500">Belum aktif</span>
        @endif
        </span>
        <span class="pointer-events-none absolute bottom-full left-0 mb-2 hidden w-52 rounded-lg bg-gray-900 px-3 py-2 text-xs font-medium text-white shadow-lg group-hover:block">{{ $hasAnyActiveAiGatewayPackage ? ($aiGatewayChatLimit > 0 ? number_format($aiGatewayChatsUsed, 0, ',', '.') . ' dari ' . number_format($aiGatewayChatLimit, 0, ',', '.') . ' chat AI terpakai' : 'Paket chat AI aktif') : ($hasAiGatewayTrial ? 'Coba Diskusi AI gratis tersedia' : 'Belum membeli paket pembahasan AI') }}</span>
    </a>

    @if($hasAiGatewayTrial)
    <div id="ai-discussion-intro-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 p-4"><div class="flex min-h-full items-center justify-center"><div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-xl"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary"><i class="ri-robot-2-line text-2xl"></i></span><h2 class="mt-4 text-xl font-semibold text-gray-900">Coba Diskusi AI Pembahasan</h2><p class="mt-2 text-sm leading-6 text-gray-500">Tanyakan konsep atau langkah penyelesaian soal. Kamu mendapat kuota coba gratis {{ $aiGatewayChatLimit > 0 ? number_format($aiGatewayChatLimit, 0, ',', '.') . ' chat AI' : 'untuk mencoba' }}.</p><div class="mt-6 flex justify-center gap-2"><button type="button" onclick="closeAiDiscussionIntro()" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100">Nanti saja</button><button type="button" onclick="startAiDiscussionTrial()" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Coba sekarang</button></div></div></div></div>
    @endif

    <div id="ai-learning-tools-modal" class="fixed inset-0 z-[60] hidden overflow-y-auto bg-slate-900/60 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="ai-learning-tools-title">
        <div class="flex min-h-full items-center justify-center">
            <div class="w-full max-w-6xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary"><i class="ri-sparkling-2-line text-xl"></i></span>
                        <div>
                            <h2 id="ai-learning-tools-title" class="text-lg font-semibold text-gray-900">AI Learning Tools</h2>
                            <p class="text-xs text-gray-500">Pilih satu alat untuk mengolah soal aktif.</p>
                        </div>
                    </div>
                    <button type="button" data-ai-learning-modal-close class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Tutup AI Learning Tools"><i class="ri-close-line text-xl"></i></button>
                </div>

                <div class="grid max-h-[78vh] min-h-[34rem] lg:grid-cols-4">
                    <aside class="order-2 border-t border-gray-100 bg-gray-50 p-4 lg:order-1 lg:col-span-1 lg:border-r lg:border-t-0">
                        <div class="flex items-center justify-between gap-2">
                            <div><p class="text-sm font-semibold text-gray-900">Riwayat soal ini</p><p class="mt-0.5 text-xs text-gray-500">Hasil sebelumnya tidak memakai token lagi.</p></div>
                            <i class="ri-history-line text-lg text-gray-400"></i>
                        </div>
                        <div class="ai-learning-history mt-3 max-h-52 space-y-2 overflow-y-auto lg:max-h-[58vh]" aria-live="polite"></div>
                        <p class="ai-learning-history-empty mt-3 text-xs leading-5 text-gray-500">Belum ada hasil untuk soal ini.</p>
                    </aside>

                    <div class="order-1 flex min-h-0 flex-col p-4 sm:p-6 lg:order-2 lg:col-span-3">
                        <div class="flex gap-2 overflow-x-auto border-b border-gray-100 pb-3" role="tablist" aria-label="AI Learning Tools">
                            <button type="button" class="ai-learning-tab shrink-0 rounded-lg bg-primary/10 px-3 py-2 text-sm font-semibold text-primary" data-tool="note" role="tab" aria-selected="true"><i class="ri-sticky-note-line mr-1"></i>Catatan</button>
                            <button type="button" class="ai-learning-tab shrink-0 rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100" data-tool="recommendation" role="tab" aria-selected="false"><i class="ri-compass-3-line mr-1"></i>Rekomendasi</button>
                            <button type="button" class="ai-learning-tab shrink-0 rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100" data-tool="question" role="tab" aria-selected="false"><i class="ri-file-add-line mr-1"></i>Soal Serupa</button>
                            <button type="button" class="ai-learning-tab shrink-0 rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100" data-tool="flashcard" role="tab" aria-selected="false"><i class="ri-stack-line mr-1"></i>Flashcard</button>
                        </div>

                        <div class="mt-4">
                            <section class="ai-learning-tab-panel" data-tool-panel="note">
                                <h3 class="font-semibold text-gray-900">AI Catatan Materi</h3>
                                <p class="mt-1 max-w-3xl text-sm leading-6 text-gray-600">Buat catatan materi yang lengkap dari konsep soal, termasuk penjelasan, poin penting, istilah atau rumus, dan hal yang sering keliru. Hasilnya dapat dipin dan diekspor ke PDF.</p>
                            </section>
                            <section class="ai-learning-tab-panel hidden" data-tool-panel="recommendation">
                                <h3 class="font-semibold text-gray-900">AI Rekomendasi Belajar</h3>
                                <p class="mt-1 max-w-3xl text-sm leading-6 text-gray-600">Dapatkan prioritas materi yang perlu dipelajari, urutan belajarnya, serta materi bimbel yang sudah disetujui admin.</p>
                            </section>
                            <section class="ai-learning-tab-panel hidden" data-tool-panel="question">
                                <h3 class="font-semibold text-gray-900">AI Generate Soal</h3>
                                <p class="mt-1 text-sm leading-6 text-gray-600">Buat latihan baru dari konsep soal aktif. Klik <strong>Buat Soal Serupa</strong> untuk membuka pengaturannya terlebih dahulu.</p>
                                <div class="ai-question-settings mt-3 hidden rounded-xl border border-primary/20 bg-primary/5 p-3" aria-hidden="true">
                                    <p class="mb-3 text-sm font-semibold text-primary">Pengaturan untuk Soal Serupa</p>
                                    <div class="grid gap-3 sm:grid-cols-4">
                                        <label class="text-xs font-medium text-primary">Jumlah soal<select class="ai-question-count mt-1 w-full rounded-lg border-primary/25 bg-white px-2.5 py-2 text-sm text-gray-700" @disabled($aiLearningMaxQuestionCount < 1)>@if($aiLearningMaxQuestionCount > 0)@for($count = 1; $count <= $aiLearningMaxQuestionCount; $count++)<option value="{{ $count }}">{{ $count }} soal</option>@endfor @else<option value="">Kuota tidak cukup</option>@endif</select></label>
                                        <label class="text-xs font-medium text-primary">Kesulitan<select class="ai-question-difficulty mt-1 w-full rounded-lg border-primary/25 bg-white px-2.5 py-2 text-sm text-gray-700"><option value="mudah">Mudah</option><option value="sedang" selected>Sedang</option><option value="sulit">Sulit</option></select></label>
                                        <label class="text-xs font-medium text-primary">Variasi<select class="ai-question-variation mt-1 w-full rounded-lg border-primary/25 bg-white px-2.5 py-2 text-sm text-gray-700"><option value="konteks">Ubah konteks</option><option value="angka">Ubah angka</option><option value="hots">Tingkatkan HOTS</option></select></label>
                                        <label class="text-xs font-medium text-primary">Level HOTS<select class="ai-question-hots mt-1 w-full rounded-lg border-primary/25 bg-white px-2.5 py-2 text-sm text-gray-700"><option value="rendah">Rendah</option><option value="sedang" selected>Sedang</option><option value="tinggi">Tinggi</option></select></label>
                                    </div>
                                    <p class="ai-question-count-help mt-2 text-xs text-primary">{{ $aiLearningMaxQuestionCount > 0 ? 'Maksimal ' . $aiLearningMaxQuestionCount . ' soal berdasarkan estimasi sisa token saat ini.' : 'Sisa token belum cukup untuk membuat soal baru.' }}</p>
                                </div>
                            </section>
                            <section class="ai-learning-tab-panel hidden" data-tool-panel="flashcard">
                                <h3 class="font-semibold text-gray-900">AI Flashcard</h3>
                                <p class="mt-1 max-w-3xl text-sm leading-6 text-gray-600">Buat 3–5 kartu fokus untuk hafalan konsep, istilah, rumus, atau pola jawaban. Buka preview untuk belajar dalam mode recall yang lebih fokus.</p>
                            </section>
                        </div>

                        <div class="ai-learning-generate-area mt-4 flex flex-col gap-3 border-b border-gray-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                            <div><p class="ai-learning-action-title text-sm font-semibold text-gray-900">Siap membuat hasil baru?</p><p class="ai-learning-action-copy mt-0.5 text-xs text-gray-500">Hanya klik tombol ini yang memakai kuota AI. Riwayat tetap gratis dibuka.</p></div>
                            <button type="button" class="ai-learning-generate inline-flex min-w-60 items-center justify-center gap-2 rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 hover:shadow disabled:cursor-not-allowed disabled:opacity-60"><i class="ri-sparkling-2-line text-base"></i><span class="ai-learning-generate-label">Buat Catatan Materi Sekarang</span></button>
                        </div>

                        <p class="ai-learning-error mt-3 hidden text-sm text-red-600" role="alert"></p>
                        <div class="ai-learning-result mt-4 hidden rounded-xl border border-green-200 bg-green-50 p-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="ai-learning-result-modal" class="fixed inset-0 z-[99999] hidden overflow-hidden bg-slate-950/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="ai-learning-result-title">
        <div class="flex h-full w-full items-center justify-center">
            <div class="flex max-h-full w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
                <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-5 py-4 sm:px-6">
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-primary">AI Learning Tools</p><h2 id="ai-learning-result-title" class="mt-1 text-lg font-bold text-gray-900">Detail hasil</h2></div>
                    <button type="button" data-ai-learning-result-modal-close class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Tutup detail hasil"><i class="ri-close-line text-xl"></i></button>
                </div>
                <div class="ai-learning-result-modal-content min-h-0 flex-1 overflow-y-auto overscroll-contain p-5 sm:p-6"></div>
            </div>
        </div>
    </div>

    <div id="ai-flashcard-preview-modal" class="fixed inset-0 z-[100000] hidden overflow-y-auto bg-slate-950/85 p-4 backdrop-blur-md sm:p-8" role="dialog" aria-modal="true" aria-labelledby="ai-flashcard-preview-title">
        <div class="flex min-h-full items-center justify-center">
            <div class="w-full max-w-2xl rounded-3xl bg-white p-5 shadow-2xl sm:p-6">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-primary">Mode recall</p><h2 id="ai-flashcard-preview-title" class="ai-flashcard-preview-title mt-1 text-lg font-semibold text-gray-900">Flashcard</h2><p class="mt-1 text-sm text-gray-500">Jawab dalam hati dulu, lalu buka jawabannya.</p></div>
                    <button type="button" data-ai-flashcard-preview-close class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Tutup preview flashcard"><i class="ri-close-line text-xl"></i></button>
                </div>
                <div class="ai-flashcard-preview-content"></div>
            </div>
        </div>
    </div>

    @if($shouldOpenAiGatewayBuyModal)
    <div id="ai-discussion-feature-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 p-4">
        <div class="flex min-h-full items-center justify-center">
            <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-xl lg:max-w-3xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <p class="text-2xl font-semibold text-gray-900 md:text-3xl">{{ $aiGatewayFeatureTitle }}</p>
                    <button type="button" onclick="closeAiDiscussionFeatureModal()" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100" aria-label="Tutup">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
                <button type="button" onclick="openAiGatewayPlanModal('Pilih paket untuk mengaktifkan Diskusi AI Pembahasan.')" class="block w-full bg-gray-50">
                    <img src="{{ asset('img/new-fitures/pembahasan-ai.webp') }}" alt="Pamflet fitur Diskusi AI Pembahasan" class="w-full object-cover">
                </button>
                <div class="flex flex-col gap-2 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" onclick="closeAiDiscussionFeatureModal()" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100">Nanti saja</button>
                    <button type="button" onclick="openAiGatewayPlanModal('Pilih paket untuk mengaktifkan Diskusi AI Pembahasan.')" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">{{ $aiGatewayFeatureButtonText }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div id="ai-gateway-plan-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 p-4">
        <div class="flex min-h-full items-center justify-center">
            <div class="w-full max-w-3xl rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Paket Diskusi AI</h2>
                        <p id="ai-gateway-plan-modal-message" class="mt-1 text-sm text-gray-500">Pilih paket untuk menggunakan Diskusi AI pada pembahasan.</p>
                    </div>
                    <button type="button" onclick="closeAiGatewayPlanModal()" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100" aria-label="Tutup">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                @if($aiGatewayPendingInvoiceUrl)
                    <div class="mt-5 rounded-xl border border-blue-200 bg-blue-50 p-4">
                        <p class="font-semibold text-blue-900">Masih ada pembayaran paket AI yang pending.</p>
                        <p class="mt-1 text-sm text-blue-800">Lanjutkan pembayaran ini dulu supaya invoice tidak dobel.</p>
                        <a href="{{ $aiGatewayPendingInvoiceUrl }}" class="mt-3 inline-flex rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Lanjutkan pembayaran</a>
                    </div>
                @else
                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        @forelse($aiGatewayPlans as $plan)
                            @php
                                $planChatLimit = (int) data_get($plan, 'chat_limit', 0);
                            @endphp
                            <div class="flex flex-col rounded-xl border border-gray-200 p-4">
                                <h3 class="font-semibold text-gray-900">{{ data_get($plan, 'name') }}</h3>
                                <p class="mt-2 text-xl font-bold text-primary">Rp {{ number_format(data_get($plan, 'price'), 0, ',', '.') }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ data_get($plan, 'duration_days') > 0 ? 'Aktif ' . data_get($plan, 'duration_days') . ' hari' : 'Tanpa masa aktif' }}</p>
                                <p class="mt-3 text-sm text-gray-700">{{ $planChatLimit > 0 ? number_format($planChatLimit, 0, ',', '.') . ' chat AI' : 'Chat AI unlimited sampai token habis' }}</p>
                                <form class="mt-4" method="POST" action="{{ route('user.ai-gateway.checkout') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ data_get($plan, 'id') }}">
                                    <input type="hidden" name="return_url" value="{{ $aiGatewayReturnUrl }}">
                                    <button class="w-full rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90">Beli sekarang</button>
                                </form>
                            </div>
                        @empty
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 md:col-span-3">
                                Paket belum dapat dimuat. Coba buka ulang halaman pembahasan ini.
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    console.log('Pembahasan loaded');

    const discussionNavButtons = document.querySelectorAll('.discussion-nav-btn');
    const discussionCards = document.querySelectorAll('.card-pembahasan[data-question-number]');
    const discussionSubtestHeaders = document.querySelectorAll('.discussion-subtest-header');
    const discussionNavStatus = document.getElementById('discussion-nav-status');

    function setDiscussionQuestion(target) {
        discussionSubtestHeaders.forEach(header => {
            header.classList.toggle('hidden', target !== 'all');
        });

        discussionCards.forEach(card => {
            const shouldShow = target === 'all' || card.dataset.questionNumber === target;
            card.classList.toggle('hidden', !shouldShow);
        });

        discussionNavButtons.forEach(button => {
            button.classList.toggle('discussion-nav-active', button.dataset.questionTarget === target);
        });

        if (discussionNavStatus) {
            discussionNavStatus.textContent = target === 'all'
                ? 'Menampilkan semua soal'
                : `Menampilkan soal ${target}`;
        }

    }

    discussionNavButtons.forEach(button => {
        button.addEventListener('click', () => {
            setDiscussionQuestion(button.dataset.questionTarget || 'all');
        });
    });

    const aiDiscussionEndpoint = @json($aiDiscussionEndpointUrl);
    const aiLearningToolEndpoint = @json($aiLearningToolEndpointUrl);
    const aiLearningHistoryEndpoint = @json($aiLearningHistoryEndpointUrl);
    const aiLearningTokensPerGeneratedQuestion = @json($aiLearningTokensPerGeneratedQuestion);
    const aiLearningNoteSaveUrlTemplate = @json(route('user.ai-learning.notes.save', ['artifact' => 'ARTIFACT_ID']));
    const aiLearningNoteExpandUrlTemplate = @json(route('user.ai-learning.notes.expand', ['artifact' => 'ARTIFACT_ID']));
    const aiSpeechEndpoint = @json($aiSpeechEndpointUrl);
    const aiDiscussionHistoryByQuestion = @json($aiDiscussionHistoryByQuestion ?? []);
    const csrfToken = @json(csrf_token());

    function openAiGatewayPlanModal(message = null) {
        closeAiDiscussionFeatureModal(false);
        closeAiLearningToolsModal();
        const modal = document.getElementById('ai-gateway-plan-modal');
        const messageElement = document.getElementById('ai-gateway-plan-modal-message');
        if (message && messageElement) messageElement.textContent = message;
        modal?.classList.remove('hidden');
    }

    function closeAiGatewayPlanModal() {
        document.getElementById('ai-gateway-plan-modal')?.classList.add('hidden');
    }

    function openAiDiscussionFeatureModal() {
        document.getElementById('ai-discussion-feature-modal')?.classList.remove('hidden');
    }

    function closeAiDiscussionFeatureModal(markSeen = true) {
        document.getElementById('ai-discussion-feature-modal')?.classList.add('hidden');
        if (markSeen) {
            localStorage.setItem(@json('ai-discussion-feature-' . $tryout->tryout_id), new Date().toISOString().slice(0, 10));
        }
    }

    function closeAiDiscussionIntro() {
        document.getElementById('ai-discussion-intro-modal')?.classList.add('hidden');
        localStorage.setItem(@json('ai-discussion-intro-' . $tryout->tryout_id), 'seen');
    }

    const aiLearningToolsModal = document.getElementById('ai-learning-tools-modal');
    const aiLearningTabButtons = aiLearningToolsModal?.querySelectorAll('.ai-learning-tab') || [];
    const aiLearningTabPanels = aiLearningToolsModal?.querySelectorAll('.ai-learning-tab-panel') || [];
    const aiLearningGenerateButton = aiLearningToolsModal?.querySelector('.ai-learning-generate');
    const aiLearningGenerateLabel = aiLearningToolsModal?.querySelector('.ai-learning-generate-label');
    const aiLearningResult = aiLearningToolsModal?.querySelector('.ai-learning-result');
    const aiLearningResultModal = document.getElementById('ai-learning-result-modal');
    const aiLearningResultModalContent = aiLearningResultModal?.querySelector('.ai-learning-result-modal-content');
    const aiLearningError = aiLearningToolsModal?.querySelector('.ai-learning-error');
    const aiLearningHistory = aiLearningToolsModal?.querySelector('.ai-learning-history');
    const aiLearningHistoryEmpty = aiLearningToolsModal?.querySelector('.ai-learning-history-empty');
    const aiLearningQuestionCount = aiLearningToolsModal?.querySelector('.ai-question-count');
    const aiLearningQuestionCountHelp = aiLearningToolsModal?.querySelector('.ai-question-count-help');
    const aiLearningQuestionSettings = aiLearningToolsModal?.querySelector('.ai-question-settings');
    const aiLearningActionTitle = aiLearningToolsModal?.querySelector('.ai-learning-action-title');
    const aiLearningActionCopy = aiLearningToolsModal?.querySelector('.ai-learning-action-copy');
    const aiLearningGenerateArea = aiLearningToolsModal?.querySelector('.ai-learning-generate-area');
    const aiVoiceTutorMessages = aiLearningToolsModal?.querySelector('.ai-voice-tutor-messages');
    const aiVoiceTutorStatus = aiLearningToolsModal?.querySelector('.ai-voice-tutor-status');
    const aiVoiceTutorStartButton = aiLearningToolsModal?.querySelector('.ai-voice-tutor-start');
    const aiVoiceTutorStopButton = aiLearningToolsModal?.querySelector('.ai-voice-tutor-stop');
    const aiFlashcardPreviewModal = document.getElementById('ai-flashcard-preview-modal');
    const aiFlashcardPreviewContent = aiFlashcardPreviewModal?.querySelector('.ai-flashcard-preview-content');
    const aiFlashcardPreviewTitle = aiFlashcardPreviewModal?.querySelector('.ai-flashcard-preview-title');
    const aiLearningToolLabels = {
        note: 'Buat Catatan Materi Sekarang',
        recommendation: 'Buat Rekomendasi Belajar',
        question: 'Buat Soal Serupa',
        flashcard: 'Buat Flashcard',
    };
    const aiLearningHistoryToolLabels = {
        note: 'Catatan Materi',
        recommendation: 'Rekomendasi Belajar',
        question: 'Soal Serupa',
        flashcard: 'Flashcard',
    };
    const aiLearningHistoryEntries = new Map();
    const aiLearningResultByTool = new Map();
    let activeAiLearningQuestionId = null;
    let activeAiLearningTool = 'note';
    let aiLearningQuestionSettingsOpen = false;

    function escapeAiLearningHistoryHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function updateAiLearningGenerateAction() {
        const isVoiceTool = activeAiLearningTool === 'voice';
        const isQuestionTool = activeAiLearningTool === 'question';
        const showQuestionSettings = isQuestionTool && aiLearningQuestionSettingsOpen;
        const questionQuotaUnavailable = showQuestionSettings && Boolean(aiLearningQuestionCount?.disabled);

        aiLearningGenerateArea?.classList.toggle('hidden', isVoiceTool);
        aiLearningQuestionSettings?.classList.toggle('hidden', !showQuestionSettings);
        aiLearningQuestionSettings?.setAttribute('aria-hidden', showQuestionSettings ? 'false' : 'true');

        if (isVoiceTool) {
            return;
        }

        if (isQuestionTool && !showQuestionSettings) {
            if (aiLearningGenerateLabel) aiLearningGenerateLabel.textContent = 'Buat Soal Serupa';
            if (aiLearningActionTitle) aiLearningActionTitle.textContent = 'Siapkan latihan baru';
            if (aiLearningActionCopy) aiLearningActionCopy.textContent = 'Klik tombol untuk membuka pengaturan jumlah, kesulitan, variasi, dan level HOTS. Belum memakai kuota AI.';
        } else if (isQuestionTool) {
            if (aiLearningGenerateLabel) aiLearningGenerateLabel.textContent = questionQuotaUnavailable ? 'Kuota Tidak Cukup' : 'Generate Soal Serupa';
            if (aiLearningActionTitle) aiLearningActionTitle.textContent = 'Pengaturan soal sudah siap';
            if (aiLearningActionCopy) aiLearningActionCopy.textContent = questionQuotaUnavailable
                ? 'Sisa token belum cukup untuk membuat soal baru.'
                : 'Klik Generate Soal Serupa untuk membuat latihan dan memakai kuota AI.';
        } else {
            if (aiLearningGenerateLabel) aiLearningGenerateLabel.textContent = aiLearningToolLabels[activeAiLearningTool];
            if (aiLearningActionTitle) aiLearningActionTitle.textContent = 'Siap membuat hasil baru?';
            if (aiLearningActionCopy) aiLearningActionCopy.textContent = 'Hanya klik tombol ini yang memakai kuota AI. Riwayat tetap gratis dibuka.';
        }

        if (aiLearningGenerateButton && !aiLearningGenerateButton.dataset.processing) {
            aiLearningGenerateButton.disabled = questionQuotaUnavailable;
        }
    }

    function selectAiLearningTool(tool) {
        activeAiLearningTool = tool;
        aiLearningTabButtons.forEach((button) => {
            const selected = button.dataset.tool === tool;
            button.setAttribute('aria-selected', selected ? 'true' : 'false');
            button.classList.toggle('bg-primary/10', selected);
            button.classList.toggle('text-primary', selected);
            button.classList.toggle('text-gray-600', !selected);
        });
        aiLearningTabPanels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.toolPanel !== tool);
        });
        updateAiLearningGenerateAction();
        showAiLearningResult(tool === 'voice' ? '' : (aiLearningResultByTool.get(tool) || ''), tool);
        if (tool === 'voice') {
            renderAiVoiceTutorHistory();
        } else if (activeAiLearningQuestionId) {
            loadAiLearningHistory();
        }
    }

    function showAiLearningResult(html, tool = activeAiLearningTool) {
        if (html) aiLearningResultByTool.set(tool, html);
        else aiLearningResultByTool.delete(tool);
        if (tool !== activeAiLearningTool) return;
        if (!aiLearningResult) return;
        aiLearningResult.innerHTML = html
            ? '<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-100 text-green-700"><i class="ri-checkbox-circle-line text-xl"></i></span><div><p class="text-sm font-semibold text-green-950">Hasil AI sudah siap</p><p class="mt-0.5 text-xs text-green-800">Buka hasil lengkapnya dalam modal.</p></div></div><button type="button" class="ai-learning-open-result inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary/90"><i class="ri-eye-line"></i>Lihat hasil</button></div>'
            : '';
        aiLearningResult.classList.toggle('hidden', !html);
    }

    function openAiLearningResultModal(html) {
        if (!html || !aiLearningResultModal || !aiLearningResultModalContent) return;
        aiLearningResultModalContent.innerHTML = html;
        aiLearningResultModal.classList.remove('hidden');
        aiLearningResultModal.classList.add('flex');
    }

    function closeAiLearningResultModal() {
        aiLearningResultModal?.classList.add('hidden');
        aiLearningResultModal?.classList.remove('flex');
        aiLearningResultModalContent?.replaceChildren();
    }

    function renderAiLearningHistory(entries, selectedArtifactId = null) {
        aiLearningHistoryEntries.clear();
        entries.forEach((entry) => aiLearningHistoryEntries.set(String(entry.id), entry));
        if (aiLearningHistory) {
            aiLearningHistory.innerHTML = entries.map((entry) => `
                <button type="button" class="ai-learning-history-item w-full rounded-lg border border-gray-200 bg-white p-2.5 text-left hover:border-primary/40 hover:bg-primary/5" data-artifact-id="${entry.id}">
                    <span class="block text-xs font-semibold text-gray-800">${escapeAiLearningHistoryHtml(entry.title)}</span>
                    <span class="mt-1 block text-[11px] text-gray-500">${escapeAiLearningHistoryHtml(entry.created_at)} · ${escapeAiLearningHistoryHtml(aiLearningHistoryToolLabels[entry.tool] || entry.tool)}</span>
                </button>
            `).join('');
        }
        aiLearningHistoryEmpty?.classList.toggle('hidden', entries.length > 0);

        const selected = selectedArtifactId
            ? aiLearningHistoryEntries.get(String(selectedArtifactId))
            : null;
        if (selected?.html) showAiLearningResult(selected.html, activeAiLearningTool);
    }

    async function loadAiLearningHistory(selectedArtifactId = null) {
        if (!activeAiLearningQuestionId) return;
        if (aiLearningHistory) aiLearningHistory.innerHTML = '<p class="px-1 py-2 text-xs text-gray-500"><i class="ri-loader-4-line mr-1 inline-block animate-spin"></i>Memuat riwayat...</p>';
        aiLearningHistoryEmpty?.classList.add('hidden');

        try {
            const response = await fetch(`${aiLearningHistoryEndpoint}?question_id=${encodeURIComponent(activeAiLearningQuestionId)}&tool=${encodeURIComponent(activeAiLearningTool)}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Riwayat belum dapat dimuat.');
            renderAiLearningHistory(data.artifacts || [], selectedArtifactId);
        } catch (err) {
            if (aiLearningHistory) aiLearningHistory.innerHTML = '';
            if (aiLearningHistoryEmpty) {
                aiLearningHistoryEmpty.textContent = err.message || 'Riwayat belum dapat dimuat.';
                aiLearningHistoryEmpty.classList.remove('hidden');
            }
        }
    }

    function openAiLearningToolsModal(questionId, requestedTool = null) {
        if (activeAiLearningQuestionId && activeAiLearningQuestionId !== questionId) {
            stopAiVoiceTutorSession();
        }
        activeAiLearningQuestionId = questionId;
        aiLearningQuestionSettingsOpen = false;
        aiLearningError?.classList.add('hidden');
        aiLearningResultByTool.clear();
        selectAiLearningTool(requestedTool || activeAiLearningTool);
        aiLearningToolsModal?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeAiLearningToolsModal() {
        stopAiVoiceTutorSession();
        closeAiLearningResultModal();
        closeAiFlashcardPreviewModal();
        aiLearningToolsModal?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        activeAiLearningQuestionId = null;
    }

    function openAiFlashcardPreview(trigger) {
        const artifact = trigger.closest('[data-ai-tool="flashcard"]');
        const template = artifact?.querySelector('.ai-flashcard-preview-template');
        if (!(template instanceof HTMLTemplateElement) || !aiFlashcardPreviewContent) return;

        aiFlashcardPreviewContent.replaceChildren(template.content.cloneNode(true));
        if (aiFlashcardPreviewTitle) aiFlashcardPreviewTitle.textContent = trigger.dataset.flashcardTitle || 'Flashcard';
        aiFlashcardPreviewModal?.classList.remove('hidden');
    }

    function closeAiFlashcardPreviewModal() {
        aiFlashcardPreviewModal?.classList.add('hidden');
        aiFlashcardPreviewContent?.replaceChildren();
    }

    const VoiceTutorRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let aiVoiceTutorRecognition = null;
    let aiVoiceTutorVad = null;
    let aiVoiceTutorActive = false;
    let aiVoiceTutorProcessing = false;
    let aiVoiceTutorAudioPlaying = false;
    let aiVoiceTutorFinalTranscript = '';
    let aiVoiceTutorSendTimer = null;
    let aiVoiceTutorLiveBubble = null;

    function setAiVoiceTutorStatus(message) {
        if (aiVoiceTutorStatus) aiVoiceTutorStatus.textContent = message;
    }

    function renderAiVoiceTutorHistory() {
        if (!aiVoiceTutorMessages || !activeAiLearningQuestionId) return;

        aiVoiceTutorMessages.innerHTML = '';
        const history = aiDiscussionHistoryByQuestion[activeAiLearningQuestionId] || [];
        if (history.length === 0) {
            appendAiDiscussionMessage(aiVoiceTutorMessages, 'Ceritakan bagian yang bikin mentok—aku bantu urut dari situ.', 'ai');
            return;
        }

        history.forEach((entry) => {
            appendAiDiscussionMessage(aiVoiceTutorMessages, entry.user_message, 'user');
            appendAiDiscussionMessage(aiVoiceTutorMessages, entry.assistant_message, 'ai', entry.id);
        });
    }

    function clearAiVoiceTutorPendingSend() {
        if (aiVoiceTutorSendTimer) window.clearTimeout(aiVoiceTutorSendTimer);
        aiVoiceTutorSendTimer = null;
    }

    function renderAiVoiceTutorLiveTranscript(text) {
        if (!aiVoiceTutorMessages) return;
        if (!aiVoiceTutorLiveBubble) {
            aiVoiceTutorLiveBubble = document.createElement('div');
            aiVoiceTutorLiveBubble.className = 'ml-auto max-w-[88%] rounded-xl bg-primary/10 px-3 py-2 text-sm italic text-primary';
            aiVoiceTutorMessages.appendChild(aiVoiceTutorLiveBubble);
        }
        aiVoiceTutorLiveBubble.textContent = text ? `Kamu: ${text}` : 'Mendengarkan...';
        aiVoiceTutorMessages.scrollTop = aiVoiceTutorMessages.scrollHeight;
    }

    function removeAiVoiceTutorLiveTranscript() {
        aiVoiceTutorLiveBubble?.remove();
        aiVoiceTutorLiveBubble = null;
    }

    function startAiVoiceTutorRecognition() {
        if (!aiVoiceTutorActive || aiVoiceTutorProcessing || aiVoiceTutorAudioPlaying || !aiVoiceTutorRecognition) return;
        try {
            aiVoiceTutorRecognition.start();
        } catch (_) {
            // Recognition sedang aktif atau browser sedang menyelesaikan giliran sebelumnya.
        }
    }

    async function initializeAiVoiceTutorVad() {
        if (aiVoiceTutorVad || !window.vad?.MicVAD) return aiVoiceTutorVad;

        try {
            aiVoiceTutorVad = await window.vad.MicVAD.new({
                onSpeechStart: () => {
                    clearAiVoiceTutorPendingSend();
                    if (aiVoiceTutorActive && !aiVoiceTutorProcessing && !aiVoiceTutorAudioPlaying) {
                        setAiVoiceTutorStatus('Aku dengar, lanjutkan dulu ya...');
                    }
                },
                onSpeechEnd: () => {
                    if (aiVoiceTutorActive && aiVoiceTutorFinalTranscript.trim() !== '') {
                        scheduleAiVoiceTutorSend();
                    }
                },
                onnxWASMBasePath: 'https://cdn.jsdelivr.net/npm/onnxruntime-web@1.22.0/dist/',
                baseAssetPath: 'https://cdn.jsdelivr.net/npm/@ricky0123/vad-web@0.0.29/dist/',
            });
        } catch (_) {
            // SpeechRecognition tetap dapat bekerja jika VAD gagal dimuat.
            aiVoiceTutorVad = null;
        }

        return aiVoiceTutorVad;
    }

    async function startAiVoiceTutorSession() {
        if (!activeAiLearningQuestionId || !VoiceTutorRecognition) {
            setAiVoiceTutorStatus('Input suara belum didukung browser ini. Coba gunakan Chrome atau Edge terbaru.');
            return;
        }

        aiVoiceTutorActive = true;
        aiVoiceTutorStartButton?.classList.add('hidden');
        aiVoiceTutorStopButton?.classList.remove('hidden');
        setAiVoiceTutorStatus('Menyiapkan mikrofon...');

        if (!aiVoiceTutorRecognition) {
            aiVoiceTutorRecognition = new VoiceTutorRecognition();
            aiVoiceTutorRecognition.lang = 'id-ID';
            aiVoiceTutorRecognition.continuous = true;
            aiVoiceTutorRecognition.interimResults = true;

            aiVoiceTutorRecognition.onstart = () => {
                if (aiVoiceTutorActive && !aiVoiceTutorProcessing && !aiVoiceTutorAudioPlaying) {
                    setAiVoiceTutorStatus('Silakan bicara. Setelah kamu berhenti, Guru AI akan menjawab otomatis.');
                }
            };
            aiVoiceTutorRecognition.onresult = (event) => {
                let interimTranscript = '';
                for (let index = event.resultIndex; index < event.results.length; index += 1) {
                    const result = event.results[index];
                    const transcript = result[0]?.transcript || '';
                    if (result.isFinal) aiVoiceTutorFinalTranscript += `${transcript} `;
                    else interimTranscript += transcript;
                }
                renderAiVoiceTutorLiveTranscript(`${aiVoiceTutorFinalTranscript}${interimTranscript}`.trim());
                if (aiVoiceTutorFinalTranscript.trim() !== '') scheduleAiVoiceTutorSend();
            };
            aiVoiceTutorRecognition.onerror = (event) => {
                if (event.error === 'aborted' || event.error === 'no-speech') return;
                setAiVoiceTutorStatus('Suara belum terbaca. Coba ucapkan lagi dengan lebih dekat ke mikrofon.');
            };
            aiVoiceTutorRecognition.onend = () => {
                if (aiVoiceTutorActive && !aiVoiceTutorProcessing && !aiVoiceTutorAudioPlaying) {
                    window.setTimeout(startAiVoiceTutorRecognition, 250);
                }
            };
        }

        const vad = await initializeAiVoiceTutorVad();
        try {
            await vad?.start();
        } catch (_) {
            // VAD bersifat peningkat pengalaman; sesi tetap berjalan lewat SpeechRecognition.
        }
        startAiVoiceTutorRecognition();
    }

    function scheduleAiVoiceTutorSend() {
        clearAiVoiceTutorPendingSend();
        aiVoiceTutorSendTimer = window.setTimeout(sendAiVoiceTutorMessage, 900);
    }

    async function sendAiVoiceTutorMessage() {
        const message = aiVoiceTutorFinalTranscript.trim();
        clearAiVoiceTutorPendingSend();
        if (!message || !activeAiLearningQuestionId || aiVoiceTutorProcessing) return;

        aiVoiceTutorFinalTranscript = '';
        aiVoiceTutorProcessing = true;
        removeAiVoiceTutorLiveTranscript();
        try {
            aiVoiceTutorRecognition?.stop();
            await aiVoiceTutorVad?.pause();
        } catch (_) {
            // Tidak perlu menghentikan alur jika browser telah menutup mikrofon lebih dulu.
        }

        appendAiDiscussionMessage(aiVoiceTutorMessages, message, 'user');
        const loadingBubble = appendAiDiscussionMessage(aiVoiceTutorMessages, 'Guru AI sedang menyiapkan penjelasan...', 'loading');
        setAiVoiceTutorStatus('Guru AI sedang menyiapkan jawaban...');

        try {
            const response = await fetch(aiDiscussionEndpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    question_id: activeAiLearningQuestionId,
                    message,
                    mode: 'voice',
                }),
            });
            const data = await response.json();
            if (!response.ok) {
                if (String(data.message || '').includes('Paket Diskusi AI')) {
                    loadingBubble.remove();
                    openAiGatewayPlanModal('Paket AI diperlukan untuk memulai sesi Guru Suara.');
                    return;
                }
                throw new Error(data.message || 'Guru AI belum dapat menjawab.');
            }

            loadingBubble.remove();
            const aiMessage = data.message || 'Maaf, jawaban Guru AI belum tersedia.';
            appendAiDiscussionMessage(aiVoiceTutorMessages, aiMessage, 'ai', data.discussion_log_id);
            const history = aiDiscussionHistoryByQuestion[activeAiLearningQuestionId] || [];
            history.push({ id: data.discussion_log_id, user_message: message, assistant_message: aiMessage });
            aiDiscussionHistoryByQuestion[activeAiLearningQuestionId] = history;
            updateAiGatewayUsageBadge(data.quota);
            setAiVoiceTutorStatus('Guru AI sedang menjelaskan...');
            aiVoiceTutorAudioPlaying = true;
            await playAiDiscussionAudio(data.discussion_log_id, aiMessage);
        } catch (error) {
            loadingBubble.remove();
            setAiVoiceTutorStatus(error.message || 'Guru AI belum dapat menjawab. Coba ucapkan lagi sebentar.');
        } finally {
            aiVoiceTutorProcessing = false;
            aiVoiceTutorAudioPlaying = false;
            if (aiVoiceTutorActive) {
                setAiVoiceTutorStatus('Silakan lanjut bicara, Guru AI sedang mendengarkan.');
                try {
                    await aiVoiceTutorVad?.start();
                } catch (_) {
                    // SpeechRecognition tetap menjadi fallback otomatis.
                }
                startAiVoiceTutorRecognition();
            }
        }
    }

    function stopAiVoiceTutorSession() {
        aiVoiceTutorActive = false;
        aiVoiceTutorProcessing = false;
        aiVoiceTutorAudioPlaying = false;
        aiVoiceTutorFinalTranscript = '';
        clearAiVoiceTutorPendingSend();
        removeAiVoiceTutorLiveTranscript();
        try {
            aiVoiceTutorRecognition?.abort();
            aiVoiceTutorVad?.pause();
        } catch (_) {
            // Browser mungkin sudah melepas mikrofon.
        }
        window.speechSynthesis?.cancel();
        aiVoiceTutorStartButton?.classList.remove('hidden');
        aiVoiceTutorStopButton?.classList.add('hidden');
        setAiVoiceTutorStatus('Sesi diakhiri. Kamu bisa mulai lagi kapan pun.');
    }

    aiVoiceTutorStartButton?.addEventListener('click', startAiVoiceTutorSession);
    aiVoiceTutorStopButton?.addEventListener('click', stopAiVoiceTutorSession);

    document.querySelectorAll('.ai-learning-open').forEach((button) => {
        button.addEventListener('click', () => {
            const questionId = button.closest('.ai-discussion')?.dataset.questionId;
            if (questionId) openAiLearningToolsModal(questionId, button.dataset.aiLearningTab || null);
        });
    });

    aiLearningTabButtons.forEach((button) => {
        button.addEventListener('click', () => selectAiLearningTool(button.dataset.tool || 'note'));
    });

    aiLearningHistory?.addEventListener('click', (event) => {
        const historyItem = event.target.closest('.ai-learning-history-item');
        const artifact = aiLearningHistoryEntries.get(String(historyItem?.dataset.artifactId || ''));
        if (artifact?.html) openAiLearningResultModal(artifact.html);
    });

    aiLearningResult?.addEventListener('click', (event) => {
        if (!event.target.closest('.ai-learning-open-result')) return;
        openAiLearningResultModal(aiLearningResultByTool.get(activeAiLearningTool) || '');
    });

    aiLearningToolsModal?.querySelectorAll('[data-ai-learning-modal-close]').forEach((button) => {
        button.addEventListener('click', closeAiLearningToolsModal);
    });

    aiLearningResultModal?.querySelectorAll('[data-ai-learning-result-modal-close]').forEach((button) => {
        button.addEventListener('click', closeAiLearningResultModal);
    });

    aiFlashcardPreviewModal?.querySelectorAll('[data-ai-flashcard-preview-close]').forEach((button) => {
        button.addEventListener('click', closeAiFlashcardPreviewModal);
    });

    aiLearningToolsModal?.addEventListener('click', (event) => {
        if (event.target === aiLearningToolsModal) closeAiLearningToolsModal();
    });

    aiLearningResultModal?.addEventListener('click', (event) => {
        if (event.target === aiLearningResultModal) closeAiLearningResultModal();
    });

    aiFlashcardPreviewModal?.addEventListener('click', (event) => {
        if (event.target === aiFlashcardPreviewModal) closeAiFlashcardPreviewModal();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !aiFlashcardPreviewModal?.classList.contains('hidden')) {
            closeAiFlashcardPreviewModal();
            return;
        }
        if (event.key === 'Escape' && !aiLearningResultModal?.classList.contains('hidden')) {
            closeAiLearningResultModal();
            return;
        }
        if (event.key === 'Escape' && !aiLearningToolsModal?.classList.contains('hidden')) {
            closeAiLearningToolsModal();
        }
    });

    function updateAiGatewayUsageBadge(quota) {
        if (!quota) return;
        const badge = document.getElementById('ai-gateway-usage-badge');
        const percentage = document.getElementById('ai-gateway-used-percentage');
        const label = document.getElementById('ai-gateway-used-label');
        const chatLimit = Number(quota.chat_limit || 0);
        const chatsUsed = Number(quota.chats_used || 0);
        if (!badge || !percentage || !label) return;
        if (chatLimit > 0) {
            const usedPercentage = Math.min(100, (chatsUsed / chatLimit) * 100);
            badge.style.background = `conic-gradient({{ $primaryColor }} ${usedPercentage}%, #e5e7eb 0)`;
            percentage.textContent = `${Math.round(usedPercentage)}%`;
            label.textContent = 'terpakai';
        } else {
            percentage.textContent = '∞';
            label.textContent = 'aktif';
        }
    }

    function updateAiLearningQuestionCountLimit(quota) {
        if (!aiLearningQuestionCount || !quota) return;
        const tokenLimit = Number(quota.token_limit || 0);
        const tokensUsed = Number(quota.tokens_used || 0);
        const remainingTokens = tokenLimit > 0 ? Math.max(0, tokenLimit - tokensUsed) : null;
        const maxCount = remainingTokens === null
            ? 5
            : Math.min(5, Math.floor(remainingTokens / aiLearningTokensPerGeneratedQuestion));
        const previousValue = Number(aiLearningQuestionCount.value || 1);

        aiLearningQuestionCount.innerHTML = '';
        if (maxCount < 1) {
            aiLearningQuestionCount.disabled = true;
            aiLearningQuestionCount.add(new Option('Kuota tidak cukup', ''));
            if (aiLearningQuestionCountHelp) aiLearningQuestionCountHelp.textContent = 'Sisa token belum cukup untuk membuat soal baru.';
            updateAiLearningGenerateAction();
            return;
        }

        aiLearningQuestionCount.disabled = false;
        for (let count = 1; count <= maxCount; count += 1) {
            aiLearningQuestionCount.add(new Option(`${count} soal`, String(count), false, count === Math.min(previousValue, maxCount)));
        }
        if (aiLearningQuestionCountHelp) aiLearningQuestionCountHelp.textContent = `Maksimal ${maxCount} soal berdasarkan estimasi sisa token saat ini.`;
        updateAiLearningGenerateAction();
    }

    function startAiDiscussionTrial() {
        closeAiDiscussionIntro();
        const toggle = document.querySelector('.ai-discussion-toggle');
        const discussion = toggle?.closest('.ai-discussion');
        discussion?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (toggle && discussion?.querySelector('.ai-discussion-body')?.classList.contains('hidden')) {
            toggle.click();
        }
    }

    const aiDiscussionFeatureSeenKey = @json('ai-discussion-feature-' . $tryout->tryout_id);
    const aiDiscussionFeatureSeenToday = localStorage.getItem(aiDiscussionFeatureSeenKey) === new Date().toISOString().slice(0, 10);

    if (@json($shouldOpenAiGatewayBuyModal && request('payment') !== 'success') && !aiDiscussionFeatureSeenToday) {
        setTimeout(() => openAiDiscussionFeatureModal(), 500);
    } else if (@json($hasAiGatewayTrial) && localStorage.getItem(@json('ai-discussion-intro-' . $tryout->tryout_id)) !== 'seen') {
        setTimeout(() => document.getElementById('ai-discussion-intro-modal')?.classList.remove('hidden'), 500);
    }

    aiLearningGenerateButton?.addEventListener('click', async () => {
        if (!activeAiLearningQuestionId) return;

        if (activeAiLearningTool === 'question' && !aiLearningQuestionSettingsOpen) {
            aiLearningQuestionSettingsOpen = true;
            updateAiLearningGenerateAction();
            aiLearningQuestionSettings?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return;
        }

        aiLearningError?.classList.add('hidden');
        aiLearningGenerateButton.disabled = true;
        aiLearningGenerateButton.dataset.processing = 'true';
        const originalHtml = aiLearningGenerateButton.innerHTML;
        aiLearningGenerateButton.innerHTML = '<i class="ri-loader-4-line animate-spin"></i>Memproses...';

        try {
            const response = await fetch(aiLearningToolEndpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    question_id: activeAiLearningQuestionId,
                    tool: activeAiLearningTool,
                    difficulty: aiLearningToolsModal?.querySelector('.ai-question-difficulty')?.value || 'sedang',
                    variation: aiLearningToolsModal?.querySelector('.ai-question-variation')?.value || 'konteks',
                    hots_level: aiLearningToolsModal?.querySelector('.ai-question-hots')?.value || 'sedang',
                    question_count: activeAiLearningTool === 'question'
                        ? Number(aiLearningQuestionCount?.value || 1)
                        : 1,
                }),
            });
            const data = await response.json();

            if (!response.ok) {
                if (String(data.message || '').includes('Paket Diskusi AI')) {
                    openAiGatewayPlanModal('Paket AI diperlukan untuk menggunakan AI Learning Tools.');
                    return;
                }
                throw new Error(data.message || 'Fitur AI gagal diproses.');
            }

            showAiLearningResult(data.html || '', activeAiLearningTool);
            updateAiGatewayUsageBadge(data.quota);
            updateAiLearningQuestionCountLimit(data.quota);
            loadAiLearningHistory(data.artifact_id);
        } catch (err) {
            if (aiLearningError) {
                aiLearningError.textContent = err.message || 'Fitur AI gagal diproses.';
                aiLearningError.classList.remove('hidden');
            }
        } finally {
            aiLearningGenerateButton.innerHTML = originalHtml;
            aiLearningGenerateButton.disabled = false;
            delete aiLearningGenerateButton.dataset.processing;
            updateAiLearningGenerateAction();
        }
    });

    function handleAiFlashcardStudyClick(event) {
        const study = event.target.closest('.ai-flashcard-study');
        if (!study) return false;

        const cards = Array.from(study.querySelectorAll('.ai-flashcard'));
        const setControlsDisabled = (disabled) => {
            study.querySelectorAll('.ai-flashcard-flip, .ai-flashcard-remember, .ai-flashcard-forgot')
                .forEach((button) => { button.disabled = disabled; });
        };
        const showCard = (index) => {
            cards.forEach((card, cardIndex) => {
                const visible = cardIndex === index;
                card.classList.toggle('hidden', !visible);
                if (visible) {
                    card.classList.remove('is-exiting-remembered', 'is-exiting-forgotten', 'is-showing-back');
                    card.dataset.showing = 'front';
                    delete card.dataset.transitioning;
                    card.querySelector('.ai-flashcard-front .ai-flashcard-content').textContent = card.dataset.front;
                    card.querySelector('.ai-flashcard-back .ai-flashcard-content').textContent = card.dataset.back;
                    card.classList.remove('is-entering');
                    window.requestAnimationFrame(() => card.classList.add('is-entering'));
                    window.setTimeout(() => card.classList.remove('is-entering'), 380);
                }
            });
            setControlsDisabled(false);
            study.dataset.currentIndex = String(index);
            const progress = study.querySelector('.ai-flashcard-progress');
            if (progress) progress.textContent = `Kartu ${index + 1} dari ${cards.length}`;
        };
        const finishRound = () => {
            const forgotten = cards.filter((card) => card.dataset.status === 'forgotten').length;
            study.querySelector('.ai-flashcard-forgot')?.classList.add('hidden');
            study.querySelector('.ai-flashcard-remember')?.classList.add('hidden');
            const complete = study.querySelector('.ai-flashcard-complete');
            const completeCopy = study.querySelector('.ai-flashcard-complete-copy');
            const recall = study.querySelector('.ai-flashcard-recall');
            complete?.classList.remove('hidden');
            if (completeCopy) completeCopy.textContent = forgotten > 0
                ? `${forgotten} kartu masih perlu diulang.`
                : 'Semua kartu sudah kamu tandai ingat.';
            recall?.classList.toggle('hidden', forgotten === 0);
        };
        const continueStudy = () => {
            const nextIndex = cards.findIndex((card) => card.dataset.status === 'new');
            if (nextIndex === -1) {
                finishRound();
                return;
            }
            showCard(nextIndex);
        };
        const currentCard = () => cards[Number(study.dataset.currentIndex || 0)];
        const advanceCard = (status) => {
            const card = currentCard();
            if (!card || card.dataset.transitioning) return;

            card.dataset.transitioning = 'true';
            setControlsDisabled(true);
            card.classList.add(status === 'remembered' ? 'is-exiting-remembered' : 'is-exiting-forgotten');
            window.setTimeout(() => {
                card.dataset.status = status;
                card.classList.add('hidden');
                card.classList.remove('is-exiting-remembered', 'is-exiting-forgotten');
                delete card.dataset.transitioning;
                continueStudy();
            }, 330);
        };

        if (event.target.closest('.ai-flashcard-flip')) {
            const card = currentCard();
            if (!card || card.dataset.transitioning) return true;
            card.dataset.transitioning = 'true';
            setControlsDisabled(true);
            const showBack = card.dataset.showing !== 'back';
            card.dataset.showing = showBack ? 'back' : 'front';
            card.classList.toggle('is-showing-back', showBack);
            window.setTimeout(() => {
                delete card.dataset.transitioning;
                setControlsDisabled(false);
            }, 620);
            return true;
        }

        if (event.target.closest('.ai-flashcard-remember')) {
            advanceCard('remembered');
            return true;
        }

        if (event.target.closest('.ai-flashcard-forgot')) {
            advanceCard('forgotten');
            return true;
        }

        if (event.target.closest('.ai-flashcard-recall')) {
            cards.forEach((card) => {
                if (card.dataset.status === 'forgotten') card.dataset.status = 'new';
            });
            study.querySelector('.ai-flashcard-complete')?.classList.add('hidden');
            study.querySelector('.ai-flashcard-forgot')?.classList.remove('hidden');
            study.querySelector('.ai-flashcard-remember')?.classList.remove('hidden');
            continueStudy();
            return true;
        }

        return false;
    }

    aiLearningResultModalContent?.addEventListener('click', async (event) => {
        const expandToggle = event.target.closest('.ai-note-expand-toggle');
        if (expandToggle) {
            const panel = expandToggle.closest('[data-ai-tool="note"]')?.querySelector('.ai-note-expand-panel');
            panel?.classList.toggle('hidden');
            return;
        }

        const expandSubmit = event.target.closest('.ai-note-expand-submit');
        if (expandSubmit) {
            const panel = expandSubmit.closest('.ai-note-expand-panel');
            const focus = panel?.querySelector('.ai-note-expand-focus')?.value?.trim() || '';
            const error = panel?.querySelector('.ai-note-expand-error');
            const original = expandSubmit.innerHTML;
            expandSubmit.disabled = true;
            expandSubmit.innerHTML = '<i class="ri-loader-4-line animate-spin"></i>Memproses...';
            error?.classList.add('hidden');

            try {
                const response = await fetch(aiLearningNoteExpandUrlTemplate.replace('ARTIFACT_ID', expandSubmit.dataset.artifactId), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: new URLSearchParams({ focus }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Catatan belum dapat diperdalam.');
                openAiLearningResultModal(data.html || '');
                updateAiGatewayUsageBadge(data.quota);
                updateAiLearningQuestionCountLimit(data.quota);
                loadAiLearningHistory(data.artifact_id);
            } catch (expandError) {
                expandSubmit.disabled = false;
                expandSubmit.innerHTML = original;
                if (error) {
                    error.textContent = expandError.message || 'Catatan belum dapat diperdalam.';
                    error.classList.remove('hidden');
                }
            }
            return;
        }

        const saveButton = event.target.closest('.ai-pin-artifact');
        if (saveButton) {
            saveButton.disabled = true;
            saveButton.textContent = 'Mem-pin...';
            try {
                const response = await fetch(aiLearningNoteSaveUrlTemplate.replace('ARTIFACT_ID', saveButton.dataset.artifactId), {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Hasil AI gagal dipin.');
                const pinnedActions = document.createElement('div');
                pinnedActions.className = 'flex shrink-0 items-center gap-2';
                pinnedActions.innerHTML = '<span class="inline-flex items-center gap-1 rounded-lg bg-primary/10 px-3 py-2 text-xs font-semibold text-primary"><i class="ri-pushpin-2-fill"></i>Dipin</span>';
                if (data.pdf_url) {
                    const pdfLink = document.createElement('a');
                    pdfLink.href = data.pdf_url;
                    pdfLink.className = 'rounded-lg border border-primary/30 px-3 py-2 text-xs font-semibold text-primary hover:bg-primary/5';
                    pdfLink.innerHTML = '<i class="ri-file-pdf-2-line mr-1"></i>Ekspor PDF';
                    pinnedActions.append(pdfLink);
                }
                saveButton.replaceWith(pinnedActions);
            } catch (err) {
                saveButton.disabled = false;
                saveButton.textContent = 'Pin';
                if (aiLearningError) {
                    aiLearningError.textContent = err.message || 'Hasil AI gagal dipin.';
                    aiLearningError.classList.remove('hidden');
                }
            }
            return;
        }

        const previewButton = event.target.closest('.ai-flashcard-preview');
        if (previewButton) {
            openAiFlashcardPreview(previewButton);
            return;
        }

        handleAiFlashcardStudyClick(event);
    });

    aiFlashcardPreviewContent?.addEventListener('click', handleAiFlashcardStudyClick);

    document.querySelectorAll('.ai-discussion').forEach((wrapper) => {
        const toggle = wrapper.querySelector('.ai-discussion-toggle');
        const body = wrapper.querySelector('.ai-discussion-body');
        const form = wrapper.querySelector('.ai-discussion-form');
        const input = wrapper.querySelector('.ai-discussion-input');
        const messages = wrapper.querySelector('.ai-discussion-messages');
        const error = wrapper.querySelector('.ai-discussion-error');
        const submitButton = form?.querySelector('button[type="submit"]');
        const voiceButton = wrapper.querySelector('.ai-discussion-voice');
        let replyWithVoice = false;
        let discussionMode = 'text';

        const history = aiDiscussionHistoryByQuestion[wrapper.dataset.questionId] || [];
        if (history.length > 0) {
            messages.innerHTML = '';
            history.forEach((entry) => {
                appendAiDiscussionMessage(messages, entry.user_message, 'user');
                appendAiDiscussionMessage(messages, entry.assistant_message, 'ai', entry.id);
            });
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (!SpeechRecognition) {
            voiceButton?.setAttribute('disabled', 'disabled');
            voiceButton?.setAttribute('title', 'Input suara belum didukung browser ini.');
        } else {
            const recognition = new SpeechRecognition();
            recognition.lang = 'id-ID';
            recognition.interimResults = false;
            recognition.continuous = false;

            voiceButton?.addEventListener('click', () => {
                error?.classList.add('hidden');
                discussionMode = 'voice';
                recognition.start();
            });

            recognition.onstart = () => {
                voiceButton.disabled = true;
                voiceButton.innerHTML = '<i class="ri-record-circle-line animate-pulse text-lg text-red-500"></i>';
            };
            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                input.value = transcript;
                replyWithVoice = true;
                form.requestSubmit();
            };
            recognition.onerror = () => {
                if (error) {
                    error.textContent = 'Suara belum terbaca. Coba lagi atau ketik pertanyaanmu.';
                    error.classList.remove('hidden');
                }
            };
            recognition.onend = () => {
                voiceButton.disabled = false;
                voiceButton.innerHTML = '<i class="ri-mic-line text-lg"></i><span class="sr-only">Tanya dengan suara</span>';
            };
        }

        toggle?.addEventListener('click', () => {
            const isHidden = body.classList.toggle('hidden');
            toggle.innerHTML = isHidden
                ? '<i class="ri-message-3-line"></i> Buka'
                : '<i class="ri-close-line"></i> Tutup';
            if (!isHidden) {
                input?.focus();
            }
        });

        form?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const message = (input.value || '').trim();
            if (!message) {
                return;
            }
            const shouldSpeakResponse = replyWithVoice;
            replyWithVoice = false;
            const requestMode = discussionMode;
            discussionMode = 'text';

            const userBubble = appendAiDiscussionMessage(messages, message, 'user');
            input.value = '';
            error?.classList.add('hidden');
            submitButton.disabled = true;
            const loadingBubble = appendAiDiscussionMessage(messages, 'AI sedang menyusun jawaban...', 'loading');

            try {
                const response = await fetch(aiDiscussionEndpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        question_id: wrapper.dataset.questionId,
                        message,
                        mode: requestMode,
                    }),
                });
                const data = await response.json();

                if (!response.ok) {
                    if (String(data.message || '').includes('Paket Diskusi AI')) {
                        loadingBubble.remove();
                        userBubble.remove();
                        openAiGatewayPlanModal('Paket AI diperlukan untuk melanjutkan diskusi. Pilih paket yang sesuai di bawah.');
                        return;
                    }
                    throw new Error(data.message || 'Diskusi AI gagal diproses.');
                }

                loadingBubble.remove();
                const aiMessage = data.message || 'AI tidak mengembalikan jawaban.';
                appendAiDiscussionMessage(messages, aiMessage, 'ai', data.discussion_log_id);
                updateAiGatewayUsageBadge(data.quota);
                if (shouldSpeakResponse) {
                    playAiDiscussionAudio(data.discussion_log_id, aiMessage);
                }
            } catch (err) {
                loadingBubble.remove();
                if (error) {
                    error.textContent = err.message || 'Diskusi AI gagal diproses.';
                    error.classList.remove('hidden');
                }
            } finally {
                submitButton.disabled = false;
                messages.scrollTop = messages.scrollHeight;
            }
        });

    });

    function appendAiDiscussionMessage(messages, text, type, usageLogId = null) {
        const bubble = document.createElement('div');
        const isUser = type === 'user';
        const isAiResponse = type === 'ai';
        bubble.className = [
            'max-w-[92%] rounded-lg px-3 py-2 text-sm leading-relaxed',
            isUser ? 'ml-auto bg-primary text-white' : 'bg-white border border-gray-200 text-gray-700',
            type === 'loading' ? 'text-gray-500 italic' : '',
        ].join(' ');

        // Respons model berupa Markdown. Format elemen yang umum digunakan,
        // tetapi escape semua HTML terlebih dahulu agar respons tetap aman.
        if (isAiResponse) {
            bubble.classList.add('ai-discussion-markdown');
            bubble.innerHTML = formatAiDiscussionMarkdown(text);

        } else {
            bubble.classList.add('whitespace-pre-line');
            bubble.textContent = text;
        }
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
        return bubble;
    }

    function aiDiscussionSpeechText(text) {
        const explanation = String(text || '')
            .split(/(?:^|\n)\s*(?:\*\*)?catatan\s+inti\s*:?(?:\*\*)?/iu)[0]
            .replace(/\*\*([^*]+)\*\*/gu, '$1')
            .replace(/[*#`_$<>]/gu, '')
            .replace(/&/gu, ' dan ')
            .replace(/=/gu, ' sama dengan ')
            .replace(/\+/gu, ' ditambah ')
            .replace(/÷/gu, ' dibagi ')
            .replace(/(\d)([a-zA-Z])/gu, '$1 $2')
            .replace(/\s+-\s+/gu, ' dikurangi ')
            .replace(/\s+/gu, ' ')
            .trim();

        return explanation || 'Maaf, penjelasan suara belum tersedia.';
    }

    async function playAudioBlob(audioBlob) {
        const audioUrl = URL.createObjectURL(audioBlob);
        const audio = new Audio(audioUrl);
        await audio.play();
        await new Promise((resolve) => {
            audio.onended = () => {
                URL.revokeObjectURL(audioUrl);
                resolve();
            };
            audio.onerror = () => {
                URL.revokeObjectURL(audioUrl);
                resolve();
            };
        });
    }

    async function playAiDiscussionAudio(usageLogId, text) {
        if (!Number.isInteger(Number(usageLogId))) {
            await speakAiDiscussionResponseFallback(text);
            return;
        }

        try {
            const response = await fetch(aiSpeechEndpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'audio/mpeg, application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ usage_log_id: usageLogId }),
            });
            if (!response.ok) throw new Error('TTS gagal');
            await playAudioBlob(await response.blob());
            return;
        } catch (_) {
            await speakAiDiscussionResponseFallback(text);
        }
    }

    function speakAiDiscussionResponseFallback(text) {
        if (!('speechSynthesis' in window)) return Promise.resolve();
        window.speechSynthesis.cancel();
        const spokenText = String(text)
            .replace(/[*#`_]/g, '')
            .replace(/\n+/g, '. ');
        const utterance = new SpeechSynthesisUtterance(spokenText);
        utterance.lang = 'id-ID';
        utterance.rate = 1.08;
        const indonesianVoice = window.speechSynthesis.getVoices()
            .find((voice) => voice.lang?.toLowerCase().startsWith('id'));
        if (indonesianVoice) utterance.voice = indonesianVoice;

        return new Promise((resolve) => {
            utterance.onend = resolve;
            utterance.onerror = resolve;
            window.speechSynthesis.speak(utterance);
        });
    }

    function formatAiDiscussionMarkdown(text) {
        const escapeHtml = (value) => String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const readableMath = (value) => String(value)
            .replace(/\$([^$\n]+)\$/g, '$1')
            .replace(/\\frac\{([^{}]+)\}\{([^{}]+)\}/g, '$1 / $2')
            .replace(/\\(?:times|cdot)/g, '×')
            .replace(/\\div/g, '÷')
            .replace(/[{}\\]/g, '');

        const inline = (value) => escapeHtml(readableMath(value))
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/__([^_]+)__/g, '<strong>$1</strong>')
            .replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>');

        const output = [];
        let listType = null;
        const closeList = () => {
            if (listType) output.push(`</${listType}>`);
            listType = null;
        };

        String(text || '').replace(/\r\n/g, '\n').split('\n').forEach((line) => {
            const bullet = line.match(/^\s*[-*•]\s+(.+)$/);
            const ordered = line.match(/^\s*\d+[.)]\s+(.+)$/);
            const heading = line.match(/^\s{0,3}#{1,3}\s+(.+)$/);

            if (bullet || ordered) {
                const nextType = bullet ? 'ul' : 'ol';
                if (listType && listType !== nextType) closeList();
                if (!listType) {
                    listType = nextType;
                    output.push(`<${listType}>`);
                }
                output.push(`<li>${inline((bullet || ordered)[1])}</li>`);
                return;
            }

            closeList();
            if (heading) output.push(`<p class="ai-discussion-heading">${inline(heading[1])}</p>`);
            else if (line.trim() === '') output.push('<div class="h-2"></div>');
            else output.push(`<p>${inline(line)}</p>`);
        });
        closeList();

        return output.join('');
    }
    
    // Cek apakah ada essay yang menunggu koreksi AI
    const pendingEssays = document.querySelectorAll('.essay-pending-ai');
    const allEssayCards = document.querySelectorAll('.essay-card[data-pending="true"]');
    
    console.log(`Found ${pendingEssays.length} pending AI essays`);
    console.log(`Found ${allEssayCards.length} total pending essays`);
    
    if (pendingEssays.length > 0 || allEssayCards.length > 0) {
        console.log('Starting polling for essay corrections...');
        
        // Polling setiap 3 detik untuk cek status (lebih cepat biar responsif)
        const pollInterval = setInterval(() => {
            // Cek apakah masih ada yang pending
            const stillPending = document.querySelectorAll('.essay-card[data-pending="true"]');
            if (stillPending.length === 0) {
                console.log('No more pending essays, stopping polling');
                clearInterval(pollInterval);
                return;
            }
            
            // Get question IDs yang pending
            const questionIds = Array.from(stillPending).map(el => el.dataset.questionId);
            console.log(`Checking status for questions: ${questionIds.join(',')}`);
            
            // Fetch status update
            fetch(`{{ route('user.tryout.check-essay-status') }}?question_ids=${questionIds.join(',')}&attempt_token={{ $token }}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Received status update:', data);
                    data.forEach(result => {
                        if (!result.pending_review) {
                            console.log(`Question ${result.question_id} is done! Updating UI...`);
                            // Essay sudah dikoreksi, update UI
                            const card = document.querySelector(`.essay-card[data-question-id="${result.question_id}"]`);
                            if (card) {
                                updateEssayCard(card, result);
                            }
                        }
                    });
                })
                .catch(error => console.error('Error checking essay status:', error));
        }, 3000); // Cek setiap 3 detik
    } else {
        console.log('No pending essays found');
    }
    
    function updateEssayCard(card, result) {
        // Update status badge
        const statusBadge = card.querySelector('.essay-status-badge');
        if (statusBadge) {
            if (result.is_correct) {
                statusBadge.className = 'essay-status-badge flex items-center gap-1 border border-green-200 bg-green-50 text-green-700 px-3 py-1 rounded-lg text-sm';
                statusBadge.innerHTML = '<i class="ri-check-line"></i> Benar' + (result.score_obtained > 0 ? ` (${result.score_obtained} poin)` : '');
            } else {
                statusBadge.className = 'essay-status-badge flex items-center gap-1 border border-red-200 bg-red-50 text-red-700 px-3 py-1 rounded-lg text-sm';
                statusBadge.innerHTML = '<i class="ri-close-line"></i> Salah' + (result.score_obtained > 0 ? ` (${result.score_obtained} poin)` : '');
            }
        }
        
        // Update AI info
        const aiInfo = card.querySelector('.essay-ai-info');
        if (aiInfo) {
            aiInfo.innerHTML = `
                <div class="flex items-center gap-2 mt-2">
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 rounded text-xs">
                        <i class="ri-robot-line"></i> Dikoreksi AI
                    </span>
                    <span class="text-xs text-gray-500">Similarity: ${Math.round(result.ai_similarity * 100)}%</span>
                </div>
            `;
        }
        
        // Update card border
        card.classList.remove('border-amber-400', 'bg-amber-50/30', 'essay-pending-ai');
        if (result.is_correct) {
            card.classList.add('border-green', 'bg-green-light/30');
        } else {
            card.classList.add('border-red', 'bg-red-light/30');
        }
        card.dataset.pending = 'false';
    }
</script>
@endpush

@section('styles')
<style>
    /* Custom styles for pembahasan */
    .card-pembahasan {
        transition: all 0.3s ease;
    }

    .card-pembahasan:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .ai-discussion-markdown p + p { margin-top: 0.4rem; }
    .ai-discussion-markdown .ai-discussion-heading { font-weight: 700; color: #111827; }
    .ai-discussion-markdown ul,
    .ai-discussion-markdown ol { margin: 0.4rem 0; padding-left: 1.25rem; }
    .ai-discussion-markdown ul { list-style: disc; }
    .ai-discussion-markdown ol { list-style: decimal; }
    .ai-discussion-markdown li + li { margin-top: 0.2rem; }
    .ai-discussion-markdown code {
        border-radius: 0.25rem;
        background: #f3f4f6;
        padding: 0.1rem 0.25rem;
        font-size: 0.85em;
    }

    /* Color definitions */
    .bg-green {
        background-color: #059669;
    }

    .text-green {
        color: #059669;
    }

    .border-green {
        border-color: #059669;
    }

    .bg-green-light {
        background-color: #d1fae5;
    }

    .text-red {
        color: #dc2626;
    }

    .bg-red {
        background-color: #dc2626;
    }

    .border-red {
        border-color: #dc2626;
    }

    .bg-red-light {
        background-color: #fee2e2;
    }

    /* Animation for progress bar */
    @keyframes progressFill {
        0% {
            width: 0%;
        }

        100% {
            width: var(--progress-width);
        }
    }

    .progress-bar {
        animation: progressFill 1.5s ease-out;
    }
</style>
@endsection
