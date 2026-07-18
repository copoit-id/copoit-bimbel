@extends('user.layout.new-user')

@section('title', 'AI Learning Tools')

@section('content')
<style>
    .standalone-flashcard { perspective: 1200px; }
    .standalone-flashcard-inner { position: relative; min-height: 18rem; transform-style: preserve-3d; transition: transform 620ms cubic-bezier(.22, 1, .36, 1); }
    .standalone-flashcard.is-showing-back .ai-flashcard-inner { transform: rotateY(180deg); }
    .standalone-flashcard .ai-flashcard-face { position: absolute; inset: 0; display: flex; flex-direction: column; backface-visibility: hidden; -webkit-backface-visibility: hidden; }
    .standalone-flashcard .ai-flashcard-back { transform: rotateY(180deg); }
</style>

@php
    $toolMeta = [
        'note' => ['label' => 'Catatan', 'action' => 'Buat Catatan Materi', 'icon' => 'ri-sticky-note-line', 'description' => 'Susun materi lengkap dari konsep, soal, rumus, atau teks yang kamu masukkan.'],
        'recommendation' => ['label' => 'Rekomendasi', 'action' => 'Buat Rekomendasi Belajar', 'icon' => 'ri-compass-3-line', 'description' => 'Cari fokus belajar dan langkah berikutnya dari materi yang sedang kamu pelajari.'],
        'question' => ['label' => 'Soal Serupa', 'action' => 'Buat Soal Serupa', 'icon' => 'ri-file-add-line', 'description' => 'Buat latihan baru dari materi atau soal dengan tingkat kesulitan yang kamu pilih.'],
        'flashcard' => ['label' => 'Flashcard', 'action' => 'Buat Flashcard', 'icon' => 'ri-stack-line', 'description' => 'Ubah materi menjadi kartu ringkas untuk latihan recall.'],
    ];
    $activeTool = $toolMeta[$currentTool];
@endphp

<div class="mx-auto max-w-7xl">
    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-sm font-semibold text-primary"><i class="ri-sparkling-2-line mr-1"></i>AI Learning Tools</p><h1 class="mt-1 text-2xl font-bold text-gray-900">Ruang belajar AI</h1><p class="mt-1 text-sm text-gray-500">Pilih alat, buat hasil baru, lalu buka riwayatnya di tempat yang sama.</p></div>
        <a href="{{ route('user.ai-gateway.index') }}" class="inline-flex w-fit items-center gap-2 rounded-lg border border-primary/25 bg-primary/5 px-3 py-2 text-sm font-semibold text-primary hover:bg-primary/10"><i class="ri-cpu-line"></i>Paket & Kuota AI</a>
    </div>

    <div class="grid gap-5 lg:grid-cols-[13rem_minmax(0,1fr)]">
        <aside class="lg:sticky lg:top-20 lg:h-fit">
            <nav class="flex gap-2 overflow-x-auto rounded-2xl border border-gray-200 bg-white p-2 lg:flex-col" aria-label="Menu AI Learning Tools">
                @foreach($toolMeta as $toolKey => $meta)
                    <a href="{{ route('user.ai-learning.index', ['tool' => $toolKey]) }}" class="shrink-0 rounded-xl px-3 py-2.5 text-sm font-semibold {{ $currentTool === $toolKey ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' }}"><i class="{{ $meta['icon'] }} mr-2"></i>{{ $meta['label'] }}</a>
                @endforeach
                <div class="hidden border-t border-gray-100 pt-2 lg:block"></div>
                <a href="{{ route('user.ai-gateway.index') }}" class="shrink-0 rounded-xl px-3 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50"><i class="ri-cpu-line mr-2"></i>Paket & kuota</a>
            </nav>
        </aside>

        <main class="grid gap-5 xl:grid-cols-[minmax(0,1.05fr)_minmax(22rem,0.95fr)]">
            <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"><i class="{{ $activeTool['icon'] }} text-xl"></i></span><div><h2 class="text-lg font-bold text-gray-900">AI {{ $activeTool['label'] }}</h2><p class="mt-1 text-sm leading-6 text-gray-500">{{ $activeTool['description'] }}</p></div></div>

                <form id="ai-learning-independent-form" class="mt-6 space-y-5">
                    <input type="hidden" name="tool" value="{{ $currentTool }}">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block"><span class="text-sm font-semibold text-gray-700">Judul atau topik <span class="font-normal text-gray-400">(opsional)</span></span><div class="relative mt-2"><i class="ri-bookmark-3-line pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i><input name="title" maxlength="120" class="h-11 w-full rounded-xl border-gray-200 pl-10 text-sm placeholder:text-gray-400 focus:border-primary focus:ring-primary" placeholder="Contoh: Hukum Newton"></div></label>
                        <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-3"><p class="text-sm font-semibold text-gray-700"><i class="ri-lightbulb-line mr-1 text-primary"></i>Tips input</p><p class="mt-1 text-xs leading-5 text-gray-500">Masukkan satu topik atau soal agar hasil AI lebih fokus.</p></div>
                    </div>
                    <label class="block"><span class="text-sm font-semibold text-gray-700">Soal atau materi <span class="text-red-500">*</span></span><textarea name="content" required minlength="20" maxlength="10000" rows="9" class="mt-2 w-full rounded-xl border-gray-200 p-3 text-sm leading-6 placeholder:text-gray-400 focus:border-primary focus:ring-primary" placeholder="Tempel soal, teks materi, rumus, atau konsep yang ingin diolah..."></textarea><span class="mt-1.5 block text-xs text-gray-400">Minimal 20 karakter.</span></label>

                    @if($currentTool === 'question')
                        <div id="ai-independent-question-settings" class="rounded-xl border border-primary/20 bg-primary/5 p-4"><p class="text-sm font-semibold text-primary">Pengaturan soal serupa</p><div class="mt-4 grid gap-3 sm:grid-cols-2"><label class="block"><span class="text-xs font-semibold text-gray-600">Jumlah soal</span><select name="question_count" class="mt-1.5 h-10 w-full rounded-lg border-primary/20 bg-white text-sm focus:border-primary focus:ring-primary"><option value="1">1 soal</option><option value="2">2 soal</option><option value="3">3 soal</option></select></label><label class="block"><span class="text-xs font-semibold text-gray-600">Kesulitan</span><select name="difficulty" class="mt-1.5 h-10 w-full rounded-lg border-primary/20 bg-white text-sm focus:border-primary focus:ring-primary"><option value="mudah">Mudah</option><option value="sedang" selected>Sedang</option><option value="sulit">Sulit</option></select></label><label class="block"><span class="text-xs font-semibold text-gray-600">Variasi</span><select name="variation" class="mt-1.5 h-10 w-full rounded-lg border-primary/20 bg-white text-sm focus:border-primary focus:ring-primary"><option value="konteks" selected>Ubah konteks</option><option value="angka">Ubah angka</option><option value="hots">Naikkan HOTS</option></select></label><label class="block"><span class="text-xs font-semibold text-gray-600">Level HOTS</span><select name="hots_level" class="mt-1.5 h-10 w-full rounded-lg border-primary/20 bg-white text-sm focus:border-primary focus:ring-primary"><option value="rendah">Rendah</option><option value="sedang" selected>Sedang</option><option value="tinggi">Tinggi</option></select></label></div></div>
                    @endif

                    <p id="ai-independent-error" class="hidden text-sm text-red-600" role="alert"></p>
                    <button id="ai-independent-submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"><i class="ri-sparkling-2-line"></i><span>{{ $activeTool['action'] }}</span></button>
                </form>

                <div id="ai-independent-result-wrap" class="mt-6 hidden rounded-2xl border border-gray-200 bg-gray-50 p-5"><div class="mb-4 flex items-center justify-between"><p class="text-sm font-semibold text-gray-900">Hasil terbaru</p><span class="text-xs text-gray-400">Masuk ke riwayat</span></div><div id="ai-independent-result"></div></div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><h2 class="text-lg font-bold text-gray-900">Riwayat {{ $activeTool['label'] }}</h2><p class="mt-1 text-sm text-gray-500">Buka hasil sebelumnya tanpa memakai token lagi.</p></div>
                    @if($currentTool === 'note')
                        <div class="inline-flex w-fit rounded-lg bg-gray-100 p-1 text-xs font-semibold"><a href="{{ route('user.ai-learning.index', ['tool' => 'note']) }}" class="rounded-md px-2.5 py-1.5 {{ ! $showSavedNotes ? 'bg-white text-primary shadow-sm' : 'text-gray-500' }}">Riwayat</a><a href="{{ route('user.ai-learning.index', ['tool' => 'note', 'saved' => 1]) }}" class="rounded-md px-2.5 py-1.5 {{ $showSavedNotes ? 'bg-white text-primary shadow-sm' : 'text-gray-500' }}"><i class="ri-pushpin-2-line mr-1"></i>Dipin ({{ $savedNotesCount }})</a></div>
                    @endif
                </div>
                <form method="GET" action="{{ route('user.ai-learning.index') }}" class="mt-4"><input type="hidden" name="tool" value="{{ $currentTool }}">@if($showSavedNotes)<input type="hidden" name="saved" value="1">@endif<label class="block text-xs font-semibold text-gray-600" for="ai-learning-source">Filter sumber</label><div class="relative mt-1.5"><i class="ri-filter-3-line pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i><select id="ai-learning-source" name="source" onchange="this.form.submit()" class="h-10 w-full rounded-xl border-gray-200 pl-9 text-sm focus:border-primary focus:ring-primary"><option value="">Semua sumber</option><option value="independent" @selected($currentSource === 'independent')>Input mandiri</option><option value="discussion" @selected($currentSource === 'discussion')>Semua pembahasan</option>@foreach($sourceOptions as $source)<option value="{{ $source['value'] }}" @selected($currentSource === $source['value'])>{{ $source['label'] }}</option>@endforeach</select></div></form>
                <div id="ai-learning-history-list" class="mt-4 max-h-64 space-y-2 overflow-y-auto pr-1">
                    @forelse($artifacts as $artifact)
                        <button type="button" class="ai-learning-history-select w-full rounded-xl border border-gray-200 p-3 text-left transition hover:border-primary/40 hover:bg-primary/5" data-artifact-id="{{ $artifact->id }}"><span class="flex items-start justify-between gap-2"><span><span class="block text-sm font-semibold text-gray-800">{{ $artifact->title }}</span><span class="mt-1 block text-xs text-gray-500">{{ $artifact->source_label ?: ($artifact->source_type === 'independent' ? 'Input mandiri' : 'Pembahasan tryout') }} · {{ $artifact->created_at?->translatedFormat('d M, H:i') }}</span></span>@if($currentTool === 'note' && $artifact->saved_at)<span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-1 text-[10px] font-semibold text-primary"><i class="ri-pushpin-2-fill"></i>Dipin</span>@endif</span></button>
                        <template id="ai-learning-artifact-{{ $artifact->id }}">@include('user.pages.ai-learning.partials.result', ['artifact' => $artifact, 'payload' => $artifact->payload])</template>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm leading-6 text-gray-500"><i class="{{ $activeTool['icon'] }} mb-2 block text-2xl text-gray-300"></i>{{ $showSavedNotes ? 'Belum ada catatan yang dipin.' : 'Belum ada hasil. Buat hasil pertamamu di panel sebelah.' }}</div>
                    @endforelse
                </div>
                <div class="mt-4">{{ $artifacts->links() }}</div>
                <div id="ai-learning-history-detail" class="mt-4 min-h-48 rounded-xl border border-gray-200 bg-gray-50 p-4"><div id="ai-learning-history-result" class="text-sm leading-6 text-gray-500">Pilih salah satu hasil di riwayat untuk melihat detailnya di sini.</div></div>
            </section>
        </main>
    </div>
</div>

<div id="ai-learning-flashcard-modal" class="fixed inset-0 z-[100000] hidden overflow-y-auto bg-slate-950/85 p-4 backdrop-blur-md" role="dialog" aria-modal="true"><div class="flex min-h-full items-center justify-center"><div class="w-full max-w-xl rounded-3xl bg-white p-5 shadow-2xl"><div class="mb-5 flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wide text-primary">Mode recall</p><p class="mt-1 text-lg font-semibold text-gray-900">Flashcard</p></div><button type="button" data-flashcard-close class="rounded-lg p-2 text-gray-400 hover:bg-gray-100"><i class="ri-close-line text-xl"></i></button></div><div id="ai-learning-flashcard-modal-content"></div></div></div></div>
@endsection

@push('scripts')
<script>
    const independentForm = document.getElementById('ai-learning-independent-form');
    const independentSubmit = document.getElementById('ai-independent-submit');
    const independentError = document.getElementById('ai-independent-error');
    const independentResult = document.getElementById('ai-independent-result');
    const independentResultWrap = document.getElementById('ai-independent-result-wrap');
    const historyResult = document.getElementById('ai-learning-history-result');
    const independentQuestionSettings = document.getElementById('ai-independent-question-settings');
    const noteSaveUrlTemplate = @json(route('user.ai-learning.notes.save', ['artifact' => 'ARTIFACT_ID']));
    const labels = { note: 'Buat Catatan Materi', recommendation: 'Buat Rekomendasi Belajar', question: 'Buat Soal Serupa', flashcard: 'Buat Flashcard' };

    function selectedTool() { return independentForm?.querySelector('input[name="tool"]')?.value || 'note'; }
    function updateIndependentTool() {
        const tool = selectedTool();
        independentQuestionSettings?.classList.toggle('hidden', tool !== 'question');
        independentSubmit?.querySelector('span')?.replaceChildren(document.createTextNode(labels[tool]));
    }
    updateIndependentTool();

    function renderStandaloneResult(html) {
        if (!independentResult || !independentResultWrap) return;
        independentResult.innerHTML = html;
        independentResultWrap.classList.remove('hidden');
        independentResultWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderHistoryResult(html) {
        if (!historyResult) return;
        historyResult.innerHTML = html;
        historyResult.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    independentForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        independentError?.classList.add('hidden');
        independentSubmit.disabled = true;
        const original = independentSubmit.innerHTML;
        independentSubmit.innerHTML = '<i class="ri-loader-4-line animate-spin"></i>Memproses...';
        try {
            const formData = new FormData(independentForm);
            const response = await fetch(@json(route('user.ai-learning.generate-independent')), { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) }, body: formData });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'AI belum dapat memproses input.');
            renderStandaloneResult(data.html || '');
        } catch (error) {
            independentError.textContent = error.message || 'AI belum dapat memproses input.';
            independentError.classList.remove('hidden');
        } finally {
            independentSubmit.innerHTML = original;
            independentSubmit.disabled = false;
        }
    });

    const flashcardModal = document.getElementById('ai-learning-flashcard-modal');
    const flashcardContent = document.getElementById('ai-learning-flashcard-modal-content');
    function closeStandaloneFlashcard() { flashcardModal?.classList.add('hidden'); flashcardContent?.replaceChildren(); }
    function openStandaloneFlashcard(button) {
        const root = button.closest('[data-ai-tool="flashcard"]');
        const template = root?.querySelector('.ai-flashcard-preview-template');
        if (!(template instanceof HTMLTemplateElement) || !flashcardContent) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'standalone-flashcard';
        wrapper.append(template.content.cloneNode(true));
        flashcardContent.replaceChildren(wrapper);
        flashcardModal?.classList.remove('hidden');
    }
    function handleStandaloneFlashcard(event) {
        const study = event.target.closest('.ai-flashcard-study');
        if (!study) return;
        const cards = Array.from(study.querySelectorAll('.ai-flashcard'));
        const current = () => cards.find((card) => !card.classList.contains('hidden'));
        const showNext = () => { const next = cards.find((card) => card.dataset.status === 'new'); if (next) { next.classList.remove('hidden'); } else { study.querySelector('.ai-flashcard-complete')?.classList.remove('hidden'); study.querySelector('.ai-flashcard-forgot')?.classList.add('hidden'); study.querySelector('.ai-flashcard-remember')?.classList.add('hidden'); } };
        if (event.target.closest('.ai-flashcard-flip')) { const card = current(); if (card) { const back = card.dataset.showing !== 'back'; card.dataset.showing = back ? 'back' : 'front'; card.classList.toggle('is-showing-back', back); } return; }
        if (event.target.closest('.ai-flashcard-remember') || event.target.closest('.ai-flashcard-forgot')) { const card = current(); if (card) { card.dataset.status = event.target.closest('.ai-flashcard-remember') ? 'remembered' : 'forgotten'; card.classList.add('hidden'); showNext(); } return; }
        if (event.target.closest('.ai-flashcard-recall')) { cards.forEach((card) => { if (card.dataset.status === 'forgotten') card.dataset.status = 'new'; }); study.querySelector('.ai-flashcard-complete')?.classList.add('hidden'); study.querySelector('.ai-flashcard-forgot')?.classList.remove('hidden'); study.querySelector('.ai-flashcard-remember')?.classList.remove('hidden'); showNext(); }
    }
    document.addEventListener('click', (event) => {
        const history = event.target.closest('.ai-learning-history-select');
        if (history) { const template = document.getElementById(`ai-learning-artifact-${history.dataset.artifactId}`); if (template instanceof HTMLTemplateElement) renderHistoryResult(template.innerHTML); }
        const pinButton = event.target.closest('.ai-save-note');
        if (pinButton) {
            pinButton.disabled = true;
            pinButton.innerHTML = '<i class="ri-loader-4-line animate-spin"></i>Mem-pin...';
            fetch(noteSaveUrlTemplate.replace('ARTIFACT_ID', pinButton.dataset.artifactId), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
            })
                .then(async (response) => {
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Catatan gagal dipin.');
                    pinButton.outerHTML = `<span class="inline-flex items-center gap-1 rounded-lg bg-primary/10 px-3 py-2 text-xs font-semibold text-primary"><i class="ri-pushpin-2-fill"></i>Dipin</span>`;
                })
                .catch((error) => {
                    pinButton.disabled = false;
                    pinButton.innerHTML = '<i class="ri-pushpin-2-line"></i>Pin Catatan';
                    independentError.textContent = error.message || 'Catatan gagal dipin.';
                    independentError.classList.remove('hidden');
                });
            return;
        }
        const preview = event.target.closest('.ai-flashcard-preview');
        if (preview) openStandaloneFlashcard(preview);
        if (event.target.closest('[data-flashcard-close]') || event.target === flashcardModal) closeStandaloneFlashcard();
        handleStandaloneFlashcard(event);
    });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !flashcardModal?.classList.contains('hidden')) closeStandaloneFlashcard(); });
</script>
@endpush
