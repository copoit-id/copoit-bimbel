@if(isset($feedbackQuestions) && $feedbackQuestions->count() > 0 && !($feedbackSubmitted ?? false))
<div id="feedback-modal" class="fixed inset-0 z-50 hidden items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-[2px]" data-feedback-overlay></div>
    <div class="relative bg-white rounded-2xl w-full max-w-xl p-6 md:p-8 shadow-2xl border border-gray-100">
        <div class="flex items-start justify-between mb-5">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Feedback Tryout</h3>
                <p class="text-sm text-gray-500">Jawab satu per satu, skala 1 (rendah) sampai 5 (tinggi).</p>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl leading-none" data-feedback-close>&times;</button>
        </div>

        <div class="mb-5">
            <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
                <span id="feedback-progress-text">Soal 1 dari {{ $feedbackQuestions->count() }}</span>
                <span id="feedback-progress-percent">0%</span>
            </div>
            <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                <div id="feedback-progress-bar" class="h-full bg-primary rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>

        @if($errors->any())
        <div class="mb-4 text-sm text-red-600">
            Mohon lengkapi semua jawaban feedback terlebih dahulu.
        </div>
        @endif

        <form id="feedback-form" method="POST" action="{{ route('user.tryout.feedback.store', [$package ? $package->package_id : 'free', $tryout->tryout_id]) }}">
            @csrf
            <input type="hidden" name="attempt_token" value="{{ $feedbackAttemptToken ?? '' }}">

            @foreach($feedbackQuestions as $question)
            <div class="feedback-question {{ $loop->first ? '' : 'hidden' }}" data-feedback-question data-index="{{ $loop->index }}">
                <div class="mb-5">
                    <p class="text-sm text-gray-500 mb-1">Pertanyaan {{ $loop->iteration }}</p>
                    <p class="text-base font-semibold text-gray-900">{!! $question->question_text !!}</p>
                </div>
                <div class="mt-6">
                    <div class="relative h-10">
                        <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-1 w-full bg-gray-200 rounded-full"></div>
                        <div class="absolute left-0 right-0 top-8 -translate-y-1/2 grid grid-cols-5 gap-2 items-center">
                            @for($i = 1; $i <= 5; $i++)
                            <label class="flex flex-col items-center gap-2 text-xs text-gray-500 cursor-pointer">
                                <input type="radio"
                                    name="scores[{{ $question->feedback_question_id }}]"
                                    value="{{ $i }}"
                                    class="sr-only peer"
                                    {{ old('scores.' . $question->feedback_question_id) == $i ? 'checked' : '' }}>
                                <span class="w-5 h-5 rounded-full border-2 border-gray-300 bg-white flex items-center justify-center transition-all peer-checked:border-primary peer-checked:bg-primary peer-checked:ring-4 peer-checked:ring-primary/20 peer-checked:scale-110"></span>
                                <span class="font-medium text-gray-700">{{ $i }}</span>
                            </label>
                            @endfor
                        </div>
                    </div>
                </div>
                @error('scores.' . $question->feedback_question_id)
                <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                @enderror
            </div>
            @endforeach

            <div class="mt-8 flex justify-end">
                <button type="submit"
                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 hidden"
                    id="feedback-submit">Kirim Feedback</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('feedback-modal');
    if (!modal) return;

    const questions = Array.from(modal.querySelectorAll('[data-feedback-question]'));
    const total = questions.length;
    const progressText = modal.querySelector('#feedback-progress-text');
    const progressPercent = modal.querySelector('#feedback-progress-percent');
    const progressBar = modal.querySelector('#feedback-progress-bar');
    const submitBtn = modal.querySelector('#feedback-submit');

    let currentIndex = Math.max(0, questions.findIndex(q => !q.classList.contains('hidden')));
    if (currentIndex < 0) currentIndex = 0;

    const updateProgress = () => {
        const answered = questions.filter((q) => q.querySelector('input[type="radio"]:checked')).length;
        const percent = total > 0 ? Math.round((answered / total) * 100) : 0;
        if (progressText) progressText.textContent = `Soal ${currentIndex + 1} dari ${total}`;
        if (progressPercent) progressPercent.textContent = `${percent}%`;
        if (progressBar) progressBar.style.width = `${percent}%`;
    };

    const showQuestion = (index) => {
        questions.forEach((q, i) => q.classList.toggle('hidden', i !== index));
        currentIndex = index;
        submitBtn?.classList.toggle('hidden', index < total - 1);
        updateProgress();
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        showQuestion(currentIndex);
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    modal.querySelectorAll('[data-feedback-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    const overlay = modal.querySelector('[data-feedback-overlay]');
    if (overlay) {
        overlay.addEventListener('click', closeModal);
    }

    modal.querySelectorAll('input[type="radio"]').forEach((input) => {
        input.addEventListener('change', () => {
            const current = questions[currentIndex];
            current?.classList.add('opacity-80');
            updateProgress();
            if (currentIndex < total - 1) {
                setTimeout(() => {
                    current?.classList.remove('opacity-80');
                    showQuestion(currentIndex + 1);
                }, 200);
            } else {
                setTimeout(() => {
                    current?.classList.remove('opacity-80');
                    submitBtn?.classList.remove('hidden');
                }, 200);
            }
        });
    });

    openModal();
});
</script>
@endpush
@endif
