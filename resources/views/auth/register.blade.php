<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ __('Register') }} - {{ $clientBranding['name'] }}</title>
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet" />

    @if($recaptcha_enabled)
    <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptcha_site_key }}"></script>
    @endif
</head>

<body class="bg-gray-50">
    @include('components.flash-alert')
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="flex justify-center">
                    <img src="{{ $clientBranding['logo_url'] }}" alt="{{ $clientBranding['name'] }} Logo"
                        class="h-32 object-contain">
                </div>
                <div class="flex justify-center mt-4">
                    <form action="{{ route('locale.set') }}" method="POST">
                        @csrf
                        <select name="locale"
                            class="text-xs rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-gray-700 focus:border-primary focus:ring-2 focus:ring-primary/20"
                            onchange="this.form.submit()">
                            <option value="id" {{ app()->getLocale() === 'id' ? 'selected' : '' }}>ID</option>
                            <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>EN</option>
                        </select>
                    </form>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    {{ __('Daftar Akun Baru') }}
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    {{ __('Sudah punya akun?') }}
                    <a href="{{ route('login') }}" class="font-medium text-primary hover:text-primary/80">
                        {{ __('Masuk di sini') }}
                    </a>
                </p>
            </div>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form class="mt-8 space-y-6" action="{{ route('register.store') }}" method="POST" id="registerForm">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            {{ __('Nama Lengkap') }}
                        </label>
                        <input id="name" name="name" type="text" autocomplete="name" required value="{{ old('name') }}"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg  placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            {{ __('Email') }}
                        </label>
                        <input id="email" name="email" type="email" autocomplete="email" required
                            value="{{ old('email') }}"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg  placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700">
                            {{ __('Tanggal Lahir') }}
                        </label>
                        <input id="date_of_birth" name="date_of_birth" type="date" required
                            value="{{ old('date_of_birth') }}"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg  placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">
                            {{ __('No. Telepon (Opsional)') }}
                        </label>
                        <input id="phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone') }}"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg  placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            {{ __('Password') }}
                        </label>
                        <div class="relative mt-1">
                            <input id="password" name="password" type="password" autocomplete="new-password" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary pr-10">
                            <button type="button" onclick="togglePassword('password', this)" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none">
                                <i class="ri-eye-line text-lg"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                            {{ __('Konfirmasi Password') }}
                        </label>
                        <div class="relative mt-1">
                            <input id="password_confirmation" name="password_confirmation" type="password"
                                autocomplete="new-password" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary pr-10">
                            <button type="button" onclick="togglePassword('password_confirmation', this)" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none">
                                <i class="ri-eye-line text-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Hidden field for reCAPTCHA v3 token -->
                @if($recaptcha_enabled)
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                @endif

                <div>
                    <button type="submit" id="submitBtn"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="ri-user-add-line text-primary/60 group-hover:text-primary/80"></i>
                        </span>
                        {{ __('Daftar') }}
                    </button>
                </div>

                @if($recaptcha_enabled)
                <div class="text-center">
                    <p class="text-xs text-gray-500">
                        {{ __('Situs ini dilindungi oleh reCAPTCHA dan berlaku') }}
                        <a href="https://policies.google.com/privacy" class="text-primary hover:underline"
                            target="_blank">{{ __('Kebijakan Privasi') }}</a> {{ __('dan') }}
                        <a href="https://policies.google.com/terms" class="text-primary hover:underline"
                            target="_blank">{{ __('Persyaratan Layanan') }}</a> {{ __('Google') }}.
                    </p>
                </div>
                @endif
            </form>
        </div>
    </div>
</body>

</html>

<script>
    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('ri-eye-line');
            icon.classList.add('ri-eye-off-line');
        } else {
            input.type = 'password';
            icon.classList.remove('ri-eye-off-line');
            icon.classList.add('ri-eye-line');
        }
    }
</script>
@if($recaptcha_enabled)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('registerForm');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i>Memverifikasi...';

            grecaptcha.ready(function() {
                grecaptcha.execute('{{ $recaptcha_site_key }}', {
                    action: 'register'
                }).then(function(token) {
                    document.getElementById('g-recaptcha-response').value = token;
                    form.submit();
                }).catch(function(error) {
                    console.error('reCAPTCHA error:', error);
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span class="absolute left-0 inset-y-0 flex items-center pl-3"><i class="ri-user-add-line text-primary/60 group-hover:text-primary/80"></i></span>Daftar';
                    alert('Terjadi kesalahan pada verifikasi reCAPTCHA. Silakan coba lagi.');
                });
            });
        });
    });
</script>
@endif
