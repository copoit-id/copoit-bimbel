<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login - {{ $clientBranding['name'] }}</title>
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
    <x-website-translation-head />
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet" />

    @if($recaptcha_enabled)
    <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptcha_site_key }}"></script>
    @endif
    <style>
        .auth-page .auth-surface,
        .auth-page .auth-surface * { box-shadow: none !important; }
    </style>
</head>

<body class="auth-page bg-gray-50">
    @include('components.flash-alert')
    @php
        $backToDashboardUrl = \App\Models\GeneralPage::findActiveByKey('landing')
            ? route('landing')
            : route('user.dashboard.index');
    @endphp
    {{-- Announcement Modal - Hidden --}}
    {{-- <div id="announcement-modal" class="fixed inset-0 z-50 hidden items-center justify-center px-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-[2px]" data-announcement-close></div>
        <div class="relative bg-white rounded-2xl w-full max-w-lg p-6 md:p-8 shadow-2xl border border-gray-100">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center">
                        <i class="ri-megaphone-line text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-widest text-gray-400">Pengumuman</p>
                        <h3 class="text-xl font-bold text-gray-900">Info Akses Demo</h3>
                    </div>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl leading-none" data-announcement-close>&times;</button>
            </div>
            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700 leading-relaxed">
                Semua akses demo telah direset, silahkan hubungi admin untuk meminta akses demo terbaru.
            </div>
            <div class="mt-6 flex justify-end">
                <button type="button" class="px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800" data-announcement-close>
                    Mengerti
                </button>
            </div>
        </div>
    </div> --}}
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6">
            <x-auth.header title="Masuk ke Akun Anda" prompt="Belum punya akun?" :href="route('register')" link-label="Daftar sekarang" />

            <x-ui.card padding="lg" class="auth-surface space-y-6 rounded-2xl">
                <x-auth.error-summary />

                <form class="space-y-6" action="{{ route('login.authenticate') }}" method="POST" id="loginForm">
                    @csrf
                    <div class="space-y-4">
                        <x-ui.input name="email" type="email" label="Email" :value="old('email')" required autocomplete="email" />
                        <x-ui.input name="password" type="password" label="Password" required autocomplete="current-password" />
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <x-ui.checkbox name="remember" label="Ingat saya" :checked="old('remember')" />

                        <div class="text-sm">
                            <a href="{{ route('password.request') }}" class="font-medium text-primary hover:text-primary/80">Lupa password?</a>
                        </div>
                    </div>

                    <!-- Hidden field for reCAPTCHA v3 token -->
                    @if($recaptcha_enabled)
                    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                    @endif

                    <x-ui.button type="submit" id="submitBtn" icon="ri-login-box-line" :full-width="true">Masuk</x-ui.button>

                    @if($recaptcha_enabled)
                    <div class="text-center">
                        <p class="text-xs text-gray-500">
                            Situs ini dilindungi oleh reCAPTCHA dan berlaku
                            <a href="https://policies.google.com/privacy" class="text-primary hover:underline" target="_blank">Kebijakan Privasi</a> dan
                            <a href="https://policies.google.com/terms" class="text-primary hover:underline" target="_blank">Persyaratan Layanan</a> Google.
                        </p>
                    </div>
                    @endif
                </form>
            </x-ui.card>

            <x-auth.legal-links />

            <div class="text-center">
                <a href="{{ $backToDashboardUrl }}"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-primary transition-colors">
                    <i class="ri-arrow-left-line text-sm"></i>
                    <span>Back to dashboard</span>
                </a>
            </div>
        </div>
    </div>

    @if($recaptcha_enabled)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i>Memverifikasi...';

                grecaptcha.ready(function() {
                    grecaptcha.execute('{{ $recaptcha_site_key }}', {
                        action: 'login'
                    }).then(function(token) {
                        document.getElementById('g-recaptcha-response').value = token;
                        form.submit();
                    }).catch(function(error) {
                        console.error('reCAPTCHA error:', error);
                        // Reset button state
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<span class="absolute left-0 inset-y-0 flex items-center pl-3"><i class="ri-login-box-line text-primary/60 group-hover:text-primary/80"></i></span>Masuk';
                        alert('Terjadi kesalahan pada verifikasi reCAPTCHA. Silakan coba lagi.');
                    });
                });
            });
        });
    </script>
    @endif

    {{-- Announcement Modal Script - Hidden --}}
    {{-- <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('announcement-modal');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            modal.querySelectorAll('[data-announcement-close]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                });
            });
        });
    </script> --}}
    <x-website-translator />
</body>

</html>
