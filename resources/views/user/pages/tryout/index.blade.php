@extends('user.layout.tryout')
@section('title', 'Tryout - Soal ' . $number)
@section('content')
<div class="min-h-screen bg-gray-50 pt-16">
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Question Section -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-lg border border-border p-6">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-6 pb-4 border-b border-border">
                        <div class="flex w-full justify-between">
                            <h2 class="text-xl font-bold text-gray-800">Soal {{ $number }} dari {{ $totalQuestions }}
                            </h2>
                            @if(isset($currentSubtest))
                            <p class="text-sm text-gray-600 mt-1">{{ $currentSubtest['name'] }}</p>
                            @endif
                        </div>
                        <div id="timer" hidden class="text-2xl font-bold text-primary">00:00:00</div>
                    </div>

                    <!-- Question Content -->
                    <div class="mb-8">
                        <div class="text-gray-700 leading-relaxed">
                            {!! $currentQuestion->question_text !!}
                        </div>

                        @if($currentQuestion->sound)
                        <div class="mt-4">
                            <audio id="audio-{{ $currentQuestion->question_id }}" controls controlsList="nodownload"
                                oncontextmenu="return false;" class="w-full">
                                <source src="{{ Storage::url($currentQuestion->sound) }}" type="audio/mpeg">
                                Browser Anda tidak mendukung audio.
                            </audio>
                        </div>
                        @endif
                    </div>

                    <!-- Answer Options -->
                    <form id="answerForm">
                        @csrf
                        <input type="hidden" name="question_id" value="{{ $currentQuestion->question_id }}">
                        <input type="hidden" name="option_id" id="selectedOptionId">
                        <input type="hidden" name="selected_option" id="selectedOption">

                        <div class="space-y-3">
                            @foreach($currentQuestion->questionOptions as $option)
                            <label
                                class="flex items-center p-4 border rounded-lg cursor-pointer transition-all duration-200
                                          hover:border-primary hover:bg-primary/5 answer-option-label
                                          {{ $userAnswerDetail && $userAnswerDetail->question_option_id == $option->question_option_id ? 'border-primary bg-primary/10 ring-1 ring-primary' : 'border-gray-200' }}"
                                for="option_{{ $option->question_option_id }}">
                                <input type="radio" id="option_{{ $option->question_option_id }}" name="answer_option"
                                    value="{{ $option->question_option_id }}"
                                    data-option-key="{{ $option->option_key ?? 'A' }}" {{ $userAnswerDetail &&
                                    $userAnswerDetail->question_option_id == $option->question_option_id ? 'checked' :
                                '' }}
                                class="w-5 h-5 text-primary border-gray-300 focus:ring-primary answer-radio">
                                <span class="ml-4 flex-1 text-gray-700">
                                    {!! $option->option_text !!}
                                </span>
                            </label>
                            @endforeach
                        </div>
                    </form>

                    <!-- Navigation -->
                    <div class="mt-8 flex justify-between items-center pt-6 border-t border-border">
                        @if($number > 1)
                        <div class="flex gap-3">
                            <a href="{{ route('user.tryout.index', [$package ? $package->package_id : 'free', $tryout->tryout_id, $number - 1]) }}"
                                class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="ri-arrow-left-line mr-2"></i>Sebelumnya
                            </a>
                        </div>
                        @endif
                        <div>
                            <button onclick="flagQuestion()"
                                class="px-4 py-2 border border-red text-red rounded-lg hover:bg-red hover:text-white transition-colors flag-btn">
                                <i class="ri-flag-line mr-2"></i>
                                <span class="flag-text">{{ in_array($currentQuestion->question_id, $flaggedQuestions) ?
                                    'Batal Tandai' : 'Tandai' }}</span>
                            </button>
                        </div>

                        <div class="flex gap-3">
                            @if($number < $totalQuestions) <a
                                href="{{ route('user.tryout.index', [$package ? $package->package_id : 'free', $tryout->tryout_id, $number + 1]) }}"
                                class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                                Selanjutnya<i class="ri-arrow-right-line ml-2"></i>
                                </a>
                                @else
                                <form
                                    action="{{ route('user.tryout.finish', [$package ? $package->package_id : 'free', $tryout->tryout_id]) }}"
                                    method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                        onclick="return confirm('Apakah Anda yakin ingin menyelesaikan tryout ini?')"
                                        class="px-6 py-2 bg-green text-white rounded-lg hover:bg-green-700 transition-colors">
                                        <i class="ri-check-line mr-2"></i>Selesai
                                    </button>
                                </form>
                                @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg border border-border p-6 sticky top-6 text-center">
                    <p class="text-sm text-gray-600 mb-2">Sisa Waktu</p>
                    <div id="timer-display" class="text-3xl font-bold text-primary">00:00:00</div>
                </div>
                <div class="bg-white rounded-lg border border-border p-6 sticky mt-4">
                    <!-- Question Navigation -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-800 mb-4">Navigasi Soal</h3>
                        <div class="grid grid-cols-5 gap-2">
                            @foreach($allQuestions as $index => $question)
                            @php
                            $questionNumber = $index + 1;
                            $isAnswered = in_array($question->question_id, $userAnswerDetails);
                            $isFlagged = in_array($question->question_id, $flaggedQuestions);
                            $isCurrent = $questionNumber == $number;
                            @endphp
                            <a href="{{ route('user.tryout.index', [$package ? $package->package_id : 'free', $tryout->tryout_id, $questionNumber]) }}"
                                class="relative w-10 h-10 flex items-center justify-center text-sm font-medium rounded-lg transition-colors
                                      {{ $isCurrent ? 'bg-primary text-white' :
                                         ($isAnswered ? 'bg-green text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200') }}">
                                {{ $questionNumber }}
                                @if($isFlagged)
                                <i class="ri-flag-fill absolute -top-1 -right-1 text-xs text-red"></i>
                                @endif
                            </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="text-sm">
                        <h4 class="font-semibold text-gray-800 mb-3">Keterangan</h4>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-primary rounded"></div>
                                <span class="text-gray-600">Soal saat ini</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-green rounded"></div>
                                <span class="text-gray-600">Sudah dijawab</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-gray-100 rounded"></div>
                                <span class="text-gray-600">Belum dijawab</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="ri-flag-fill text-red"></i>
                                <span class="text-gray-600">Ditandai</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- SKD Progress (if multiple subtests) -->
                @if(isset($subtestInfo) && count($subtestInfo) > 1)
                <div class="mb-6 p-4 bg-white border border-border mt-4 rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-gray-700">Progress SKD Full</span>
                        <span class="text-sm text-gray-600">{{ count($subtestInfo) }} Subtest</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach($subtestInfo as $index => $subtest)
                        @php
                        $isCurrentSubtest = $currentSubtest && $currentSubtest['type'] === $subtest['type'];
                        $isCompleted = $number > $subtest['end_number'];
                        @endphp
                        <div class="text-center">
                            <div class="w-8 h-8 rounded-full mx-auto mb-1 flex items-center justify-center text-sm font-semibold
                                    {{ $isCompleted ? 'bg-green text-white' :
                                       ($isCurrentSubtest ? 'bg-primary text-white' : 'bg-gray-200 text-gray-600') }}">
                                {{ $index + 1 }}
                            </div>
                            <p class="text-xs text-gray-600">{{ strtoupper($subtest['type']) }}</p>
                            @if($isCurrentSubtest)
                            <div class="mt-2">
                                @php
                                $subtestProgress = (($number - $subtest['start_number'] + 1) /
                                ($subtest['end_number'] - $subtest['start_number'] + 1)) * 100;
                                @endphp
                                <div class="w-full bg-gray-200 rounded-full h-1">
                                    <div class="bg-primary h-1 rounded-full" style="width: {{ $subtestProgress }}%">
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    console.log('Tryout page loaded');

    const form = document.getElementById('answerForm');
    const saveUrl = '{{ route("user.tryout.save", [$package ? $package->package_id : "free", $tryout->tryout_id, $number]) }}';

    // Timer configuration
    @php
        $now = \Carbon\Carbon::now('Asia/Jakarta');
        $startTime = \Carbon\Carbon::parse($currentUserAnswer->started_at, 'Asia/Jakarta');

        if (isset($tryoutDetails) && $tryoutDetails->count() > 1) {
            $totalDuration = $tryoutDetails->sum('duration');
        } else {
            $totalDuration = $tryoutDetails->first()->duration ?? 60;
        }

        $endTime = $startTime->copy()->addMinutes($totalDuration);

        if ($now->lt($endTime)) {
            $remainingSecondsCalc = (int) $now->diffInSeconds($endTime);
        } else {
            $remainingSecondsCalc = 0;
        }
    @endphp

    let timeLeft = {{ $remainingSecondsCalc }};
    let timerInterval;

    function updateTimer() {
        if (timeLeft <= 0) {
            document.getElementById('timer').textContent = '00:00:00';
            document.getElementById('timer-display').textContent = '00:00:00';

            clearInterval(timerInterval);

            alert('Waktu ujian telah habis!');

            // Auto submit finish form
            const finishForm = document.createElement('form');
            finishForm.method = 'POST';
            finishForm.action = '{{ route("user.tryout.finish", [$package ? $package->package_id : "free", $tryout->tryout_id]) }}';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            finishForm.appendChild(csrfToken);

            document.body.appendChild(finishForm);
            finishForm.submit();
            return;
        }

        const hours = Math.floor(timeLeft / 3600);
        const minutes = Math.floor((timeLeft % 3600) / 60);
        const seconds = timeLeft % 60;

        const display = hours.toString().padStart(2, '0') + ':' +
                       minutes.toString().padStart(2, '0') + ':' +
                       seconds.toString().padStart(2, '0');

        document.getElementById('timer').textContent = display;
        document.getElementById('timer-display').textContent = display;

        // Warning colors
        if (timeLeft <= 300) { // 5 minutes
            document.getElementById('timer').style.color = '#dc2626';
            document.getElementById('timer-display').style.color = '#dc2626';
        } else if (timeLeft <= 600) { // 10 minutes
            document.getElementById('timer').style.color = '#f59e0b';
            document.getElementById('timer-display').style.color = '#f59e0b';
        }

        timeLeft--;
    }

    // Start timer
    timerInterval = setInterval(updateTimer, 1000);
    updateTimer(); // Run immediately

    // Auto-save on answer selection
    const radioButtons = document.querySelectorAll('.answer-radio');
    console.log('Found radio buttons:', radioButtons.length);

    radioButtons.forEach(radio => {
        radio.addEventListener('change', function(e) {
            console.log('Radio button changed:', this.value, this.dataset.optionKey);

            // Update hidden fields
            document.getElementById('selectedOptionId').value = this.value;
            document.getElementById('selectedOption').value = this.dataset.optionKey;

            // Update visual feedback
            document.querySelectorAll('.answer-option-label').forEach(label => {
                label.classList.remove('border-primary', 'bg-primary/10', 'ring-1', 'ring-primary');
                label.classList.add('border-gray-200');
            });

            this.closest('.answer-option-label').classList.remove('border-gray-200');
            this.closest('.answer-option-label').classList.add('border-primary', 'bg-primary/10', 'ring-1', 'ring-primary');

            // Auto-save immediately
            autoSaveAnswer();
        });
    });

    function autoSaveAnswer() {
        const selectedOption = document.querySelector('.answer-radio:checked');
        if (!selectedOption) {
            console.log('No option selected');
            return;
        }

        console.log('Auto-saving answer...');

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('question_id', '{{ $currentQuestion->question_id }}');
        formData.append('option_id', selectedOption.value);
        formData.append('selected_option', selectedOption.dataset.optionKey);

        // Log what we're sending
        console.log('Sending data:', {
            question_id: '{{ $currentQuestion->question_id }}',
            option_id: selectedOption.value,
            selected_option: selectedOption.dataset.optionKey
        });

        fetch(saveUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Save response:', data);
            if (data.success) {
                console.log('Answer saved successfully');
                showSaveIndicator(true);
            } else {
                console.error('Failed to save answer:', data.message);
                showSaveIndicator(false);
            }
        })
        .catch(error => {
            console.error('Error saving answer:', error);
            showSaveIndicator(false);
        });
    }

    function showSaveIndicator(success) {
        // Remove existing indicators
        const existingIndicators = document.querySelectorAll('.save-indicator');
        existingIndicators.forEach(indicator => indicator.remove());

        // Create new indicator
        const indicator = document.createElement('div');
        indicator.className = `save-indicator fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-white ${success ? 'bg-green' : 'bg-red'}`;
        indicator.textContent = success ? 'Jawaban tersimpan' : 'Gagal menyimpan';

        document.body.appendChild(indicator);

        // Remove after 2 seconds
        setTimeout(() => {
            indicator.remove();
        }, 2000);
    }

    // Flag question function
    window.flagQuestion = function() {
        fetch('{{ route("user.tryout.flag", [$package ? $package->package_id : "free", $tryout->tryout_id]) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                question_id: {{ $currentQuestion->question_id }}
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const flagBtn = document.querySelector('.flag-btn');
                const flagText = document.querySelector('.flag-text');
                const flagIcon = flagBtn.querySelector('i');

                if (data.flagged) {
                    flagText.textContent = 'Batal Tandai';
                    flagIcon.className = 'ri-flag-fill mr-2';
                } else {
                    flagText.textContent = 'Tandai';
                    flagIcon.className = 'ri-flag-line mr-2';
                }

                console.log('Flag toggled successfully');
            }
        })
        .catch(error => console.error('Error:', error));
    };
});
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
    const audio = document.getElementById("audio-{{ $currentQuestion->question_id }}");
    let alreadyMarked = {{ $userAnswerDetail && $userAnswerDetail->is_played ? 'true' : 'false' }};

   if (alreadyMarked) {
        // Disable audio
        audio.controls = false;
        audio.removeAttribute("controls");

        // Tambahkan badge menarik
        const badge = document.createElement("div");
        badge.className = "mt-2 inline-flex items-center gap-2 px-4 py-2 bg-primary text-white font-bold rounded-full  animate-fade-in";
        badge.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                           </svg>
                           Audio sudah diputar`;

        audio.insertAdjacentElement('afterend', badge);
        return;
    }

    audio.addEventListener("play", function() {
        if (!alreadyMarked) {
            fetch("{{ route('user.tryout.markPlayed', [$package->id ?? 'free', $tryout->tryout_id, $currentQuestion->question_id]) }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    alreadyMarked = true;
                }
            });
        }
    }, { once: true });
});
</script>

@endsection