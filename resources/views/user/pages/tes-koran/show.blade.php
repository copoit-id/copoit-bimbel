@extends('user.layout.new-user')

@section('title', $tesKoran->name)

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$effectiveDirection = $tesKoran->test_type === 'kraepelin' ? 'bottom_to_top' : 'top_to_bottom';
@endphp

<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-4 border-b bg-gray-50">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-gray-800">{{ $tesKoran->name }}</h2>
                <p class="text-sm text-gray-500">
                    {{ $tesKoran->test_type == 'pauli' ? 'Pauli' : 'Kraepelin' }} -
                    {{ $effectiveDirection == 'top_to_bottom' ? 'Atas ke Bawah' : 'Bawah ke Atas' }}
                </p>
            </div>
            <div class="text-right">
                <div id="currentColumnLabel" class="text-lg font-bold text-primary">Kolom 1</div>
                <p class="text-xs text-gray-400">Kolom aktif</p>
            </div>
        </div>
    </div>

    <!-- Instructions -->
    <div id="instructionPanel" class="p-4 bg-blue-50 border-b">
        <div class="flex items-start gap-3">
            <i class="ri-information-line text-blue-500 text-xl mt-0.5"></i>
            <div class="text-sm text-blue-700">
                <p class="font-medium mb-1">Petunjuk:</p>
                <ul class="space-y-1">
                    <li>• Kerjakan operasi {{ strtolower($tesKoran->operationLabel()) }} secara berurutan ({{ $effectiveDirection == 'top_to_bottom' ? 'dari atas ke bawah' : 'dari bawah ke atas' }})</li>
                    <li>• Tulis hanya digit satuan dari hasil hitung</li>
                    <li>• Timer tidak ditampilkan. Saat waktu kolom habis, sistem otomatis memberi instruksi pindah kolom</li>
                    <li>• Kolom sebelumnya akan terkunci setelah waktunya habis</li>
                    <li>• Tekan Enter atau Tab untuk next</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Test Grid -->
    <div class="p-4 overflow-x-auto">
        <form id="tesKoranForm" method="POST" action="{{ route('user.tes-koran.start', $tesKoran) }}">
            @csrf
            <input type="hidden" name="columns_data" id="columnsData" value="{{ $columnsJson }}">

            <div class="inline-block min-w-full">
                <table class="border-collapse">
                    <thead>
                        <tr>
                            <th class="w-12 h-8 border border-gray-300 bg-gray-100 text-center text-xs font-medium text-gray-600">
                                No
                            </th>
                            @for($c = 0; $c < $tesKoran->columns_count; $c++)
                            <th data-col-header="{{ $c }}" class="w-12 h-8 border border-gray-300 bg-gray-100 text-center text-xs font-medium text-gray-600">
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
                            <td data-col-cell="{{ $c }}" class="w-12 h-10 border border-gray-300 bg-gray-50 text-center font-bold text-lg text-gray-700">
                                {{ $columns[$c][$r] }}
                            </td>
                            @else
                            <td data-col-cell="{{ $c }}" class="w-12 h-10 border border-gray-300 text-center align-middle">
                                <div class="flex flex-col items-center">
                                    <span class="text-lg font-bold text-gray-500">{{ $columns[$c][$r] }}</span>
                                    <input type="text"
                                           maxlength="{{ $tesKoran->answerMaxLength() }}"
                                           pattern="[0-9]"
                                           inputmode="numeric"
                                           data-row="{{ $r }}"
                                           data-col="{{ $c }}"
                                           class="w-12 h-6 text-center text-sm font-bold border border-gray-300 rounded focus:border-primary focus:ring-1 focus:ring-primary/20 mt-1 disabled:bg-gray-100 disabled:text-gray-400"
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
                <div id="submitStatus" class="hidden text-sm font-medium text-gray-500">
                    <i class="ri-loader-4-line animate-spin mr-2"></i>Memproses jawaban...
                </div>
            </div>
        </form>
    </div>
</div>

<div id="changeColumnModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-xs rounded-2xl bg-white p-8 text-center shadow-2xl">
        <div id="changeColumnTitle" class="text-4xl font-black tracking-wide text-primary"></div>
        <div id="changeColumnText" class="mt-3 text-sm font-medium text-gray-600"></div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let timeLeft = Math.floor({{ $timeLeft }});
    const columnDurationSeconds = {{ $tesKoran->column_duration_seconds ?? 60 }};
    const columnsCount = {{ $tesKoran->columns_count }};
    const rowsCount = {{ $tesKoran->rows_count }};
    const totalDurationSeconds = columnDurationSeconds * columnsCount;
    const elapsedSeconds = Math.max(0, totalDurationSeconds - timeLeft);
    let currentColumn = Math.min(columnsCount - 1, Math.floor(elapsedSeconds / columnDurationSeconds));
    let currentColumnRemaining = columnDurationSeconds - (elapsedSeconds % columnDurationSeconds);
    if (timeLeft <= 0) currentColumnRemaining = 0;
    const currentColumnLabel = document.getElementById('currentColumnLabel');
    const changeColumnModal = document.getElementById('changeColumnModal');
    const changeColumnTitle = document.getElementById('changeColumnTitle');
    const changeColumnText = document.getElementById('changeColumnText');
    const answeredCountEl = document.getElementById('answeredCount');
    const inputs = document.querySelectorAll('input[data-row][data-col]');
    const form = document.getElementById('tesKoranForm');
    const submitStatus = document.getElementById('submitStatus');
    let isSubmitting = false;
    let columnTimeout = null;
    const transitionInstruction = '{{ $tesKoran->test_type === 'pauli' ? 'GARIS!' : 'PINDAH!' }}';
    const disallowedInputPattern = /[^0-9]/g;

    startColumnFlow();

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
            this.value = this.value.replace(disallowedInputPattern, '').slice(-1);
            updateCount();

            if (this.value.length === 1) {
                moveToNextInput(this);
            }
        });

        input.addEventListener('keyup', function(e) {
            if (/^[0-9]$/.test(e.key) && this.value.length === 1) {
                moveToNextInput(this);
            }
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === 'Tab') {
                e.preventDefault();
                moveToNextInput(this);
            }
        });
    });

    function moveToNextInput(input) {
        const columnInputs = getColumnInputs(parseInt(input.dataset.col));
        const currentIndex = columnInputs.indexOf(input);
        const nextInput = columnInputs[currentIndex + 1] ?? null;

        if (nextInput) {
            focusInput(nextInput);
        }
    }

    function getColumnInputs(columnIndex) {
        const direction = '{{ $effectiveDirection }}';
        const columnInputs = Array.from(document.querySelectorAll(`input[data-col="${columnIndex}"]:not(:disabled)`));

        return columnInputs.sort((first, second) => {
            const firstRow = parseInt(first.dataset.row);
            const secondRow = parseInt(second.dataset.row);

            return direction === 'bottom_to_top' ? secondRow - firstRow : firstRow - secondRow;
        });
    }

    function startColumnFlow() {
        activateColumn(currentColumn);

        if (currentColumnRemaining <= 0) {
            advanceColumn();
            return;
        }

        columnTimeout = setTimeout(advanceColumn, currentColumnRemaining * 1000);
    }

    function activateColumn(columnIndex) {
        currentColumnLabel.textContent = `Kolom ${columnIndex + 1}`;

        inputs.forEach(input => {
            const inputColumn = parseInt(input.dataset.col);
            input.disabled = inputColumn !== columnIndex;
        });

        document.querySelectorAll('[data-col-header], [data-col-cell]').forEach(cell => {
            const cellColumn = parseInt(cell.dataset.colHeader ?? cell.dataset.colCell);
            cell.classList.toggle('bg-emerald-50', cellColumn === columnIndex);
            cell.classList.toggle('bg-gray-100', cellColumn < columnIndex);
            cell.classList.toggle('opacity-60', cellColumn < columnIndex);
        });

        focusFirstInput(columnIndex);
    }

    function advanceColumn() {
        if (isSubmitting) return;

        if (currentColumn >= columnsCount - 1) {
            showChangeColumnModal('Tes selesai. Jawaban sedang diproses.');
            setTimeout(submitForm, 900);
            return;
        }

        const previousColumn = currentColumn;
        currentColumn++;
        activateColumn(currentColumn);
        showChangeColumnModal(`Lanjut ke kolom ${currentColumn + 1}`);

        clearTimeout(columnTimeout);
        columnTimeout = setTimeout(advanceColumn, columnDurationSeconds * 1000);
    }

    function focusFirstInput(columnIndex) {
        const firstInput = getColumnInputs(columnIndex)[0] ?? null;
        if (firstInput) focusInput(firstInput);
    }

    function showChangeColumnModal(message) {
        changeColumnTitle.textContent = transitionInstruction;
        changeColumnText.textContent = message;
        changeColumnModal.classList.remove('hidden');
        changeColumnModal.classList.add('flex');

        setTimeout(() => {
            changeColumnModal.classList.add('hidden');
            changeColumnModal.classList.remove('flex');
            focusFirstInput(currentColumn);
        }, 750);
    }

    function focusInput(input) {
        requestAnimationFrame(() => {
            input.focus();
            input.select();
        });
    }

    // Submit form
    async function submitForm() {
        if (isSubmitting) return;
        isSubmitting = true;
        clearTimeout(columnTimeout);

        inputs.forEach(input => input.disabled = true);
        submitStatus.classList.remove('hidden');

        // Collect answers
        const answers = {};
        inputs.forEach(input => {
            const row = parseInt(input.dataset.row);
            const col = input.dataset.col;
            const answerIndex = row - 1;

            if (!answers[col]) answers[col] = {};
            answers[col][answerIndex] = input.value || null;
        });

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    answers,
                    columns_data: document.getElementById('columnsData').value,
                }),
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Gagal menyimpan jawaban.');
            }

            window.location.href = data.redirect;
        } catch (error) {
            alert(error.message);
            submitStatus.classList.add('hidden');
            isSubmitting = false;
            activateColumn(currentColumn);
        }
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
    });

});
</script>
@endpush
