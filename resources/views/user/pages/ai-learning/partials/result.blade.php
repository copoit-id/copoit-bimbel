@php($tool = $artifact->tool)
<div class="space-y-4" data-ai-artifact-id="{{ $artifact->id }}" data-ai-tool="{{ $tool }}">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-primary">{{ match($tool) { 'note' => 'AI Catatan Materi', 'recommendation' => 'AI Rekomendasi Belajar', 'question' => 'AI Generate Soal', default => 'AI Flashcard' } }}</p>
            <h4 class="mt-1 text-base font-semibold text-gray-900">{{ data_get($payload, 'title', $artifact->title) }}</h4>
        </div>
        @if($artifact->saved_at)
            <div class="flex shrink-0 items-center gap-2"><span class="inline-flex items-center gap-1 rounded-lg bg-primary/10 px-3 py-2 text-xs font-semibold text-primary"><i class="ri-pushpin-2-fill"></i>Dipin</span>@if($tool === 'note')<a href="{{ route('user.ai-learning.notes.pdf', $artifact) }}" class="rounded-lg border border-primary/25 px-3 py-2 text-xs font-semibold text-primary hover:bg-primary/5"><i class="ri-file-pdf-2-line"></i><span class="sr-only sm:not-sr-only sm:ml-1">PDF</span></a>@endif</div>
        @else
            <button type="button" class="ai-pin-artifact inline-flex items-center gap-1 rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white hover:bg-primary/90" data-artifact-id="{{ $artifact->id }}"><i class="ri-pushpin-2-line"></i>Pin</button>
        @endif
    </div>

    @if($tool === 'note')
        @php($noteSections = data_get($payload, 'sections', []))
        @php($summaryParagraphs = collect(preg_split('/\\R{2,}/', (string) data_get($payload, 'summary', '')))->filter())
        @if($summaryParagraphs->isNotEmpty())
            <div class="rounded-2xl border border-primary/15 bg-primary/5 p-4 sm:p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-primary">Gambaran umum</p>
                <div class="mt-2 space-y-3 text-sm leading-7 text-gray-700">
                    @foreach($summaryParagraphs as $paragraph)<p>{{ $paragraph }}</p>@endforeach
                </div>
            </div>
        @endif
        @if(collect($noteSections)->isNotEmpty())
            <div class="space-y-5">
                @foreach($noteSections as $section)
                    <section class="border-l-2 border-primary/30 pl-4 sm:pl-5">
                        <h5 class="text-base font-bold text-gray-900">{{ data_get($section, 'title') }}</h5>
                        <div class="mt-2 space-y-3 text-sm leading-7 text-gray-700">
                            @foreach(data_get($section, 'paragraphs', []) as $paragraph)<p>{{ $paragraph }}</p>@endforeach
                        </div>
                        @if(collect(data_get($section, 'bullets', []))->isNotEmpty())
                            <ul class="mt-3 space-y-2 text-sm leading-6 text-gray-700">
                                @foreach(data_get($section, 'bullets', []) as $bullet)
                                    <li class="flex gap-2"><i class="ri-check-line mt-0.5 text-primary"></i><span>{{ $bullet }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
        @if(collect(data_get($payload, 'key_points', []))->isNotEmpty())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5"><div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700"><i class="ri-lightbulb-flash-line"></i></span><p class="text-sm font-bold text-amber-950">Poin penting untuk diingat</p></div><ul class="mt-3 space-y-2 text-sm leading-6 text-amber-900">@foreach(data_get($payload, 'key_points', []) as $point)<li class="flex gap-2"><span class="font-bold">•</span><span>{{ $point }}</span></li>@endforeach</ul></div>
        @endif
        @if(collect(data_get($payload, 'formulas', []))->isNotEmpty())
            <div class="rounded-2xl border border-primary/20 bg-primary/5 p-4 sm:p-5"><div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-white"><i class="ri-function-line"></i></span><p class="text-sm font-bold text-gray-900">Rumus / istilah kunci</p></div><div class="mt-3 grid gap-2 sm:grid-cols-2">@foreach(data_get($payload, 'formulas', []) as $formula)<p class="rounded-xl border border-primary/15 bg-white px-3 py-2.5 text-sm font-medium leading-6 text-gray-800">{{ $formula }}</p>@endforeach</div></div>
        @endif
    @elseif($tool === 'recommendation')
        <div class="grid gap-3 md:grid-cols-2">
            @foreach(data_get($payload, 'focus_topics', []) as $topic)
                <div class="rounded-lg border border-gray-200 p-3"><div class="flex items-start justify-between gap-2"><p class="text-sm font-semibold text-gray-900">{{ data_get($topic, 'topic') }}</p><span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">{{ ucfirst(data_get($topic, 'priority', 'sedang')) }}</span></div><p class="mt-1 text-xs leading-5 text-gray-600">{{ data_get($topic, 'reason') }}</p></div>
            @endforeach
        </div>
        @if(collect(data_get($payload, 'study_plan', []))->isNotEmpty())
            <div><p class="text-sm font-semibold text-gray-800">Urutan belajar</p><ol class="mt-2 list-decimal space-y-1 pl-5 text-sm text-gray-700">@foreach(data_get($payload, 'study_plan', []) as $step)<li>{{ $step }}</li>@endforeach</ol></div>
        @endif
        <div><p class="text-sm font-semibold text-gray-800">Materi yang disetujui admin</p><div class="mt-2 grid gap-2 md:grid-cols-2">@forelse(data_get($payload, 'materials', []) as $material)<a href="{{ data_get($material, 'url') }}" class="rounded-lg border border-gray-200 p-3 hover:border-primary/50 hover:bg-primary/5"><div class="flex items-start gap-2"><i class="{{ data_get($material, 'type') === 'video' ? 'ri-video-line' : 'ri-file-text-line' }} mt-0.5 text-primary"></i><div><p class="text-sm font-semibold text-gray-900">{{ data_get($material, 'title') }}</p><p class="mt-1 text-xs text-gray-500">{{ data_get($material, 'type_label') }} · {{ data_get($material, 'source') }}</p></div></div></a>@empty<p class="text-sm text-gray-500 md:col-span-2">Belum ada materi relevan yang telah disetujui admin.</p>@endforelse</div></div>
    @elseif($tool === 'question')
        @php($generatedQuestions = data_get($payload, 'questions', []))
        @if(empty($generatedQuestions) && filled(data_get($payload, 'question_text')))
            @php($generatedQuestions = [$payload])
        @endif
        <div class="space-y-5">
            @foreach($generatedQuestions as $index => $question)
                <article class="rounded-xl border border-gray-200 bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-900">Soal {{ $index + 1 }}</p>
                        <div class="flex flex-wrap justify-end gap-1.5 text-[11px]"><span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700">{{ ucfirst(data_get($question, 'difficulty', 'sedang')) }}</span><span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700">HOTS {{ ucfirst(data_get($question, 'hots_level', 'sedang')) }}</span></div>
                    </div>
                    <p class="mt-3 text-sm font-medium leading-6 text-gray-900">{{ data_get($question, 'question_text') }}</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">@foreach(data_get($question, 'options', []) as $option)<div class="rounded-lg border px-3 py-2 text-sm {{ data_get($option, 'key') === data_get($question, 'correct_answer') ? 'border-green-200 bg-green-50 text-green-800' : 'border-gray-200 text-gray-700' }}"><span class="font-semibold">{{ data_get($option, 'key') }}.</span> {{ data_get($option, 'text') }}</div>@endforeach</div>
                    <div class="mt-3 rounded-lg bg-gray-50 p-3"><p class="text-sm font-semibold text-gray-800">Pembahasan</p><p class="mt-1 text-sm leading-6 text-gray-700">{{ data_get($question, 'explanation') }}</p></div>
                </article>
            @endforeach
        </div>
    @else
        @php($flashcardCount = count(data_get($payload, 'cards', [])))
        <div class="rounded-2xl border border-primary/20 bg-gradient-to-br from-primary/10 via-white to-primary/5 p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-xl text-white shadow-lg shadow-primary/20"><i class="ri-stack-line"></i></span>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-primary">Set flashcard</p><p class="mt-0.5 font-semibold text-gray-900">{{ data_get($payload, 'title', $artifact->title) }}</p><p class="mt-1 text-sm leading-6 text-gray-600">{{ $flashcardCount }} kartu siap untuk recall. Buka mode fokus untuk melihat satu kartu per giliran dan tandai mana yang sudah kamu ingat.</p></div>
                </div>
                <button type="button" class="ai-flashcard-preview inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary/90" data-flashcard-title="{{ data_get($payload, 'title', $artifact->title) }}"><i class="ri-eye-line"></i>Preview Flashcard</button>
            </div>

            <template class="ai-flashcard-preview-template">
                <div class="ai-flashcard-study mx-auto w-full max-w-xl" data-current-index="0">
                    <div class="relative px-3 pb-3 pt-2">
                        <div class="absolute inset-x-7 top-0 h-full rounded-[2rem] bg-violet-100/80"></div>
                        <div class="absolute inset-x-5 top-1 h-full rounded-[2rem] bg-violet-200/70"></div>
                        @foreach(data_get($payload, 'cards', []) as $index => $card)
                            <article class="ai-flashcard {{ $index === 0 ? '' : 'hidden' }} relative min-h-72" data-card-index="{{ $index }}" data-front="{{ data_get($card, 'front') }}" data-back="{{ data_get($card, 'back') }}" data-showing="front" data-status="new">
                                <div class="ai-flashcard-inner min-h-72">
                                    <div class="ai-flashcard-face ai-flashcard-front rounded-[2rem] bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-600 p-6 text-white shadow-xl">
                                        <div class="flex items-center justify-between gap-3 text-xs font-semibold text-violet-100"><span><i class="ri-stack-line mr-1"></i>Flashcard {{ $index + 1 }}</span><span class="ai-flashcard-side rounded-full bg-white/15 px-2.5 py-1">Pertanyaan</span></div>
                                        <div class="flex min-h-40 items-center justify-center py-6 text-center"><p class="ai-flashcard-content text-xl font-semibold leading-relaxed sm:text-2xl">{{ data_get($card, 'front') }}</p></div>
                                        <button type="button" class="ai-flashcard-flip w-full rounded-xl border border-white/30 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/20"><i class="ri-arrow-left-right-line mr-1"></i>Lihat jawaban</button>
                                    </div>
                                    <div class="ai-flashcard-face ai-flashcard-back rounded-[2rem] bg-white p-6 text-gray-900 shadow-xl">
                                        <div class="flex items-center justify-between gap-3 text-xs font-semibold text-gray-500"><span><i class="ri-lightbulb-flash-line mr-1 text-primary"></i>Flashcard {{ $index + 1 }}</span><span class="ai-flashcard-side rounded-full bg-primary/10 px-2.5 py-1 text-primary">Jawaban</span></div>
                                        <div class="flex min-h-40 items-center justify-center py-6 text-center"><p class="ai-flashcard-content text-xl font-semibold leading-relaxed sm:text-2xl">{{ data_get($card, 'back') }}</p></div>
                                        <button type="button" class="ai-flashcard-flip w-full rounded-xl border border-primary/25 bg-primary/5 px-4 py-2.5 text-sm font-semibold text-primary hover:bg-primary/10"><i class="ri-arrow-go-back-line mr-1"></i>Lihat pertanyaan</button>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-4 flex items-center justify-between gap-3"><p class="ai-flashcard-progress text-xs font-medium text-gray-500">Kartu 1 dari {{ $flashcardCount }}</p><p class="text-xs text-gray-400">Pilih sesuai ingatanmu</p></div>
                    <div class="mt-3 grid grid-cols-2 gap-3"><button type="button" class="ai-flashcard-forgot rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 hover:bg-amber-100"><i class="ri-restart-line mr-1"></i>Masih lupa</button><button type="button" class="ai-flashcard-remember rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white hover:bg-primary/90"><i class="ri-check-line mr-1"></i>Sudah ingat</button></div>
                    <div class="ai-flashcard-complete mt-4 hidden rounded-xl border border-green-200 bg-green-50 p-4 text-center"><i class="ri-checkbox-circle-line text-2xl text-green-600"></i><p class="mt-1 text-sm font-semibold text-green-900">Satu putaran selesai.</p><p class="ai-flashcard-complete-copy mt-1 text-xs text-green-700"></p><button type="button" class="ai-flashcard-recall mt-3 hidden rounded-lg bg-amber-500 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-600"><i class="ri-restart-line mr-1"></i>Ulangi kartu yang lupa</button></div>
                </div>
            </template>
        </div>
    @endif
</div>
