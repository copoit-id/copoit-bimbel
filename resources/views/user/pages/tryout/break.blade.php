@extends('user.layout.tryout')

@section('title', 'Jeda Subtest')

@section('content')
<div class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-primary"></div>
    <div class="relative flex flex-col h-full w-full items-center justify-center px-6 py-10 sm:px-10">
        <div class="max-w-3xl w-full text-center text-white space-y-10">
            <div class="space-y-4">
                <p class="uppercase tracking-[0.55em] text-xs text-white/70">Jeda Antar Subtest</p>
                <h1 class="text-3xl md:text-4xl font-bold leading-tight">
                    {{ $subtest['name'] ?? 'Subtest Berikutnya' }}
                </h1>
                <p class="text-white/90 text-base md:text-lg leading-relaxed">
                    Subtest ke-{{ $subtestIndex }} dari {{ $totalSubtests }} akan dimulai setelah hitung mundur selesai.
                    Tarik napas, regangkan badan, dan siapkan fokus terbaikmu.
                </p>
            </div>

            <div class="flex flex-col items-center gap-3">
                <div class="text-6xl md:text-7xl font-mono font-semibold drop-shadow-lg" id="break-countdown">00:00
                </div>
                <p class="text-sm text-white/70">Gunakan jeda ini untuk menjaga ritme ujianmu</p>
            </div>

            <div class="pt-2 flex flex-col items-center gap-4">
                <button id="break-continue" disabled
                    class="w-full sm:w-auto px-12 py-3 rounded-full bg-white/10 text-white/80 uppercase tracking-[0.35em] text-xs font-semibold transition-all duration-300 cursor-not-allowed border border-white/40">
                    Menunggu...
                </button>
                <p class="text-xs text-white/60">Halaman akan otomatis berlanjut ketika hitung mundur selesai.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (() => {
        document.documentElement.classList.add('overflow-hidden');
        document.body.classList.add('overflow-hidden');

        const countdownTarget = document.getElementById('break-countdown');
        const continueBtn = document.getElementById('break-continue');
        const continueUrl = @json($continueUrl);
        let remaining = {{ max(1, (int) ($countdownSeconds ?? 1)) }};
        let redirected = false;

        const formatTime = (seconds) => {
            const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
            const secs = (seconds % 60).toString().padStart(2, '0');
            return `${mins}:${secs}`;
        };

        const enableContinue = () => {
            continueBtn.disabled = false;
            continueBtn.classList.remove('bg-white/10', 'text-white/80', 'cursor-not-allowed');
            continueBtn.classList.add('bg-white', 'text-primary', 'shadow-lg');
            continueBtn.textContent = 'Mulai Subtest';
        };

        const tick = () => {
            countdownTarget.textContent = formatTime(Math.max(0, remaining));

            if (remaining <= 0) {
                if (continueBtn.disabled) {
                    enableContinue();
                }
                if (!redirected) {
                    redirected = true;
                    setTimeout(() => {
                        window.location.href = continueUrl;
                    }, 1200);
                }
                return;
            }

            remaining -= 1;
            setTimeout(tick, 1000);
        };

        continueBtn.addEventListener('click', () => {
            if (continueBtn.disabled) {
                return;
            }
            window.location.href = continueUrl;
        });

        window.addEventListener('beforeunload', () => {
            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        });

        tick();
    })();
</script>
@endsection
