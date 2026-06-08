@extends('user.layout.new-user')

@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$effectiveDirection = $tesKoran->test_type === 'kraepelin' ? 'bottom_to_top' : 'top_to_bottom';
@endphp

@section('title', $tesKoran->name)

@section('styles')
<style>
    /* Styling for the column containers */
    .column-container {
        border: 2px solid transparent;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1;
    }

    /* Column states */
    .column-active {
        background-color: color-mix(in srgb, {{ $primaryColor }} 4%, transparent) !important;
        border-color: color-mix(in srgb, {{ $primaryColor }} 15%, transparent) !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.02), 0 8px 10px -6px rgba(0, 0, 0, 0.02) !important;
        opacity: 1 !important;
        transform: scale(1.02);
    }

    .column-past {
        opacity: 1 !important;
        pointer-events: none;
    }

    .column-future {
        opacity: 1 !important;
        pointer-events: none;
    }

    /* Number Bubble Highlighting */
    .num-highlight {
        background-color: {{ $primaryColor }} !important;
        border-color: {{ $primaryColor }} !important;
        transform: scale(1.15) !important;
        box-shadow: 0 4px 12px -2px color-mix(in srgb, {{ $primaryColor }} 30%, transparent) !important;
    }
    
    .num-highlight span {
        color: #ffffff !important;
    }

    /* Custom Input States */
    .tes-koran-input {
        width: 3.5rem; /* w-14 */
        height: 2.5rem; /* h-10 */
        font-family: monospace;
        font-size: 1.25rem; /* text-xl */
        font-weight: 800;
        text-align: center;
        border-width: 2px;
        border-color: #e5e7eb;
        border-radius: 0.75rem; /* rounded-xl */
        background-color: #ffffff;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        outline: none;
        transition: all 0.15s ease-in-out;
    }

    .tes-koran-input:focus {
        border-color: {{ $primaryColor }} !important;
        box-shadow: 0 0 0 4px color-mix(in srgb, {{ $primaryColor }} 15%, transparent) !important;
    }

    .tes-koran-input:disabled {
        background-color: #f9fafb !important;
        color: #d1d5db !important;
        border-color: #f3f4f6 !important;
        box-shadow: none !important;
    }

    /* Smooth Scrolling */
    .grid-scroll-container {
        scrollbar-width: thin;
        scroll-behavior: smooth;
    }
</style>
@endsection

@section('content')

<div class="bg-white rounded-3xl shadow-xl shadow-gray-100/40 border border-gray-100/80 overflow-hidden transition-all duration-300">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 p-6 border-b border-gray-100 bg-gradient-to-r from-gray-50/50 to-white">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full text-white bg-primary">
                    <i class="ri-survey-line mr-1"></i>{{ $tesKoran->test_type == 'pauli' ? 'Pauli' : 'Kraepelin' }}
                </span>
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full text-emerald-700 bg-emerald-50 border border-emerald-100/80">
                    <i class="ri-calculator-line mr-1"></i>{{ $tesKoran->operationLabel() }}
                </span>
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full text-blue-700 bg-blue-50 border border-blue-100/80">
                    <i class="ri-direction-line mr-1"></i>{{ $effectiveDirection == 'top_to_bottom' ? 'Atas ke Bawah' : 'Bawah ke Atas' }}
                </span>
            </div>
            <h2 class="text-xl font-extrabold text-gray-800 mt-2.5 tracking-tight">{{ $tesKoran->name }}</h2>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right">
                <div id="currentColumnLabel" class="text-2xl font-black text-primary tracking-tight">Kolom 1</div>
                <p class="text-xs font-semibold text-gray-400">Sedang Dikerjakan</p>
            </div>
        </div>
    </div>

    <!-- Progress Timeline / Dashboard -->
    <div class="px-6 py-3.5 border-b border-gray-100 bg-gray-50/20 flex flex-wrap items-center justify-between gap-4">
        <div class="text-xs font-bold text-gray-500 flex items-center gap-1.5">
            <i class="ri-loader-3-line text-primary animate-spin"></i>
            Progres Kolom: <span id="colProgressText" class="text-primary">1 / {{ $tesKoran->columns_count }}</span>
        </div>
        <div class="flex-1 min-w-[200px] max-w-md bg-gray-100 h-2.5 rounded-full overflow-hidden">
            <div id="colProgressBar" class="bg-primary h-full rounded-full transition-all duration-500 ease-out" style="width: 0%;"></div>
        </div>
        <div class="text-xs font-bold text-gray-500 flex items-center gap-1">
            <i class="ri-check-double-line text-emerald-500 text-sm"></i>
            Terisi: <span id="answeredCount" class="text-emerald-600 font-extrabold">0</span>
        </div>
    </div>

    <!-- Instructions -->
    <div id="instructionPanel" class="mx-6 my-4 p-4 bg-blue-50/50 rounded-2xl border border-blue-100/80 transition-all duration-300">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <div class="p-2.5 bg-blue-100/40 rounded-xl text-blue-600">
                    <i class="ri-information-line text-xl"></i>
                </div>
                <div class="text-sm text-blue-800/90 leading-relaxed">
                    <p class="font-bold text-blue-900 mb-1">Petunjuk Pengisian:</p>
                    <ul class="space-y-1.5 list-disc list-inside text-blue-800/80 font-medium">
                        <li>Kerjakan hitungan secara berurutan (<strong>{{ $effectiveDirection == 'top_to_bottom' ? 'dari atas ke bawah' : 'dari bawah ke atas' }}</strong>).</li>
                        <li>Tulis hanya <strong>angka satuan</strong> dari hasil hitung (contoh: 5 + 7 = 12, maka ketik <strong>2</strong>).</li>
                        <li>Tekan <strong>Enter</strong>, <strong>Tab</strong>, atau <strong>Spasi</strong> untuk lanjut ke baris berikutnya.</li>
                        <li>Waktu per kolom terbatas. Ketika waktu habis, Anda akan otomatis berpindah kolom.</li>
                    </ul>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('instructionPanel').remove()" class="text-blue-400 hover:text-blue-600 hover:bg-blue-100/40 p-1.5 rounded-lg transition-all" title="Tutup Petunjuk">
                <i class="ri-close-line text-lg"></i>
            </button>
        </div>
    </div>

    <!-- Test Grid -->
    <div class="p-6 overflow-x-auto grid-scroll-container">
        <form id="tesKoranForm" method="POST" action="{{ route('user.tes-koran.start', $tesKoran) }}">
            @csrf
            <input type="hidden" name="columns_data" id="columnsData" value="{{ $columnsJson }}">

            <div class="flex gap-8 min-w-full pb-4">
                <!-- Sticky Row Numbers -->
                <div class="sticky left-0 bg-white/95 backdrop-blur-sm z-20 pr-4 pl-2 flex flex-col items-center border-r border-gray-200/60 shadow-sm rounded-l-2xl">
                    <div class="w-10 text-center mb-4 text-xs font-extrabold text-gray-400 border-b-2 border-transparent pb-1">Baris</div>
                    
                    @for($r = 0; $r < $tesKoran->rows_count; $r++)
                    <div class="h-16 flex items-center justify-center text-xs font-bold text-gray-400 font-mono">
                        {{ $r + 1 }}
                    </div>
                    @endfor
                </div>

                <!-- Columns -->
                @for($c = 0; $c < $tesKoran->columns_count; $c++)
                <div data-column-container="{{ $c }}" class="column-container relative flex flex-col items-start min-w-[8rem] w-32 py-6 px-0 rounded-2xl transition-all duration-300">
                    <!-- Vertical Connective Line -->
                    <div class="absolute left-[36px] top-16 bottom-6 w-0.5 bg-gray-150/85 z-0"></div>

                    <!-- Column Header -->
                    <div data-col-header="{{ $c }}" class="z-10 w-10 text-center ml-4 mb-4 text-xs font-bold text-gray-400 border-b-2 border-gray-100 pb-1 transition-all duration-300">
                        {{ $c + 1 }}
                    </div>

                    <!-- Row 0: Digit Only -->
                    <div class="z-10 h-16 w-full flex items-center justify-start pl-4 relative">
                        <div class="w-10 h-10 flex items-center justify-center rounded-full bg-white border-2 border-gray-200 shadow-sm transition-all duration-200">
                            <span data-num-cell="{{ $c }}-0" class="text-base font-black text-gray-700 font-mono transition-all duration-200">
                                {{ $columns[$c][0] }}
                            </span>
                        </div>
                    </div>

                    <!-- Rows 1 to N-1: Digit + Input on the side, centered vertically between this digit and the one above it -->
                    @for($r = 1; $r < $tesKoran->rows_count; $r++)
                    <div class="z-10 h-16 w-full flex items-center justify-start pl-4 relative">
                        <!-- Digit Tile -->
                        <div class="w-10 h-10 flex items-center justify-center rounded-full bg-white border-2 border-gray-200 shadow-sm transition-all duration-200">
                            <span data-num-cell="{{ $c }}-{{ $r }}" class="text-base font-black text-gray-700 font-mono transition-all duration-200">
                                {{ $columns[$c][$r] }}
                            </span>
                        </div>
                        
                        <!-- Input Box (Centered vertically at top border between digits) -->
                        <div class="absolute top-0 left-16 -translate-y-1/2 z-20">
                            <input type="text"
                                   maxlength="{{ $tesKoran->answerMaxLength() }}"
                                   pattern="[0-9]"
                                   inputmode="numeric"
                                   data-row="{{ $r }}"
                                   data-col="{{ $c }}"
                                   class="tes-koran-input bg-white"
                                   autocomplete="off">
                        </div>
                    </div>
                    @endfor
                </div>
                @endfor
            </div>
            
            <div class="mt-8 flex items-center justify-end">
                <div id="submitStatus" class="hidden items-center gap-2 px-4 py-2 bg-gray-50 border border-gray-100 rounded-xl text-sm font-semibold text-gray-600 animate-pulse">
                    <i class="ri-loader-4-line animate-spin text-primary text-base"></i>
                    <span>Memproses jawaban, mohon tunggu...</span>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Transition Modal -->
<div id="changeColumnModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4 transition-all duration-300">
    <div class="w-full max-w-xs rounded-2xl bg-white p-8 text-center shadow-2xl border border-gray-100/80 transform scale-95 opacity-0 transition-all duration-300">
        <div class="w-16 h-16 bg-primary/10 text-primary rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce">
            <i class="ri-refresh-line text-2xl font-bold"></i>
        </div>
        <div id="changeColumnTitle" class="text-3xl font-black tracking-wider text-primary">PINDAH!</div>
        <div id="changeColumnText" class="mt-3 text-sm font-semibold text-gray-500">Lanjut ke kolom berikutnya</div>
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
    const storageKey = 'tes_koran_answers:{{ auth()->id() }}:{{ $tesKoran->id }}:{{ md5($columnsJson) }}';

    restoreAnswersFromLocal();
    startColumnFlow();
    updateCount();

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
            saveAnswersToLocal();
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
            if (e.key === 'Enter' || e.key === 'Tab' || e.key === ' ') {
                e.preventDefault();
                moveToNextInput(this);
            } else if (e.key === 'Backspace' && this.value === '') {
                e.preventDefault();
                moveToPrevInput(this);
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                moveToNextInput(this);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                moveToPrevInput(this);
            }
        });

        // Addend number bubble highlighting on focus
        const row = parseInt(input.dataset.row);
        const col = parseInt(input.dataset.col);

        input.addEventListener('focus', function() {
            const numTile1 = document.querySelector(`[data-num-cell="${col}-${row}"]`)?.closest('.rounded-full');
            const numTile2 = document.querySelector(`[data-num-cell="${col}-${row - 1}"]`)?.closest('.rounded-full');
            if (numTile1) numTile1.classList.add('num-highlight');
            if (numTile2) numTile2.classList.add('num-highlight');
        });

        input.addEventListener('blur', function() {
            const numTile1 = document.querySelector(`[data-num-cell="${col}-${row}"]`)?.closest('.rounded-full');
            const numTile2 = document.querySelector(`[data-num-cell="${col}-${row - 1}"]`)?.closest('.rounded-full');
            if (numTile1) numTile1.classList.remove('num-highlight');
            if (numTile2) numTile2.classList.remove('num-highlight');
        });
    });

    function collectAnswers() {
        const answers = {};

        inputs.forEach(input => {
            const row = parseInt(input.dataset.row);
            const col = input.dataset.col;
            const answerIndex = row - 1;

            if (!answers[col]) answers[col] = {};
            answers[col][answerIndex] = input.value || null;
        });

        return answers;
    }

    function saveAnswersToLocal() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(collectAnswers()));
        } catch (error) {
            console.warn('Gagal menyimpan jawaban lokal.', error);
        }
    }

    function restoreAnswersFromLocal() {
        try {
            const savedAnswers = JSON.parse(localStorage.getItem(storageKey) || '{}');

            inputs.forEach(input => {
                const col = input.dataset.col;
                const answerIndex = parseInt(input.dataset.row) - 1;
                const savedAnswer = savedAnswers?.[col]?.[answerIndex];

                if (savedAnswer !== null && savedAnswer !== undefined && savedAnswer !== '') {
                    input.value = String(savedAnswer).replace(disallowedInputPattern, '').slice(-1);
                }
            });
        } catch (error) {
            console.warn('Gagal memulihkan jawaban lokal.', error);
        }
    }

    function clearStoredAnswers() {
        try {
            localStorage.removeItem(storageKey);
        } catch (error) {
            console.warn('Gagal menghapus jawaban lokal.', error);
        }
    }

    function moveToNextInput(input) {
        const columnInputs = getColumnInputs(parseInt(input.dataset.col));
        const currentIndex = columnInputs.indexOf(input);
        const nextInput = columnInputs[currentIndex + 1] ?? null;

        if (nextInput) {
            focusInput(nextInput);
        }
    }

    function moveToPrevInput(input) {
        const columnInputs = getColumnInputs(parseInt(input.dataset.col));
        const currentIndex = columnInputs.indexOf(input);
        const prevInput = columnInputs[currentIndex - 1] ?? null;

        if (prevInput) {
            focusInput(prevInput);
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

    // Initialize Column Flow
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

        // Update progress bar
        const progressPercentage = (columnIndex / columnsCount) * 100;
        const colProgressBar = document.getElementById('colProgressBar');
        const colProgressText = document.getElementById('colProgressText');
        if (colProgressBar) colProgressBar.style.width = `${progressPercentage}%`;
        if (colProgressText) colProgressText.textContent = `${columnIndex + 1} / ${columnsCount}`;

        inputs.forEach(input => {
            const inputColumn = parseInt(input.dataset.col);
            input.disabled = inputColumn !== columnIndex;
        });

        // Set column container states
        document.querySelectorAll('[data-column-container]').forEach(col => {
            const colIndex = parseInt(col.dataset.columnContainer);
            col.classList.remove('column-active', 'column-past', 'column-future');
            
            const header = col.querySelector('[data-col-header]');
            if (header) {
                header.classList.remove('text-primary', 'border-primary', 'font-black', 'text-gray-400', 'border-gray-100');
            }

            if (colIndex === columnIndex) {
                col.classList.add('column-active');
                if (header) {
                    header.classList.remove('text-gray-400', 'border-gray-100');
                    header.classList.add('text-primary', 'border-primary', 'font-black');
                }
            } else {
                col.classList.add(colIndex < columnIndex ? 'column-past' : 'column-future');
                if (header) {
                    header.classList.remove('text-primary', 'border-primary', 'font-black');
                    header.classList.add('text-gray-400', 'border-gray-100');
                }
            }
        });

        // Center active column in horizontal scroll
        const activeCol = document.querySelector(`[data-column-container="${columnIndex}"]`);
        if (activeCol) {
            activeCol.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }

        focusFirstInput(columnIndex);
    }

    function advanceColumn() {
        if (isSubmitting) return;

        if (currentColumn >= columnsCount - 1) {
            showChangeColumnModal('Tes selesai. Jawaban sedang diproses.');
            setTimeout(submitForm, 900);
            return;
        }

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
        
        requestAnimationFrame(() => {
            const content = changeColumnModal.querySelector('.transform');
            if (content) {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }
        });

        setTimeout(() => {
            const content = changeColumnModal.querySelector('.transform');
            if (content) {
                content.classList.remove('scale-100', 'opacity-100');
                content.classList.add('scale-95', 'opacity-0');
            }
            
            setTimeout(() => {
                changeColumnModal.classList.add('hidden');
                changeColumnModal.classList.remove('flex');
                focusFirstInput(currentColumn);
            }, 200);
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
        submitStatus.classList.add('flex');

        const answers = collectAnswers();

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

            clearStoredAnswers();
            window.location.href = data.redirect;
        } catch (error) {
            alert(error.message);
            submitStatus.classList.add('hidden');
            submitStatus.classList.remove('flex');
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
