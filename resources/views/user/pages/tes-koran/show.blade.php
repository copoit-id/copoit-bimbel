@extends('user.layout.new-user')

@section('title', $tesKoran->name)

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-4 border-b bg-gray-50">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-gray-800">{{ $tesKoran->name }}</h2>
                <p class="text-sm text-gray-500">
                    {{ $tesKoran->test_type == 'pauli' ? 'Pauli' : 'Kraepelin' }} -
                    {{ $tesKoran->direction == 'top_to_bottom' ? 'Atas ke Bawah' : 'Bawah ke Atas' }}
                </p>
            </div>
            <div class="text-right">
                <div id="timer" class="text-2xl font-bold text-primary">{{ $tesKoran->duration_minutes }}:00</div>
                <p class="text-xs text-gray-400">Sisa Waktu</p>
            </div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="p-4 bg-blue-50 border-b">
        <div class="flex items-start gap-3">
            <i class="ri-information-line text-blue-500 text-xl mt-0.5"></i>
            <div class="text-sm text-blue-700">
                <p class="font-medium mb-1">Petunjuk:</p>
                <ul class="space-y-1">
                    <li>• Jumlahkan angka berurutan ({{ $tesKoran->direction == 'top_to_bottom' ? 'dari atas ke bawah' : 'dari bawah ke atas' }})</li>
                    <li>• Jika hasil > 9, tulis hanya digit terakhir (contoh: 14 → 4)</li>
                    <li>• Ketik jawaban di kolom yang tersedia</li>
                    <li>• Tekan Enter atau Tab untuk next</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Test Grid -->
    <div class="p-4 overflow-x-auto">
        <form id="tesKoranForm" method="POST" action="{{ route('user.tes-koran.start', $tesKoran) }}">
            @csrf
            <input type="hidden" name="answers" id="answersJson">
            <input type="hidden" name="columns_data" id="columnsData" value="{{ $columnsJson }}">

            <div class="inline-block min-w-full">
                <table class="border-collapse">
                    <thead>
                        <tr>
                            <th class="w-12 h-8 border border-gray-300 bg-gray-100 text-center text-xs font-medium text-gray-600">
                                No
                            </th>
                            @for($c = 0; $c < $tesKoran->columns_count; $c++)
                            <th class="w-12 h-8 border border-gray-300 bg-gray-100 text-center text-xs font-medium text-gray-600">
                                {{ $c + 1 }}
                            </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody id="testGrid">
                        @for($r = 0; $r < $tesKoran->rows_count; $r++)
                        <tr data-row="{{ $r }}">
                            <td class="w-12 h-10 border border-gray-300 bg-gray-100 text-center text-sm font-medium text-gray-600">
                                {{ $r + 1 }}
                            </td>
                            @for($c = 0; $c < $tesKoran->columns_count; $c++)
                            @if($r == 0)
                            <td class="w-12 h-10 border border-gray-300 bg-gray-50 text-center font-bold text-lg text-gray-700">
                                {{ $columns[$c][$r] }}
                            </td>
                            @else
                            <td class="w-12 h-10 border border-gray-300 text-center align-middle">
                                <div class="flex flex-col items-center">
                                    <span class="text-lg font-bold text-gray-500">{{ $columns[$c][$r] }}</span>
                                    <input type="text"
                                           maxlength="1"
                                           pattern="[0-9]"
                                           inputmode="numeric"
                                           data-row="{{ $r }}"
                                           data-col="{{ $c }}"
                                           class="w-8 h-6 text-center text-sm font-bold border border-gray-300 rounded focus:border-primary focus:ring-1 focus:ring-primary/20 mt-1"
                                           autocomplete="off">
                                </div>
                            </td>
                            @endif
                            @endfor
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    <span id="answeredCount">0</span> jawaban terisi
                </div>
                <button type="submit" id="submitBtn"
                        class="px-6 py-3 bg-primary text-white rounded-xl font-medium hover:opacity-90 transition-opacity"
                        style="background-color: {{ $primaryColor }}">
                    <i class="ri-check-line mr-2"></i>Submit Jawaban
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const duration = {{ $tesKoran->duration_minutes }};
    let timeLeft = duration * 60;
    const timerEl = document.getElementById('timer');
    const answeredCountEl = document.getElementById('answeredCount');
    const inputs = document.querySelectorAll('input[data-row][data-col]');
    const form = document.getElementById('tesKoranForm');
    const submitBtn = document.getElementById('submitBtn');

    // Timer
    const timerInterval = setInterval(() => {
        timeLeft--;
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            submitForm();
        }
    }, 1000);

    // Count answered
    function updateCount() {
        let count = 0;
        inputs.forEach(input => {
            if (input.value.trim() !== '') count++;
        });
        answeredCountEl.textContent = count;
    }

    // Input handling
    inputs.forEach(input => {
        input.addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');

            // Auto move to next input
            if (this.value.length === 1) {
                const row = parseInt(this.dataset.row);
                const col = parseInt(this.dataset.col);
                const nextInput = document.querySelector(`input[data-row="${row}"][data-col="${col + 1}"]`);
                if (nextInput) {
                    nextInput.focus();
                }
            }

            updateCount();
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === 'Tab') {
                e.preventDefault();
                const row = parseInt(this.dataset.row);
                const col = parseInt(this.dataset.col);

                if (col < {{ $tesKoran->columns_count - 1 }}) {
                    const nextInput = document.querySelector(`input[data-row="${row}"][data-col="${col + 1}"]`);
                    if (nextInput) nextInput.focus();
                } else if (row < {{ $tesKoran->rows_count - 2 }}) {
                    const nextInput = document.querySelector(`input[data-row="${row + 1}"][data-col="1"]`);
                    if (nextInput) nextInput.focus();
                }
            }
        });
    });

    // Submit form
    function submitForm() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i>Memproses...';

        // Collect answers
        const answers = {};
        inputs.forEach(input => {
            const row = input.dataset.row;
            const col = input.dataset.col;
            if (!answers[row]) answers[row] = {};
            answers[row][col] = input.value || null;
        });

        document.getElementById('answersJson').value = JSON.stringify(answers);
        form.submit();
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitForm();
    });
});
</script>
@endsection