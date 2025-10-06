@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">{{ isset($question) ? 'Edit Soal' : 'Tambah Soal' }}</h2>
            <p class="text-gray-500">{{ isset($question) ? 'Edit soal untuk subtest' : 'Buat soal baru untuk subtest' }}
            </p>
        </div>
        <a href="{{ route('admin.package.tryout.soal', [$package->package_id, $tryout->tryoutDetails->first()->tryout_detail_id]) }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Create Form -->
    <div class="bg-white rounded-lg border border-gray-200">
        <form
            action="{{ isset($question) ? route('admin.package.tryout.soal.update', ['package_id' => $package->package_id, 'tryout_detail_id' => $tryout->tryoutDetails->first()->tryout_detail_id, 'question_id' => $question->question_id]) : route('admin.package.tryout.soal.store', ['package_id' => $package->package_id, 'tryout_detail_id' => $tryout->tryoutDetails->first()->tryout_detail_id]) }}"
            method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($question))
            @method('PUT')
            @endif
            <div class="p-6 space-y-6">
                <!-- Question Text -->
                <div>
                    <label for="question_text" class="block font-medium text-gray-700 mb-2">Pertanyaan <span
                            class="text-red-500">*</span></label>
                    <textarea id="question_text" name="question_text" rows="4" required
                        class="tinymce w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Tulis pertanyaan di sini...">{{ isset($question) ? $question->question_text : old('question_text') }}</textarea>
                </div>

                <!-- Media Files -->
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="sound" class="block text-sm font-medium text-gray-700 mb-2">Audio (Opsional)</label>
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
                        <h3 class=" font-medium text-gray-800">Pilihan Jawaban</h3>
                        <div class="flex items-center">
                            <input type="checkbox" id="use_custom_scores" name="use_custom_scores" value="1" {{
                                (isset($question) && $question->custom_score == 'yes') ||
                            old('use_custom_scores') ? 'checked' : '' }}
                            class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary
                            focus:ring-2">
                            <label for="use_custom_scores" class="ml-2 text-sm font-medium text-gray-700">
                                Custom Score
                            </label>
                        </div>
                    </div>

                    <!-- Option A -->
                    <div class="option-row grid grid-cols-12 gap-3 items-center">
                        <div class="col-span-1 flex justify-center">
                            <input type="radio" id="correct_a" name="correct_answer" value="A" {{
                                (isset($question->questionOptions[0]) && $question->questionOptions[0]->is_correct == 1)
                            ||
                            old('correct_answer') == 'A' ? 'checked' : '' }} required
                            class="w-4 h-4 text-primary bg-gray-100 border-gray-300 focus:ring-primary focus:ring-2">
                        </div>
                        <div class="option-input col-span-11">
                            <label for="option_a" class="block text-sm font-medium text-gray-700 mb-2">Pilihan A <span
                                    class="text-red-500">*</span></label>
                            <textarea id="option_a" name="option_a" required
                                class="tinymce w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Pilihan A">{{ isset($question->questionOptions[0]) ? $question->questionOptions[0]->option_text : old('option_a') }}</textarea>
                        </div>
                        <div class="custom-score-field col-span-3" style="{{
                            (isset($question) && $question->custom_score == 'yes') ||
                            old('use_custom_scores') ? '' : 'display: none;' }}">
                            <label for="score_a" class="block text-sm font-medium text-gray-700 mb-2">Score A</label>
                            <input type="number" id="score_a" name="score_a"
                                value="{{ isset($question->questionOptions[0]) ? $question->questionOptions[0]->weight : old('score_a', 0) }}"
                                min="0" step="0.1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="0">
                        </div>
                    </div>

                    <!-- Option B -->
                    <div class="option-row grid grid-cols-12 gap-3 items-center">
                        <div class="col-span-1 flex justify-center">
                            <input type="radio" id="correct_b" name="correct_answer" value="B" {{
                                (isset($question->questionOptions[1]) && $question->questionOptions[1]->is_correct == 1)
                            ||
                            old('correct_answer') == 'B' ? 'checked' : '' }}
                            class="w-4 h-4 text-primary bg-gray-100 border-gray-300 focus:ring-primary focus:ring-2">
                        </div>
                        <div class="option-input col-span-11">
                            <label for="option_b" class="block text-sm font-medium text-gray-700 mb-2">Pilihan B <span
                                    class="text-red-500">*</span></label>
                            <textarea id="option_b" name="option_b" required
                                class="tinymce w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Pilihan B">{{ isset($question->questionOptions[1]) ? $question->questionOptions[1]->option_text : old('option_b') }}</textarea>
                        </div>
                        <div class="custom-score-field col-span-3" style="{{
                            (isset($question) && $question->custom_score == 'yes') ||
                            old('use_custom_scores') ? '' : 'display: none;' }}">
                            <label for="score_b" class="block text-sm font-medium text-gray-700 mb-2">Score B</label>
                            <input type="number" id="score_b" name="score_b"
                                value="{{ isset($question->questionOptions[1]) ? $question->questionOptions[1]->weight : old('score_b', 0) }}"
                                min="0" step="0.1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="0">
                        </div>
                    </div>

                    <!-- Option C -->
                    <div class="option-row grid grid-cols-12 gap-3 items-center">
                        <div class="col-span-1 flex justify-center">
                            <input type="radio" id="correct_c" name="correct_answer" value="C" {{
                                (isset($question->questionOptions[2]) && $question->questionOptions[2]->is_correct == 1)
                            ||
                            old('correct_answer') == 'C' ? 'checked' : '' }}
                            class="w-4 h-4 text-primary bg-gray-100 border-gray-300 focus:ring-primary focus:ring-2">
                        </div>
                        <div class="option-input col-span-11">
                            <label for="option_c" class="block text-sm font-medium text-gray-700 mb-2">Pilihan C <span
                                    class="text-red-500">*</span></label>
                            <textarea type="text" id="option_c" name="option_c" required
                                class="tinymce w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Pilihan C">{{ isset($question->questionOptions[2]) ? $question->questionOptions[2]->option_text : old('option_c') }}</textarea>
                        </div>
                        <div class="custom-score-field col-span-3" style="{{
                            (isset($question) && $question->custom_score == 'yes') ||
                            old('use_custom_scores') ? '' : 'display: none;' }}">
                            <label for="score_c" class="block text-sm font-medium text-gray-700 mb-2">Score C</label>
                            <input type="number" id="score_c" name="score_c"
                                value="{{ isset($question->questionOptions[2]) ? $question->questionOptions[2]->weight : old('score_c', 0) }}"
                                min="0" step="0.1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="0">
                        </div>
                    </div>

                    <!-- Option D -->
                    <div class="option-row grid grid-cols-12 gap-3 items-center">
                        <div class="col-span-1 flex justify-center">
                            <input type="radio" id="correct_d" name="correct_answer" value="D" {{
                                (isset($question->questionOptions[3]) && $question->questionOptions[3]->is_correct == 1)
                            ||
                            old('correct_answer') == 'D' ? 'checked' : '' }}
                            class="w-4 h-4 text-primary bg-gray-100 border-gray-300 focus:ring-primary focus:ring-2">
                        </div>
                        <div class="option-input col-span-11">
                            <label for="option_d" class="block text-sm font-medium text-gray-700 mb-2">Pilihan D <span
                                    class="text-red-500">*</span></label>
                            <textarea type="text" id="option_d" name="option_d" required
                                class="tinymce w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Pilihan D">{{ isset($question->questionOptions[3]) ? $question->questionOptions[3]->option_text : old('option_d') }}</textarea>
                        </div>
                        <div class="custom-score-field col-span-3" style="{{
                            (isset($question) && $question->custom_score == 'yes') ||
                            old('use_custom_scores') ? '' : 'display: none;' }}">
                            <label for="score_d" class="block text-sm font-medium text-gray-700 mb-2">Score D</label>
                            <input type="number" id="score_d" name="score_d"
                                value="{{ isset($question->questionOptions[3]) ? $question->questionOptions[3]->weight : old('score_d', 0) }}"
                                min="0" step="0.1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="0">
                        </div>
                    </div>

                    <!-- Option E -->
                    <div class="option-row grid grid-cols-12 gap-3 items-center">
                        <div class="col-span-1 flex justify-center">
                            <input type="radio" id="correct_e" name="correct_answer" value="E" {{
                                (isset($question->questionOptions[4]) && $question->questionOptions[4]->is_correct == 1)
                            ||
                            old('correct_answer') == 'E' ? 'checked' : '' }}
                            class="w-4 h-4 text-primary bg-gray-100 border-gray-300 focus:ring-primary focus:ring-2"
                            {{ !isset($question->questionOptions[4]) && !old('option_e') ? 'disabled' : '' }}>
                        </div>
                        <div class="option-input col-span-11">
                            <label for="option_e" class="block text-sm font-medium text-gray-700 mb-2">Pilihan E
                                (Opsional)</label>
                            <textarea type="text" id="option_e" name="option_e"
                                class="tinymce w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Pilihan E (kosongkan jika tidak digunakan)">{{ isset($question->questionOptions[4]) ? $question->questionOptions[4]->option_text : old('option_e') }}</textarea>
                        </div>
                        <div class="custom-score-field col-span-3" style="{{
                            (isset($question) && $question->custom_score == 'yes') ||
                            old('use_custom_scores') ? '' : 'display: none;' }}">
                            <label for="score_e" class="block text-sm font-medium text-gray-700 mb-2">Score E</label>
                            <input type="number" id="score_e" name="score_e"
                                value="{{ isset($question->questionOptions[4]) ? $question->questionOptions[4]->weight : old('score_e', 0) }}"
                                min="0" step="0.1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="0">
                        </div>
                    </div>
                </div>

                <!-- Explanation -->
                <div>
                    <label for="explanation" class="block font-medium text-gray-700 mb-2">Pembahasan
                        (Opsional)</label>
                    <textarea id="explanation" name="explanation" rows="4"
                        class="tinymce w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Jelaskan mengapa jawaban tersebut benar...">{{ isset($question) ? $question->explanation : old('explanation') }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end px-6 py-5 space-x-2 border-t border-gray-200">
                <a href="{{ route('admin.package.tryout.soal', [$package->package_id, $tryout->tryoutDetails->first()->tryout_detail_id]) }}"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                    Batal
                </a>
                <button type="submit"
                    class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    {{ isset($question) ? 'Update Soal' : 'Simpan Soal' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle custom score fields
        const useCustomScores = document.getElementById('use_custom_scores');
        const customScoreFields = document.querySelectorAll('.custom-score-field');
        const optionInputs = document.querySelectorAll('.option-input');

        function toggleCustomScores() {
            customScoreFields.forEach(field => {
                if (useCustomScores.checked) {
                    field.style.display = 'block';
                } else {
                    field.style.display = 'none';
                    // Reset values when hiding
                    const input = field.querySelector('input');
                    if (input) input.value = 0;
                }
            });

            // Update option input column spans for responsive layout
            optionInputs.forEach(optionInput => {
                if (useCustomScores.checked) {
                    // When custom scores are shown, use col-span-8
                    optionInput.classList.remove('col-span-11');
                    optionInput.classList.add('col-span-8');
                } else {
                    // When custom scores are hidden, use col-span-11 (almost full width)
                    optionInput.classList.remove('col-span-8');
                    optionInput.classList.add('col-span-11');
                }
            });
        }

        useCustomScores.addEventListener('change', toggleCustomScores);

        // Auto disable/enable option E radio when option E is empty/filled
        const optionE = document.getElementById('option_e');
        const correctE = document.getElementById('correct_e');

        function toggleOptionE() {
            if (optionE.value.trim() === '') {
                correctE.disabled = true;
                // If E was selected and now disabled, clear selection
                if (correctE.checked) {
                    correctE.checked = false;
                }
            } else {
                correctE.disabled = false;
            }
        }

        optionE.addEventListener('input', toggleOptionE);
        toggleOptionE(); // Initial check

        // Auto set correct answer score to 1 when selected (if using custom scores)
        const correctAnswers = document.querySelectorAll('input[name="correct_answer"]');
        correctAnswers.forEach(radio => {
            radio.addEventListener('change', function() {
                if (useCustomScores.checked && this.checked) {
                    // Set selected answer score to 1, others to 0
                    const scoreInputs = document.querySelectorAll('input[name^="score_"]');
                    scoreInputs.forEach(scoreInput => {
                        const option = scoreInput.name.split('_')[1]; // get 'a', 'b', 'c', etc.
                        if (this.value.toLowerCase() === option) {
                            scoreInput.value = 1;
                        } else {
                            scoreInput.value = 0;
                        }
                    });
                }
            });
        });

        // Initialize layout on page load
        toggleCustomScores();

        console.log('Create/Edit question form scripts loaded');
    });
</script>

@endsection
