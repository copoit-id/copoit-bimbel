@extends('admin.layout.admin')
@section('title', 'Koreksi Essay')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.dashboard') }}" title="Dashboard" />
            <x-breadcrumb-item href="{{ route('admin.essay-review.index') }}" title="Koreksi Essay" />
            <x-breadcrumb-item href="{{ route('admin.essay-review.tryout', $tryout->tryout_id) }}" title="Pilih Peserta" />
            <x-breadcrumb-item href="" title="Jawaban Essay" />
        </x-slot>
    </x-breadcrumb>
</div>

<x-page-desc title="Koreksi Essay - {{ $tryout->name }}" description="Jawaban essay manual dari {{ $user->name }}." />

@if (session('success'))
<div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
    {{ session('success') }}
</div>
@endif
@if (session('error'))
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
    {{ session('error') }}
</div>
@endif

<div class="space-y-4">
    @forelse($reviews as $review)
        @php
            $question = $review->question;
            $subtestName = optional($review->userAnswer->tryoutDetail)->type_subtest ?? '-';
            $scoringMode = $question->essay_scoring_mode ?? 'full';
            $scoreCorrect = $question->getEssayScoreCorrect();
            $scoreWrong = $question->getEssayScoreWrong();
            $correctAnswer = $question->metadata['correct_answer'] ?? ($question->metadata['short_answer']['expected_answers'][0] ?? null) ?? $question->answer_key ?? 'Tidak ada kunci jawaban';
        @endphp
        <div class="bg-white border border-border rounded-xl p-6" id="essay-{{ $review->user_answer_detail_id }}">
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                    Belum Dikoreksi
                </span>
                <span class="text-sm text-gray-600">Subtest: <span class="font-medium text-gray-800">{{ strtoupper($subtestName) }}</span></span>
                @if($review->answered_at)
                    <span class="text-sm text-gray-500">Dijawab: {{ $review->answered_at->translatedFormat('d M Y H:i') }}</span>
                @endif
            </div>

            <div class="space-y-3">
                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-1">Soal:</p>
                    <div class="prose max-w-none text-gray-800">{!! $question->question_text ?? '-' !!}</div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-700 mb-1">Jawaban Peserta:</p>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-gray-800 min-h-[80px]">
                            {!! nl2br(e($review->answer_text ?? '')) ?: '-' !!}
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-700 mb-1">Kunci Jawaban:</p>
                        <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-gray-800 min-h-[80px]">
                            {!! nl2br(e($correctAnswer)) !!}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Info Scoring --}}
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex flex-wrap gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Mode Penilaian:</span>
                        <span class="font-semibold text-blue-700">
                            {{ $scoringMode === 'range' ? 'RANGE (Proporsional)' : 'FULL (Benar/Salah)' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600">Skor Benar:</span>
                        <span class="font-semibold text-green-700">{{ $scoreCorrect }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Skor Salah:</span>
                        <span class="font-semibold text-red-700">{{ $scoreWrong }}</span>
                    </div>
                </div>
                @if($scoringMode === 'range')
                    <p class="text-xs text-blue-600 mt-2">
                        <i class="ri-information-line mr-1"></i>
                        Mode RANGE: Skor dihitung proporsional berdasarkan kesamaan jawaban (0-{{ $scoreCorrect }})
                    </p>
                @else
                    <p class="text-xs text-blue-600 mt-2">
                        <i class="ri-information-line mr-1"></i>
                        Mode FULL: Benar = {{ $scoreCorrect }}, Salah = {{ $scoreWrong }}
                    </p>
                @endif
            </div>

            {{-- Form Penilaian --}}
            <form action="{{ route('admin.essay-review.review', $review->user_answer_detail_id) }}" method="POST" class="mt-4">
                @csrf
                <input type="hidden" name="question_id" value="{{ $question->question_id }}">
                
                <div class="flex flex-wrap items-end gap-4">
                    {{-- Pilihan Benar/Salah --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Penilaian</label>
                        <div class="flex gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="result" value="correct" class="peer sr-only" required>
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border-2 border-gray-200 font-semibold text-gray-600 peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 hover:bg-gray-50">
                                    <i class="ri-check-line"></i> Benar
                                </span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="result" value="incorrect" class="peer sr-only">
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border-2 border-gray-200 font-semibold text-gray-600 peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 hover:bg-gray-50">
                                    <i class="ri-close-line"></i> Salah
                                </span>
                            </label>
                        </div>
                    </div>

                    {{-- Input Skor (untuk mode RANGE atau kalau admin mau custom) --}}
                    <div class="flex-1 min-w-[200px]">
                        <label for="score_obtained_{{ $review->user_answer_detail_id }}" class="block text-sm font-medium text-gray-700 mb-2">
                            Skor yang Diberikan
                            @if($scoringMode === 'range')
                                <span class="text-xs text-gray-500">(0 - {{ $scoreCorrect }})</span>
                            @else
                                <span class="text-xs text-gray-500">({{ $scoreWrong }} - {{ $scoreCorrect }})</span>
                            @endif
                        </label>
                        <input type="number" 
                            id="score_obtained_{{ $review->user_answer_detail_id }}"
                            name="score_obtained" 
                            step="0.01"
                            min="{{ $scoreWrong }}"
                            max="{{ $scoreCorrect }}"
                            value="{{ $scoreCorrect }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    {{-- Similarity (optional, untuk referensi admin) --}}
                    <div class="min-w-[150px]">
                        <label for="similarity_{{ $review->user_answer_detail_id }}" class="block text-sm font-medium text-gray-700 mb-2">
                            Similarity (%)
                            <span class="text-xs text-gray-500">(opsional)</span>
                        </label>
                        <input type="number" 
                            id="similarity_{{ $review->user_answer_detail_id }}"
                            name="similarity" 
                            step="0.01"
                            min="0"
                            max="100"
                            placeholder="0-100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    {{-- Submit Button --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 opacity-0">Action</label>
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2 rounded-lg bg-primary text-white font-semibold hover:bg-primary/90">
                            <i class="ri-save-line"></i> Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @empty
        <div class="bg-white border border-border rounded-xl p-8 text-center text-gray-500">
            <i class="ri-check-double-line text-4xl mb-2 block"></i>
            <p>Tidak ada jawaban essay yang perlu dikoreksi untuk peserta ini.</p>
        </div>
    @endforelse
</div>

@endsection

@section('scripts')
<script>
    // Auto-set score berdasarkan pilihan benar/salah
    document.querySelectorAll('input[name="result"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const form = this.closest('form');
            const scoreInput = form.querySelector('input[name="score_obtained"]');
            const maxScore = parseFloat(scoreInput.max);
            const minScore = parseFloat(scoreInput.min);
            
            if (this.value === 'correct') {
                scoreInput.value = maxScore;
            } else {
                scoreInput.value = minScore;
            }
        });
    });
</script>
@endsection
