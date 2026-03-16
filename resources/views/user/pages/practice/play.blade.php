@extends('user.layout.tryout')

@section('title', __('Latihan Soal') . ' - ' . __('Soal') . ' ' . $number)

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    $questionType = $question->question_type ?? 'multiple_choice';
    $metadata = is_array($question->metadata) ? $question->metadata : [];
    $matchingPairs = isset($metadata['matching_pairs']) && is_array($metadata['matching_pairs']) ? $metadata['matching_pairs'] : [];
    $matchingRightOptions = [];
    foreach ($matchingPairs as $pair) {
        $right = trim((string) ($pair['right'] ?? ''));
        if ($right !== '' && !in_array($right, $matchingRightOptions, true)) {
            $matchingRightOptions[] = $right;
        }
    }
    $answeredMap = collect($navigation)->keyBy('question_id');
    $shortMeta = $metadata['short_answer'] ?? [];
    $audioMeta = $metadata['audio_answer'] ?? [];
    $flaggedQuestions = $flaggedQuestions ?? [];
    $isCurrentFlagged = $isCurrentFlagged ?? false;
@endphp
<div class="min-h-screen bg-gray-50 pt-20 md:pt-8 pb-16" data-anticopy="practice">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-col lg:flex-row gap-6">
            <div class="lg:flex-1">
                <div class="bg-white border border-border rounded-2xl p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 pb-4 mb-6">
                        <div>
                            <p class="text-sm text-gray-500">{{ __('Soal Latihan') }}</p>
                            <h2 class="text-2xl font-semibold text-gray-900">{{ __('Soal :number dari :total', ['number' => $number, 'total' => $totalQuestions]) }}</h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="lg:hidden text-right">
                                <p class="text-[11px] uppercase tracking-wide text-gray-500">{{ __('Waktu Latihan') }}</p>
                                <div data-practice-timer class="text-lg font-bold text-primary leading-none">00:00:00</div>
                            </div>
                            <button type="button"
                                class="calculator-trigger inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                aria-haspopup="dialog" aria-controls="calculatorModal">
                                <i class="ri-calculator-line text-lg"></i>
                                {{ __('Kalkulator') }}
                            </button>
                            <a href="{{ route('user.practice.index') }}" class="inline-flex items-center text-sm text-primary hover:underline">
                                <i class="ri-arrow-left-line mr-1"></i> {{ __('Kembali ke Latihan') }}
                            </a>
                        </div>
                    </div>

                    <div class="text-gray-700 leading-relaxed select-none practice-protected" oncontextmenu="return false;">
                        {!! $question->question_text !!}
                    </div>

                    @if($question->sound)
                        <div class="mt-4">
                            <audio controls class="w-full">
                                <source src="{{ Storage::url($question->sound) }}" type="audio/mpeg">
                                {{ __('Browser tidak mendukung audio.') }}
                            </audio>
                        </div>
                    @endif

                    <form id="practiceAnswerForm" class="mt-6 space-y-4" enctype="multipart/form-data"
                        action="{{ route('user.practice.answer', $question) }}"
                        data-question-id="{{ $question->id }}"
                        data-question-type="{{ $questionType }}">
                        @csrf
                        @if(in_array($questionType, ['multiple_choice', 'true_false']))
                            @foreach($question->options as $option)
                                @php
                                    $isSelected = $currentAnswer && $currentAnswer->question_bank_question_option_id === $option->id;
                                    $optionClasses = $isSelected
                                        ? 'border-primary bg-primary/10 ring-1 ring-primary'
                                        : 'border-gray-200';
                                @endphp
                                <label class="flex items-start gap-3 p-4 border rounded-xl cursor-pointer transition-all duration-200 hover:border-primary/40 hover:bg-primary/5 select-none {{ $optionClasses }}">
                                    <input type="radio" name="option_id" value="{{ $option->id }}" class="mt-1 text-primary focus:ring-primary"
                                        @checked($isSelected)>
                                    <span class="flex-1 text-gray-700">{!! $option->option_text !!}</span>
                                </label>
                            @endforeach
                        @elseif($questionType === 'matching')
                            <input type="hidden" name="matching_answers">
                            <p class="text-sm text-gray-500">{{ __('Cocokkan kolom kiri dengan jawaban yang tepat.') }}</p>
                            <div class="space-y-3">
                                @foreach($matchingPairs as $pair)
                                    @php
                                        $leftLabel = trim((string) ($pair['left'] ?? ''));
                                        $selectedRight = $currentAnswer->answer_json['matches'][$leftLabel] ?? '';
                                    @endphp
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                                        <div class="font-medium text-gray-800">{{ $leftLabel }}</div>
                                        <select class="matching-select w-full border border-gray-200 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20"
                                            data-left="{{ $leftLabel }}">
                                            <option value="">{{ __('Pilih jawaban') }}</option>
                                            @foreach($matchingRightOptions as $rightOption)
                                                <option value="{{ $rightOption }}" @selected($selectedRight === $rightOption)>{{ $rightOption }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        @elseif(in_array($questionType, ['essay', 'short_answer']))
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">{{ __('Jawabanmu') }}</label>
                                <textarea name="answer_text" rows="5"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    placeholder="{{ __('Tulis jawaban di sini...') }}">{{ $currentAnswer->answer_text ?? '' }}</textarea>
                                @if(!empty($shortMeta['expected_answers'] ?? []))
                                    <p class="text-xs text-gray-500 mt-2">{{ __('Jawaban otomatis benar jika sesuai salah satu kunci.') }}</p>
                                @endif
                            </div>
                        @elseif($questionType === 'audio')
                            <div class="space-y-4">
                                @if($currentAnswer && $currentAnswer->answer_file_path)
                                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                                        <p class="text-sm text-gray-600 mb-2">{{ __('Jawaban tersimpan:') }}</p>
                                        <audio controls class="w-full">
                                            <source src="{{ Storage::url($currentAnswer->answer_file_path) }}" type="audio/mpeg">
                                        </audio>
                                    </div>
                                @endif
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">{{ __('Unggah jawaban audio (MP3/WAV/M4A)') }}</label>
                                    <input type="file" name="answer_audio" accept="audio/*"
                                        class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-primary file:text-white hover:file:bg-primary/90">
                                    <p class="text-xs text-gray-500 mt-2">
                                        {{ $audioMeta['instructions'] ?? __('Upload rekaman jawabanmu.') }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </form>

                    <div id="practiceFeedback" class="hidden mt-6 border border-gray-100 rounded-2xl p-5 bg-gray-50 select-none practice-protected" oncontextmenu="return false;">
                        <div id="practiceFeedbackStatusWrapper" class="mb-4">
                            <span id="practiceFeedbackStatus" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-200 text-gray-700">{{ __('Jawaban tersimpan') }}</span>
                        </div>
                        <div class="space-y-4 text-sm text-gray-700">
                            <div id="practiceFeedbackCorrectWrapper">
                                <p class="font-semibold text-gray-800">{{ __('Jawaban Benar') }}</p>
                                <div id="practiceFeedbackCorrect" class="mt-2 text-gray-700"></div>
                            </div>
                            <div id="practiceFeedbackExplanationWrapper">
                                <p class="font-semibold text-gray-800">{{ __('Pembahasan') }}</p>
                                <div id="practiceFeedbackExplanation" class="mt-2 text-gray-700"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between items-center gap-2 border-t border-gray-100 pt-4">
                        @if($number > 1)
                        <div class="flex gap-3">
                            <a href="{{ route('user.practice.play', ['number' => $number - 1]) }}"
                                class="inline-flex items-center justify-center px-3 md:px-4 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors"
                                aria-label="{{ __('Sebelumnya') }}">
                                <i class="ri-arrow-left-line text-lg md:text-base md:mr-2"></i>
                                <span class="hidden md:inline">{{ __('Sebelumnya') }}</span>
                            </a>
                        </div>
                        @endif

                        <div>
                            <button type="button" id="practiceFlagButton"
                                data-flagged="{{ $isCurrentFlagged ? 'true' : 'false' }}"
                                class="inline-flex items-center justify-center px-3 md:px-4 py-2 border border-red text-red rounded-lg hover:bg-red hover:text-white transition-colors flag-btn"
                                aria-label="{{ __('Tandai Soal') }}">
                                <i class="{{ $isCurrentFlagged ? 'ri-flag-fill text-lg md:text-base md:mr-2' : 'ri-flag-line text-lg md:text-base md:mr-2' }}"></i>
                                <span class="flag-text hidden md:inline">{{ $isCurrentFlagged ? __('Batalkan Tandai') : __('Tandai Soal') }}</span>
                            </button>
                        </div>

                        <div class="flex gap-3">
                            @if($number < $totalQuestions)
                                <a href="{{ route('user.practice.play', ['number' => $number + 1]) }}"
                                    class="inline-flex items-center justify-center px-3 md:px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors"
                                    aria-label="{{ __('Selanjutnya') }}">
                                    <span class="hidden md:inline">{{ __('Selanjutnya') }}</span>
                                    <i class="ri-arrow-right-line text-lg md:text-base md:ml-2"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:w-80">
                <div class="bg-white border border-border rounded-2xl p-4 space-y-4">
                    <div class="text-center rounded-xl border border-gray-100 bg-gray-50 p-3 hidden lg:block">
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Waktu Latihan') }}</p>
                        <div data-practice-timer class="text-2xl font-bold text-primary mt-1">00:00:00</div>
                    </div>

                    <div class="text-sm">
                        <h4 class="font-semibold text-gray-800 mb-3">{{ __('Question Status') }}</h4>
                        <div class="space-y-2 text-gray-500">
                            <p><span class="inline-flex items-center justify-center w-4 h-4 rounded bg-primary text-white mr-2 text-xs"> &nbsp;</span> {{ __('Soal aktif') }}</p>
                            <p><span class="inline-flex items-center justify-center w-4 h-4 rounded bg-emerald-100 text-emerald-700 mr-2 text-xs"> &nbsp;</span> {{ __('Sudah terjawab') }}</p>
                            <p><span class="inline-flex items-center justify-center w-4 h-4 rounded bg-gray-200 mr-2 text-xs"> &nbsp;</span> {{ __('Belum dijawab') }}</p>
                            <p class="flex items-center"><i class="ri-flag-fill text-red-500 mr-2 text-base"></i> {{ __('Soal ditandai') }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-gray-700 mb-3">{{ __('Navigasi Soal') }}</p>
                        <div class="grid grid-cols-5 gap-2 practice-nav-grid">
                            @foreach($navigation as $item)
                                <a href="{{ route('user.practice.play', ['number' => $item['number']]) }}"
                                    data-question-id="{{ $item['question_id'] }}"
                                    class="relative w-full h-10 flex items-center justify-center rounded-lg text-sm font-semibold
                                    {{ $item['number'] === $number ? 'bg-primary text-white' : ($item['answered'] ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-gray-100 text-gray-600') }}">
                                    {{ $item['number'] }}
                                    @if($item['flagged'] ?? false)
                                        <i class="flag-badge ri-flag-fill absolute -top-1 -right-1 text-xs text-red-500"></i>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="calculatorModal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6" role="dialog" aria-modal="true">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-sm text-gray-500">{{ __('Kalkulator') }}</p>
                <h3 class="text-lg font-semibold text-gray-800">{{ __('Latihan & Tryout') }}</h3>
            </div>
            <button type="button" id="closeCalculator" class="text-gray-500 hover:text-gray-800">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        <div class="mb-4">
            <input id="calculatorDisplay" type="text" readonly class="w-full text-right text-3xl font-semibold px-4 py-3 bg-gray-50 rounded-lg border border-gray-200" placeholder="0">
        </div>
        <div class="grid grid-cols-4 gap-3">
            <button data-calculator-key="7" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-gray-700 hover:bg-gray-200 transition">7</button>
            <button data-calculator-key="8" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-gray-700 hover:bg-gray-200 transition">8</button>
            <button data-calculator-key="9" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-gray-700 hover:bg-gray-200 transition">9</button>
            <button data-calculator-key="/" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-primary hover:bg-gray-200 transition">÷</button>
            <button data-calculator-key="4" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-gray-700 hover:bg-gray-200 transition">4</button>
            <button data-calculator-key="5" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-gray-700 hover:bg-gray-200 transition">5</button>
            <button data-calculator-key="6" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-gray-700 hover:bg-gray-200 transition">6</button>
            <button data-calculator-key="*" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-primary hover:bg-gray-200 transition">×</button>
            <button data-calculator-key="1" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-gray-700 hover:bg-gray-200 transition">1</button>
            <button data-calculator-key="2" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-gray-700 hover:bg-gray-200 transition">2</button>
            <button data-calculator-key="3" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-gray-700 hover:bg-gray-200 transition">3</button>
            <button data-calculator-key="-" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-primary hover:bg-gray-200 transition">−</button>
            <button data-calculator-key="0" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-gray-700 hover:bg-gray-200 transition">0</button>
            <button data-calculator-key="." class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-gray-700 hover:bg-gray-200 transition">.</button>
            <button id="calculatorClear" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-red-600 hover:bg-gray-200 transition">C</button>
            <button data-calculator-key="+" class="w-full py-3 rounded-lg bg-gray-100 text-lg font-semibold text-primary hover:bg-gray-200 transition">+</button>
            <button data-calculator-key="=" class="col-span-4 bg-primary text-white rounded-lg py-3 text-lg font-semibold">=</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const PRACTICE_FLAG_TOKEN = '{{ csrf_token() }}';
const PRACTICE_CURRENT_QUESTION_ID = {{ $question->id }};

document.addEventListener('DOMContentLoaded', () => {
    // Timer mulai dari 0 setiap masuk halaman
    const practiceTimerEls = document.querySelectorAll('[data-practice-timer]');
    let timerStartTime = Date.now();
    let timerInterval = null;

    if (practiceTimerEls.length > 0) {
        const renderPracticeTimer = () => {
            const elapsedSeconds = Math.max(0, Math.floor((Date.now() - timerStartTime) / 1000));
            const hours = Math.floor(elapsedSeconds / 3600);
            const minutes = Math.floor((elapsedSeconds % 3600) / 60);
            const seconds = elapsedSeconds % 60;
            const display = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            practiceTimerEls.forEach((el) => {
                el.textContent = display;
            });
        };

        renderPracticeTimer();
        timerInterval = setInterval(renderPracticeTimer, 1000);
    }

    // Fungsi untuk mencatat waktu keluar dari halaman
    const recordStudyDuration = () => {
        if (!timerStartTime) return;

        const elapsedSeconds = Math.max(0, Math.floor((Date.now() - timerStartTime) / 1000));

        // Kirim ke backend menggunakan sendBeacon untuk beforeunload
        const data = JSON.stringify({
            _token: PRACTICE_FLAG_TOKEN,
            duration_seconds: elapsedSeconds
        });

        if (navigator.sendBeacon) {
            navigator.sendBeacon('{{ route('user.practice.record-exit') }}', new Blob([data], { type: 'application/json' }));
        } else {
            // Fallback untuk browser yang tidak support sendBeacon
            fetch('{{ route('user.practice.record-exit') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': PRACTICE_FLAG_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: data,
                keepalive: true
            }).catch(() => {});
        }
    };

    // Catat waktu keluar saat user meninggalkan halaman
    window.addEventListener('beforeunload', recordStudyDuration);

    // Tangani klik link navigasi internal (Sebelumnya, Selanjutnya, Kembali)
    document.querySelectorAll('a[href*="practice"]').forEach(link => {
        link.addEventListener('click', (e) => {
            // Hentikan timer
            if (timerInterval) {
                clearInterval(timerInterval);
            }
            recordStudyDuration();
        });
    });

    const antiCopyRoot = document.querySelector('[data-anticopy="practice"]') || document.body;
    const protectedBlocks = antiCopyRoot.querySelectorAll('.practice-protected');
    const antiCopyHandler = (event) => {
        event.preventDefault();
    };

    ['copy', 'cut', 'paste', 'contextmenu', 'selectstart', 'dragstart'].forEach((eventName) => {
        protectedBlocks.forEach((block) => {
            block.addEventListener(eventName, antiCopyHandler, true);
        });
    });

    const isSelectionInsideProtected = () => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return false;
        }

        const anchorParent = selection.anchorNode?.parentElement;
        const focusParent = selection.focusNode?.parentElement;

        return !!(anchorParent?.closest('.practice-protected') || focusParent?.closest('.practice-protected'));
    };

    document.addEventListener('keydown', (event) => {
        if (!isSelectionInsideProtected()) {
            return;
        }

        if ((event.ctrlKey || event.metaKey) && ['c', 'x', 'v', 'a'].includes(event.key.toLowerCase())) {
            event.preventDefault();
        }
    }, true);

    const form = document.getElementById('practiceAnswerForm');
    if (!form) return;

    const questionType = form.dataset.questionType;
    const initialFeedback = @json($initialFeedback);
    const feedbackBox = document.getElementById('practiceFeedback');
    const statusBadge = document.getElementById('practiceFeedbackStatus');
    const correctWrapper = document.getElementById('practiceFeedbackCorrectWrapper');
    const correctContainer = document.getElementById('practiceFeedbackCorrect');
    const explanationWrapper = document.getElementById('practiceFeedbackExplanationWrapper');
    const explanationContainer = document.getElementById('practiceFeedbackExplanation');
    const debounce = (fn, delay = 600) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn.apply(null, args), delay);
        };
    };

    const updateNavigation = () => {
        const navLinks = document.querySelectorAll('.practice-nav-grid a');
        navLinks.forEach(link => {
            if (parseInt(link.textContent.trim(), 10) === {{ $number }}) {
                link.classList.remove('bg-gray-100', 'text-gray-600', 'bg-emerald-50', 'text-emerald-600', 'border', 'border-emerald-100');
                link.classList.add('bg-primary', 'text-white');
            }
        });
    };

    const handleSubmit = () => {
        const formData = new FormData(form);

        if (questionType === 'matching') {
            const data = {};
            let allFilled = true;
            form.querySelectorAll('.matching-select').forEach(select => {
                if (!select.value) {
                    allFilled = false;
                }
                data[select.dataset.left] = select.value;
            });
            if (!allFilled) {
                return;
            }
            formData.set('matching_answers', JSON.stringify(data));
        } else if (questionType === 'essay' || questionType === 'short_answer') {
            const text = (formData.get('answer_text') || '').trim();
            if (!text.length) {
                return;
            }
            formData.set('answer_text', text);
        } else if (questionType === 'audio') {
            const file = form.querySelector('input[type="file"]');
            if (!file || !file.files.length) return;
        } else if (questionType === 'multiple_choice' || questionType === 'true_false') {
            const selected = form.querySelector('input[type="radio"]:checked');
            if (!selected) return;
        }

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw data;
            }
            updateNavigation();
            renderFeedback(data.feedback);
            const progressLabel = document.getElementById('practice-progress-label');
            const nextUnlock = document.getElementById('practice-next-unlock');
            if (progressLabel && data.answered_count !== undefined && data.total_questions !== undefined) {
                progressLabel.textContent = `${data.answered_count} / ${data.total_questions}`;
            }
            if (nextUnlock && data.next_unlock_remaining !== undefined && data.tryout_count !== undefined) {
                if (data.tryout_count === 0) {
                    nextUnlock.textContent = @json(__('Tryout akan muncul setelah admin menambahkannya.'));
                } else if (data.next_unlock_remaining === 0 && data.unlocked_count >= data.tryout_count) {
                    nextUnlock.textContent = @json(__('Semua tryout sudah terbuka. Tetap lanjutkan latihan untuk mempertahankan progresmu.'));
                } else if (!data.threshold_per_tryout) {
                    nextUnlock.textContent = @json(__('Tryout akan terbuka otomatis begitu tersedia.'));
                } else {
                    nextUnlock.innerHTML = @json(__('Selesaikan <span class="font-semibold text-gray-800">:count</span> soal lagi untuk membuka tryout berikutnya.')).replace(':count', data.next_unlock_remaining);
                }
            }
        })
        .catch(error => {
            console.error(error);
        });
    };

    if (questionType === 'multiple_choice' || questionType === 'true_false') {
        form.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', handleSubmit);
        });
    } else if (questionType === 'matching') {
        form.querySelectorAll('.matching-select').forEach(select => {
            select.addEventListener('change', debounce(handleSubmit));
        });
    } else if (questionType === 'essay' || questionType === 'short_answer') {
        const textarea = form.querySelector('textarea[name="answer_text"]');
        if (textarea) {
            textarea.addEventListener('input', debounce(handleSubmit));
        }
    } else if (questionType === 'audio') {
        const fileInput = form.querySelector('input[type="file"]');
        if (fileInput) {
            fileInput.addEventListener('change', handleSubmit);
        }
    }

    const renderFeedback = (payload) => {
        if (!feedbackBox || !payload) {
            return;
        }

        feedbackBox.classList.remove('hidden');
        const status = payload.is_correct === true
            ? { text: @json(__('Jawaban benar')), classes: 'bg-emerald-100 text-emerald-700' }
            : (payload.is_correct === false
                ? { text: @json(__('Jawaban salah')), classes: 'bg-red-100 text-red-700' }
                : { text: @json(__('Jawaban tersimpan')), classes: 'bg-amber-100 text-amber-700' });

        if (statusBadge) {
            statusBadge.textContent = status.text;
            statusBadge.className = `inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${status.classes}`;
        }

        if (correctWrapper && correctContainer) {
            if (payload.correct_answer_html) {
                correctWrapper.classList.remove('hidden');
                correctContainer.innerHTML = payload.correct_answer_html;
            } else {
                correctWrapper.classList.add('hidden');
                correctContainer.innerHTML = '';
            }
        }

        if (explanationWrapper && explanationContainer) {
            if (payload.explanation_html) {
                explanationWrapper.classList.remove('hidden');
                explanationContainer.innerHTML = payload.explanation_html;
            } else {
                explanationWrapper.classList.add('hidden');
                explanationContainer.innerHTML = '';
            }
        }
    };

    if (initialFeedback) {
        renderFeedback(initialFeedback);
    }

    setupCalculator();
    setupPracticeFlagging();
});

function setupCalculator() {
    const modal = document.getElementById('calculatorModal');
    const display = document.getElementById('calculatorDisplay');
    const openers = document.querySelectorAll('.calculator-trigger');
    const closeBtn = document.getElementById('closeCalculator');
    const clearBtn = document.getElementById('calculatorClear');
    const buttons = document.querySelectorAll('[data-calculator-key]');

    if (!modal || !display) {
        return;
    }

    const openModal = () => {
        modal.classList.remove('hidden');
    };
    const closeModal = () => {
        modal.classList.add('hidden');
    };

    openers.forEach(btn => btn.addEventListener('click', openModal));
    closeBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    clearBtn?.addEventListener('click', () => {
        display.value = '';
    });

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.calculatorKey;
            if (key === '=') {
                try {
                    const result = display.value ? eval(display.value) : '';
                    display.value = result;
                } catch (error) {
                    display.value = @json(__('Error'));
                }
                return;
            }

            display.value += key;
        });
    });
}

function setupPracticeFlagging() {
    const flagButton = document.getElementById('practiceFlagButton');
    if (!flagButton) {
        return;
    }

    const flagIcon = flagButton.querySelector('i');
    const flagText = flagButton.querySelector('.flag-text');
    const navCurrent = document.querySelector(`.practice-nav-grid a[data-question-id="${PRACTICE_CURRENT_QUESTION_ID}"]`);
    let state = flagButton.dataset.flagged === 'true';

    const updateNavBadge = (flagged) => {
        if (!navCurrent) {
            return;
        }
        let badge = navCurrent.querySelector('.flag-badge');
        if (flagged) {
            if (!badge) {
                badge = document.createElement('i');
                badge.className = 'flag-badge ri-flag-fill absolute -top-1 -right-1 text-xs text-red-500';
                navCurrent.appendChild(badge);
            }
        } else if (badge) {
            badge.remove();
        }
    };

    const setState = (flagged) => {
        state = flagged;
        flagButton.dataset.flagged = flagged ? 'true' : 'false';
        flagIcon.className = flagged
            ? 'ri-flag-fill text-lg md:text-base md:mr-2'
            : 'ri-flag-line text-lg md:text-base md:mr-2';
        flagText.textContent = flagged ? @json(__('Batalkan Tandai')) : @json(__('Tandai Soal'));
        updateNavBadge(flagged);
    };

    setState(state);

    flagButton.addEventListener('click', () => {
        if (flagButton.disabled) {
            return;
        }
        flagButton.disabled = true;
        fetch('{{ route('user.practice.flag') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': PRACTICE_FLAG_TOKEN,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ question_id: PRACTICE_CURRENT_QUESTION_ID }),
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw data;
                }
                setState(!!data.flagged);
            })
            .catch(error => console.error(error))
            .finally(() => {
                flagButton.disabled = false;
            });
    });
}
</script>
@endpush
