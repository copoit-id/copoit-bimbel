@extends('user.layout.tryout')

@section('title', 'Latihan Soal - Soal ' . $number)

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
@endphp
<div class="min-h-screen bg-gray-50 pt-8 pb-16">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-col lg:flex-row gap-6">
            <div class="lg:flex-1">
                <div class="bg-white border border-border rounded-2xl p-6">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-4 mb-6">
                        <div>
                            <p class="text-sm text-gray-500">Soal Latihan</p>
                            <h2 class="text-2xl font-semibold text-gray-900">Soal {{ $number }} dari {{ $totalQuestions }}</h2>
                        </div>
                        <a href="{{ route('user.practice.index') }}" class="inline-flex items-center text-sm text-primary hover:underline">
                            <i class="ri-arrow-left-line mr-1"></i> Kembali ke Latihan
                        </a>
                    </div>

                    <div class="text-gray-700 leading-relaxed">
                        {!! $question->question_text !!}
                    </div>

                    @if($question->sound)
                        <div class="mt-4">
                            <audio controls class="w-full">
                                <source src="{{ Storage::url($question->sound) }}" type="audio/mpeg">
                                Browser tidak mendukung audio.
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
                                <label class="flex items-start gap-3 p-4 border rounded-xl cursor-pointer transition-all duration-200 hover:border-primary/40 hover:bg-primary/5">
                                    <input type="radio" name="option_id" value="{{ $option->id }}" class="mt-1 text-primary focus:ring-primary"
                                        @checked($currentAnswer && $currentAnswer->question_bank_question_option_id === $option->id)>
                                    <span class="flex-1 text-gray-700">{!! $option->option_text !!}</span>
                                </label>
                            @endforeach
                        @elseif($questionType === 'matching')
                            <input type="hidden" name="matching_answers">
                            <p class="text-sm text-gray-500">Cocokkan kolom kiri dengan jawaban yang tepat.</p>
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
                                            <option value="">Pilih jawaban</option>
                                            @foreach($matchingRightOptions as $rightOption)
                                                <option value="{{ $rightOption }}" @selected($selectedRight === $rightOption)>{{ $rightOption }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        @elseif(in_array($questionType, ['essay', 'short_answer']))
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Jawabanmu</label>
                                <textarea name="answer_text" rows="5"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    placeholder="Tulis jawaban di sini...">{{ $currentAnswer->answer_text ?? '' }}</textarea>
                                @if(!empty($shortMeta['expected_answers'] ?? []))
                                    <p class="text-xs text-gray-500 mt-2">Jawaban otomatis benar jika sesuai salah satu kunci.</p>
                                @endif
                            </div>
                        @elseif($questionType === 'audio')
                            <div class="space-y-4">
                                @if($currentAnswer && $currentAnswer->answer_file_path)
                                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                                        <p class="text-sm text-gray-600 mb-2">Jawaban tersimpan:</p>
                                        <audio controls class="w-full">
                                            <source src="{{ Storage::url($currentAnswer->answer_file_path) }}" type="audio/mpeg">
                                        </audio>
                                    </div>
                                @endif
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Unggah jawaban audio (MP3/WAV/M4A)</label>
                                    <input type="file" name="answer_audio" accept="audio/*"
                                        class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-primary file:text-white hover:file:bg-primary/90">
                                    <p class="text-xs text-gray-500 mt-2">
                                        {{ $audioMeta['instructions'] ?? 'Upload rekaman jawabanmu.' }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </form>

                    <div class="mt-6 flex flex-wrap gap-3 border-t border-gray-100 pt-4">
                        <a href="{{ route('user.practice.index') }}"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                            <i class="ri-layout-grid-line mr-2"></i> Daftar Soal
                        </a>
                        @if($number > 1)
                            <a href="{{ route('user.practice.play', ['number' => $number - 1]) }}"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                <i class="ri-arrow-left-line mr-2"></i> Sebelumnya
                            </a>
                        @endif
                        @if($number < $totalQuestions)
                            <a href="{{ route('user.practice.play', ['number' => $number + 1]) }}"
                                class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                                Selanjutnya <i class="ri-arrow-right-line ml-2"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="lg:w-80">
                <div class="bg-white border border-border rounded-2xl p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Navigasi Soal</p>
                    <div class="grid grid-cols-5 gap-2 practice-nav-grid">
                        @foreach($navigation as $item)
                            <a href="{{ route('user.practice.play', ['number' => $item['number']]) }}"
                                class="w-full h-10 flex items-center justify-center rounded-lg text-sm font-semibold
                                {{ $item['number'] === $number ? 'bg-primary text-white' : ($item['answered'] ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-gray-100 text-gray-600') }}">
                                {{ $item['number'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-500">
                    <p><span class="inline-flex items-center justify-center w-4 h-4 rounded bg-primary text-white mr-2 text-xs"> &nbsp;</span> Soal aktif</p>
                    <p class="mt-2"><span class="inline-flex items-center justify-center w-4 h-4 rounded bg-emerald-100 text-emerald-700 mr-2 text-xs"> &nbsp;</span> Sudah terjawab</p>
                    <p class="mt-2"><span class="inline-flex items-center justify-center w-4 h-4 rounded bg-gray-200 mr-2 text-xs"> &nbsp;</span> Belum dijawab</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('practiceAnswerForm');
    if (!form) return;

    const questionType = form.dataset.questionType;
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
            const progressLabel = document.getElementById('practice-progress-label');
            const nextUnlock = document.getElementById('practice-next-unlock');
            if (progressLabel && data.answered_count !== undefined && data.total_questions !== undefined) {
                progressLabel.textContent = `${data.answered_count} / ${data.total_questions}`;
            }
            if (nextUnlock && data.next_unlock_remaining !== undefined && data.package_count !== undefined) {
                if (data.package_count === 0) {
                    nextUnlock.textContent = 'Paket tryout akan muncul setelah admin menambahkannya.';
                } else if (data.next_unlock_remaining === 0 && data.unlocked_count >= data.package_count) {
                    nextUnlock.textContent = 'Semua paket tryout sudah terbuka. Tetap lanjutkan latihan untuk mempertahankan progresmu.';
                } else if (!data.threshold_per_package) {
                    nextUnlock.textContent = 'Paket akan terbuka otomatis begitu tersedia.';
                } else {
                    nextUnlock.innerHTML = `Selesaikan <span class="font-semibold text-gray-800">${data.next_unlock_remaining}</span> soal lagi untuk membuka paket berikutnya.`;
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
});
</script>
@endpush
