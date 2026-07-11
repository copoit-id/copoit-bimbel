@extends('user.layout.new-user')
@section('title', 'Pembahasan Tryout')
@section('content')
<div class="package-bimbel flex flex-col gap-4">
    @php
        $formatScore = function ($value) {
            return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        };
        $primaryColor = $clientBranding['primary_color'] ?? '#10b981';
        $aiDiscussionEnabled = (bool) ($clientBranding['ai_discussion_feature_enabled'] ?? false)
            && (bool) data_get($clientBranding, 'ai_discussion_settings.enabled', false);
        $packageRouteId = $packageRouteId ?? ($package->package_id ?? 'free');
        $aiDiscussionEndpointUrl = route('user.package.tryout.pembahasan.ai-chat', [$packageRouteId, $tryout->tryout_id, $token]);
        $aiSpeechEndpointUrl = route('user.package.tryout.pembahasan.ai-speech', [$packageRouteId, $tryout->tryout_id, $token]);
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
    </style>
    <div class="bg-white px-4 py-10 rounded-lg border border-border flex flex-col md:flex-row gap-4 text-dark">
        <div class="flex order-2 md:order-1 flex-col items-center gap-4 w-full">
            <p class="font-semibold">Pembahasan - {{ $tryout->name }}</p>
            <p class="text-5xl font-medium">{{ $formatScore($overallStats['total_score']) }}</p>
            <span
                class="flex items-center gap-1 border px-6 py-0.5 rounded-lg {{ $overallStats['is_passed'] ? 'border-green bg-green-light text-green' : 'border-red bg-red-light text-red' }}">
                <i class="ri-checkbox-circle-fill text-lg"></i>
                <span>{{ $overallStats['is_passed'] ? 'Lulus' : 'Tidak Lulus' }}</span>
            </span>
            @if(isset($tryoutDetails) && $tryoutDetails->count() > 1)
            <div class="mt-2">
                <span class="inline-flex px-3 py-1 bg-primary/10 text-primary text-sm font-medium rounded-full">
                    SKD Full - {{ $tryoutDetails->count() }} Subtest
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

    <!-- SKD Full Subtest Summary (if multiple subtests) -->
    @if(!empty($subtestSummaries))
    <div class="bg-white px-4 py-6 rounded-lg border border-border">
        <h3 class="text-lg font-bold mb-4 text-gray-800">Ringkasan Per Subtest</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($subtestSummaries as $summary)
            <div
                class="p-4 border rounded-lg {{ $summary['is_passed'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                <div class="text-center mb-3">
                    <h4 class="font-semibold text-gray-800">{{ strtoupper($summary['type']) }}
                    </h4>
                    <p class="text-sm text-gray-600">{{ $summary['name'] }}</p>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold {{ $summary['is_passed'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $formatScore($summary['score']) }}/{{ $formatScore($summary['max_score']) }}
                    </div>
                    <div class="text-sm {{ $summary['is_passed'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($summary['percentage'], 1) }}% - {{ $summary['is_passed'] ? 'LULUS' : 'TIDAK LULUS' }}
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-600 text-center">
                    {{ $summary['correct_answers'] }} benar, {{ $summary['wrong_answers'] }} salah
                </div>
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
        @endphp

        {{-- Subtest Header --}}
        @if($currentSubtest !== $detail->subtest_type)
        @php $currentSubtest = $detail->subtest_type; @endphp
        <div class="discussion-subtest-header border-t-2 border-primary pt-6 -mt-2">
            <div class="flex items-center gap-3 mb-4">
                <div
                    class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-bold text-sm">
                    {{ strtoupper($detail->subtest_type) }}
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
            <div class="flex items-center justify-start gap-4">
                <p class="font-semibold">Soal {{ $index + 1 }}</p>
                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                    {{ strtoupper($detail->subtest_type) }}
                </span>
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
                    $scoreWrong = (float) ($multipleAnswerMeta['score_wrong'] ?? 0);
                    $correctOptionIds = $question->questionOptions
                        ->where('is_correct', true)
                        ->pluck('question_option_id')
                        ->map(fn ($id) => (int) $id)
                        ->values()
                        ->all();
                    $normalizedSelected = collect($selectedOptionIds)->map(fn ($id) => (int) $id)->unique()->values()->all();

                    sort($correctOptionIds);
                    sort($normalizedSelected);

                    $matchedCorrect = count(array_intersect($normalizedSelected, $correctOptionIds));
                    $totalCorrect = max(1, count($correctOptionIds));
                    $isExactCorrect = ($normalizedSelected === $correctOptionIds);

                    if ($multipleAnswerScoringMode === 'partial') {
                        $scoreEarned = $matchedCorrect > 0
                            ? ($matchedCorrect / $totalCorrect) * $multipleAnswerTotalScore
                            : $scoreWrong;
                    } else {
                        $scoreEarned = $isExactCorrect ? $multipleAnswerTotalScore : $scoreWrong;
                    }

                    $scoreEarned = max(0, $scoreEarned);
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
                    <span class="essay-score-badge flex items-center gap-1 border {{ $isCorrect ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700' }} px-3 py-1 rounded-lg text-sm">
                        <i class="{{ $isCorrect ? 'ri-check-line' : 'ri-close-line' }}"></i>
                        {{ $isCorrect ? 'Benar' : 'Salah' }}
                        @if($scoreEarned > 0)
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
                    <button type="button"
                        class="ai-discussion-toggle inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                        <i class="ri-message-3-line"></i>
                        Buka
                    </button>
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
                        <button type="button"
                            class="ai-discussion-voice inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                            title="Tanya dengan suara">
                            <i class="ri-mic-line text-lg"></i>
                            <span class="sr-only">Tanya dengan suara</span>
                        </button>
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
                        <span class="font-semibold">{{ number_format($overallStats['total_score'], 0) }}/{{
                            number_format($overallStats['max_score'], 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Persentase:</span>
                        <span class="font-semibold {{ $overallStats['is_passed'] ? 'text-green' : 'text-red' }}">
                            {{ number_format($overallStats['percentage'], 1) }}%
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-semibold {{ $overallStats['is_passed'] ? 'text-green' : 'text-red' }}">
                            {{ $overallStats['is_passed'] ? 'LULUS' : 'TIDAK LULUS' }}
                        </span>
                    </div>
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

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
        @if($package)
            <a href="{{ route('user.package.tryout', $package->package_id) }}"
                class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-center">
                <i class="ri-arrow-left-line mr-2"></i>Kembali ke Tryout
            </a>
        @else
            <a href="{{ route('user.event.index') }}"
                class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-center">
                <i class="ri-arrow-left-line mr-2"></i>Kembali ke Event
            </a>
        @endif

        @if($package)
        <a href="{{ route('user.package.tryout.riwayat', [$package->package_id, $tryout->tryout_id]) }}"
            class="px-6 py-3 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors text-center">
            <i class="ri-history-line mr-2"></i>Lihat Riwayat
        </a>
        @endif

        @if($package && $tryout->show_leaderboard)
        <a href="{{ route('user.package.tryout.ranking', [$package->package_id, $tryout->tryout_id]) }}"
            class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-center">
            <i class="ri-trophy-line mr-2"></i>Lihat Ranking
        </a>
        @endif

        @if($package && ($clientBranding['certificate_management_enabled'] ?? true))
        <a href="{{ route('user.certificate.preview', [$package->package_id, $tryout->tryout_id, 'token' => $token]) }}"
            class="px-6 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors text-center">
            <i class="ri-award-line mr-2"></i>Preview Sertifikat
        </a>
        @endif

        <a href="{{ route('user.tryout.lobby', [$packageRouteId, $tryout->tryout_id]) }}"
            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-center">
            <i class="ri-refresh-line mr-2"></i>Coba Lagi
        </a>
    </div>
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
    const aiSpeechEndpoint = @json($aiSpeechEndpointUrl);
    const aiDiscussionHistoryByQuestion = @json($aiDiscussionHistoryByQuestion ?? []);
    const csrfToken = @json(csrf_token());

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

        const history = aiDiscussionHistoryByQuestion[wrapper.dataset.questionId] || [];
        if (history.length > 0) {
            messages.innerHTML = '';
            history.forEach((entry) => {
                appendAiDiscussionMessage(messages, entry.user_message, 'user');
                appendAiDiscussionMessage(messages, entry.assistant_message, 'ai');
            });
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            voiceButton?.setAttribute('disabled', 'disabled');
            voiceButton?.setAttribute('title', 'Input suara belum didukung browser ini.');
        } else if (voiceButton) {
            const recognition = new SpeechRecognition();
            recognition.lang = 'id-ID';
            recognition.interimResults = false;
            recognition.continuous = false;

            voiceButton.addEventListener('click', () => {
                error?.classList.add('hidden');
                recognition.start();
            });

            recognition.onstart = () => {
                voiceButton.disabled = true;
                voiceButton.innerHTML = '<i class="ri-record-circle-line animate-pulse text-lg text-red-500"></i>';
            };
            recognition.onresult = (event) => {
                input.value = event.results[0][0].transcript;
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

            appendAiDiscussionMessage(messages, message, 'user');
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
                    }),
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Diskusi AI gagal diproses.');
                }

                loadingBubble.remove();
                const aiMessage = data.message || 'AI tidak mengembalikan jawaban.';
                appendAiDiscussionMessage(messages, aiMessage, 'ai');
                if (shouldSpeakResponse) {
                    playAiDiscussionAudio(aiMessage);
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

    function appendAiDiscussionMessage(messages, text, type) {
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

            const replay = document.createElement('button');
            replay.type = 'button';
            replay.className = 'mt-2 inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline';
            replay.innerHTML = '<i class="ri-volume-up-line"></i> Dengarkan lagi';
            replay.addEventListener('click', () => playAiDiscussionAudio(text));
            bubble.appendChild(replay);
        } else {
            bubble.classList.add('whitespace-pre-line');
            bubble.textContent = text;
        }
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
        return bubble;
    }

    async function playAiDiscussionAudio(text) {
        try {
            const response = await fetch(aiSpeechEndpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'audio/mpeg, application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ text }),
            });
            if (!response.ok) throw new Error('TTS gagal');
            const audioUrl = URL.createObjectURL(await response.blob());
            const audio = new Audio(audioUrl);
            audio.onended = () => URL.revokeObjectURL(audioUrl);
            await audio.play();
            return;
        } catch (_) {
            speakAiDiscussionResponseFallback(text);
        }
    }

    function speakAiDiscussionResponseFallback(text) {
        if (!('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();
        const spokenText = String(text)
            .replace(/[*#`_]/g, '')
            .replace(/\n+/g, '. ');
        const utterance = new SpeechSynthesisUtterance(spokenText);
        utterance.lang = 'id-ID';
        utterance.rate = 0.96;
        window.speechSynthesis.speak(utterance);
    }

    function formatAiDiscussionMarkdown(text) {
        const escapeHtml = (value) => String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const inline = (value) => escapeHtml(value)
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
