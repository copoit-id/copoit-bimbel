@extends('user.layout.new-user')

@section('title', $kecermatan->name)

@section('content')
@php
    $primaryColor = $clientBranding['primary_color'] ?? '#10b981';
    $questions = $column->questions->values();
@endphp
<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
        <p class="text-sm font-semibold" style="color: {{ $primaryColor }}">{{ $kecermatan->typeLabel() }}</p>
        <h1 class="text-2xl font-bold text-gray-800">{{ $column->name }}</h1>
        <p class="text-sm text-gray-500">{{ $questions->count() }} soal · {{ $column->duration_seconds }} detik</p>
    </div>
    <div class="rounded-xl border border-gray-100 bg-white px-5 py-3 text-center shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Sisa Waktu</p>
        <p id="timerText" class="text-2xl font-black text-gray-900">--:--</p>
    </div>
</div>

<div class="grid gap-5 lg:grid-cols-[minmax(0,1fr),280px]">
    {{-- Section --}}
    <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
        @if($kecermatan->type === 'kecermatan_polri')
        <div class="mb-6">
            <p class="mb-2 text-sm font-semibold text-gray-700">Referensi A-E</p>
            <div class="grid grid-cols-5 overflow-hidden rounded-xl border border-gray-200 text-center w-full shadow-xs">
                @foreach($column->references ?? [] as $index => $reference)
                <div class="border-r border-gray-200 last:border-r-0">
                    <div class="bg-gray-50 px-3 py-2 text-xs font-bold text-gray-500">{{ chr(65 + $index) }}</div>
                    <div class="px-3 py-4 text-lg font-bold text-gray-900">{{ $reference }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="mb-5 flex items-center justify-between">
            <span id="questionCounter" class="text-sm font-semibold text-gray-500">Soal 1 dari {{ $questions->count() }}</span>
            <span id="answeredCounter" class="text-sm font-semibold text-gray-500">0 terjawab</span>
        </div>

        <div id="questionBox" class="mb-6 rounded-2xl bg-gray-50 p-6 text-center"></div>
        <div id="optionsBox" class="grid grid-cols-2 gap-3 sm:grid-cols-5"></div>

        <div class="mt-6 flex justify-end border-t border-gray-50 pt-4">
            <button id="finishButton" type="button" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 cursor-pointer transition-colors duration-150">Selesaikan Sekarang</button>
        </div>
    </section>

    {{-- Aside --}}
    <aside class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm lg:sticky lg:top-24 lg:self-start">
        <h2 class="mb-3 font-bold text-gray-800">Daftar Kolom</h2>
        <div class="space-y-2">
            @foreach($columns as $item)
            @php($isCompleted = in_array((int) $item->id, $completedColumnIds ?? [], true))
            <div class="rounded-lg border px-3 py-2 text-sm {{ $item->id === $column->id ? 'border-primary bg-primary/5 text-primary' : ($isCompleted ? 'border-green-100 bg-green-50 text-green-700' : 'border-gray-100 text-gray-600') }}">
                <div class="flex items-center justify-between gap-2">
                    <div class="font-semibold">{{ $item->name }}</div>
                    @if($isCompleted)
                    <i class="ri-check-line text-green-600"></i>
                    @endif
                </div>
                <div class="text-xs opacity-75">{{ $item->questions_count }} soal · {{ $item->duration_seconds }} detik</div>
            </div>
            @endforeach
        </div>
    </aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const questions = @json($questions->map(fn($question) => [
        'id' => $question->id,
        'payload' => $question->payload,
    ])->values());
    const type = @json($kecermatan->type);
    const references = @json($column->references ?? []);
    const submitUrl = @json(route('user.kecermatan.submit', [$kecermatan, $column]));
    const attemptToken = @json($attempt->attempt_token);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const primaryColor = @json($primaryColor);
    let timeLeft = {{ (int) $timeLeft }};
    let currentIndex = 0;
    const answers = {};
    let submitting = false;

    const timerText = document.getElementById('timerText');
    const questionBox = document.getElementById('questionBox');
    const optionsBox = document.getElementById('optionsBox');
    const questionCounter = document.getElementById('questionCounter');
    const answeredCounter = document.getElementById('answeredCounter');
    const finishButton = document.getElementById('finishButton');

    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60).toString().padStart(2, '0');
        const rest = (seconds % 60).toString().padStart(2, '0');
        return `${minutes}:${rest}`;
    }

    function renderQuestion() {
        const question = questions[currentIndex];
        if (!question) {
            submitAnswers();
            return;
        }

        questionCounter.textContent = `Soal ${currentIndex + 1} dari ${questions.length}`;
        answeredCounter.textContent = `${Object.keys(answers).length} terjawab`;

        if (type === 'kecermatan_tni') {
            questionBox.innerHTML = `<div class="flex items-center justify-center gap-4 text-6xl md:text-7xl font-black text-gray-900 py-6"><span>${question.payload[0]}</span><span class="text-gray-400 font-medium">+</span><span>${question.payload[1]}</span></div>`;
            optionsBox.innerHTML = Array.from({ length: 10 }, (_, index) => {
                const value = String(index + 1);
                return `<button type="button" data-answer="${value}" class="answer-btn rounded-xl border border-gray-200 px-4 py-4 text-xl font-bold text-gray-800 hover:border-primary hover:bg-primary/5 transition-all duration-150 cursor-pointer">${value}</button>`;
            }).join('');
        } else {
            questionBox.innerHTML = `<div class="grid grid-cols-4 gap-4 w-full">${question.payload.map((item) => `<div class="rounded-xl bg-white border border-gray-100 py-6 md:py-8 text-4xl md:text-5xl font-black text-gray-900 shadow-xs flex items-center justify-center">${item}</div>`).join('')}</div>`;
            optionsBox.innerHTML = references.map((item) => `<button type="button" data-answer="${item}" class="answer-btn rounded-xl border border-gray-200 px-4 py-4 text-lg font-bold text-gray-800 hover:border-primary hover:bg-primary/5 transition-all duration-150 cursor-pointer">${item}</button>`).join('');
        }

        document.querySelectorAll('.answer-btn').forEach((button) => {
            button.addEventListener('click', function() {
                answers[question.id] = this.dataset.answer;
                currentIndex++;
                renderQuestion();
            });
        });
    }

    async function submitAnswers() {
        if (submitting) return;
        submitting = true;
        finishButton.disabled = true;
        finishButton.textContent = 'Menyimpan...';

        try {
            const response = await fetch(submitUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    attempt_token: attemptToken,
                    answers
                })
            });
            const data = await response.json();
            if (!response.ok || !data.redirect) throw new Error(data.message || 'Gagal submit jawaban');
            window.location.href = data.redirect;
        } catch (error) {
            submitting = false;
            finishButton.disabled = false;
            finishButton.textContent = 'Selesaikan Sekarang';
            alert(error.message || 'Gagal submit jawaban');
        }
    }

    finishButton.addEventListener('click', submitAnswers);
    renderQuestion();
    timerText.textContent = formatTime(timeLeft);

    setInterval(() => {
        if (submitting) return;
        timeLeft = Math.max(0, timeLeft - 1);
        timerText.textContent = formatTime(timeLeft);
        if (timeLeft <= 0) submitAnswers();
    }, 1000);
});
</script>
@endsection
