@extends('admin.layout.admin')
@section('title', 'Detail Jawaban Peserta')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <x-breadcrumb>
            <x-slot name="items">
                <x-breadcrumb-item href="{{ route('admin.laporan.index') }}" title="Laporan Tryout" />
                <x-breadcrumb-item href="{{ route('admin.laporan.show', $tryout->tryout_id) }}" title="{{ $tryout->name }}" />
                <x-breadcrumb-item href="" title="Detail Jawaban" />
            </x-slot>
        </x-breadcrumb>
        <a href="{{ route('admin.laporan.show', $tryout->tryout_id) }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">
            <i class="ri-arrow-left-line"></i>
            Kembali ke Laporan
        </a>
    </div>

    <x-page-desc title="Jawaban Peserta - {{ $user->name }}" />

    <div class="mt-6 rounded-xl border border-border bg-white p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=4f46e5&color=fff"
                    class="h-14 w-14 rounded-full" alt="{{ $user->name }}">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $user->name }}</h2>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                    <p class="mt-1 text-xs text-gray-400">Attempt: <span class="font-mono text-gray-600">{{ $attemptToken }}</span></p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm sm:flex sm:text-right">
                <div>
                    <p class="text-xs text-gray-500">Mulai</p>
                    <p class="font-semibold text-gray-800">{{ optional($overallStats['started_at'])->format('d M Y H:i') ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Selesai</p>
                    <p class="font-semibold text-gray-800">{{ optional($overallStats['finished_at'])->format('d M Y H:i') ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-5">
        <div class="rounded-xl border border-border bg-white p-4 text-center">
            <p class="text-xs text-gray-500">Total Soal</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $overallStats['total_questions'] }}</p>
        </div>
        <div class="rounded-xl border border-border bg-white p-4 text-center">
            <p class="text-xs text-gray-500">Benar</p>
            <p class="mt-1 text-2xl font-bold text-green-600">{{ $overallStats['correct'] }}</p>
        </div>
        <div class="rounded-xl border border-border bg-white p-4 text-center">
            <p class="text-xs text-gray-500">Salah</p>
            <p class="mt-1 text-2xl font-bold text-red-500">{{ $overallStats['wrong'] }}</p>
        </div>
        <div class="rounded-xl border border-border bg-white p-4 text-center">
            <p class="text-xs text-gray-500">Kosong</p>
            <p class="mt-1 text-2xl font-bold text-gray-600">{{ $overallStats['unanswered'] }}</p>
        </div>
        <div class="col-span-2 rounded-xl border border-border bg-white p-4 text-center md:col-span-1">
            <p class="text-xs text-gray-500">Total Skor</p>
            <p class="mt-1 text-2xl font-bold text-primary">{{ $overallStats['score'] }}</p>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-border bg-white p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Rangkuman Per Subtest</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-900">Pilih subtest untuk melihat preview soal</h3>
            </div>
            <div class="relative w-full lg:w-72">
                <input type="search" id="answer-search" placeholder="Cari soal pada subtest ini..."
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/10">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>

        <div class="mt-5 flex gap-2 overflow-x-auto pb-2" role="tablist" aria-label="Subtest">
            @foreach ($subtests as $subtest)
                @php $isActive = (int) $subtest['id'] === (int) $activeSubtestId; @endphp
                <a href="{{ route('admin.laporan.attempt', [$tryout->tryout_id, $attemptToken, 'subtest' => $subtest['id']]) }}"
                    role="tab" aria-selected="{{ $isActive ? 'true' : 'false' }}"
                    class="min-w-max rounded-lg border px-4 py-3 text-left transition {{ $isActive ? 'border-primary bg-primary text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-primary/40 hover:bg-primary/5' }}">
                    <span class="block text-sm font-semibold">{{ $subtest['name'] }}</span>
                    <span class="mt-1 block text-xs {{ $isActive ? 'text-white/75' : 'text-gray-500' }}">
                        {{ $subtest['correct'] }} benar · {{ $subtest['wrong'] }} salah · {{ $subtest['unanswered'] }} kosong
                    </span>
                </a>
            @endforeach
        </div>

        <div class="mt-2 grid grid-cols-3 gap-2 rounded-lg bg-gray-50 p-3 text-center text-xs sm:max-w-md">
            @php $activeSubtest = $subtests->firstWhere('id', $activeSubtestId); @endphp
            <div class="rounded-md bg-green-50 py-2 text-green-700">
                <p class="font-bold">{{ $activeSubtest['correct'] ?? 0 }}</p><p>Benar</p>
            </div>
            <div class="rounded-md bg-red-50 py-2 text-red-600">
                <p class="font-bold">{{ $activeSubtest['wrong'] ?? 0 }}</p><p>Salah</p>
            </div>
            <div class="rounded-md bg-gray-200/70 py-2 text-gray-600">
                <p class="font-bold">{{ $activeSubtest['unanswered'] ?? 0 }}</p><p>Kosong</p>
            </div>
        </div>
    </div>

    <section class="mt-6 space-y-4" aria-label="Detail jawaban soal">
        @forelse ($questionPreviews as $index => $preview)
            @php
                $question = $preview['question'];
                $answer = $preview['answer'];
                $questionType = $question->question_type ?? 'multiple_choice';
                $answerMeta = is_array($answer?->answer_json) ? $answer->answer_json : [];
                $questionMetadata = is_array($question->metadata) ? $question->metadata : [];
                $selectedOptionIds = $questionType === 'multiple_answer'
                    ? collect($answerMeta['selected_option_ids'] ?? [])->map(fn ($id) => (int) $id)->all()
                    : array_filter([(int) ($answer?->question_option_id ?? 0)]);
                $isOptionQuestion = in_array($questionType, ['multiple_choice', 'multiple_answer', 'true_false'], true);
                $multipleAnswerResult = $questionType === 'multiple_answer' && $answer
                    ? app(\App\Services\MultipleAnswerScoringService::class)->evaluateDetail($question, $answer)
                    : null;
                $isPartiallyCorrect = $multipleAnswerResult
                    ? app(\App\Services\MultipleAnswerScoringService::class)->isPartiallyCorrect($multipleAnswerResult)
                    : false;
                $status = $answer === null
                    ? 'Kosong'
                    : ($isPartiallyCorrect ? 'Sebagian Benar' : ($answer->is_correct ? 'Benar' : 'Salah'));
                $statusClass = $answer === null
                    ? 'bg-gray-100 text-gray-600 border-gray-200'
                    : ($isPartiallyCorrect
                        ? 'bg-amber-50 text-amber-700 border-amber-200'
                        : ($answer->is_correct ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-600 border-red-200'));
                $awardedScore = $multipleAnswerResult['score_obtained'] ?? null;
            @endphp
            <article data-question-card class="overflow-hidden rounded-xl border border-border bg-white">
                <div class="flex flex-col gap-3 border-b border-gray-100 bg-gray-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">{{ $index + 1 }}</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $activeSubtest['name'] ?? 'Subtest' }}</p>
                            <p class="text-xs text-gray-500">Soal #{{ $question->question_id }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($awardedScore !== null)
                            <span class="inline-flex w-fit items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">Skor: {{ $awardedScore }}</span>
                        @endif
                        <span class="inline-flex w-fit items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $status }}</span>
                    </div>
                </div>

                <div class="p-5">
                    <div class="prose prose-sm max-w-none text-gray-800">
                        {!! $question->question_text !!}
                    </div>

                    @if ($isOptionQuestion)
                        <div class="mt-5 space-y-2">
                            @forelse ($question->questionOptions as $option)
                                @php
                                    $isSelected = in_array((int) $option->question_option_id, $selectedOptionIds, true);
                                    $isCorrect = (bool) $option->is_correct;
                                    $optionClass = $isSelected && $isCorrect
                                        ? 'border-green-300 bg-green-50 text-green-900'
                                        : ($isSelected ? 'border-red-300 bg-red-50 text-red-900' : ($isCorrect ? 'border-green-200 bg-green-50/60 text-green-900' : 'border-gray-200 bg-white text-gray-700'));
                                @endphp
                                <div class="flex gap-3 rounded-lg border px-4 py-3 {{ $optionClass }}">
                                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-current text-xs font-bold">
                                        {{ strtoupper(chr(65 + $loop->index)) }}
                                    </span>
                                    <div class="min-w-0 flex-1 text-sm leading-relaxed">{!! $option->option_text !!}</div>
                                    <div class="flex shrink-0 flex-wrap items-start justify-end gap-1 text-xs font-semibold">
                                        @if ($isSelected)
                                            <span class="rounded-full bg-white/70 px-2 py-1">Dipilih peserta</span>
                                        @endif
                                        @if ($isCorrect)
                                            <span class="rounded-full bg-white/70 px-2 py-1">Kunci benar</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-500">Pilihan jawaban tidak tersedia.</p>
                            @endforelse
                        </div>
                    @else
                        @php
                            $participantAnswer = $answer?->answer_text;
                            $correctAnswer = $question->answer_text;
                            if ($questionType === 'matching') {
                                $participantAnswer = collect($answerMeta['matches'] ?? [])->map(fn ($right, $left) => "{$left} → " . ($right ?: '-'))->implode("\n") ?: null;
                                $correctAnswer = collect($questionMetadata['matching_pairs'] ?? [])->map(fn ($pair) => ($pair['left'] ?? '-') . ' → ' . ($pair['right'] ?? '-'))->implode("\n") ?: null;
                            }
                            if ($questionType === 'multiple_true_false') {
                                $meta = is_array($questionMetadata['multiple_true_false'] ?? null)
                                    ? $questionMetadata['multiple_true_false']
                                    : [];
                                $participantAnswer = collect($meta['statements'] ?? [])->map(function ($statement) use ($answerMeta) {
                                    $value = strtolower((string) ($answerMeta['answers'][$statement['id'] ?? ''] ?? ''));
                                    return ($statement['text'] ?? '-') . ' → ' . ($value === 'true' ? 'Benar' : ($value === 'false' ? 'Salah' : '-'));
                                })->implode("\n") ?: null;
                                $correctAnswer = collect($meta['statements'] ?? [])->map(fn ($statement) => ($statement['text'] ?? '-') . ' → ' . (strtolower((string) ($statement['correct'] ?? 'true')) === 'true' ? 'Benar' : 'Salah'))->implode("\n") ?: null;
                            }
                        @endphp
                        <div class="mt-5 grid gap-3 md:grid-cols-2">
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Jawaban peserta</p>
                                <p class="whitespace-pre-line text-sm text-gray-800">{{ $participantAnswer ?: 'Belum dijawab' }}</p>
                                @if ($answer?->answer_file_path)
                                    <a href="{{ Storage::url($answer->answer_file_path) }}" target="_blank" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-primary">
                                        <i class="ri-download-2-line"></i> Unduh lampiran
                                    </a>
                                @endif
                            </div>
                            <div class="rounded-lg border border-green-200 bg-green-50/60 p-4">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-green-700">Kunci jawaban</p>
                                <p class="whitespace-pre-line text-sm text-green-950">{{ $correctAnswer ?: '-' }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-gray-200 bg-white px-6 py-12 text-center text-gray-500">
                Tidak ada soal pada subtest ini.
            </div>
        @endforelse
    </section>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('answer-search');
            const cards = document.querySelectorAll('[data-question-card]');

            searchInput?.addEventListener('input', () => {
                const term = searchInput.value.trim().toLowerCase();
                cards.forEach((card) => {
                    card.classList.toggle('hidden', !card.textContent.toLowerCase().includes(term));
                });
            });
        });
    </script>
@endsection
