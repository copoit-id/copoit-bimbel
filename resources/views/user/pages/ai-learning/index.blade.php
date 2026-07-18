@extends('user.layout.new-user')

@section('title', 'AI Learning Tools')
@section('container_width', 'max-w-[96rem]')

@section('content')
<style>
    .standalone-flashcard { perspective: 1200px; }
    .standalone-flashcard .ai-flashcard { will-change: transform, opacity; }
    .standalone-flashcard .ai-flashcard-inner { position: relative; min-height: 18rem; transform-style: preserve-3d; transition: transform 620ms cubic-bezier(.22, 1, .36, 1); will-change: transform; }
    .standalone-flashcard .ai-flashcard.is-showing-back .ai-flashcard-inner { transform: rotateY(180deg); }
    .standalone-flashcard .ai-flashcard-face { position: absolute; inset: 0; display: flex; flex-direction: column; backface-visibility: hidden; -webkit-backface-visibility: hidden; }
    .standalone-flashcard .ai-flashcard-back { transform: rotateY(180deg); }
    .standalone-flashcard .ai-flashcard.is-entering { animation: standalone-flashcard-enter 360ms cubic-bezier(.22, 1, .36, 1); }
    .standalone-flashcard .ai-flashcard.is-exiting-remembered { animation: standalone-flashcard-exit-right 320ms ease-in forwards; }
    .standalone-flashcard .ai-flashcard.is-exiting-forgotten { animation: standalone-flashcard-exit-left 320ms ease-in forwards; }

    @keyframes standalone-flashcard-enter {
        from { opacity: 0; transform: translateY(22px) scale(.94) rotateY(-10deg); }
        to { opacity: 1; transform: translateY(0) scale(1) rotateY(0); }
    }

    @keyframes standalone-flashcard-exit-right {
        to { opacity: 0; transform: translateX(56px) rotate(4deg) scale(.94); }
    }

    @keyframes standalone-flashcard-exit-left {
        to { opacity: 0; transform: translateX(-56px) rotate(-4deg) scale(.94); }
    }

    @media (prefers-reduced-motion: reduce) {
        .standalone-flashcard .ai-flashcard.is-entering,
        .standalone-flashcard .ai-flashcard.is-exiting-remembered,
        .standalone-flashcard .ai-flashcard.is-exiting-forgotten { animation-duration: 1ms; }
        .standalone-flashcard .ai-flashcard-inner { transition-duration: 1ms; }
    }
</style>

@php
    $toolMeta = [
        'quota' => ['label' => 'Paket & Kuota', 'icon' => 'ri-cpu-line'],
        'note' => ['label' => 'Catatan', 'action' => 'Buat Catatan Materi', 'icon' => 'ri-sticky-note-line', 'description' => 'Susun materi lengkap dari konsep, soal, rumus, atau teks yang kamu masukkan.'],
        'recommendation' => ['label' => 'Rekomendasi', 'action' => 'Buat Rekomendasi Belajar', 'icon' => 'ri-compass-3-line', 'description' => 'Cari fokus belajar dan langkah berikutnya dari materi yang sedang kamu pelajari.'],
        'question' => ['label' => 'Soal Serupa', 'action' => 'Buat Soal Serupa', 'icon' => 'ri-file-add-line', 'description' => 'Buat latihan baru dari materi atau soal dengan tingkat kesulitan yang kamu pilih.'],
        'flashcard' => ['label' => 'Flashcard', 'action' => 'Buat Flashcard', 'icon' => 'ri-stack-line', 'description' => 'Ubah materi menjadi kartu ringkas untuk latihan recall.'],
    ];
    $activeTool = $toolMeta[$currentTool];
    $sourceFilterOptions = collect([
        'independent' => 'Input mandiri',
        'discussion' => 'Semua pembahasan',
    ])->merge($sourceOptions->mapWithKeys(fn ($source) => [$source['value'] => $source['label']]))->all();
@endphp

<div class="w-full">
    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-sm font-semibold text-primary"><i class="ri-sparkling-2-line mr-1"></i>AI Learning Tools</p><h1 class="mt-1 text-2xl font-bold text-gray-900">Ruang belajar AI</h1><p class="mt-1 text-sm text-gray-500">Pilih alat, buat hasil baru, lalu buka riwayatnya di tempat yang sama.</p></div>
    </div>

    <div class="grid gap-5 lg:grid-cols-[13.5rem_minmax(0,1fr)] lg:gap-8">
        <aside class="lg:sticky lg:top-20 lg:h-fit">
            <nav class="flex gap-2 overflow-x-auto rounded-2xl border border-gray-200 bg-white p-2 lg:flex-col" aria-label="Menu AI Learning Tools">
                @foreach($toolMeta as $toolKey => $meta)
                    <a href="{{ route('user.ai-learning.index', ['tool' => $toolKey]) }}" class="shrink-0 rounded-xl px-3 py-2.5 text-sm font-semibold {{ $currentTool === $toolKey ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' }}"><i class="{{ $meta['icon'] }} mr-2"></i>{{ $meta['label'] }}</a>
                @endforeach
            </nav>
        </aside>

        <main class="{{ $currentTool === 'quota' ? '' : 'grid gap-5 xl:grid-cols-[minmax(0,1.05fr)_minmax(22rem,0.95fr)]' }}">
            @if($currentTool === 'quota')
                @include('user.pages.ai-gateway.partials.dashboard-content', $gatewayDashboardData)
            @else
            <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"><i class="{{ $activeTool['icon'] }} text-xl"></i></span><div><h2 class="text-lg font-bold text-gray-900">AI {{ $activeTool['label'] }}</h2><p class="mt-1 text-sm leading-6 text-gray-500">{{ $activeTool['description'] }}</p></div></div>

                <form id="ai-learning-independent-form" class="mt-6 space-y-5">
                    <input type="hidden" name="tool" value="{{ $currentTool }}">
                    <x-ui.input name="title" label="Judul atau topik (opsional)" maxlength="120" icon="ri-bookmark-3-line" placeholder="Contoh: Hukum Newton" class="h-11 max-w-xl rounded-xl" />
                    <x-ui.input.textarea name="content" label="Soal atau materi" :required="true" minlength="20" maxlength="10000" rows="9" resize="vertical" helper="Minimal 20 karakter." placeholder="Tempel soal, teks materi, rumus, atau konsep yang ingin diolah..." class="rounded-xl leading-6" />

                    @if($currentTool === 'question')
                        <div id="ai-independent-question-settings" class="rounded-xl border border-primary/20 bg-primary/5 p-4"><p class="text-sm font-semibold text-primary">Pengaturan soal serupa</p><div class="mt-4 grid gap-3 sm:grid-cols-2"><x-ui.input.select name="question_count" label="Jumlah soal" :options="['1' => '1 soal', '2' => '2 soal', '3' => '3 soal']" value="1" class="h-10 rounded-lg border-primary/20" /><x-ui.input.select name="difficulty" label="Kesulitan" :options="['mudah' => 'Mudah', 'sedang' => 'Sedang', 'sulit' => 'Sulit']" value="sedang" class="h-10 rounded-lg border-primary/20" /><x-ui.input.select name="variation" label="Variasi" :options="['konteks' => 'Ubah konteks', 'angka' => 'Ubah angka', 'hots' => 'Naikkan HOTS']" value="konteks" class="h-10 rounded-lg border-primary/20" /><x-ui.input.select name="hots_level" label="Level HOTS" :options="['rendah' => 'Rendah', 'sedang' => 'Sedang', 'tinggi' => 'Tinggi']" value="sedang" class="h-10 rounded-lg border-primary/20" /></div></div>
                    @endif

                    <p id="ai-independent-error" class="hidden text-sm text-red-600" role="alert"></p>
                    <button id="ai-independent-submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"><i class="ri-sparkling-2-line"></i><span>{{ $activeTool['action'] }}</span></button>
                </form>

                <div id="ai-independent-result-wrap" class="mt-6 hidden rounded-2xl border border-gray-200 bg-gray-50 p-5"><div class="mb-4 flex items-center justify-between"><p class="text-sm font-semibold text-gray-900">Hasil terbaru</p><span class="text-xs text-gray-400">Masuk ke riwayat</span></div><div id="ai-independent-result"></div></div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><h2 class="text-lg font-bold text-gray-900">Riwayat {{ $activeTool['label'] }}</h2><p class="mt-1 text-sm text-gray-500">Buka hasil sebelumnya tanpa memakai token lagi.</p></div>
                    <div class="inline-flex w-fit rounded-lg bg-gray-100 p-1 text-xs font-semibold"><a href="{{ route('user.ai-learning.index', ['tool' => $currentTool]) }}" class="rounded-md px-2.5 py-1.5 {{ ! $showPinned ? 'bg-white text-primary shadow-sm' : 'text-gray-500' }}">Riwayat</a><a href="{{ route('user.ai-learning.index', ['tool' => $currentTool, 'pinned' => 1]) }}" class="rounded-md px-2.5 py-1.5 {{ $showPinned ? 'bg-white text-primary shadow-sm' : 'text-gray-500' }}"><i class="ri-pushpin-2-line mr-1"></i>Pin ({{ $pinnedArtifactsCount }})</a></div>
                </div>
                <form method="GET" action="{{ route('user.ai-learning.index') }}" class="mt-4"><input type="hidden" name="tool" value="{{ $currentTool }}">@if($showPinned)<input type="hidden" name="pinned" value="1">@endif<x-ui.input.select name="source" label="Filter sumber" :options="$sourceFilterOptions" :value="$currentSource" placeholder="Semua sumber" onchange="this.form.submit()" class="h-10 rounded-xl" /></form>
                <div id="ai-learning-history-list" class="mt-4 max-h-64 space-y-2 overflow-y-auto pr-1">
                    @forelse($artifacts as $artifact)
                        <div class="group relative"><button type="button" class="ai-learning-history-select w-full rounded-xl border border-gray-200 p-3 pr-28 text-left transition hover:border-primary/40 hover:bg-primary/5" data-artifact-id="{{ $artifact->id }}"><span class="block"><span class="block text-sm font-semibold text-gray-800">{{ $artifact->title }}</span><span class="mt-1 block text-xs text-gray-500">{{ $artifact->source_label ?: ($artifact->source_type === 'independent' ? 'Input mandiri' : 'Pembahasan tryout') }} · {{ $artifact->created_at?->translatedFormat('d M, H:i') }}</span></span></button><button type="button" class="ai-toggle-artifact-pin absolute right-3 top-1/2 inline-flex -translate-y-1/2 items-center gap-1 rounded-md px-1.5 py-1 text-[11px] font-semibold transition {{ $artifact->saved_at ? 'text-primary hover:bg-amber-50 hover:text-amber-700 lg:group-hover:bg-amber-50 lg:group-hover:text-amber-700' : 'text-gray-400 hover:bg-primary/5 hover:text-primary lg:group-hover:bg-primary/5 lg:group-hover:text-primary' }}" data-artifact-id="{{ $artifact->id }}" data-pinned="{{ $artifact->saved_at ? '1' : '0' }}"><i class="{{ $artifact->saved_at ? 'ri-pushpin-2-fill' : 'ri-pushpin-2-line' }}"></i><span class="lg:group-hover:hidden">{{ $artifact->saved_at ? 'Dipin' : 'Pin' }}</span><span class="hidden lg:group-hover:inline">{{ $artifact->saved_at ? 'Lepas pin' : 'Pin hasil' }}</span></button></div>
                        <template id="ai-learning-artifact-{{ $artifact->id }}">@include('user.pages.ai-learning.partials.result', ['artifact' => $artifact, 'payload' => $artifact->payload])</template>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm leading-6 text-gray-500"><i class="{{ $activeTool['icon'] }} mb-2 block text-2xl text-gray-300"></i>{{ $showPinned ? 'Belum ada hasil yang dipin.' : 'Belum ada hasil. Buat hasil pertamamu di panel sebelah.' }}</div>
                    @endforelse
                </div>
                <div class="mt-4">{{ $artifacts->links() }}</div>
            </section>
            @endif
        </main>
    </div>
</div>

<div id="ai-learning-artifact-modal" class="fixed inset-0 z-[99999] hidden overflow-hidden bg-slate-950/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true"><div class="flex h-full w-full items-center justify-center"><div class="flex max-h-full w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"><div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-5 py-4 sm:px-6"><div><p class="text-xs font-semibold uppercase tracking-wide text-primary">Riwayat AI Learning</p><p class="mt-1 text-lg font-bold text-gray-900">Detail hasil</p></div><button type="button" data-artifact-modal-close class="rounded-lg p-2 text-gray-400 hover:bg-gray-100" aria-label="Tutup detail hasil"><i class="ri-close-line text-xl"></i></button></div><div id="ai-learning-artifact-modal-content" class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-5 sm:p-6"></div></div></div></div>
<div id="ai-learning-flashcard-modal" class="fixed inset-0 z-[100000] hidden overflow-y-auto bg-slate-950/85 p-4 backdrop-blur-md" role="dialog" aria-modal="true"><div class="flex min-h-full items-center justify-center"><div class="w-full max-w-xl rounded-3xl bg-white p-5 shadow-2xl"><div class="mb-5 flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wide text-primary">Mode recall</p><p class="mt-1 text-lg font-semibold text-gray-900">Flashcard</p></div><button type="button" data-flashcard-close class="rounded-lg p-2 text-gray-400 hover:bg-gray-100"><i class="ri-close-line text-xl"></i></button></div><div id="ai-learning-flashcard-modal-content"></div></div></div></div>
@endsection

@push('scripts')
<script>
    const independentForm = document.getElementById('ai-learning-independent-form');
    const independentSubmit = document.getElementById('ai-independent-submit');
    const independentError = document.getElementById('ai-independent-error');
    const independentResult = document.getElementById('ai-independent-result');
    const independentResultWrap = document.getElementById('ai-independent-result-wrap');
    const artifactModal = document.getElementById('ai-learning-artifact-modal');
    const artifactModalContent = document.getElementById('ai-learning-artifact-modal-content');
    const independentQuestionSettings = document.getElementById('ai-independent-question-settings');
    const artifactPinUrlTemplate = @json(route('user.ai-learning.notes.save', ['artifact' => 'ARTIFACT_ID']));
    const artifactExpandUrlTemplate = @json(route('user.ai-learning.notes.expand', ['artifact' => 'ARTIFACT_ID']));
    const labels = { note: 'Buat Catatan Materi', recommendation: 'Buat Rekomendasi Belajar', question: 'Buat Soal Serupa', flashcard: 'Buat Flashcard' };

    function selectedTool() { return independentForm?.querySelector('input[name="tool"]')?.value || 'note'; }
    function updateIndependentTool() {
        const tool = selectedTool();
        independentQuestionSettings?.classList.toggle('hidden', tool !== 'question');
        independentSubmit?.querySelector('span')?.replaceChildren(document.createTextNode(labels[tool]));
    }
    updateIndependentTool();

    const generatedResultHtml = new Map();

    function renderStandaloneResult(result) {
        if (!independentResult || !independentResultWrap) return;
        const artifactId = String(result.artifact_id || 'latest');
        generatedResultHtml.set(artifactId, result.html || '');
        independentResult.innerHTML = '<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-100 text-green-700"><i class="ri-checkbox-circle-line text-xl"></i></span><div><p class="text-sm font-semibold text-gray-900">Hasil AI sudah siap</p><p data-generated-result-title class="mt-0.5 text-sm text-gray-500"></p></div></div><button type="button" class="ai-learning-open-generated-result inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary/90" data-artifact-id="' + artifactId + '"><i class="ri-eye-line"></i>Lihat hasil</button></div>';
        independentResult.querySelector('[data-generated-result-title]').textContent = result.title || 'Buka hasil lengkapnya dalam modal.';
        independentResultWrap.classList.remove('hidden');
        independentResultWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderHistoryResult(html) {
        if (!artifactModal || !artifactModalContent) return;
        artifactModalContent.innerHTML = html;
        artifactModal.classList.remove('hidden');
        artifactModal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeArtifactModal() {
        artifactModal?.classList.add('hidden');
        artifactModal?.classList.remove('flex');
        artifactModalContent?.replaceChildren();
        document.body.classList.remove('overflow-hidden');
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
            renderStandaloneResult(data);
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
        const setControlsDisabled = (disabled) => {
            study.querySelectorAll('.ai-flashcard-flip, .ai-flashcard-remember, .ai-flashcard-forgot')
                .forEach((button) => { button.disabled = disabled; });
        };
        const showCard = (index) => {
            cards.forEach((card, cardIndex) => {
                const visible = cardIndex === index;
                card.classList.toggle('hidden', !visible);
                if (!visible) return;

                card.classList.remove('is-exiting-remembered', 'is-exiting-forgotten', 'is-showing-back');
                card.dataset.showing = 'front';
                delete card.dataset.transitioning;
                card.classList.remove('is-entering');
                window.requestAnimationFrame(() => card.classList.add('is-entering'));
                window.setTimeout(() => card.classList.remove('is-entering'), 380);
            });
            setControlsDisabled(false);
            study.dataset.currentIndex = String(index);
            const progress = study.querySelector('.ai-flashcard-progress');
            if (progress) progress.textContent = `Kartu ${index + 1} dari ${cards.length}`;
        };
        const finishRound = () => {
            const forgotten = cards.filter((card) => card.dataset.status === 'forgotten').length;
            study.querySelector('.ai-flashcard-forgot')?.classList.add('hidden');
            study.querySelector('.ai-flashcard-remember')?.classList.add('hidden');
            const complete = study.querySelector('.ai-flashcard-complete');
            const completeCopy = study.querySelector('.ai-flashcard-complete-copy');
            const recall = study.querySelector('.ai-flashcard-recall');
            complete?.classList.remove('hidden');
            if (completeCopy) completeCopy.textContent = forgotten > 0
                ? `${forgotten} kartu masih perlu diulang.`
                : 'Semua kartu sudah kamu tandai ingat.';
            recall?.classList.toggle('hidden', forgotten === 0);
        };
        const continueStudy = () => {
            const nextIndex = cards.findIndex((card) => card.dataset.status === 'new');
            if (nextIndex === -1) {
                finishRound();
                return;
            }
            showCard(nextIndex);
        };
        const currentCard = () => cards[Number(study.dataset.currentIndex || 0)];
        const advanceCard = (status) => {
            const card = currentCard();
            if (!card || card.dataset.transitioning) return;

            card.dataset.transitioning = 'true';
            setControlsDisabled(true);
            card.classList.add(status === 'remembered' ? 'is-exiting-remembered' : 'is-exiting-forgotten');
            window.setTimeout(() => {
                card.dataset.status = status;
                card.classList.add('hidden');
                card.classList.remove('is-exiting-remembered', 'is-exiting-forgotten');
                delete card.dataset.transitioning;
                continueStudy();
            }, 330);
        };

        if (event.target.closest('.ai-flashcard-flip')) {
            const card = currentCard();
            if (!card || card.dataset.transitioning) return;
            card.dataset.transitioning = 'true';
            setControlsDisabled(true);
            const showBack = card.dataset.showing !== 'back';
            card.dataset.showing = showBack ? 'back' : 'front';
            card.classList.toggle('is-showing-back', showBack);
            window.setTimeout(() => {
                delete card.dataset.transitioning;
                setControlsDisabled(false);
            }, 620);
            return;
        }
        if (event.target.closest('.ai-flashcard-remember')) {
            advanceCard('remembered');
            return;
        }
        if (event.target.closest('.ai-flashcard-forgot')) {
            advanceCard('forgotten');
            return;
        }
        if (event.target.closest('.ai-flashcard-recall')) {
            cards.forEach((card) => {
                if (card.dataset.status === 'forgotten') card.dataset.status = 'new';
            });
            study.querySelector('.ai-flashcard-complete')?.classList.add('hidden');
            study.querySelector('.ai-flashcard-forgot')?.classList.remove('hidden');
            study.querySelector('.ai-flashcard-remember')?.classList.remove('hidden');
            continueStudy();
        }
    }
    document.addEventListener('click', (event) => {
        const generatedResultButton = event.target.closest('.ai-learning-open-generated-result');
        if (generatedResultButton) {
            const html = generatedResultHtml.get(String(generatedResultButton.dataset.artifactId || ''));
            if (html) renderHistoryResult(html);
            return;
        }
        const expandToggle = event.target.closest('.ai-note-expand-toggle');
        if (expandToggle) {
            const panel = expandToggle.closest('[data-ai-tool="note"]')?.querySelector('.ai-note-expand-panel');
            panel?.classList.toggle('hidden');
            return;
        }
        const expandSubmit = event.target.closest('.ai-note-expand-submit');
        if (expandSubmit) {
            const panel = expandSubmit.closest('.ai-note-expand-panel');
            const focus = panel?.querySelector('.ai-note-expand-focus')?.value?.trim() || '';
            const error = panel?.querySelector('.ai-note-expand-error');
            const original = expandSubmit.innerHTML;
            expandSubmit.disabled = true;
            expandSubmit.innerHTML = '<i class="ri-loader-4-line animate-spin"></i>Memproses...';
            error?.classList.add('hidden');
            fetch(artifactExpandUrlTemplate.replace('ARTIFACT_ID', expandSubmit.dataset.artifactId), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
                body: new URLSearchParams({ focus }),
            })
                .then(async (response) => {
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Catatan belum dapat diperdalam.');
                    renderHistoryResult(data.html || '');
                })
                .catch((expandError) => {
                    expandSubmit.disabled = false;
                    expandSubmit.innerHTML = original;
                    if (error) {
                        error.textContent = expandError.message || 'Catatan belum dapat diperdalam.';
                        error.classList.remove('hidden');
                    }
                });
            return;
        }
        const historyPinButton = event.target.closest('.ai-toggle-artifact-pin');
        if (historyPinButton) {
            const shouldPin = historyPinButton.dataset.pinned !== '1';
            historyPinButton.disabled = true;
            historyPinButton.innerHTML = '<i class="ri-loader-4-line animate-spin"></i>Memperbarui...';
            fetch(artifactPinUrlTemplate.replace('ARTIFACT_ID', historyPinButton.dataset.artifactId), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
                body: new URLSearchParams({ pinned: shouldPin ? '1' : '0' }),
            })
                .then(async (response) => {
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Pin hasil AI gagal diperbarui.');
                    window.location.reload();
                })
                .catch((error) => {
                    historyPinButton.disabled = false;
                    historyPinButton.innerHTML = '<i class="ri-error-warning-line"></i>Coba lagi';
                    independentError.textContent = error.message || 'Pin hasil AI gagal diperbarui.';
                    independentError.classList.remove('hidden');
                });
            return;
        }
        const history = event.target.closest('.ai-learning-history-select');
        if (history) { const template = document.getElementById(`ai-learning-artifact-${history.dataset.artifactId}`); if (template instanceof HTMLTemplateElement) renderHistoryResult(template.innerHTML); }
        const pinButton = event.target.closest('.ai-pin-artifact');
        if (pinButton) {
            pinButton.disabled = true;
            pinButton.innerHTML = '<i class="ri-loader-4-line animate-spin"></i>Mem-pin...';
            fetch(artifactPinUrlTemplate.replace('ARTIFACT_ID', pinButton.dataset.artifactId), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
            })
                .then(async (response) => {
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Hasil AI gagal dipin.');
                    pinButton.outerHTML = `<span class="inline-flex items-center gap-1 rounded-lg bg-primary/10 px-3 py-2 text-xs font-semibold text-primary"><i class="ri-pushpin-2-fill"></i>Dipin</span>`;
                })
                .catch((error) => {
                    pinButton.disabled = false;
                    pinButton.innerHTML = '<i class="ri-pushpin-2-line"></i>Pin';
                    independentError.textContent = error.message || 'Hasil AI gagal dipin.';
                    independentError.classList.remove('hidden');
                });
            return;
        }
        const preview = event.target.closest('.ai-flashcard-preview');
        if (preview) openStandaloneFlashcard(preview);
        if (event.target.closest('[data-artifact-modal-close]') || event.target === artifactModal) closeArtifactModal();
        if (event.target.closest('[data-flashcard-close]') || event.target === flashcardModal) closeStandaloneFlashcard();
        handleStandaloneFlashcard(event);
    });
    document.addEventListener('keydown', (event) => { if (event.key !== 'Escape') return; if (!flashcardModal?.classList.contains('hidden')) closeStandaloneFlashcard(); else if (!artifactModal?.classList.contains('hidden')) closeArtifactModal(); });
</script>
@endpush
