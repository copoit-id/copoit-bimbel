@extends('admin.layout.admin')
@section('title', 'Generate Soal AI')
@section('content')
@php
    $previewQuestions = collect($preview['questions'] ?? []);
    $requestData = $preview['request'] ?? [];
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-2">
        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('admin.question-bank.index', ['import_for' => $importTarget]) }}" class="text-primary hover:underline">Bank Soal</a>
            @foreach ($breadcrumbs as $item)
            <i class="ri-arrow-right-s-line text-gray-400"></i>
            <a href="{{ route('admin.question-bank.show', ['questionBank' => $item->id, 'import_for' => $importTarget]) }}" class="text-primary hover:underline">{{ $item->name }}</a>
            @endforeach
            <i class="ri-arrow-right-s-line text-gray-400"></i>
            <span>Generate AI</span>
        </div>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-600">AI Question Generator</p>
                <h1 class="text-2xl font-bold text-gray-900">Generate Soal untuk {{ $bank->name }}</h1>
                <p class="mt-1 max-w-3xl text-sm text-gray-500">
                    Buat draft soal pilihan ganda, review hasilnya, lalu simpan ke Bank Soal jika sudah sesuai.
                </p>
            </div>
            <a href="{{ route('admin.question-bank.show', ['questionBank' => $bank->id, 'import_for' => $importTarget]) }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <i class="ri-arrow-left-line"></i>
                Kembali
            </a>
        </div>
    </div>

    @if ($tryoutDetail)
    <div class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-800">
        Konteks import untuk tryout <span class="font-semibold">{{ $tryoutDetail->tryout->name ?? '-' }}</span>
        - subtest <span class="font-semibold">{{ strtoupper($tryoutDetail->type_subtest ?? '-') }}</span>.
    </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[420px_minmax(0,1fr)]">
        <section class="rounded-2xl border border-border bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-gray-900">Form Generator</h2>
                <p class="text-sm text-gray-500">Generate bertahap 10-25 soal agar hasilnya mudah dicek.</p>
            </div>

            <form action="{{ route('admin.question-bank.questions.ai-generator.preview', $bank->id) }}" method="POST" class="space-y-4">
                @csrf
                @if($importTarget)
                <input type="hidden" name="import_for" value="{{ $importTarget }}">
                @endif

                <div>
                    <label for="model" class="mb-1 block text-sm font-medium text-gray-700">Model AI</label>
                    <select id="model" name="model" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                        @foreach($models as $modelId => $modelLabel)
                        <option value="{{ $modelId }}" @selected(old('model', $requestData['model'] ?? $defaultModel) === $modelId)>
                            {{ $modelLabel }}
                        </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Untuk saat ini opsi model dibatasi satu dulu supaya output stabil.</p>
                </div>

                <div>
                    <label for="subject" class="mb-1 block text-sm font-medium text-gray-700">Mata Pelajaran / Kategori</label>
                    <input type="text" id="subject" name="subject" value="{{ old('subject', $requestData['subject'] ?? '') }}" required
                        placeholder="Contoh: TPA, Matematika, Bahasa Indonesia"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>

                <div>
                    <label for="topic" class="mb-1 block text-sm font-medium text-gray-700">Topik Soal</label>
                    <input type="text" id="topic" name="topic" value="{{ old('topic', $requestData['topic'] ?? '') }}" required
                        placeholder="Contoh: Penalaran kuantitatif persentase"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="question_count" class="mb-1 block text-sm font-medium text-gray-700">Jumlah Soal</label>
                        <input type="number" id="question_count" name="question_count" value="{{ old('question_count', $requestData['question_count'] ?? 10) }}" min="1" max="25" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label for="option_count" class="mb-1 block text-sm font-medium text-gray-700">Jumlah Opsi</label>
                        <select id="option_count" name="option_count" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                            @foreach([4, 5, 3, 2] as $count)
                            <option value="{{ $count }}" @selected((int) old('option_count', $requestData['option_count'] ?? 5) === $count)>{{ $count }} opsi</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="difficulty" class="mb-1 block text-sm font-medium text-gray-700">Level</label>
                        <select id="difficulty" name="difficulty" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                            @foreach(['sedang' => 'Sedang', 'mudah' => 'Mudah', 'sulit' => 'Sulit', 'campuran' => 'Campuran'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('difficulty', $requestData['difficulty'] ?? 'sedang') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="explanation_style" class="mb-1 block text-sm font-medium text-gray-700">Pembahasan</label>
                        <select id="explanation_style" name="explanation_style" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                            @foreach(['normal' => 'Normal', 'singkat' => 'Singkat', 'detail' => 'Detail'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('explanation_style', $requestData['explanation_style'] ?? 'normal') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="instruction" class="mb-1 block text-sm font-medium text-gray-700">Instruksi Tambahan</label>
                    <textarea id="instruction" name="instruction" rows="5"
                        placeholder="Contoh: buat konteks soal UTBK, jangan terlalu panjang, gunakan angka yang mudah dihitung manual."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ old('instruction', $requestData['instruction'] ?? '') }}</textarea>
                </div>

                <button type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-violet-600 px-4 py-3 text-sm font-semibold text-white hover:bg-violet-700">
                    <i class="ri-sparkling-2-line"></i>
                    Generate Preview
                </button>
            </form>
        </section>

        <section class="min-w-0 rounded-2xl border border-border bg-white p-6 shadow-sm">
            <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Preview Soal</h2>
                    <p class="text-sm text-gray-500">
                        Edit teks jika perlu. Data belum masuk Bank Soal sebelum tombol simpan ditekan.
                    </p>
                </div>
                @if($previewQuestions->isNotEmpty())
                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">
                    <i class="ri-check-line"></i>
                    {{ $previewQuestions->count() }} soal siap direview
                </span>
                @endif
            </div>

            @if($previewQuestions->isEmpty())
            <div class="flex min-h-[420px] flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-6 text-center">
                <div class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-100 text-violet-700">
                    <i class="ri-sparkling-2-line text-3xl"></i>
                </div>
                <h3 class="text-base font-semibold text-gray-900">Belum ada preview</h3>
                <p class="mt-1 max-w-md text-sm text-gray-500">
                    Isi form di kiri lalu generate. Hasil akan muncul di sini untuk dicek sebelum disimpan.
                </p>
            </div>
            @else
            <form id="aiPreviewStoreForm" action="{{ route('admin.question-bank.questions.ai-generator.store', $bank->id) }}" method="POST" class="space-y-4">
                @csrf
                @if($importTarget)
                <input type="hidden" name="import_for" value="{{ $importTarget }}">
                @endif
                <input type="hidden" name="model" value="{{ $preview['model'] ?? $defaultModel }}">
                <input type="hidden" id="questionsJson" name="questions_json">

                <div id="aiPreviewList" class="space-y-4">
                    @foreach($previewQuestions as $index => $question)
                    @php
                        $correctOption = $question['correct_option'] ?? 'A';
                    @endphp
                    <article class="ai-preview-question rounded-xl border border-gray-200 bg-gray-50 p-4" data-index="{{ $index }}">
                        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="font-semibold text-gray-900">Soal {{ $index + 1 }}</h3>
                            <button type="button" data-remove-ai-question
                                class="inline-flex w-fit items-center gap-1 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">
                                <i class="ri-delete-bin-line"></i>
                                Hapus
                            </button>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Teks Soal</label>
                            <textarea data-question-text rows="4"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $question['question_text'] ?? '' }}</textarea>
                        </div>

                        <div class="mt-3 space-y-2">
                            @foreach(($question['options'] ?? []) as $option)
                            <div class="grid gap-2 rounded-lg border border-gray-200 bg-white p-3 md:grid-cols-[44px_minmax(0,1fr)] md:items-start">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 text-sm font-bold text-gray-700">
                                    {{ $option['label'] ?? '' }}
                                </div>
                                <textarea data-option-text data-option-label="{{ $option['label'] ?? '' }}" rows="2"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $option['text'] ?? '' }}</textarea>
                            </div>
                            @endforeach
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-[160px_minmax(0,1fr)]">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-600">Jawaban Benar</label>
                                <select data-correct-option class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                    @foreach(($question['options'] ?? []) as $option)
                                    <option value="{{ $option['label'] ?? '' }}" @selected(($option['label'] ?? '') === $correctOption)>{{ $option['label'] ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-600">Pembahasan</label>
                                <textarea data-explanation rows="3"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $question['explanation'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>

                <div class="sticky bottom-0 -mx-6 mt-6 border-t border-gray-100 bg-white/95 px-6 py-4 backdrop-blur">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-gray-500">
                            Soal valid yang disimpan akan masuk sebagai pilihan ganda dengan skor benar 1 dan salah 0.
                        </p>
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">
                            <i class="ri-save-3-line"></i>
                            Simpan ke Bank Soal
                        </button>
                    </div>
                </div>
            </form>
            @endif
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('aiPreviewStoreForm');
        const questionsJson = document.getElementById('questionsJson');
        const previewList = document.getElementById('aiPreviewList');

        previewList?.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-remove-ai-question]');
            if (!removeButton) {
                return;
            }

            removeButton.closest('.ai-preview-question')?.remove();
            document.querySelectorAll('.ai-preview-question').forEach((item, index) => {
                item.querySelector('h3').textContent = `Soal ${index + 1}`;
            });
        });

        form?.addEventListener('submit', (event) => {
            const questions = Array.from(document.querySelectorAll('.ai-preview-question')).map((item) => ({
                question_text: item.querySelector('[data-question-text]')?.value || '',
                correct_option: item.querySelector('[data-correct-option]')?.value || '',
                explanation: item.querySelector('[data-explanation]')?.value || '',
                options: Array.from(item.querySelectorAll('[data-option-text]')).map((optionInput) => ({
                    label: optionInput.dataset.optionLabel || '',
                    text: optionInput.value || '',
                })),
            }));

            if (questions.length === 0) {
                event.preventDefault();
                window.alert('Minimal simpan 1 soal dari preview.');
                return;
            }

            questionsJson.value = JSON.stringify(questions);
        });
    });
</script>
@endpush
