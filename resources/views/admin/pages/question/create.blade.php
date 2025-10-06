@extends('admin.layout.admin')
@section('title', isset($question) ? 'Edit Soal' : 'Tambah Soal')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.tryout.index') }}" title="Manajemen Tryout" />
            <x-breadcrumb-item href="{{ route('admin.question.index', $tryout_detail->tryout_detail_id) }}"
                title="Soal" />
            <x-breadcrumb-item href="" title="{{ isset($question) ? 'Edit Soal' : 'Tambah Soal' }}" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="{{ isset($question) ? 'Edit Soal' : 'Tambah Soal' }} - {{ $tryout->name }}">
    <x-slot name="description">
        Subtest: {{ strtoupper($tryout_detail->type_subtest) }} • Durasi: {{ $tryout_detail->duration }} menit
    </x-slot>
</x-page-desc>

<div class="space-y-6">
    <div class="bg-white rounded-lg border border-gray-200">
        <form
            action="{{ isset($question) ? route('admin.question.update', [$tryout_detail->tryout_detail_id, $question->question_id]) : route('admin.question.store', $tryout_detail->tryout_detail_id) }}"
            method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            @if(isset($question))
            @method('PUT')
            @endif

            <div class="p-6 space-y-6">
                <!-- Question Text -->
                <div>
                    <label for="question_text" class="block text-sm font-medium text-gray-700 mb-2">Teks Soal <span
                            class="text-red-500">*</span></label>
                    <textarea id="question_text" name="question_text" required rows="4"
                        class="tinymce w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Masukkan teks soal...">{{ isset($question) ? $question->question_text : old('question_text') }}</textarea>
                </div>

                <!-- Audio Upload -->
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="sound" class="block text-sm font-medium text-gray-700 mb-2">Audio Soal
                            (Opsional)</label>
                        @if(isset($question) && $question->sound)
                        <div class="mb-2">
                            <p class="text-sm text-gray-600">File audio saat ini:</p>
                            <audio controls class="mt-1">
                                <source src="{{ Storage::url($question->sound) }}" type="audio/mpeg">
                                Browser Anda tidak mendukung audio.
                            </audio>
                        </div>
                        @endif
                        <input type="file" id="sound" name="sound" accept="audio/*"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="text-sm text-gray-500 mt-1">Format: MP3, WAV (Max: 5MB)</p>
                    </div>
                </div>

                <!-- Answer Options -->
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-800">Pilihan Jawaban</h3>
                        @if ($tryout->is_toefl !== 1)
                        @if($tryout_detail->type_subtest !== 'tkp')
                        <div class="flex items-center">
                            <input type="checkbox" id="use_custom_scores" name="use_custom_scores" value="1" {{
                                (isset($question) && $question->custom_score == 'yes') || old('use_custom_scores') ?
                            'checked' : '' }}
                            class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary
                            focus:ring-2">
                            <label for="use_custom_scores" class="ml-2 text-sm font-medium text-gray-700">
                                Custom Score (Opsional)
                            </label>
                        </div>
                        @else
                        <div class="text-sm text-blue-600 font-medium">
                            <i class="ri-information-line mr-1"></i>
                            Mode TKP: Semua opsi dapat diberi skor 1-5
                        </div>
                        <input type="hidden" name="use_custom_scores" value="1">
                        @endif
                        @endif
                    </div>

                    <!-- Options A-E -->
                    @foreach(['A', 'B', 'C', 'D', 'E'] as $index => $optionKey)
                    @php
                    // Get option data for editing mode by index
                    $optionData = null;
                    $isCorrect = false;
                    if (isset($question) && $question->questionOptions && isset($question->questionOptions[$index])) {
                    $optionData = $question->questionOptions[$index];
                    $isCorrect = $optionData->is_correct == 1;
                    }
                    @endphp
                    <div class="option-row flex gap-3 items-center">
                        <div class="flex justify-center">
                            <input type="radio" id="correct_{{ strtolower($optionKey) }}" name="correct_answer"
                                value="{{ $optionKey }}" {{ $isCorrect || old('correct_answer')==$optionKey ? 'checked'
                                : '' }} {{ $optionKey==='E' ? '' : 'required' }}
                                class="w-4 h-4 text-primary bg-gray-100 border-gray-300 focus:ring-primary focus:ring-2">
                        </div>
                        <div class="option-input w-full">
                            <label for="option_{{ strtolower($optionKey) }}"
                                class="block text-sm font-medium text-gray-700 mb-2">
                                Pilihan {{ $optionKey }}
                                @if($optionKey !== 'E')<span class="text-red-500">*</span>@endif
                            </label>
                            <textarea id="option_{{ strtolower($optionKey) }}"
                                name="option_{{ strtolower($optionKey) }}" {{ $optionKey==='E' ? '' : 'required' }}
                                class="tinymce-opsi w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Pilihan {{ $optionKey }}">{{ $optionData ? $optionData->option_text : old('option_' . strtolower($optionKey)) }}</textarea>
                        </div>
                        <div class="custom-score-field w-1/3" style="{{
                            ($tryout_detail->type_subtest === 'tkp') ||
                            (isset($question) && $question->custom_score == 'yes') ||
                            old('use_custom_scores') ? '' : 'display: none;' }}">
                            <label for="score_{{ strtolower($optionKey) }}"
                                class="block text-sm font-medium text-gray-700 mb-2">
                                @if($tryout_detail->type_subtest === 'tkp')
                                Skor {{ $optionKey }} (1-5)
                                @else
                                Score {{ $optionKey }}
                                @endif
                            </label>
                            <input type="number" id="score_{{ strtolower($optionKey) }}"
                                name="score_{{ strtolower($optionKey) }}"
                                value="{{ $optionData ? $optionData->weight : old('score_' . strtolower($optionKey), $tryout_detail->type_subtest === 'tkp' ? 1 : 0) }}"
                                min="0" max="5" step="0.1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="{{ $tryout_detail->type_subtest === 'tkp' ? '1-5' : '0' }}">
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Explanation -->
                <div>
                    <label for="explanation" class="block text-sm font-medium text-gray-700 mb-2">Pembahasan
                        (Opsional)</label>
                    <textarea id="explanation" name="explanation" rows="4"
                        class="tinymce w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Masukkan pembahasan soal...">{{ isset($question) ? $question->explanation : old('explanation') }}</textarea>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end gap-4 pt-6 border-t">
                    <a href="{{ route('admin.question.index', $tryout_detail->tryout_detail_id) }}"
                        class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                        {{ isset($question) ? 'Perbarui' : 'Simpan' }} Soal
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const useCustomScores = document.getElementById('use_custom_scores');
    const customScoreFields = document.querySelectorAll('.custom-score-field');
    const tryoutType = '{{ $tryout_detail->type_subtest }}';

    // For TKP, always show score fields
    if (tryoutType === 'tkp') {
        customScoreFields.forEach(field => {
            field.style.display = '';
        });
        return;
    }

    // For non-TKP, handle custom score toggle
    if (useCustomScores) {
        function toggleScoreFields() {
            const isChecked = useCustomScores.checked;
            customScoreFields.forEach(field => {
                field.style.display = isChecked ? '' : 'none';
            });
        }

        useCustomScores.addEventListener('change', toggleScoreFields);
        toggleScoreFields(); // Initial state
    }

    // Add TKP specific validation
    if (tryoutType === 'tkp') {
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const scoreInputs = document.querySelectorAll('input[name^="score_"]');
            let isValid = true;

            scoreInputs.forEach(input => {
                const value = parseFloat(input.value);
                if (input.value && (value < 1 || value > 5)) {
                    isValid = false;
                    input.classList.add('border-red-500');
                } else {
                    input.classList.remove('border-red-500');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Untuk TKP, skor harus antara 1-5 poin');
            }
        });
    }

    console.log('Question form loaded for', tryoutType.toUpperCase());
});
</script>

@endsection