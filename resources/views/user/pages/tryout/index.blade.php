@extends('user.layout.tryout')
@section('title', 'Tryout - Soal ' . $number)
@section('content')
<div class="min-h-screen bg-gray-50 pt-16">
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Question Section -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-lg border border-border p-6">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-6 pb-4 border-b border-border">
                        <div class="flex w-full justify-between">
                            <h2 class="text-xl font-bold text-gray-800">Soal {{ $number }} dari {{ $totalQuestions }}
                            </h2>
                            @if(isset($currentSubtest))
                            <p class="text-sm text-gray-600 mt-1">{{ $currentSubtest['name'] }}</p>
                            @endif
                        </div>
                        <div id="timer" hidden class="text-2xl font-bold text-primary">00:00:00</div>
                    </div>

                    <!-- Question Content -->
                    <div class="mb-8">
                        <div class="text-gray-700 leading-relaxed">
                            {!! $currentQuestion->question_text !!}
                        </div>

                        @if($currentQuestion->sound)
                        <div class="mt-4">
                            <audio id="audio-{{ $currentQuestion->question_id }}" controls controlsList="nodownload"
                                oncontextmenu="return false;" class="w-full">
                                <source src="{{ Storage::url($currentQuestion->sound) }}" type="audio/mpeg">
                                Browser Anda tidak mendukung audio.
                            </audio>
                        </div>
                        @endif
                    </div>

                    <!-- Answer Options -->
                    @php
                        $questionType = $currentQuestion->question_type ?? 'multiple_choice';
                        $metadata = is_array($currentQuestion->metadata) ? $currentQuestion->metadata : [];
                        $matchingPairs = isset($metadata['matching_pairs']) && is_array($metadata['matching_pairs']) ? $metadata['matching_pairs'] : [];
                        $matchingRightOptions = [];
                        foreach ($matchingPairs as $pair) {
                            $right = trim((string)($pair['right'] ?? ''));
                            if ($right !== '' && !in_array($right, $matchingRightOptions, true)) {
                                $matchingRightOptions[] = $right;
                            }
                        }
                        $shuffledMatchingOptions = $matchingRightOptions;
                        shuffle($shuffledMatchingOptions);
                        $shortMeta = isset($metadata['short_answer']) && is_array($metadata['short_answer']) ? $metadata['short_answer'] : [];
                        $audioMeta = isset($metadata['audio_answer']) && is_array($metadata['audio_answer']) ? $metadata['audio_answer'] : [];
                        $pendingReview = false;

                        $initialAnswer = null;
                        if ($userAnswerDetail) {
                            switch ($questionType) {
                                case 'multiple_choice':
                                case 'true_false':
                                    $initialAnswer = [
                                        'question_id' => $currentQuestion->question_id,
                                        'type' => $questionType,
                                        'option_id' => $userAnswerDetail->question_option_id,
                                        'option_key' => optional($userAnswerDetail->questionOption)->option_key,
                                    ];
                                    break;
                                case 'matching':
                                    $initialAnswer = [
                                        'question_id' => $currentQuestion->question_id,
                                        'type' => 'matching',
                                        'matching_answers' => $userAnswerDetail->answer_json['matches'] ?? [],
                                    ];
                                    break;
                                case 'short_answer':
                                case 'essay':
                                    $initialAnswer = [
                                        'question_id' => $currentQuestion->question_id,
                                        'type' => $questionType,
                                        'answer_text' => $userAnswerDetail->answer_text,
                                    ];
                                    break;
                                case 'audio':
                                    $initialAnswer = [
                                        'question_id' => $currentQuestion->question_id,
                                        'type' => 'audio',
                                        'answer_audio_remote' => $userAnswerDetail->answer_file_path ? Storage::url($userAnswerDetail->answer_file_path) : null,
                                        'answer_audio_name' => $userAnswerDetail->answer_json['original_name'] ?? basename($userAnswerDetail->answer_file_path ?? ''),
                                    ];
                                    break;
                            }
                        }
                    @endphp

                    <form id="answerForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="question_id" value="{{ $currentQuestion->question_id }}">
                        <input type="hidden" id="questionType" value="{{ $questionType }}">

                        @if(in_array($questionType, ['multiple_choice', 'true_false']))
                        <div class="space-y-3" id="multipleChoiceOptions">
                            @foreach($currentQuestion->questionOptions as $option)
                            <label
                                class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer transition-all duration-200 hover:border-primary hover:bg-primary/5 answer-option-label"
                                for="option_{{ $option->question_option_id }}">
                                <input type="radio" id="option_{{ $option->question_option_id }}" name="answer_option"
                                    value="{{ $option->question_option_id }}"
                                    data-option-key="{{ $option->option_key ?? 'A' }}"
                                    class="w-5 h-5 text-primary border-gray-300 focus:ring-primary answer-radio">
                                <span class="ml-4 flex-1 text-gray-700">
                                    {!! $option->option_text !!}
                                </span>
                            </label>
                            @endforeach
                        </div>

                        @elseif($questionType === 'matching')
                        <div class="space-y-4" id="matchingAnswerContainer">
                            @if(empty($matchingPairs))
                            <p class="text-sm text-gray-500">Belum ada pasangan jawaban untuk soal ini.</p>
                            @else
                            <p class="text-sm text-gray-600">Cocokkan kolom di kiri dengan jawaban yang tepat di kolom kanan.</p>
                            @foreach($matchingPairs as $index => $pair)
                            @php
                                $leftLabel = trim((string)($pair['left'] ?? 'Item ' . ($index + 1)));
                            @endphp
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                                <div class="font-medium text-gray-800">{{ $leftLabel }}</div>
                                <div>
                                    <select class="matching-select w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        data-left="{{ $leftLabel }}">
                                        <option value="">Pilih jawaban</option>
                                        @foreach($shuffledMatchingOptions as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endforeach
                            @endif
                        </div>

                        @elseif(in_array($questionType, ['short_answer', 'essay']))
                        <div class="space-y-3" id="shortAnswerWrapper">
                            <label for="shortAnswerInput" class="block text-sm font-medium text-gray-700">Jawabanmu</label>
                            <textarea id="shortAnswerInput" rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Ketik jawaban singkatmu di sini"></textarea>
                            @if(!empty($shortMeta['expected_answers']))
                            <p class="text-xs text-gray-500">Saran: Jawaban otomatis benar jika sesuai dengan salah satu kunci.</p>
                            @endif
                            @if($pendingReview)
                            <div class="inline-flex items-center gap-2 px-3 py-2 bg-amber-50 text-amber-700 text-xs rounded border border-amber-200">
                                <i class="ri-hourglass-line"></i>
                                Menunggu penilaian manual
                            </div>
                            @endif
                        </div>

                        @elseif($questionType === 'audio')
                        <div class="space-y-4" id="audioAnswerWrapper">
                            <div id="audioAnswerPreview" class="space-y-2"></div>
                            <div>
                                <label for="audioAnswerInput" class="block text-sm font-medium text-gray-700 mb-2">Unggah
                                    Jawaban Audio</label>
                                <input type="file" id="audioAnswerInput" accept="audio/*"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <p class="text-xs text-gray-500 mt-2">
                                    {{ isset($audioMeta['instructions']) ? $audioMeta['instructions'] : 'Rekam jawabanmu lalu unggah (format MP3/WAV/M4A).' }}
                                </p>
                                @if(!empty($audioMeta['max_duration']))
                                <p class="text-xs text-gray-500">Durasi maksimal: {{ $audioMeta['max_duration'] }} detik.</p>
                                @endif
                                @if(!empty($audioMeta['max_size']))
                                <p class="text-xs text-gray-500">Ukuran file maksimal: {{ $audioMeta['max_size'] }} MB.</p>
                                @endif
                            </div>
                            <div class="inline-flex items-center gap-2 px-3 py-2 bg-amber-50 text-amber-700 text-xs rounded border border-amber-200">
                                <i class="ri-hourglass-line"></i>
                                Jawaban audio akan dinilai manual oleh mentor
                            </div>
                        </div>
                        @endif
                    </form>

                    <!-- Navigation -->
                    <div class="mt-8 flex justify-between items-center pt-6 border-t border-border">
                        @if($number > 1)
                        <div class="flex gap-3">
                            <a href="{{ route('user.tryout.index', [$package ? $package->package_id : 'free', $tryout->tryout_id, $number - 1]) }}"
                                class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="ri-arrow-left-line mr-2"></i>Sebelumnya
                            </a>
                        </div>
                        @endif
                        <div>
                            <button onclick="flagQuestion()"
                                class="px-4 py-2 border border-red text-red rounded-lg hover:bg-red hover:text-white transition-colors flag-btn">
                                <i class="ri-flag-line mr-2"></i>
                                <span class="flag-text">{{ in_array($currentQuestion->question_id, $flaggedQuestions) ?
                                    'Batal Tandai' : 'Tandai' }}</span>
                            </button>
                        </div>

                        <div class="flex gap-3">
                            @if($number < $totalQuestions) <a
                                href="{{ route('user.tryout.index', [$package ? $package->package_id : 'free', $tryout->tryout_id, $number + 1]) }}"
                                class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                                Selanjutnya<i class="ri-arrow-right-line ml-2"></i>
                                </a>
                                @else
                                <form
                                    action="{{ route('user.tryout.finish', [$package ? $package->package_id : 'free', $tryout->tryout_id]) }}"
                                    method="POST" class="inline" id="finishForm">
                                    @csrf
                                    <input type="hidden" name="answers_payload" id="answersPayloadInput">
                                    <input type="hidden" name="attempt_token" value="{{ $attemptToken }}">
                                    <button type="submit"
                                        onclick="return confirm('Apakah Anda yakin ingin menyelesaikan tryout ini?')"
                                        class="px-6 py-2 bg-green text-white rounded-lg hover:bg-green-700 transition-colors">
                                        <i class="ri-check-line mr-2"></i>Selesai
                                    </button>
                                </form>
                                @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg border border-border p-6 sticky top-6 text-center">
                    <p class="text-sm text-gray-600 mb-2">Sisa Waktu</p>
                    <div id="timer-display" class="text-3xl font-bold text-primary">00:00:00</div>
                </div>
                <div class="bg-white rounded-lg border border-border p-6 sticky mt-4">
                    <!-- Question Navigation -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-800 mb-4">Navigasi Soal</h3>
                        <div class="grid grid-cols-5 gap-2">
                        @foreach($allQuestions as $index => $question)
                        @php
                            $questionNumber = $index + 1;
                            $isCurrent = $questionNumber == $number;
                            $isFlagged = in_array($question->question_id, $flaggedQuestions);
                        @endphp
                        <a href="{{ route('user.tryout.index', [$package ? $package->package_id : 'free', $tryout->tryout_id, $questionNumber]) }}"
                            class="relative w-10 h-10 flex items-center justify-center text-sm font-medium rounded-lg transition-colors question-nav-item {{ $isCurrent ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                            data-question-id="{{ $question->question_id }}">
                            {{ $questionNumber }}
                            @if($isFlagged)
                            <i class="ri-flag-fill absolute -top-1 -right-1 text-xs text-red"></i>
                            @endif
                        </a>
                        @endforeach
                        </div>
                    </div>

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
                @if(isset($subtestInfo) && count($subtestInfo) > 1)
                <div class="mb-6 p-4 bg-white border border-border mt-4 rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-gray-700">Progress SKD Full</span>
                        <span class="text-sm text-gray-600">{{ count($subtestInfo) }} Subtest</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach($subtestInfo as $index => $subtest)
                        @php
                        $isCurrentSubtest = $currentSubtest && $currentSubtest['type'] === $subtest['type'];
                        $isCompleted = $number > $subtest['end_number'];
                        @endphp
                        <div class="text-center">
                            <div class="w-8 h-8 rounded-full mx-auto mb-1 flex items-center justify-center text-sm font-semibold
                                    {{ $isCompleted ? 'bg-green text-white' :
                                       ($isCurrentSubtest ? 'bg-primary text-white' : 'bg-gray-200 text-gray-600') }}">
                                {{ $index + 1 }}
                            </div>
                            <p class="text-xs text-gray-600">{{ strtoupper($subtest['type']) }}</p>
                            @if($isCurrentSubtest)
                            <div class="mt-2">
                                @php
                                $subtestProgress = (($number - $subtest['start_number'] + 1) /
                                ($subtest['end_number'] - $subtest['start_number'] + 1)) * 100;
                                @endphp
                                <div class="w-full bg-gray-200 rounded-full h-1">
                                    <div class="bg-primary h-1 rounded-full" style="width: {{ $subtestProgress }}%">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Tryout page loaded (client-first mode)');

        const attemptToken = '{{ $attemptToken }}';
        const answersKey = `tryout_answers_${attemptToken}`;
        const questionId = {{ $currentQuestion->question_id }};
        const questionType = (document.getElementById('questionType')?.value || 'multiple_choice');
        const normalizedType = questionType === 'true_false' ? 'multiple_choice' : questionType;
        const csrfToken = '{{ csrf_token() }}';
        const finishForm = document.getElementById('finishForm');
        const answersPayloadInput = document.getElementById('answersPayloadInput');
        const serverAnswered = @json($userAnswerDetails);
        const initialAnswer = @json($initialAnswer);

        let answerCache = loadAnswers();

        if (Array.isArray(serverAnswered)) {
            serverAnswered.forEach(id => {
                const key = id.toString();
                if (!answerCache[key]) {
                    answerCache[key] = {
                        question_id: id,
                        type: 'synced',
                        answered: true,
                        synced_at: Date.now()
                    };
                }
            });
        }

        if (initialAnswer && (!answerCache[questionId] || !answerCache[questionId].updated_at)) {
            answerCache[questionId] = {
                question_id: questionId,
                ...initialAnswer,
                answered: true,
                updated_at: Date.now()
            };
        }

        persistAnswers();

        function loadAnswers() {
            try {
                const raw = localStorage.getItem(answersKey);
                if (!raw) {
                    return {};
                }
                const parsed = JSON.parse(raw);
                return (parsed && typeof parsed === 'object') ? parsed : {};
            } catch (error) {
                console.warn('Gagal memuat jawaban lokal', error);
                return {};
            }
        }

        function persistAnswers() {
            try {
                localStorage.setItem(answersKey, JSON.stringify(answerCache));
            } catch (error) {
                console.warn('Tidak dapat menyimpan jawaban lokal', error);
            }
        }

        function getAnswer(qid) {
            return answerCache[qid] || null;
        }

        function isAnswered(qid) {
            const answer = getAnswer(qid);
            if (!answer) {
                return false;
            }

             if (answer.answered) {
                return true;
            }

            switch (answer.type) {
                case 'multiple_choice':
                case 'true_false':
                    return !!answer.option_id;
                case 'matching':
                    if (!answer.matching_answers) {
                        return false;
                    }
                    return Object.values(answer.matching_answers).every(value => (value || '').trim() !== '');
                case 'short_answer':
                case 'essay':
                    return !!(answer.answer_text && answer.answer_text.trim().length > 0);
                case 'audio':
                    return !!(answer.answer_audio_base64 || answer.answer_audio_remote);
                default:
                    return !!answer.answer_audio_remote;
            }
        }

        function updateNavigation(qid) {
            const navItem = document.querySelector(`.question-nav-item[data-question-id="${qid}"]`);
            if (!navItem) {
                return;
            }

            const isCurrent = navItem.classList.contains('bg-primary');
            if (isAnswered(qid) && !isCurrent) {
                navItem.classList.remove('bg-gray-100', 'text-gray-600', 'hover:bg-gray-200');
                navItem.classList.add('bg-green', 'text-white');
            } else if (!isCurrent) {
                navItem.classList.remove('bg-green', 'text-white');
                navItem.classList.add('bg-gray-100', 'text-gray-600', 'hover:bg-gray-200');
            }
        }

        function setAnswer(qid, data) {
            answerCache[qid] = {
                question_id: qid,
                ...data,
                answered: true,
                updated_at: Date.now()
            };
            persistAnswers();
            updateNavigation(qid);
            showSaveIndicator(true, 'Jawaban tersimpan sementara');
        }

        function clearAnswer(qid) {
            delete answerCache[qid];
            persistAnswers();
            updateNavigation(qid);
        }

        function highlightSelectedOption(radio) {
            document.querySelectorAll('.answer-option-label').forEach(label => {
                label.classList.remove('border-primary', 'bg-primary/10', 'ring-1', 'ring-primary');
                label.classList.add('border-gray-200');
            });
            const label = radio.closest('.answer-option-label');
            if (label) {
                label.classList.remove('border-gray-200');
                label.classList.add('border-primary', 'bg-primary/10', 'ring-1', 'ring-primary');
            }
        }

        function applyAnswerToUI() {
            const saved = getAnswer(questionId);
            if (!saved) {
                return;
            }

            switch (questionType) {
                case 'multiple_choice':
                case 'true_false':
                    if (saved.option_id) {
                        const radio = document.querySelector(`input[name="answer_option"][value="${saved.option_id}"]`);
                        if (radio) {
                            radio.checked = true;
                            highlightSelectedOption(radio);
                        }
                    }
                    break;
                case 'matching':
                    if (saved.matching_answers) {
                        document.querySelectorAll('.matching-select').forEach(select => {
                            const left = select.dataset.left;
                            if (saved.matching_answers[left]) {
                                select.value = saved.matching_answers[left];
                            }
                        });
                    }
                    break;
                case 'short_answer':
                case 'essay':
                    if (saved.answer_text) {
                        const textarea = document.getElementById('shortAnswerInput');
                        if (textarea) {
                            textarea.value = saved.answer_text;
                        }
                    }
                    break;
                case 'audio':
                    updateAudioPreview();
                    break;
            }
        }

        function updateAudioPreview() {
            const previewWrapper = document.getElementById('audioAnswerPreview');
            if (!previewWrapper) {
                return;
            }
            previewWrapper.innerHTML = '';

            const saved = getAnswer(questionId);
            if (saved && saved.answer_audio_base64) {
                const info = document.createElement('p');
                info.className = 'text-sm text-gray-600';
                info.textContent = 'Jawaban audio tersimpan di perangkat.';

                const audioEl = document.createElement('audio');
                audioEl.controls = true;
                audioEl.className = 'w-full';
                audioEl.src = saved.answer_audio_base64;

                previewWrapper.appendChild(info);
                previewWrapper.appendChild(audioEl);
            } else if (saved && saved.answer_audio_remote) {
                const info = document.createElement('p');
                info.className = 'text-sm text-gray-600';
                info.textContent = 'Jawaban audio tersimpan di server.';

                const audioEl = document.createElement('audio');
                audioEl.controls = true;
                audioEl.className = 'w-full';
                audioEl.src = saved.answer_audio_remote;

                previewWrapper.appendChild(info);
                previewWrapper.appendChild(audioEl);
            }
        }

        if (['multiple_choice', 'true_false'].includes(questionType)) {
            document.querySelectorAll('.answer-radio').forEach(radio => {
                radio.addEventListener('change', function() {
                    setAnswer(questionId, {
                        type: questionType,
                        option_id: this.value,
                        option_key: this.dataset.optionKey || null
                    });
                    highlightSelectedOption(this);
                });
            });
        }

        if (normalizedType === 'matching') {
            const selects = document.querySelectorAll('.matching-select');
            if (selects.length) {
                selects.forEach(select => {
                    select.addEventListener('change', function() {
                        const matches = {};
                        selects.forEach(sel => {
                            const left = sel.dataset.left;
                            matches[left] = (sel.value || '').trim();
                        });

                        setAnswer(questionId, {
                            type: 'matching',
                            matching_answers: matches
                        });
                    });
                });
            }
        }

        if (normalizedType === 'short_answer' || normalizedType === 'essay') {
            const textarea = document.getElementById('shortAnswerInput');
            if (textarea) {
                let debounceTimer;
                const persistShortAnswer = () => {
                    const value = textarea.value || '';
                    setAnswer(questionId, {
                        type: questionType,
                        answer_text: value
                    });
                };

                textarea.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(persistShortAnswer, 500);
                });

                textarea.addEventListener('blur', function() {
                    clearTimeout(debounceTimer);
                    persistShortAnswer();
                });
            }
        }

        if (normalizedType === 'audio') {
            const audioInput = document.getElementById('audioAnswerInput');
            if (audioInput) {
                audioInput.addEventListener('change', function() {
                    const file = audioInput.files && audioInput.files[0];
                    if (!file) {
                        clearAnswer(questionId);
                        updateAudioPreview();
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const dataUrl = event.target?.result;
                        if (!dataUrl) {
                            showSaveIndicator(false, 'Gagal membaca file audio');
                            return;
                        }

                        setAnswer(questionId, {
                            type: 'audio',
                            answer_audio_base64: dataUrl,
                            answer_audio_name: file.name,
                            answer_audio_mime: file.type || 'audio/mpeg'
                        });
                        updateAudioPreview();
                    };
                    reader.readAsDataURL(file);
                });
            }
        }

        function buildAnswersPayload() {
            return JSON.stringify(Object.values(answerCache));
        }

        if (finishForm && answersPayloadInput) {
            finishForm.addEventListener('submit', function() {
                answersPayloadInput.value = buildAnswersPayload();
            });
        }

        function showSaveIndicator(success, message) {
            const existingIndicators = document.querySelectorAll('.save-indicator');
            existingIndicators.forEach(indicator => indicator.remove());

            const indicator = document.createElement('div');
            indicator.className = `save-indicator fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-white ${success ? 'bg-green' : 'bg-red'}`;
            indicator.textContent = message || (success ? 'Jawaban tersimpan sementara' : 'Gagal menyimpan');

            document.body.appendChild(indicator);

            setTimeout(() => {
                indicator.remove();
            }, 2000);
        }

        window.flagQuestion = function() {
            fetch('{{ route("user.tryout.flag", [$package ? $package->package_id : "free", $tryout->tryout_id]) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    question_id: {{ $currentQuestion->question_id }}
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const flagBtn = document.querySelector('.flag-btn');
                    const flagText = document.querySelector('.flag-text');
                    const flagIcon = flagBtn.querySelector('i');

                    if (data.flagged) {
                        flagText.textContent = 'Batal Tandai';
                        flagIcon.className = 'ri-flag-fill mr-2';
                    } else {
                        flagText.textContent = 'Tandai';
                        flagIcon.className = 'ri-flag-line mr-2';
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        };

        function setupTimer() {
            @php
                $now = \Carbon\Carbon::now('Asia/Jakarta');
                $startTime = \Carbon\Carbon::parse($currentUserAnswer->started_at, 'Asia/Jakarta');

                if (isset($tryoutDetails) && $tryoutDetails->count() > 1) {
                    $totalDuration = $tryoutDetails->sum('duration');
                } else {
                    $totalDuration = $tryoutDetails->first()->duration ?? 60;
                }

                $endTime = $startTime->copy()->addMinutes($totalDuration);

                if ($now->lt($endTime)) {
                    $remainingSecondsCalc = (int) $now->diffInSeconds($endTime);
                } else {
                    $remainingSecondsCalc = 0;
                }
            @endphp

            let timeLeft = {{ $remainingSecondsCalc }};
            const timerEl = document.getElementById('timer');
            const timerDisplayEl = document.getElementById('timer-display');

            function renderTimer() {
                const hours = Math.floor(timeLeft / 3600);
                const minutes = Math.floor((timeLeft % 3600) / 60);
                const seconds = timeLeft % 60;
                const display = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                if (timerEl) timerEl.textContent = display;
                if (timerDisplayEl) timerDisplayEl.textContent = display;

                if (timeLeft <= 300) {
                    if (timerEl) timerEl.style.color = '#dc2626';
                    if (timerDisplayEl) timerDisplayEl.style.color = '#dc2626';
                } else if (timeLeft <= 600) {
                    if (timerEl) timerEl.style.color = '#f59e0b';
                    if (timerDisplayEl) timerDisplayEl.style.color = '#f59e0b';
                }
            }

            function triggerAutoFinish() {
                alert('Waktu ujian telah habis!');
                const autoForm = document.createElement('form');
                autoForm.method = 'POST';
                autoForm.action = '{{ route("user.tryout.finish", [$package ? $package->package_id : "free", $tryout->tryout_id]) }}';

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                autoForm.appendChild(csrfInput);

                const attemptInput = document.createElement('input');
                attemptInput.type = 'hidden';
                attemptInput.name = 'attempt_token';
                attemptInput.value = attemptToken;
                autoForm.appendChild(attemptInput);

                const answersInput = document.createElement('input');
                answersInput.type = 'hidden';
                answersInput.name = 'answers_payload';
                answersInput.value = buildAnswersPayload();
                autoForm.appendChild(answersInput);

                document.body.appendChild(autoForm);
                autoForm.submit();
            }

            renderTimer();

            const interval = setInterval(() => {
                timeLeft--;
                if (timeLeft <= 0) {
                    clearInterval(interval);
                    if (timerEl) timerEl.textContent = '00:00:00';
                    if (timerDisplayEl) timerDisplayEl.textContent = '00:00:00';
                    triggerAutoFinish();
                } else {
                    renderTimer();
                }
            }, 1000);
        }

        Object.keys(answerCache).forEach(updateNavigation);
        applyAnswerToUI();
        if (questionType === 'audio') {
            updateAudioPreview();
        }
        setupTimer();
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
    const audio = document.getElementById("audio-{{ $currentQuestion->question_id }}");
    if (!audio) {
        return;
    }

    let alreadyMarked = {{ $userAnswerDetail && $userAnswerDetail->is_played ? 'true' : 'false' }};

   if (alreadyMarked) {
        // Disable audio
        audio.controls = false;
        audio.removeAttribute("controls");

        // Tambahkan badge menarik
        const badge = document.createElement("div");
        badge.className = "mt-2 inline-flex items-center gap-2 px-4 py-2 bg-primary text-white font-bold rounded-full  animate-fade-in";
        badge.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                           </svg>
                           Audio sudah diputar`;

        audio.insertAdjacentElement('afterend', badge);
        return;
    }

    audio.addEventListener("play", function() {
        if (!alreadyMarked) {
            fetch("{{ route('user.tryout.markPlayed', [$package->id ?? 'free', $tryout->tryout_id, $currentQuestion->question_id]) }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    alreadyMarked = true;
                }
            });
        }
    }, { once: true });
});
</script>

@endsection
