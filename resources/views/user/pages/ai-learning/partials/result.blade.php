@php($tool = $artifact->tool)
<div class="space-y-4" data-ai-artifact-id="{{ $artifact->id }}" data-ai-tool="{{ $tool }}">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-primary">{{ match($tool) { 'note' => 'AI Catatan Materi', 'recommendation' => 'AI Rekomendasi Belajar', 'question' => 'AI Generate Soal', default => 'AI Flashcard' } }}</p>
            <h4 class="mt-1 text-base font-semibold text-gray-900">{{ data_get($payload, 'title', $artifact->title) }}</h4>
        </div>
        @if($tool === 'note')
            <button type="button" class="ai-save-note rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white hover:bg-primary/90" data-artifact-id="{{ $artifact->id }}">Simpan ke Catatan Saya</button>
        @endif
    </div>

    @if($tool === 'note')
        <p class="text-sm leading-6 text-gray-700">{{ data_get($payload, 'summary') }}</p>
        @if(collect(data_get($payload, 'key_points', []))->isNotEmpty())
            <div><p class="text-sm font-semibold text-gray-800">Poin penting</p><ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-gray-700">@foreach(data_get($payload, 'key_points', []) as $point)<li>{{ $point }}</li>@endforeach</ul></div>
        @endif
        @if(collect(data_get($payload, 'formulas', []))->isNotEmpty())
            <div class="rounded-lg bg-blue-50 p-3"><p class="text-sm font-semibold text-blue-900">Rumus / istilah</p><ul class="mt-2 space-y-1 text-sm text-blue-800">@foreach(data_get($payload, 'formulas', []) as $formula)<li>{{ $formula }}</li>@endforeach</ul></div>
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
        <div class="flex flex-wrap gap-2 text-xs"><span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700">Kesulitan: {{ ucfirst(data_get($payload, 'difficulty', 'sedang')) }}</span><span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700">HOTS: {{ ucfirst(data_get($payload, 'hots_level', 'sedang')) }}</span></div>
        <p class="text-sm font-medium leading-6 text-gray-900">{{ data_get($payload, 'question_text') }}</p>
        <div class="space-y-2">@foreach(data_get($payload, 'options', []) as $option)<div class="rounded-lg border px-3 py-2 text-sm {{ data_get($option, 'key') === data_get($payload, 'correct_answer') ? 'border-green-200 bg-green-50 text-green-800' : 'border-gray-200 text-gray-700' }}"><span class="font-semibold">{{ data_get($option, 'key') }}.</span> {{ data_get($option, 'text') }}</div>@endforeach</div>
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-sm font-semibold text-gray-800">Pembahasan</p><p class="mt-1 text-sm leading-6 text-gray-700">{{ data_get($payload, 'explanation') }}</p></div>
    @else
        <div class="grid gap-3 sm:grid-cols-2">@foreach(data_get($payload, 'cards', []) as $index => $card)<button type="button" class="ai-flashcard min-h-36 rounded-xl border border-violet-200 bg-violet-50 p-4 text-left transition hover:-translate-y-0.5 hover:shadow" data-front="{{ data_get($card, 'front') }}" data-back="{{ data_get($card, 'back') }}" data-showing="front"><span class="text-[11px] font-semibold uppercase tracking-wide text-violet-600">Kartu {{ $index + 1 }} · klik untuk balik</span><span class="ai-flashcard-content mt-4 block text-sm font-semibold leading-6 text-violet-950">{{ data_get($card, 'front') }}</span></button>@endforeach</div>
    @endif
</div>
