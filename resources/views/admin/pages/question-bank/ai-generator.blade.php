@extends('admin.layout.admin')
@section('title', 'Generate Soal AI')
@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .ai-preview-question [data-option-row] .cke {
        align-self: stretch;
        width: 100% !important;
    }
</style>
@endpush
@section('content')
@php
    $previewQuestions = collect($preview['questions'] ?? []);
    $requestData = $preview['request'] ?? [];
    $remainingQuestionEstimate = data_get($quota, 'remaining_question_estimate.label', '0');
    $previewRemainingQuestionEstimate = data_get($preview, 'quota.remaining_question_estimate.label', '0');
    $initialReferenceSource = old('reference_source', $requestData['reference_source'] ?? 'question_bank');
    $initialUseReference = (bool) old('use_reference', $requestData['use_reference'] ?? !empty($requestData['reference_source']));
    $initialReferenceBankId = (int) old('reference_bank_id', $requestData['reference_bank_id'] ?? 0);
    $initialReferenceTryoutId = (int) old('reference_tryout_id', $requestData['reference_tryout_id'] ?? 0);
    $initialReferenceTryoutDetailId = (int) old('reference_tryout_detail_id', $requestData['reference_tryout_detail_id'] ?? 0);
    $isTryoutGenerator = ($generatorMode ?? 'bank') === 'tryout';
    $generatorTargetName = $isTryoutGenerator
        ? ($tryoutDetail->tryout->name ?? 'Tryout') . ' · ' . $tryoutDetail->display_name
        : $bank->name;
    $previewRoute = $isTryoutGenerator
        ? route('admin.question.ai-generator.preview', $tryoutDetail)
        : route('admin.question-bank.questions.ai-generator.preview', $bank->id);
    $resetRoute = $isTryoutGenerator
        ? route('admin.question.ai-generator.reset', $tryoutDetail)
        : route('admin.question-bank.questions.ai-generator.reset', $bank->id);
    $storeRoute = $isTryoutGenerator
        ? route('admin.question.ai-generator.store', $tryoutDetail)
        : route('admin.question-bank.questions.ai-generator.store', $bank->id);
    $backRoute = $isTryoutGenerator
        ? route('admin.question.index', $tryoutDetail)
        : route('admin.question-bank.show', ['questionBank' => $bank->id, 'import_for' => $importTarget]);
    $storageLabel = $isTryoutGenerator ? 'subtest ini' : 'Bank Soal';
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-2">
        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
            @if($isTryoutGenerator)
            <a href="{{ route('admin.tryout.index') }}" class="text-primary hover:underline">Manajemen Tryout</a>
            <i class="ri-arrow-right-s-line text-gray-400"></i>
            <a href="{{ route('admin.question.index', $tryoutDetail) }}" class="text-primary hover:underline">Soal</a>
            <i class="ri-arrow-right-s-line text-gray-400"></i>
            <span>Generate AI</span>
            @else
            <a href="{{ route('admin.question-bank.index', ['import_for' => $importTarget]) }}" class="text-primary hover:underline">Bank Soal</a>
            @foreach ($breadcrumbs as $item)
            <i class="ri-arrow-right-s-line text-gray-400"></i>
            <a href="{{ route('admin.question-bank.show', ['questionBank' => $item->id, 'import_for' => $importTarget]) }}" class="text-primary hover:underline">{{ $item->name }}</a>
            @endforeach
            <i class="ri-arrow-right-s-line text-gray-400"></i>
            <span>Generate AI</span>
            @endif
        </div>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-primary">AI Question Generator</p>
                <h1 class="text-2xl font-bold text-gray-900">Generate Soal untuk {{ $generatorTargetName }}</h1>
                <p class="mt-1 max-w-3xl text-sm text-gray-500">
                    Buat draft soal pilihan ganda, review hasilnya, lalu simpan ke {{ $storageLabel }} jika sudah sesuai.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.question-generator.quota.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90"><i class="ri-coin-line"></i> Kuota AI</a>
                <a href="{{ $backRoute }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="ri-arrow-left-line"></i>
                    Kembali
                </a>
            </div>
        </div>
    </div>

    @if ($tryoutDetail)
    <div class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-800">
        @if($isTryoutGenerator)
        Soal yang disimpan akan langsung masuk ke tryout <span class="font-semibold">{{ $tryoutDetail->tryout->name ?? '-' }}</span>
        - subtest <span class="font-semibold">{{ $tryoutDetail->display_name }}</span>.
        @else
        Konteks import untuk tryout <span class="font-semibold">{{ $tryoutDetail->tryout->name ?? '-' }}</span>
        - subtest <span class="font-semibold">{{ $tryoutDetail->display_name }}</span>.
        @endif
    </div>
    @endif

    @if ($gatewayError)
    <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900" role="alert">
        <i class="ri-alert-line mt-0.5 text-lg"></i>
        <div>
            <p class="font-semibold">Generator Soal AI belum siap digunakan</p>
            <p class="mt-1 text-amber-800">{{ $gatewayError }}</p>
        </div>
    </div>
    @endif

    <div class="rounded-xl border {{ $quota ? 'border-primary/20 bg-primary/5' : 'border-amber-200 bg-amber-50' }} px-5 py-4 text-sm">
        @if($quota)
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-semibold text-gray-900">{{ $quota['plan_name'] ?? 'Paket AI Generator Soal' }}</p><p class="mt-0.5 text-gray-600">Kapasitas berkurang saat membuat preview, bukan saat menyimpan soal.</p></div><p class="text-lg font-bold text-primary">{{ $remainingQuestionEstimate }} soal tersisa</p></div>
        @else
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><p class="text-amber-800">Belum ada kuota AI Generator Soal aktif untuk akun ini.</p><a href="{{ route('admin.question-generator.quota.index') }}" class="font-semibold text-primary hover:underline">Lihat paket</a></div>
        @endif
    </div>

    <div class="space-y-6">
        <section class="rounded-2xl border border-border bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-gray-900">Form Generator</h2>
                <p class="text-sm text-gray-500">Generate bertahap 10-25 soal agar hasilnya mudah dicek.</p>
            </div>

            <form id="aiPreviewGenerateForm" action="{{ $previewRoute }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <fieldset class="space-y-4" @disabled($gatewayError)>
                @if($importTarget)
                <input type="hidden" name="import_for" value="{{ $importTarget }}">
                @endif

                <div class="grid grid-cols-1 gap-4">
                <div>
                    <label for="subject" class="mb-1 block text-sm font-medium text-gray-700">Mata Pelajaran / Kategori</label>
                    <input type="text" id="subject" name="subject" value="{{ old('subject', $requestData['subject'] ?? '') }}" required
                        placeholder="Contoh: TPA, Matematika, Bahasa Indonesia"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>

                <div class="lg:col-span-2">
                    <label for="topic" class="mb-1 block text-sm font-medium text-gray-700">Topik Soal</label>
                    <input type="text" id="topic" name="topic" value="{{ old('topic', $requestData['topic'] ?? '') }}" required
                        placeholder="Contoh: Penalaran kuantitatif persentase"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>

                <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-gray-50 p-4" x-data="{ enabled: @js($initialUseReference), source: @js($initialReferenceSource), tryoutId: @js((string) $initialReferenceTryoutId) }">
                    <input type="hidden" name="use_reference" value="0">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="use_reference" value="1" x-model="enabled" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                        <span><span class="block font-semibold text-gray-900">Gunakan referensi gaya soal</span><span class="mt-1 block text-xs leading-5 text-gray-500">AI meniru tingkat kedalaman dan pola soal tanpa menyalin soal secara identik.</span></span>
                    </label>

                    <div x-show="enabled" x-cloak class="mt-4 space-y-3 border-t border-gray-200 pt-4">
                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label for="reference_source" class="mb-1 block text-sm font-medium text-gray-700">Sumber Referensi</label>
                                <select id="reference_source" name="reference_source" x-model="source" :disabled="!enabled" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm">
                                    <option value="question_bank">Bank soal</option>
                                    <option value="tryout">Tryout</option>
                                    <option value="pdf">Upload PDF</option>
                                </select>
                            </div>
                            <div x-show="source === 'question_bank'">
                                <label for="reference_bank_id" class="mb-1 block text-sm font-medium text-gray-700">Pilih Bank Soal</label>
                                <select id="reference_bank_id" name="reference_bank_id" :disabled="!enabled || source !== 'question_bank'" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm">
                                    <option value="">Pilih bank soal</option>
                                    @foreach($referenceBanks as $referenceBank)
                                        <option value="{{ $referenceBank->id }}" @selected($initialReferenceBankId === $referenceBank->id)>{{ $referenceBank->name }} · {{ $referenceBank->questions_count }} soal</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div x-show="source === 'tryout'" x-cloak class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label for="reference_tryout_id" class="mb-1 block text-sm font-medium text-gray-700">Pilih Tryout</label>
                                <select id="reference_tryout_id" name="reference_tryout_id" x-model="tryoutId" @change="$el.form.querySelector('#reference_tryout_detail_id').value = ''" :disabled="!enabled || source !== 'tryout'" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm">
                                    <option value="">Pilih tryout</option>
                                    @foreach($referenceTryouts as $referenceTryout)
                                        <option value="{{ $referenceTryout->tryout_id }}">{{ $referenceTryout->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="reference_tryout_detail_id" class="mb-1 block text-sm font-medium text-gray-700">Pilih Subtest</label>
                                <select id="reference_tryout_detail_id" name="reference_tryout_detail_id" :disabled="!enabled || source !== 'tryout' || !tryoutId" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm">
                                    <option value="">Pilih subtest</option>
                                    @foreach($referenceTryouts as $referenceTryout)
                                        @foreach($referenceTryout->tryoutDetails as $referenceTryoutDetail)
                                            <option value="{{ $referenceTryoutDetail->tryout_detail_id }}" x-show="tryoutId === '{{ $referenceTryout->tryout_id }}'" @selected($initialReferenceTryoutDetailId === $referenceTryoutDetail->tryout_detail_id)>{{ $referenceTryoutDetail->display_name }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div x-show="source === 'pdf'" x-cloak>
                            <label for="reference_pdf" class="mb-1 block text-sm font-medium text-gray-700">File PDF Referensi</label>
                            <input id="reference_pdf" name="reference_pdf" type="file" accept="application/pdf,.pdf" :disabled="!enabled || source !== 'pdf'" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                            <p class="mt-1 text-xs text-gray-500">Maksimal 10 MB. PDF harus memiliki teks yang dapat diseleksi; file scan gambar belum didukung.</p>
                            @error('reference_pdf')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <label for="reference_note" class="block text-sm font-medium text-gray-700">Arahan terhadap Referensi <span class="font-normal text-gray-400">(opsional)</span></label>
                        <textarea id="reference_note" name="reference_note" rows="3" :disabled="!enabled" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm" placeholder="Contoh: buat tingkat kesulitan setara, konteks berbeda, dan jangan mengulang angka/kalimat yang sama.">{{ old('reference_note', $requestData['reference_note'] ?? '') }}</textarea>
                    </div>
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

                <div class="lg:col-span-2">
                    <label for="instruction" class="mb-1 block text-sm font-medium text-gray-700">Instruksi Tambahan</label>
                    <textarea id="instruction" name="instruction" rows="5"
                        placeholder="Contoh: buat konteks soal UTBK, jangan terlalu panjang, gunakan angka yang mudah dihitung manual."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ old('instruction', $requestData['instruction'] ?? '') }}</textarea>
                </div>
                </div>

                <button type="submit" data-loading-button
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60">
                    <i data-loading-icon class="ri-sparkling-2-line"></i>
                    <span data-loading-label>Generate Preview dengan AI</span>
                </button>
                </fieldset>
            </form>
        </section>

        <section id="aiPreviewPanel" class="relative min-w-0 rounded-2xl border border-border bg-white shadow-sm" aria-busy="false">
            <div id="aiPreviewLoading" class="absolute inset-0 z-20 hidden items-center justify-center rounded-2xl bg-white/90 px-6 text-center backdrop-blur-sm" role="status" aria-live="polite">
                <div class="max-w-sm rounded-2xl border border-primary/15 bg-white px-6 py-5 shadow-xl">
                    <i class="ri-loader-4-line inline-block animate-spin text-3xl text-primary"></i>
                    <p class="mt-3 font-semibold text-gray-900">Sedang membuat preview soal</p>
                    <p class="mt-1 text-sm text-gray-500">AI sedang menyusun soal, opsi jawaban, dan pembahasan.</p>
                </div>
            </div>
            <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="px-6 pt-6">
                    <h2 class="text-lg font-semibold text-gray-900">Preview Soal</h2>
                    <p class="text-sm text-gray-500">
                        Edit teks jika perlu. Data belum masuk {{ $storageLabel }} sebelum tombol simpan ditekan.
                    </p>
                </div>
                @if($previewQuestions->isNotEmpty())
                <div class="px-6 pt-6 lg:px-6 flex flex-wrap items-center gap-3">
                    <span class="inline-flex w-fit items-center gap-2 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                        <i class="ri-check-line"></i>
                        {{ $previewQuestions->count() }} soal siap direview
                    </span>
                    <form action="{{ $resetRoute }}" method="POST" class="inline" data-ai-preview-reset>
                        @csrf
                        @if($importTarget)
                        <input type="hidden" name="import_for" value="{{ $importTarget }}">
                        @endif
                        <button type="submit" data-loading-button class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60">
                            <i data-loading-icon class="ri-refresh-line"></i>
                            <span data-loading-label>Reset Preview</span>
                        </button>
                    </form>
                </div>
                @endif
            </div>

            @if($previewQuestions->isEmpty())
            <div class="mx-6 mb-6 flex min-h-[420px] flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-6 text-center">
                <div class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <i class="ri-sparkling-2-line text-3xl"></i>
                </div>
                <h3 class="text-base font-semibold text-gray-900">Belum ada preview</h3>
                <p class="mt-1 max-w-md text-sm text-gray-500">
                    Isi form di kiri lalu generate. Hasil akan muncul di sini untuk dicek sebelum disimpan.
                </p>
            </div>
            @else
            <form id="aiPreviewStoreForm" action="{{ $storeRoute }}" method="POST" class="space-y-4">
                @csrf
                @if($importTarget)
                <input type="hidden" name="import_for" value="{{ $importTarget }}">
                @endif
                <input type="hidden" id="questionsJson" name="questions_json">

                <div class="border-y border-gray-100 bg-gray-50 px-6 py-4">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Topik</p>
                            <p class="mt-1 truncate text-sm font-semibold text-gray-900">{{ $requestData['topic'] ?? '-' }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Level</p>
                            <p class="mt-1 text-sm font-semibold capitalize text-gray-900">{{ $requestData['difficulty'] ?? '-' }}</p>
                        </div>
                        <div class="rounded-xl border border-primary/20 bg-primary/5 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-primary">Kapasitas AI</p>
                            <p class="mt-1 text-sm font-semibold text-primary">Preview sudah dibuat</p>
                            <p class="mt-0.5 text-xs text-gray-500">Sisa sekitar {{ $previewRemainingQuestionEstimate }} soal</p>
                        </div>
                    </div>
                </div>

                <div id="aiPreviewList" class="space-y-5 bg-gray-50 px-6 py-6">
                    @foreach($previewQuestions as $index => $question)
                    @php
                        $correctOption = $question['correct_option'] ?? 'A';
                        $questionScore = max(0, (float) ($question['question_score'] ?? 1));
                    @endphp
                    <article class="ai-preview-question overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" data-index="{{ $index }}">
                        <div class="flex flex-col gap-3 border-b border-gray-100 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div data-question-number class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-sm font-bold text-primary">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <h3 data-question-title class="font-semibold text-gray-900">Soal {{ $index + 1 }}</h3>
                                </div>
                            </div>
                            <button type="button" data-remove-ai-question
                                class="inline-flex w-fit items-center gap-1 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">
                                <i class="ri-delete-bin-line"></i>
                                Hapus
                            </button>
                        </div>

                        <div class="space-y-5 p-5">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <label class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <i class="ri-question-line"></i>
                                    Teks Soal
                                </label>
                                <textarea data-question-text rows="5"
                                    class="ckeditor w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm leading-6 text-gray-900 focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $question['question_text'] ?? '' }}</textarea>
                            </div>

                            <div>
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <label class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        <i class="ri-list-check-3"></i>
                                        Pilihan Jawaban
                                    </label>
                                </div>
                                <div class="grid grid-cols-1 gap-3">
                                    @foreach(($question['options'] ?? []) as $option)
                                    @php
                                        $optionLabel = $option['label'] ?? '';
                                        $isCorrect = $optionLabel === $correctOption;
                                        $optionScore = $option['score'] ?? ($isCorrect ? $questionScore : 0);
                                    @endphp
                                    <div data-option-row data-option-label="{{ $optionLabel }}"
                                        class="flex flex-col gap-3 rounded-xl border {{ $isCorrect ? 'border-green-300 bg-green-50' : 'border-gray-200 bg-white' }} p-3">
                                        <div class="flex items-center justify-between gap-3">
                                            <div data-option-label-badge class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $isCorrect ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700' }} text-sm font-bold">
                                                {{ $optionLabel }}
                                            </div>
                                            <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold text-green-700">
                                                <input type="radio" name="correct_option_{{ $index }}" value="{{ $optionLabel }}" data-correct-option-control @checked($isCorrect)
                                                    class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500">
                                                Jawaban benar
                                            </label>
                                        </div>
                                        <textarea data-option-text data-option-label="{{ $optionLabel }}" rows="3"
                                            class="ckeditor-option w-full min-w-0 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $option['text'] ?? '' }}</textarea>
                                        <div class="flex w-full items-center justify-end gap-2">
                                            <span class="text-xs font-semibold text-gray-500">Skor:</span>
                                            <input type="number" data-option-score data-option-label="{{ $optionLabel }}" min="0" max="999" step="0.1"
                                                value="{{ rtrim(rtrim(number_format($optionScore, 2, '.', ''), '0'), '.') }}"
                                                class="w-20 rounded-lg border border-gray-300 bg-white px-2 py-1 text-sm font-semibold text-gray-900 focus:border-primary focus:ring-2 focus:ring-primary/20">
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                    <label class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-700">
                                        <i class="ri-lightbulb-line"></i>
                                        Pembahasan
                                    </label>
                                    <textarea data-explanation rows="4"
                                        class="ckeditor w-full rounded-lg border border-amber-200 bg-white px-4 py-3 text-sm leading-6 text-gray-900 focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $question['explanation'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>

                <div class="sticky bottom-0 z-10 mt-6 w-full border-t border-gray-100 bg-white/95 px-6 py-4 backdrop-blur">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-gray-500">
                            Soal valid yang disimpan akan masuk sebagai pilihan ganda dengan skor sesuai preview.
                        </p>
                        <button type="submit" data-loading-button
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60">
                            <i data-loading-icon class="ri-save-3-line"></i>
                            <span data-loading-label>Simpan ke {{ $storageLabel }}</span>
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
        const generateForm = document.getElementById('aiPreviewGenerateForm');
        const form = document.getElementById('aiPreviewStoreForm');
        const questionsJson = document.getElementById('questionsJson');
        const previewList = document.getElementById('aiPreviewList');
        const previewPanel = document.getElementById('aiPreviewPanel');
        const previewLoading = document.getElementById('aiPreviewLoading');

        const normalizeRichEditorValue = (value) => {
            const html = String(value ?? '').trim();
            return ['<p><br></p>', '<p></p>', '<br>'].includes(html) ? '' : html;
        };

        const getRichEditorValue = (element) => {
            if (!element) {
                return '';
            }

            const editor = window.CKEDITOR?.instances?.[element.id];
            if (editor) {
                return normalizeRichEditorValue(editor.getData());
            }

            return normalizeRichEditorValue(element.value);
        };

        const setButtonLoading = (button, label) => {
            if (!button || button.dataset.loading === 'true') {
                return false;
            }

            button.dataset.loading = 'true';
            button.disabled = true;
            button.querySelector('[data-loading-icon]')?.classList.replace('ri-sparkling-2-line', 'ri-loader-4-line');
            button.querySelector('[data-loading-icon]')?.classList.replace('ri-save-3-line', 'ri-loader-4-line');
            button.querySelector('[data-loading-icon]')?.classList.replace('ri-refresh-line', 'ri-loader-4-line');
            button.querySelector('[data-loading-icon]')?.classList.add('animate-spin');

            const labelElement = button.querySelector('[data-loading-label]');
            if (labelElement) {
                labelElement.textContent = label;
            }

            return true;
        };

        const showPreviewLoading = () => {
            previewPanel?.setAttribute('aria-busy', 'true');
            previewLoading?.classList.remove('hidden');
            previewLoading?.classList.add('flex');
        };

        generateForm?.addEventListener('submit', () => {
            const button = generateForm.querySelector('[data-loading-button]');
            if (setButtonLoading(button, 'Membuat preview soal...')) {
                showPreviewLoading();
            }
        });

        document.querySelector('[data-ai-preview-reset]')?.addEventListener('submit', (event) => {
            const button = event.currentTarget.querySelector('[data-loading-button]');
            if (setButtonLoading(button, 'Mereset preview...')) {
                showPreviewLoading();
            }
        });

        const syncQuestionDisplay = (question, index) => {
            const title = question.querySelector('[data-question-title]');
            if (title) {
                title.textContent = `Soal ${index + 1}`;
            }

            const numberBadge = question.querySelector('[data-question-number]');
            if (numberBadge) {
                numberBadge.textContent = index + 1;
            }

            const correctValue = question.querySelector('[data-correct-option-control]:checked')?.value || '';

            question.querySelectorAll('[data-option-row]').forEach((row) => {
                const isCorrect = row.dataset.optionLabel === correctValue;
                row.classList.toggle('border-green-300', isCorrect);
                row.classList.toggle('bg-green-50', isCorrect);
                row.classList.toggle('border-gray-200', !isCorrect);
                row.classList.toggle('bg-white', !isCorrect);

                const labelBadge = row.querySelector('[data-option-label-badge]');
                labelBadge?.classList.toggle('bg-green-600', isCorrect);
                labelBadge?.classList.toggle('text-white', isCorrect);
                labelBadge?.classList.toggle('bg-gray-100', !isCorrect);
                labelBadge?.classList.toggle('text-gray-700', !isCorrect);
            });
        };

        document.querySelectorAll('.ai-preview-question').forEach(syncQuestionDisplay);

        previewList?.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-remove-ai-question]');
            if (!removeButton) {
                return;
            }

            removeButton.closest('.ai-preview-question')?.remove();
            document.querySelectorAll('.ai-preview-question').forEach((item, index) => {
                syncQuestionDisplay(item, index);
            });
        });

        previewList?.addEventListener('change', (event) => {
            if (!event.target.matches('[data-correct-option-control]')) {
                return;
            }

            const question = event.target.closest('.ai-preview-question');
            if (!question) {
                return;
            }

            syncQuestionDisplay(question, Array.from(document.querySelectorAll('.ai-preview-question')).indexOf(question));
        });

        form?.addEventListener('submit', (event) => {
            const questions = Array.from(document.querySelectorAll('.ai-preview-question')).map((item) => {
                const correctOption = item.querySelector('[data-correct-option-control]:checked')?.value || '';
                const options = Array.from(item.querySelectorAll('[data-option-text]')).map((optionInput) => {
                    const optionRow = optionInput.closest('[data-option-row]');
                    const optionScoreInput = optionRow?.querySelector('[data-option-score]');
                    return {
                        label: optionInput.dataset.optionLabel || '',
                        text: getRichEditorValue(optionInput),
                        score: Number.parseFloat(optionScoreInput?.value || '0') || 0,
                    };
                });

                return {
                    question_text: getRichEditorValue(item.querySelector('[data-question-text]')),
                    question_score: options.find((option) => option.label === correctOption)?.score || 0,
                    correct_option: correctOption,
                    explanation: getRichEditorValue(item.querySelector('[data-explanation]')),
                    options,
                };
            });

            if (questions.length === 0) {
                event.preventDefault();
                window.alert('Minimal simpan 1 soal dari preview.');
                return;
            }

            questionsJson.value = JSON.stringify(questions);

            const button = form.querySelector('[data-loading-button]');
            if (setButtonLoading(button, 'Menyimpan soal...')) {
                showPreviewLoading();
            }
        });
    });
</script>
@endpush
