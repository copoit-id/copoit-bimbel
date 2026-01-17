@extends('admin.layout.admin')
@section('title', 'Tambah Soal Bank')
@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <x-breadcrumb>
            <x-slot name="items">
                <x-breadcrumb-item href="{{ route('admin.question-bank.index') }}" title="Bank Soal" />
                <x-breadcrumb-item href="{{ route('admin.question-bank.show', $bank->id) }}" title="{{ $bank->name }}" />
                <x-breadcrumb-item href="" title="Tambah Soal" />
            </x-slot>
        </x-breadcrumb>
    </div>

    <x-page-desc title="Tambah Soal - {{ $bank->name }}">
        <x-slot name="description">
            Simpan soal ke bank agar bisa digunakan kembali saat menyusun tryout.
        </x-slot>
    </x-page-desc>

    @if ($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200">
        <form action="{{ route('admin.question-bank.questions.store', $bank->id) }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            @if ($importTarget)
            <input type="hidden" name="import_for" value="{{ $importTarget }}">
            @endif
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Soal <span class="text-red-500">*</span></label>
                        <select name="question_type" id="question_type"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            @foreach (['multiple_choice' => 'Multiple Choice', 'true_false' => 'Benar / Salah', 'matching' => 'Pencocokan', 'short_answer' => 'Jawaban Singkat', 'essay' => 'Essay', 'audio' => 'Jawaban Audio'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('question_type', 'multiple_choice') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bobot Nilai</label>
                        <input type="number" name="default_weight" step="0.1" min="0"
                            value="{{ old('default_weight', 1) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teks Soal <span class="text-red-500">*</span></label>
                    <textarea name="question_text" rows="4" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Masukkan teks soal...">{{ old('question_text') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Audio Soal (opsional)</label>
                    <input type="file" name="sound" accept="audio/*"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <p class="text-xs text-gray-500 mt-1">Format: MP3/WAV/M4A, maks 5MB.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pembahasan / Catatan</label>
                    <textarea name="explanation" rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Opsional, isi jika ingin menambahkan pembahasan.">{{ old('explanation') }}</textarea>
                </div>

                <div class="space-y-4 question-section" data-type="multiple_choice">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">Pilihan Jawaban</h3>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" name="use_custom_scores" value="1" @checked(old('use_custom_scores'))
                                class="rounded border-gray-300 text-primary focus:ring-primary">
                            Gunakan custom skor
                        </label>
                    </div>
                    @foreach (['A','B','C','D','E'] as $optionKey)
                    <div class="flex flex-col gap-2 border border-gray-200 rounded-xl p-4">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="correct_answer" value="{{ $optionKey }}" id="correct_{{ strtolower($optionKey) }}"
                                @checked(old('correct_answer', 'A') === $optionKey)
                                class="text-primary focus:ring-primary">
                            <label class="font-semibold text-gray-800" for="correct_{{ strtolower($optionKey) }}">
                                Pilihan {{ $optionKey }} @if($optionKey !== 'E') <span class="text-red-500">*</span> @endif
                            </label>
                        </div>
                        <textarea name="option_{{ strtolower($optionKey) }}" rows="2"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Teks pilihan">{{ old('option_' . strtolower($optionKey)) }}</textarea>
                        <div class="flex items-center gap-3 custom-score-field @if(!old('use_custom_scores')) hidden @endif">
                            <label class="text-sm text-gray-600">Skor</label>
                            <input type="number" step="0.1" name="score_{{ strtolower($optionKey) }}" min="0" max="5"
                                value="{{ old('score_' . strtolower($optionKey), $optionKey === 'A' ? 1 : 0) }}"
                                class="w-24 px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="space-y-4 question-section hidden" data-type="matching">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">Pasangan Pencocokan</h3>
                        <button type="button" id="addMatchingPair"
                            class="text-sm font-semibold text-primary hover:underline">Tambah Pasangan</button>
                    </div>
                    <div id="matchingPairsContainer" class="space-y-3">
                        @foreach ($matchingPairs as $index => $pair)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 matching-row">
                            <input type="text" name="matching_pairs[{{ $index }}][left]" value="{{ $pair['left'] ?? '' }}"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Kolom kiri">
                            <input type="text" name="matching_pairs[{{ $index }}][right]" value="{{ $pair['right'] ?? '' }}"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Kolom kanan">
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-4 question-section hidden" data-type="short_answer">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jawaban Referensi</label>
                        <textarea name="short_answer_expected" rows="4"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Masukkan jawaban referensi (pisahkan dengan baris baru)">{{ old('short_answer_expected') }}</textarea>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-600 mt-2">
                            <input type="checkbox" name="short_answer_case_sensitive" value="1" @checked(old('short_answer_case_sensitive'))
                                class="rounded border-gray-300 text-primary focus:ring-primary">
                            Perhatikan huruf besar-kecil
                        </label>
                    </div>
                </div>

                <div class="space-y-4 question-section hidden" data-type="essay">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Penilaian</label>
                        <textarea name="short_answer_expected" rows="4"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Opsional: masukkan jawaban ideal atau panduan penilaian.">{{ old('short_answer_expected') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Isi jawaban referensi jika memilih koreksi otomatis.</p>
                    </div>
                    <div class="space-y-2">
                        <span class="text-sm font-medium text-gray-700">Mode Koreksi Essay</span>
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="radio" name="essay_scoring_mode" value="auto" @checked(old('essay_scoring_mode') === 'auto')
                                    class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                                Otomatis (berdasarkan jawaban referensi)
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="radio" name="essay_scoring_mode" value="manual" @checked(old('essay_scoring_mode', 'manual') !== 'auto')
                                    class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                                Manual (belum dikoreksi)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 question-section hidden" data-type="audio">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Instruksi Jawaban Audio</label>
                        <textarea name="audio_instructions" rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Contoh: Bacakan jawabanmu dengan jelas dan maksimal 2 menit.">{{ old('audio_instructions') }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Durasi Maks (detik)</label>
                            <input type="number" name="audio_max_duration" min="5" max="600"
                                value="{{ old('audio_max_duration') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Misal: 120">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ukuran Maks (MB)</label>
                            <input type="number" name="audio_max_size" min="1" max="100"
                                value="{{ old('audio_max_size') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Misal: 10">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <a href="{{ route('admin.question-bank.show', $bank->id) }}"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Batal</a>
                <button type="submit"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan
                    Soal</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const typeSelect = document.getElementById('question_type');
        const sections = document.querySelectorAll('.question-section');
        const matchingContainer = document.getElementById('matchingPairsContainer');
        const addMatchingBtn = document.getElementById('addMatchingPair');
        const customScoreFields = document.querySelectorAll('.custom-score-field');
        const customScoreToggle = document.querySelector('input[name="use_custom_scores"]');

        function toggleSections() {
            const type = typeSelect.value;
            sections.forEach(section => {
                const visible = section.dataset.type === type || (section.dataset.type === 'multiple_choice' && type === 'multiple_choice');
                if (section.dataset.type === 'multiple_choice') {
                    section.classList.toggle('hidden', !['multiple_choice', 'true_false'].includes(type));
                } else if (section.dataset.type === 'short_answer') {
                    section.classList.toggle('hidden', type !== 'short_answer');
                } else if (section.dataset.type === 'essay') {
                    section.classList.toggle('hidden', type !== 'essay');
                } else if (section.dataset.type === 'matching') {
                    section.classList.toggle('hidden', type !== 'matching');
                } else if (section.dataset.type === 'audio') {
                    section.classList.toggle('hidden', type !== 'audio');
                }
            });
        }

        function toggleCustomScores() {
            customScoreFields.forEach(field => {
                field.classList.toggle('hidden', !customScoreToggle.checked);
            });
        }

        addMatchingBtn?.addEventListener('click', () => {
            if (!matchingContainer) return;
            const index = matchingContainer.children.length;
            const row = document.createElement('div');
            row.className = 'grid grid-cols-1 md:grid-cols-2 gap-3 matching-row';
            row.innerHTML = `
                <input type="text" name="matching_pairs[${index}][left]"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    placeholder="Kolom kiri">
                <input type="text" name="matching_pairs[${index}][right]"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    placeholder="Kolom kanan">
            `;
            matchingContainer.appendChild(row);
        });

        typeSelect?.addEventListener('change', toggleSections);
        customScoreToggle?.addEventListener('change', toggleCustomScores);

        toggleSections();
        toggleCustomScores();
    });
</script>
@endpush
