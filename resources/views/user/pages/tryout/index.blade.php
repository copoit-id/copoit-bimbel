@extends('user.layout.tryout')
@section('title', 'Tryout - Soal ' . $number)
@section('content')
    <div id="tryoutPage" class="min-h-screen bg-gray-50 pt-16 {{ $effectiveProctoringSettings['enable_anti_copy'] ? 'select-none' : '' }}"
        @if($effectiveProctoringSettings['enable_anti_copy']) oncopy="return false" oncut="return false" oncontextmenu="return false" ondragstart="return false" @endif>
        <div class="max-w-7xl mx-auto py-2 sm:px-4 sm:py-6">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Question Section -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-lg border border-border p-6">
                        <!-- Header -->
                        <div class="flex justify-between items-center mb-6 pb-4 border-b border-border">
                            <div class="flex flex-col sm:flex-row w-full justify-between items-start sm:items-center gap-2">
                                <h2 class="text-lg md:text-xl font-bold text-gray-800">Soal <span
                                        id="display-number">{{ $number }}</span> dari {{ $totalQuestions }}
                                </h2>
                                @if (isset($currentSubtest))
                                    <p id="display-subtest-name"
                                        class="text-xs md:text-sm text-gray-600 font-medium px-3 py-1 bg-gray-100 rounded-full">
                                        {{ $currentSubtest['name'] }}</p>
                                @endif
                            </div>
                            <div id="timer" hidden class="text-2xl font-bold text-primary">00:00:00</div>
                        </div>

                        <!-- Questions Loop -->
                        <div id="questions-container">
                            @foreach ($allQuestions as $index => $q)
                                @php
                                    $qNum = $index + 1;
                                    $userAnswerDetail = $allAnswerDetails->get($q->question_id);
                                    $rawQuestionType = $q->question_type ?? 'multiple_choice';
                                    $qType =
                                        $rawQuestionType === 'multiple_select' ? 'multiple_answer' : $rawQuestionType;
                                    $qMetadata = is_array($q->metadata) ? $q->metadata : [];
                                @endphp
                                <div id="question-wrapper-{{ $qNum }}"
                                    class="question-wrapper {{ $qNum == $number ? '' : 'hidden' }}"
                                    data-number="{{ $qNum }}" data-question-id="{{ $q->question_id }}"
                                    data-question-type="{{ $qType }}"
                                    data-subtest-detail-id="{{ $q->tryout_detail_id }}"
                                    data-subtest-name="{{ $q->subtest_name }}">

                                    <div class="mb-8">
                                        <div class="question-rich-text text-gray-700 leading-relaxed overflow-x-auto">
                                            {!! $q->question_text !!}
                                        </div>

                                        @if ($q->sound)
                                            <div class="mt-4">
                                                <audio id="audio-{{ $q->question_id }}" controls controlsList="nodownload"
                                                    oncontextmenu="return false;" class="w-full">
                                                    <source src="{{ Storage::url($q->sound) }}" type="audio/mpeg">
                                                    Browser Anda tidak mendukung audio.
                                                </audio>
                                            </div>
                                        @endif
                                    </div>

                                    @php
                                        $matchingPairs =
                                            isset($qMetadata['matching_pairs']) &&
                                            is_array($qMetadata['matching_pairs'])
                                                ? $qMetadata['matching_pairs']
                                                : [];
                                        $matchingRightOptions = [];
                                        foreach ($matchingPairs as $pair) {
                                            $right = trim((string) ($pair['right'] ?? ''));
                                            if ($right !== '' && !in_array($right, $matchingRightOptions, true)) {
                                                $matchingRightOptions[] = $right;
                                            }
                                        }
                                        $shuffledMatchingOptions = $matchingRightOptions;
                                        shuffle($shuffledMatchingOptions);
                                        $shortMeta =
                                            isset($qMetadata['short_answer']) && is_array($qMetadata['short_answer'])
                                                ? $qMetadata['short_answer']
                                                : [];
                                        $audioMeta =
                                            isset($qMetadata['audio_answer']) && is_array($qMetadata['audio_answer'])
                                                ? $qMetadata['audio_answer']
                                                : [];
                                        $mtfMeta =
                                            isset($qMetadata['multiple_true_false']) &&
                                            is_array($qMetadata['multiple_true_false'])
                                                ? $qMetadata['multiple_true_false']
                                                : [];
                                        $mtfStatements =
                                            isset($mtfMeta['statements']) && is_array($mtfMeta['statements'])
                                                ? $mtfMeta['statements']
                                                : [];
                                        $mtfTrueLabel = trim((string) ($mtfMeta['true_label'] ?? 'Benar'));
                                        $mtfFalseLabel = trim((string) ($mtfMeta['false_label'] ?? 'Salah'));
                                    @endphp

                                    <form class="answer-form" data-question-id="{{ $q->question_id }}"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="question_id" value="{{ $q->question_id }}">
                                        <input type="hidden" class="question-type-input" value="{{ $qType }}">

                                        @if (in_array($qType, ['multiple_choice', 'true_false', 'multiple_answer']))
                                            <div class="space-y-3 multiple-choice-container">
                                                @foreach ($q->questionOptions as $option)
                                                    @php
                                                        $optionKey = $option->option_key ?? chr(65 + $loop->index);
                                                        $isMultipleAnswer = $qType === 'multiple_answer';
                                                    @endphp
                                                    <label
                                                        class="flex items-start p-4 border border-gray-200 rounded-lg cursor-pointer transition-all duration-200 hover:border-primary hover:bg-primary/5 answer-option-label"
                                                        for="option_{{ $q->question_id }}_{{ $option->question_option_id }}">
                                                        <input type="{{ $isMultipleAnswer ? 'checkbox' : 'radio' }}"
                                                            id="option_{{ $q->question_id }}_{{ $option->question_option_id }}"
                                                            name="{{ $isMultipleAnswer ? 'answer_options[]' : 'answer_option' }}"
                                                            value="{{ $option->question_option_id }}"
                                                            data-option-key="{{ $optionKey }}"
                                                            class="w-5 h-5 mt-1 text-primary border-gray-300 focus:ring-primary answer-input">
                                                        <span class="ml-4 flex-1 text-gray-700">
                                                            <span class="flex items-start gap-2">
                                                                <span
                                                                    class="font-semibold shrink-0">{{ $optionKey }}.</span>
                                                                <span
                                                                    class="option-inline-text [&_p]:inline [&_p]:m-0 [&_div]:inline">{!! $option->option_text !!}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @elseif($qType === 'matching')
                                            <div class="space-y-4 matching-container">
                                                @if (empty($matchingPairs))
                                                    <p class="text-sm text-gray-500">Belum ada pasangan jawaban untuk soal
                                                        ini.</p>
                                                @else
                                                    @foreach ($matchingPairs as $pairIndex => $pair)
                                                        @php $leftLabel = trim((string)($pair['left'] ?? 'Item ' . ($pairIndex + 1))); @endphp
                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                                                            <div class="font-medium text-gray-800">{{ $leftLabel }}
                                                            </div>
                                                            <div>
                                                                <select
                                                                    class="matching-select w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                                                    data-left="{{ $leftLabel }}">
                                                                    <option value="">Pilih jawaban</option>
                                                                    @foreach ($shuffledMatchingOptions as $option)
                                                                        <option value="{{ $option }}">
                                                                            {{ $option }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        @elseif($qType === 'multiple_true_false')
                                            <div class="space-y-4 mtf-container">
                                                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                                    <table class="min-w-full text-sm">
                                                        <thead class="bg-gray-100">
                                                            <tr>
                                                                <th
                                                                    class="px-5 py-3.5 text-left font-semibold text-gray-800 w-[70%]">
                                                                    Pernyataan</th>
                                                                <th
                                                                    class="px-5 py-3.5 text-center font-semibold text-gray-800 whitespace-nowrap">
                                                                    {{ $mtfTrueLabel ?: 'Benar' }}</th>
                                                                <th
                                                                    class="px-5 py-3.5 text-center font-semibold text-gray-800 whitespace-nowrap">
                                                                    {{ $mtfFalseLabel ?: 'Salah' }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($mtfStatements as $stmtIndex => $statement)
                                                                @php $stmtId = trim((string) ($statement['id'] ?? 'stmt_' . ($stmtIndex + 1))); @endphp
                                                                <tr class="border-t border-gray-200">
                                                                    <td class="px-5 py-3.5 text-gray-800">
                                                                        {!! $statement['text'] ?? '' !!}</td>
                                                                    <td class="px-5 py-3.5 text-center">
                                                                        <input type="radio"
                                                                            name="mtf_{{ $q->question_id }}_{{ $stmtId }}"
                                                                            value="true"
                                                                            class="mtf-radio w-4 h-4 text-primary"
                                                                            data-statement-id="{{ $stmtId }}">
                                                                    </td>
                                                                    <td class="px-5 py-3.5 text-center">
                                                                        <input type="radio"
                                                                            name="mtf_{{ $q->question_id }}_{{ $stmtId }}"
                                                                            value="false"
                                                                            class="mtf-radio w-4 h-4 text-primary"
                                                                            data-statement-id="{{ $stmtId }}">
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @elseif(in_array($qType, ['short_answer', 'essay']))
                                            <div class="space-y-3 short-answer-container">
                                                <textarea rows="4"
                                                    class="short-answer-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20"
                                                    placeholder="Ketik jawabanmu di sini"></textarea>
                                            </div>
                                        @elseif($qType === 'audio')
                                            <div class="space-y-4 audio-answer-container">
                                                <div class="audio-answer-preview space-y-2"></div>
                                                <input type="file"
                                                    class="audio-answer-input w-full px-4 py-3 border border-gray-300 rounded-lg"
                                                    accept="audio/*">
                                            </div>
                                        @endif
                                    </form>
                                </div>
                            @endforeach
                        </div>

                        <!-- Navigation -->
                        @php
                            $isFirstQuestionOfSubtest = $number === ($currentSubtest['start_number'] ?? $number);
                            $canGoPrev =
                                $number > 1 &&
                                (($isCombinedSubtestView ?? false) ||
                                    !($isFirstQuestionOfSubtest && ($currentSubtestIndex ?? 0) > 0));
                        @endphp
                        <div class="mt-8 flex justify-between items-center pt-6 border-t border-border">
                            <div id="prevBtnContainer" class="flex gap-2 sm:gap-3 {{ $number === 1 ? 'hidden' : '' }}">
                                <button id="prevBtn" onclick="prevQuestion()"
                                    class="px-3 sm:px-4 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="ri-arrow-left-line sm:mr-2"></i><span
                                        class="hidden sm:inline">Sebelumnya</span>
                                </button>
                            </div>
                            <div>
                                <button onclick="flagQuestion()"
                                    class="px-4 py-2 border border-red text-red rounded-lg hover:bg-red hover:text-white transition-colors flag-btn">
                                    <i class="ri-flag-line mr-2"></i>
                                    <span
                                        class="flag-text">{{ in_array($currentQuestion->question_id, $flaggedQuestions) ? 'Batal Tandai' : 'Tandai' }}</span>
                                </button>
                            </div>

                            <div class="flex gap-2 sm:gap-3">
                                <button id="nextBtn" onclick="nextQuestion()"
                                    class="px-5 sm:px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center">
                                    <span id="nextBtnText">Selanjutnya</span><i class="ri-arrow-right-line sm:ml-2"></i>
                                </button>

                                <form
                                    action="{{ url('/user/tryout/' . ($package ? $package->package_id : 'free') . '/' . $tryout->tryout_id . '/finish') }}"
                                    method="POST" class="hidden" id="finishForm">
                                    @csrf
                                    <input type="hidden" name="answers_payload" id="answersPayloadInput">
                                    <input type="hidden" name="attempt_token" value="{{ $attemptToken }}">
                                    <button type="submit"
                                        onclick="return confirm('Apakah Anda yakin ingin menyelesaikan tryout ini?')"
                                        class="px-5 sm:px-6 py-2 bg-green text-white rounded-lg hover:bg-green-700 transition-colors">
                                        <i class="ri-check-line mr-2"></i>Selesai
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg border border-border p-6 sticky top-6 text-center">
                        <p class="text-sm text-gray-600 mb-2">Sisa Waktu</p>
                        <div id="timer-display" class="text-3xl font-bold text-primary">00:00:00</div>
                        <p class="text-xs text-gray-500 mt-3 uppercase tracking-wide">
                            @if ($isCombinedSubtestView ?? false)
                                Mode Gabungan Subtest
                            @else
                                Subtest {{ ($currentSubtestIndex ?? 0) + 1 }} / {{ $totalSubtests ?? 1 }} ·
                                {{ $currentSubtest['name'] ?? 'Subtest' }}
                            @endif
                        </p>
                    </div>
                    <div class="bg-white rounded-lg border border-border p-6 sticky mt-4">
                        <!-- Question Navigation -->
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-800 mb-4">Navigasi Soal</h3>
                            <div
                                class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-4 xl:grid-cols-5 gap-2">
                                @for ($qNum = 1; $qNum <= $totalQuestions; $qNum++)
                                    @php
                                        $q = $allQuestions[$qNum - 1] ?? null;
                                        if (!$q) {
                                            continue;
                                        }
                                        $isCurrent = $qNum == $number;
                                        $isFlagged = in_array($q->question_id, $flaggedQuestions);
                                        $subtestRange = [];
                                        foreach ($subtestInfo as $si) {
                                            if ($qNum >= $si['start_number'] && $qNum <= $si['end_number']) {
                                                $subtestRange = $si;
                                                break;
                                            }
                                        }
                                    @endphp
                                    <button type="button" onclick="goToQuestion({{ $qNum }})"
                                        class="relative w-10 h-10 flex items-center justify-center text-sm font-medium rounded-lg transition-colors question-nav-item
                                       {{ $isCurrent ? 'bg-primary text-white active-nav' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}
                                       {{ !($qNum >= $currentSubtestRange[0] && $qNum <= $currentSubtestRange[1]) ? 'hidden' : '' }}"
                                        data-number="{{ $qNum }}" data-question-id="{{ $q->question_id }}"
                                        data-start-range="{{ $subtestRange['start_number'] ?? 0 }}"
                                        data-end-range="{{ $subtestRange['end_number'] ?? 0 }}">
                                        {{ $qNum }}
                                        <i
                                            class="ri-flag-fill absolute -top-1 -right-1 text-[10px] text-red {{ $isFlagged ? '' : 'hidden' }} flag-icon-nav"></i>
                                    </button>
                                @endfor
                            </div>
                        </div>

                        <button type="submit"
                            form="finishForm"
                            onclick="return confirm('Apakah Anda yakin ingin mengakhiri ujian ini? Jawaban yang sudah terisi akan disubmit.')"
                            class="w-full mb-6 rounded-lg border border-primary bg-white px-4 py-2.5 text-sm font-semibold text-primary transition-colors hover:bg-primary hover:text-white">
                            <i class="ri-check-line mr-2"></i>Akhiri Ujian
                        </button>

                        <!-- Legend -->
                        <div class="text-sm">
                            <h4 class="font-semibold text-gray-800 mb-3">Keterangan</h4>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 bg-primary rounded"></div>
                                    <span class="text-gray-600">Soal saat ini</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 bg-green rounded"></div>
                                    <span class="text-gray-600">Sudah dijawab</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 bg-gray-100 rounded"></div>
                                    <span class="text-gray-600">Belum dijawab</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="ri-flag-fill text-red"></i>
                                    <span class="text-gray-600">Ditandai</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- SKD Progress (if multiple subtests) -->
                    @if (isset($subtestInfo) && count($subtestInfo) > 1 && !($isCombinedSubtestView ?? false))
                        @php
                            $isUtbkTryout =
                                isset($tryout) &&
                                method_exists($tryout, 'requiresIrtScoring') &&
                                $tryout->requiresIrtScoring();
                            $subtestProgressTitle = $isUtbkTryout ? 'Progress UTBK' : 'Progress SKD Full';
                        @endphp
                        <div class="mb-6 p-4 bg-white border border-border mt-4 rounded-lg">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                                <span class="text-sm font-medium text-gray-700">{{ $subtestProgressTitle }}</span>
                                <span class="text-sm text-gray-600">{{ count($subtestInfo) }} Subtest</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                @foreach ($subtestInfo as $index => $subtest)
                                    @php
                                        $isCurrentSubtest =
                                            $currentSubtest && $currentSubtest['type'] === $subtest['type'];
                                        $isCompleted = $number > $subtest['end_number'];
                                        $displayLabel =
                                            $subtest['alias'] ?? \Illuminate\Support\Str::limit($subtest['name'], 18);
                                    @endphp
                                    <div class="text-center">
                                        <div
                                            class="w-9 h-9 rounded-full mx-auto mb-1 flex items-center justify-center text-sm font-semibold
                                    {{ $isCompleted
                                        ? 'bg-green text-white'
                                        : ($isCurrentSubtest
                                            ? 'bg-primary text-white'
                                            : 'bg-gray-200 text-gray-600') }}">
                                            {{ $index + 1 }}
                                        </div>
                                        <p class="text-[11px] leading-tight text-gray-600">{{ $displayLabel }}</p>
                                        @if ($isCurrentSubtest)
                                            <div class="mt-2">
                                                @php
                                                    $subtestProgress =
                                                        (($number - $subtest['start_number'] + 1) /
                                                            ($subtest['end_number'] - $subtest['start_number'] + 1)) *
                                                        100;
                                                @endphp
                                                <div class="w-full bg-gray-200 rounded-full h-1">
                                                    <div class="bg-primary h-1 rounded-full"
                                                        style="width: {{ $subtestProgress }}%">
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div id="tabSwitchAlert" class="fixed inset-0 hidden items-center justify-center bg-gray-950/80 px-4 backdrop-blur-sm"
        style="z-index: 2147483647;">
        <div class="w-full max-w-md rounded-xl border border-red/20 bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red/10">
                <i class="ri-error-warning-line text-3xl text-red"></i>
            </div>
            <h3 class="mb-2 text-lg font-bold text-gray-900">Terdeteksi Membuka Tab Lain</h3>
            <p id="tabSwitchAlertMessage" class="text-sm leading-relaxed text-gray-600">
                Tetap berada di halaman tryout selama ujian berlangsung. Pelanggaran ini sudah tercatat.
            </p>
            <p id="tabSwitchAlertCount" class="mb-5 mt-3 hidden rounded-lg bg-red/5 px-3 py-2 text-sm font-semibold text-red">
                Total pelanggaran: 0
            </p>
            <button type="button" id="tabSwitchAlertClose"
                class="w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary/90">
                Saya Mengerti
            </button>
        </div>
    </div>

    @php
        $isLobbyProctoringFrame = request()->boolean('lobby_proctoring');
    @endphp

    @if($effectiveProctoringSettings['enable_webcam_check'] || $effectiveProctoringSettings['enable_screen_check'])
    <div id="proctoringPermissionModal"
        class="fixed inset-0 {{ $isLobbyProctoringFrame ? 'hidden' : 'flex' }} items-center justify-center bg-gray-950/85 px-4 backdrop-blur-sm"
        style="z-index: 2147483646;">
        <div class="w-full max-w-lg rounded-xl border border-gray-200 bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10">
                <i class="ri-shield-check-line text-3xl text-primary"></i>
            </div>
            <h3 class="mb-2 text-lg font-bold text-gray-900">Aktifkan Pengawasan Ujian</h3>
            <p class="text-sm leading-relaxed text-gray-600">
                Ujian ini membutuhkan {{ $effectiveProctoringSettings['enable_webcam_check'] ? 'kamera' : '' }}{{ $effectiveProctoringSettings['enable_webcam_check'] && $effectiveProctoringSettings['enable_screen_check'] ? ' dan ' : '' }}{{ $effectiveProctoringSettings['enable_screen_check'] ? 'screen sharing' : '' }} aktif sebelum pengerjaan dilanjutkan.
            </p>
            <p id="proctoringPermissionError" class="mt-3 hidden rounded-lg bg-red/5 px-3 py-2 text-sm font-semibold text-red"></p>
            <button type="button" id="startProctoringBtn"
                class="mt-5 w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary/90">
                Aktifkan Pengawasan
            </button>
        </div>
    </div>
    <video id="webcamPreview" class="hidden" autoplay muted playsinline></video>
    <video id="screenPreview" class="hidden" autoplay muted playsinline></video>
    @endif

    @php
        $subtestRangesForJs = collect($subtestInfo ?? [])
            ->map(function ($subtest) {
                return [
                    'tryout_detail_id' => (int) ($subtest['tryout_detail_id'] ?? 0),
                    'start_number' => (int) ($subtest['start_number'] ?? 0),
                    'end_number' => (int) ($subtest['end_number'] ?? 0),
                    'name' => (string) ($subtest['name'] ?? ''),
                ];
            })
            ->values();

        $allAnswerDetailsForJs = $allAnswerDetails->map(function ($detail) {
            $rawType = $detail->question->question_type ?? 'multiple_choice';
            $qType = $rawType === 'multiple_select' ? 'multiple_answer' : $rawType;

            $data = [
                'question_id' => $detail->question_id,
                'type' => $qType,
                'answered' => true,
                'synced' => true,
                'is_played' => $detail->is_played ?? false,
            ];

            switch ($qType) {
                case 'multiple_choice':
                case 'true_false':
                    $data['option_id'] = $detail->question_option_id;
                    $data['option_key'] = optional($detail->questionOption)->option_key;
                    break;
                case 'multiple_answer':
                    $data['option_ids'] = $detail->answer_json['selected_option_ids'] ?? [];
                    break;
                case 'matching':
                    $data['matching_answers'] = $detail->answer_json['matches'] ?? [];
                    break;
                case 'multiple_true_false':
                    $data['mtf_answers'] = $detail->answer_json['answers'] ?? [];
                    break;
                case 'short_answer':
                case 'essay':
                    $data['answer_text'] = $detail->answer_text;
                    break;
                case 'audio':
                    $data['answer_audio_remote'] = $detail->answer_file_path
                        ? Storage::url($detail->answer_file_path)
                        : null;
                    $data['answer_audio_name'] =
                        $detail->answer_json['original_name'] ?? basename($detail->answer_file_path ?? '');
                    break;
            }
            return $data;
        });

        $proctoringSettingsForJs = [
            'antiCopy' => (bool) $effectiveProctoringSettings['enable_anti_copy'],
            'tabSwitch' => (bool) $effectiveProctoringSettings['enable_tab_switch_detection'],
            'webcam' => (bool) $effectiveProctoringSettings['enable_webcam_check'],
            'screen' => (bool) $effectiveProctoringSettings['enable_screen_check'],
            'snapshotIntervalMs' => 600000,
        ];
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialization
            let currentNumber = {{ $number }};
            const totalQuestions = {{ $totalQuestions }};
            const attemptToken = '{{ $attemptToken }}';
            const answersKey = `tryout_answers_${attemptToken}`;
            const csrfToken = '{{ csrf_token() }}';
            const answerPersistenceMode = @json($tryout->answer_persistence_mode ?? 'client_side');
            const subtestRanges = @json($subtestRangesForJs);
            const allServerAnswers = @json($allAnswerDetailsForJs);
            const trackTabSwitchUrl =
                '{{ url('/user/tryout/' . ($package ? $package->package_id : 'free') . '/' . $tryout->tryout_id . '/track-tab-switch') }}';
            const proctoringSnapshotUrl =
                '{{ url('/user/tryout/' . ($package ? $package->package_id : 'free') . '/' . $tryout->tryout_id . '/proctoring-snapshot') }}';
            const proctoringSettings = {{ \Illuminate\Support\Js::from($proctoringSettingsForJs) }};
            const baseUrlTemplate =
                '{{ url('/user/tryout/' . ($package ? $package->package_id : 'free') . '/' . $tryout->tryout_id . '/tryout/:num') }}';

            let answerCache = loadAnswers();
            let lastTabSwitchTrackedAt = 0;
            let tabSwitchInFlight = false;
            let isLeavingTryout = false;
            const proctoringStreams = {};
            const proctoringTimers = {};

            // Sync server answers into local cache if not present or older
            Object.values(allServerAnswers).forEach(ans => {
                const qid = ans.question_id;
                if (!answerCache[qid] || !answerCache[qid].updated_at) {
                    answerCache[qid] = {
                        ...ans,
                        updated_at: 0
                    };
                }
            });
            persistAnswers();

            function showTabSwitchAlert(count) {
                const modal = document.getElementById('tabSwitchAlert');
                const message = document.getElementById('tabSwitchAlertMessage');
                const countMessage = document.getElementById('tabSwitchAlertCount');
                if (!modal) return;

                if (message) {
                    message.textContent =
                        'Tetap berada di halaman tryout selama ujian berlangsung. Pelanggaran ini sudah tercatat.';
                }

                if (countMessage) {
                    if (count) {
                        countMessage.textContent = `Total pelanggaran: ${count}`;
                        countMessage.classList.remove('hidden');
                    } else {
                        countMessage.classList.add('hidden');
                    }
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function getCurrentQuestionId() {
                const wrapper = document.getElementById(`question-wrapper-${currentNumber}`);
                return wrapper ? wrapper.dataset.questionId : null;
            }

            async function trackTabSwitch(reason) {
                if (!proctoringSettings.tabSwitch || isLeavingTryout) {
                    return;
                }

                const now = Date.now();
                if (tabSwitchInFlight || now - lastTabSwitchTrackedAt < 3000) {
                    return;
                }

                lastTabSwitchTrackedAt = now;
                tabSwitchInFlight = true;

                try {
                    const response = await fetch(trackTabSwitchUrl, {
                        method: 'POST',
                        keepalive: true,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            attempt_token: attemptToken,
                            question_id: getCurrentQuestionId(),
                            reason: reason
                        })
                    });

                    const data = await response.json().catch(() => ({}));
                    showTabSwitchAlert(data.count);
                } catch (e) {
                    showTabSwitchAlert();
                } finally {
                    tabSwitchInFlight = false;
                }
            }

            function setupTabSwitchGuard() {
                const closeAlert = document.getElementById('tabSwitchAlertClose');
                if (closeAlert) {
                    closeAlert.addEventListener('click', function() {
                        const modal = document.getElementById('tabSwitchAlert');
                        if (!modal) return;
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    });
                }

                document.addEventListener('visibilitychange', function() {
                    if (document.visibilityState === 'hidden') {
                        trackTabSwitch('visibility_hidden');
                    }
                });

                window.addEventListener('blur', function() {
                    trackTabSwitch('window_blur');
                });
            }

            if (proctoringSettings.tabSwitch) {
                setupTabSwitchGuard();
            }

            function setupCopyGuard() {
                const tryoutPage = document.getElementById('tryoutPage');
                if (!tryoutPage) return;

                ['copy', 'cut', 'contextmenu', 'dragstart'].forEach(eventName => {
                    tryoutPage.addEventListener(eventName, function(event) {
                        event.preventDefault();
                    });
                });

                tryoutPage.addEventListener('keydown', function(event) {
                    const key = event.key.toLowerCase();
                    const isCopyShortcut = (event.ctrlKey || event.metaKey) && ['a', 'c', 'x'].includes(key);
                    if (isCopyShortcut) {
                        event.preventDefault();
                    }
                });
            }

            if (proctoringSettings.antiCopy) {
                setupCopyGuard();
            }

            function showProctoringError(message) {
                const error = document.getElementById('proctoringPermissionError');
                if (!error) return;
                error.textContent = message;
                error.classList.remove('hidden');
            }

            function clearProctoringError() {
                const error = document.getElementById('proctoringPermissionError');
                if (!error) return;
                error.textContent = '';
                error.classList.add('hidden');
            }

            function isStreamActive(stream) {
                return stream && stream.getVideoTracks().some(track => track.readyState === 'live');
            }

            function getProctoringModal() {
                return document.getElementById('proctoringPermissionModal');
            }

            function showProctoringModal(message) {
                const modal = getProctoringModal();
                const button = document.getElementById('startProctoringBtn');
                if (!modal) return;

                if (message) {
                    showProctoringError(message);
                }

                if (button) {
                    button.disabled = false;
                    button.textContent = 'Aktifkan Pengawasan';
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function hideProctoringModal() {
                const modal = getProctoringModal();
                if (!modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            function clearProctoringTimer(type) {
                if (!proctoringTimers[type]) return;
                clearInterval(proctoringTimers[type]);
                delete proctoringTimers[type];
            }

            async function startMediaStream(type) {
                if (type === 'webcam') {
                    return navigator.mediaDevices.getUserMedia({
                        video: { width: { ideal: 640 }, height: { ideal: 360 }, facingMode: 'user' },
                        audio: false
                    });
                }

                return navigator.mediaDevices.getDisplayMedia({
                    video: { width: { ideal: 960 }, height: { ideal: 540 } },
                    selfBrowserSurface: 'exclude',
                    monitorTypeSurfaces: 'include',
                    audio: false
                });
            }

            async function captureSnapshot(type, video) {
                if (!isStreamActive(proctoringStreams[type]) || !video || video.readyState < 2) return;

                const canvas = document.createElement('canvas');
                canvas.width = 480;
                canvas.height = 270;
                const context = canvas.getContext('2d');
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                const image = canvas.toDataURL('image/jpeg', 0.42);

                await fetch(proctoringSnapshotUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        attempt_token: attemptToken,
                        type: type,
                        image: image
                    })
                });
            }

            async function attachProctoringStream(type, stream) {
                const video = document.getElementById(type === 'webcam' ? 'webcamPreview' : 'screenPreview');
                if (!video) return;

                if (isStreamActive(proctoringStreams[type])) {
                    return;
                }

                if (proctoringStreams[type]) {
                    proctoringStreams[type].getTracks().forEach(track => track.stop());
                }

                proctoringStreams[type] = stream;
                video.srcObject = stream;
                await video.play();

                stream.getVideoTracks().forEach(track => {
                    track.addEventListener('ended', function() {
                        clearProctoringTimer(type);
                        delete proctoringStreams[type];
                        showProctoringModal(type === 'webcam'
                            ? 'Kamera terhenti. Aktifkan kembali pengawasan untuk melanjutkan ujian.'
                            : 'Screen sharing terhenti. Aktifkan kembali pengawasan untuk melanjutkan ujian.');
                    });
                });

                setTimeout(() => captureSnapshot(type, video).catch(() => {}), 1500);
                clearProctoringTimer(type);
                proctoringTimers[type] = setInterval(() => {
                    captureSnapshot(type, video).catch(() => {});
                }, proctoringSettings.snapshotIntervalMs);
            }

            async function setupProctoringMedia() {
                if (!proctoringSettings.webcam && !proctoringSettings.screen) return;

                if (window.self !== window.top) {
                    hideProctoringModal();
                    return;
                }

                if (!navigator.mediaDevices || (!navigator.mediaDevices.getUserMedia && proctoringSettings.webcam)) {
                    showProctoringError('Browser tidak mendukung akses kamera.');
                    return;
                }

                if (proctoringSettings.screen && !navigator.mediaDevices.getDisplayMedia) {
                    showProctoringError('Browser tidak mendukung screen sharing.');
                    return;
                }

                const button = document.getElementById('startProctoringBtn');
                const modal = document.getElementById('proctoringPermissionModal');
                if (!button || !modal) return;

                modal.classList.remove('hidden');
                modal.classList.add('flex');

                button.addEventListener('click', async function() {
                    button.disabled = true;
                    button.textContent = 'Meminta izin...';
                    clearProctoringError();

                    try {
                        if (proctoringSettings.webcam && !isStreamActive(proctoringStreams.webcam)) {
                            const webcamStream = await startMediaStream('webcam');
                            await attachProctoringStream('webcam', webcamStream);
                        }

                        if (proctoringSettings.screen && !isStreamActive(proctoringStreams.screen)) {
                            const screenStream = await startMediaStream('screen');
                            await attachProctoringStream('screen', screenStream);
                        }

                        hideProctoringModal();
                    } catch (e) {
                        button.disabled = false;
                        button.textContent = 'Aktifkan Pengawasan';
                        showProctoringError('Izin kamera atau screen sharing wajib diberikan untuk melanjutkan ujian.');
                    }
                });

                window.addEventListener('beforeunload', function() {
                    Object.values(proctoringStreams).forEach(stream => {
                        if (stream) {
                            stream.getTracks().forEach(track => track.stop());
                        }
                    });
                    Object.keys(proctoringTimers).forEach(clearProctoringTimer);
                });
            }

            setupProctoringMedia();

            // --- NAVIGATION CORE ---

            window.goToQuestion = function(num) {
                if (num < 1 || num > totalQuestions) return;

                // Pause any playing audio
                document.querySelectorAll('audio').forEach(a => a.pause());

                const currentWrapper = document.getElementById(`question-wrapper-${currentNumber}`);
                const targetWrapper = document.getElementById(`question-wrapper-${num}`);

                if (currentWrapper) currentWrapper.classList.add('hidden');
                if (targetWrapper) targetWrapper.classList.remove('hidden');

                // Audio play tracking initialization for the target question
                setupAudioTracking(num);

                currentNumber = num;

                // Update Header
                document.getElementById('display-number').textContent = num;
                const subtestName = targetWrapper.dataset.subtestName;
                document.getElementById('display-subtest-name').textContent = subtestName;

                // Update URL
                const newUrl = baseUrlTemplate.replace(':num', num);
                history.pushState({
                    number: num
                }, "", newUrl);

                // Update Sidebar Nav
                document.querySelectorAll('.question-nav-item').forEach(item => {
                    const itemNum = parseInt(item.dataset.number);
                    item.classList.remove('bg-primary', 'text-white', 'active-nav');

                    const isAnsweredQ = isAnswered(item.dataset.questionId);
                    if (itemNum === num) {
                        item.classList.add('bg-primary', 'text-white', 'active-nav');
                        item.classList.remove('bg-green');
                    } else if (isAnsweredQ) {
                        item.classList.add('bg-green', 'text-white');
                        item.classList.remove('bg-gray-100', 'text-gray-600');
                    } else {
                        item.classList.add('bg-gray-100', 'text-gray-600');
                        item.classList.remove('bg-green', 'text-white');
                    }

                    // Visibility logic (subtest filter)
                    const rangeIdx = subtestRanges.findIndex(r => num >= r.start_number && num <= r
                        .end_number);
                    const currentRange = subtestRanges[rangeIdx];
                    if (itemNum >= currentRange.start_number && itemNum <= currentRange.end_number) {
                        item.classList.remove('hidden');
                    } else {
                        item.classList.add('hidden');
                    }
                });

                // Update Buttons
                const prevBtnContainer = document.getElementById('prevBtnContainer');
                const nextBtn = document.getElementById('nextBtn');
                const nextBtnText = document.getElementById('nextBtnText');
                const finishForm = document.getElementById('finishForm');

                if (prevBtnContainer) {
                    if (num === 1) {
                        prevBtnContainer.classList.add('hidden');
                    } else {
                        prevBtnContainer.classList.remove('hidden');
                    }
                }

                const currentRangeIdx = subtestRanges.findIndex(r => num >= r.start_number && num <= r
                    .end_number);
                const currentRange = subtestRanges[currentRangeIdx];
                const isLastOfSubtest = (num === currentRange.end_number);
                const hasNextSubtest = (currentRangeIdx < subtestRanges.length - 1);

                if (num === totalQuestions) {
                    nextBtn.classList.add('hidden');
                    finishForm.classList.remove('hidden');
                } else {
                    nextBtn.classList.remove('hidden');
                    finishForm.classList.add('hidden');

                    if (isLastOfSubtest && hasNextSubtest) {
                        nextBtnText.textContent = "Mulai Subtest Berikutnya";
                    } else {
                        nextBtnText.textContent = "Selanjutnya";
                    }
                }

                // UI Re-application
                applyAnswerToUI(num);

                // MathJax re-render
                if (window.renderMathJax) window.renderMathJax();

                // Scroll to top of question content for better UX on mobile
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });

                // Update Flag Button UI
                const qid = targetWrapper.dataset.questionId;
                const navIcon = document.querySelector(
                    `.question-nav-item[data-question-id="${qid}"] .flag-icon-nav`);
                const flagBtn = document.querySelector('.flag-btn');
                const flagIcon = flagBtn.querySelector('i');
                const flagText = flagBtn.querySelector('.flag-text');

                if (navIcon && !navIcon.classList.contains('hidden')) {
                    flagIcon.className = 'ri-flag-fill mr-2';
                    flagText.textContent = 'Batal Tandai';
                } else {
                    flagIcon.className = 'ri-flag-line mr-2';
                    flagText.textContent = 'Tandai';
                }

                // Setup audio tracking if question has audio
                setupAudioTracking(num);
            };

            window.prevQuestion = function() {
                goToQuestion(currentNumber - 1);
            };

            window.nextQuestion = async function() {
                const wrapper = document.getElementById(`question-wrapper-${currentNumber}`);
                const detailId = wrapper.dataset.subtestDetailId;

                const currentRangeIdx = subtestRanges.findIndex(r => currentNumber >= r.start_number &&
                    currentNumber <= r.end_number);
                const currentRange = subtestRanges[currentRangeIdx];
                const isLastOfSubtest = (currentNumber === currentRange.end_number);
                const hasNextSubtest = (currentRangeIdx < subtestRanges.length - 1);

                if (isLastOfSubtest && hasNextSubtest && answerPersistenceMode === 'hybrid_subtest') {
                    const ok = confirm(
                        "Anda akan beralih ke subtest berikutnya. Jawaban subtest ini akan dikunci. Lanjutkan?"
                    );
                    if (!ok) return;

                    showSaveIndicator(true, 'Menyinkronkan subtest...');
                    try {
                        await flushSubtestAnswers(detailId);
                        goToQuestion(currentNumber + 1);
                    } catch (e) {
                        showSaveIndicator(false, e.message);
                    }
                } else {
                    goToQuestion(currentNumber + 1);
                }
            };

            // Handle browser back/forward
            window.addEventListener('popstate', function(event) {
                if (event.state && event.state.number) {
                    goToQuestion(event.state.number);
                }
            });

            // --- ANSWER HANDLING ---

            function loadAnswers() {
                try {
                    const raw = localStorage.getItem(answersKey);
                    return raw ? JSON.parse(raw) : {};
                } catch (e) {
                    return {};
                }
            }

            function persistAnswers() {
                localStorage.setItem(answersKey, JSON.stringify(answerCache));
            }

            function isAnswered(qid) {
                const ans = answerCache[qid];
                if (!ans) return false;
                if (ans.answered === true) return true;

                // Compatibility check for various types
                if (ans.option_id) return true;
                if (ans.option_ids && ans.option_ids.length > 0) return true;
                if (ans.answer_text && ans.answer_text.trim() !== '') return true;
                if (ans.mtf_answers && Object.values(ans.mtf_answers).some(v => v !== '')) return true;
                if (ans.matching_answers && Object.values(ans.matching_answers).some(v => v !== '')) return true;
                if (ans.answer_audio_base64 || ans.answer_audio_remote) return true;
                return false;
            }

            function setAnswer(qid, data) {
                answerCache[qid] = {
                    question_id: qid,
                    ...data,
                    answered: true,
                    synced: false,
                    updated_at: Date.now()
                };
                persistAnswers();
                updateSidebarIcon(qid);
                showSaveIndicator(true, 'Tersimpan');
            }

            function capturePendingTextAnswers() {
                document.querySelectorAll('.short-answer-input').forEach(input => {
                    const wrapper = input.closest('.question-wrapper');
                    if (!wrapper) return;

                    const value = input.value;
                    if (value.trim() === '') return;

                    if (input.debounce) {
                        clearTimeout(input.debounce);
                        input.debounce = null;
                    }

                    setAnswer(wrapper.dataset.questionId, {
                        type: wrapper.dataset.questionType,
                        answer_text: value
                    });
                });
            }

            function updateSidebarIcon(qid) {
                const item = document.querySelector(`.question-nav-item[data-question-id="${qid}"]`);
                if (!item) return;
                const num = parseInt(item.dataset.number);
                if (num === currentNumber) return; // Keep primary color

                if (isAnswered(qid)) {
                    item.classList.add('bg-green', 'text-white');
                    item.classList.remove('bg-gray-100', 'text-gray-600');
                } else {
                    item.classList.remove('bg-green', 'text-white');
                    item.classList.add('bg-gray-100', 'text-gray-600');
                }
            }

            function applyAnswerToUI(num) {
                const wrapper = document.getElementById(`question-wrapper-${num}`);
                if (!wrapper) return;
                const qid = wrapper.dataset.questionId;
                const qType = wrapper.dataset.questionType;
                const saved = answerCache[qid];
                if (!saved) return;

                switch (qType) {
                    case 'multiple_choice':
                    case 'true_false':
                        if (saved.option_id) {
                            const radio = wrapper.querySelector(
                                `input[name="answer_option"][value="${saved.option_id}"]`);
                            if (radio) radio.checked = true;
                        }
                        break;
                    case 'multiple_answer':
                        if (Array.isArray(saved.option_ids)) {
                            wrapper.querySelectorAll('input[name="answer_options[]"]').forEach(input => {
                                input.checked = saved.option_ids.includes(Number(input.value));
                            });
                        }
                        break;
                    case 'matching':
                        if (saved.matching_answers) {
                            wrapper.querySelectorAll('.matching-select').forEach(sel => {
                                sel.value = saved.matching_answers[sel.dataset.left] || "";
                            });
                        }
                        break;
                    case 'multiple_true_false':
                        if (saved.mtf_answers) {
                            wrapper.querySelectorAll('.mtf-radio').forEach(rad => {
                                const val = saved.mtf_answers[rad.dataset.statementId];
                                rad.checked = (val && val.toString() === rad.value);
                            });
                        }
                        break;
                    case 'short_answer':
                    case 'essay':
                        const txt = wrapper.querySelector('.short-answer-input');
                        if (txt) txt.value = saved.answer_text || "";
                        break;
                    case 'audio':
                        const preview = wrapper.querySelector('.audio-answer-preview');
                        if (preview) {
                            preview.innerHTML = '';
                            const src = saved.answer_audio_base64 || saved.answer_audio_remote;
                            if (src) {
                                const audio = document.createElement('audio');
                                audio.controls = true;
                                audio.className = 'w-full';
                                audio.src = src;
                                preview.appendChild(audio);
                            }
                        }
                        break;
                }

                // Refresh labels
                wrapper.querySelectorAll('.answer-input').forEach(updateOptionLabelState);
            }

            function updateOptionLabelState(input) {
                const label = input.closest('.answer-option-label');
                if (!label) return;
                if (input.checked) {
                    label.classList.add('border-primary', 'bg-primary/10', 'ring-1', 'ring-primary');
                    label.classList.remove('border-gray-200');
                } else {
                    label.classList.remove('border-primary', 'bg-primary/10', 'ring-1', 'ring-primary');
                    label.classList.add('border-gray-200');
                }
            }

            // --- EVENT DELEGATION FOR ANSWERS ---

            document.getElementById('questions-container').addEventListener('change', function(e) {
                const target = e.target;
                const wrapper = target.closest('.question-wrapper');
                if (!wrapper) return;

                const qid = wrapper.dataset.questionId;
                const qType = wrapper.dataset.questionType;

                if (target.classList.contains('answer-input')) {
                    if (qType === 'multiple_answer') {
                        const checked = Array.from(wrapper.querySelectorAll(
                            'input[name="answer_options[]"]:checked'));
                        setAnswer(qid, {
                            type: 'multiple_answer',
                            option_ids: checked.map(i => Number(i.value))
                        });
                    } else {
                        setAnswer(qid, {
                            type: qType,
                            option_id: target.value,
                            option_key: target.dataset.optionKey
                        });
                    }
                    updateOptionLabelState(target);
                    // Update siblings if radio
                    if (target.type === 'radio') {
                        wrapper.querySelectorAll(`input[name="${target.name}"]`).forEach(i => {
                            if (i !== target) updateOptionLabelState(i);
                        });
                    }
                } else if (target.classList.contains('matching-select')) {
                    const matches = {};
                    wrapper.querySelectorAll('.matching-select').forEach(s => {
                        matches[s.dataset.left] = s.value;
                    });
                    setAnswer(qid, {
                        type: 'matching',
                        matching_answers: matches
                    });
                } else if (target.classList.contains('mtf-radio')) {
                    const answers = {};
                    wrapper.querySelectorAll('.mtf-radio:checked').forEach(r => {
                        answers[r.dataset.statementId] = r.value;
                    });
                    setAnswer(qid, {
                        type: 'multiple_true_false',
                        mtf_answers: answers
                    });
                } else if (target.classList.contains('audio-answer-input')) {
                    const file = target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        setAnswer(qid, {
                            type: 'audio',
                            answer_audio_base64: ev.target.result,
                            answer_audio_name: file.name
                        });
                        applyAnswerToUI(currentNumber);
                    };
                    reader.readAsDataURL(file);
                }
            });

            document.getElementById('questions-container').addEventListener('input', function(e) {
                if (e.target.classList.contains('short-answer-input')) {
                    const wrapper = e.target.closest('.question-wrapper');
                    const qid = wrapper.dataset.questionId;
                    const qType = wrapper.dataset.questionType;

                    clearTimeout(e.target.debounce);
                    e.target.debounce = setTimeout(() => {
                        setAnswer(qid, {
                            type: qType,
                            answer_text: e.target.value
                        });
                    }, 500);
                }
            });

            // --- SUBMIT & SYNC ---

            window.flushSubtestAnswers = async function(detailId) {
                const subtestQuestions = Array.from(document.querySelectorAll(
                        `.question-wrapper[data-subtest-detail-id="${detailId}"]`))
                    .map(w => w.dataset.questionId);
                const payload = Object.values(answerCache).filter(a => subtestQuestions.includes(a
                    .question_id) && !a.synced);

                const response = await fetch(
                    '{{ url('/user/tryout/' . ($package ? $package->package_id : 'free') . '/' . $tryout->tryout_id . '/subtest/flush') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            tryout_detail_id: detailId,
                            attempt_token: attemptToken,
                            answers_payload: JSON.stringify(payload)
                        })
                    });

                const data = await response.json();
                if (!data.success) throw new Error(data.message || "Gagal sinkron");

                // Mark as synced locally
                subtestQuestions.forEach(qid => {
                    if (answerCache[qid]) answerCache[qid].synced = true;
                });
                persistAnswers();
                return data;
            };

            const finishButton = document.querySelector('#finishForm button[type="submit"]');
            if (finishButton) {
                finishButton.parentElement.addEventListener('submit', function() {
                    isLeavingTryout = true;
                    capturePendingTextAnswers();
                    const unsynced = Object.values(answerCache).filter(a => !a.synced);
                    document.getElementById('answersPayloadInput').value = JSON.stringify(unsynced);
                });
            }

            function showSaveIndicator(success, msg) {
                const div = document.createElement('div');
                div.className =
                    `fixed top-4 right-4 z-[999] px-4 py-2 rounded shadow-lg text-white transition-opacity duration-500 ${success ? 'bg-green' : 'bg-red'}`;
                div.textContent = msg;
                document.body.appendChild(div);
                setTimeout(() => {
                    div.style.opacity = '0';
                    setTimeout(() => div.remove(), 500);
                }, 2000);
            }

            // Flags
            window.flagQuestion = function() {
                const wrapper = document.getElementById(`question-wrapper-${currentNumber}`);
                const qid = wrapper.dataset.questionId;

                fetch('{{ url('/user/tryout/' . ($package ? $package->package_id : 'free') . '/' . $tryout->tryout_id . '/flag') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            question_id: qid
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const btn = document.querySelector('.flag-btn');
                            const icon = btn.querySelector('i');
                            const text = btn.querySelector('.flag-text');
                            const navIcon = document.querySelector(
                                `.question-nav-item[data-question-id="${qid}"] .flag-icon-nav`);

                            if (data.flagged) {
                                icon.className = 'ri-flag-fill mr-2';
                                text.textContent = 'Batal Tandai';
                                if (navIcon) navIcon.classList.remove('hidden');
                            } else {
                                icon.className = 'ri-flag-line mr-2';
                                text.textContent = 'Tandai';
                                if (navIcon) navIcon.classList.add('hidden');
                            }
                        }
                    });
            };

            function setupAudioTracking(num) {
                const wrapper = document.getElementById(`question-wrapper-${num}`);
                if (!wrapper) return;
                const audio = wrapper.querySelector('audio');
                if (!audio) return;
                const qid = wrapper.dataset.questionId;
                const saved = answerCache[qid];

                if (saved && saved.is_played) {
                    audio.controls = false;
                    audio.removeAttribute('controls');
                    if (!wrapper.querySelector('.audio-played-badge')) {
                        const badge = document.createElement('div');
                        badge.className =
                            "audio-played-badge mt-2 inline-flex items-center gap-2 px-4 py-2 bg-primary text-white font-bold rounded-full animate-fade-in";
                        badge.innerHTML = `<i class="ri-check-line"></i> Audio sudah diputar`;
                        audio.insertAdjacentElement('afterend', badge);
                    }
                    return;
                }

                audio.onplay = function() {
                    const markUrl =
                        '{{ url('/user/tryout/listening/mark-played/' . ($package ? $package->package_id : 'free') . '/' . $tryout->tryout_id . '/:qid') }}'
                        .replace(':qid', qid);
                    fetch(markUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    }).then(res => res.json()).then(data => {
                        if (data.status === 'success') {
                            if (!answerCache[qid]) answerCache[qid] = {
                                question_id: qid
                            };
                            answerCache[qid].is_played = true;
                            persistAnswers();
                            setupAudioTracking(num); // Refresh UI
                        }
                    });
                };
            }

            // Timer Setup (adapted)
            function setupTimerSPA() {
                let timeLeft = {{ $remainingSeconds }};
                const timerDisplay = document.getElementById('timer-display');

                const interval = setInterval(() => {
                    timeLeft--;
                    if (timeLeft <= 0) {
                        clearInterval(interval);
                        alert("Waktu habis!");
                        finishButton.click();
                        return;
                    }

                    const h = Math.floor(timeLeft / 3600);
                    const m = Math.floor((timeLeft % 3600) / 60);
                    const s = timeLeft % 60;
                    const str =
                        `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
                    if (timerDisplay) {
                        timerDisplay.textContent = str;
                        if (timeLeft < 300) timerDisplay.style.color = 'red';
                    }
                }, 1000);
            }

            // Initial UI Sync
            for (let i = 1; i <= totalQuestions; i++) {
                applyAnswerToUI(i);
                const qid = document.getElementById(`question-wrapper-${i}`)?.dataset.questionId;
                if (qid) updateSidebarIcon(qid);
            }

            goToQuestion(currentNumber);
            setupTimerSPA();
        });
    </script>


@endsection
