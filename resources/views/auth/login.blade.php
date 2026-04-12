<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ __('Login') }} - {{ $clientBranding['name'] }}</title>
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
                    {{ __('Masuk ke Akun Anda') }}
                </h2>
                @if($clientBranding['allow_register'] ?? true)
                <p class="mt-2 text-center text-sm text-gray-600">
                    {{ __('Atau') }}
                    <a href="{{ route('register') }}" class="font-medium text-primary hover:text-primary/80">
                        {{ __('daftar akun baru') }}
                    </a>
                </p>
                @endif
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

            <form class="mt-8 space-y-6" action="{{ route('login.authenticate') }}" method="POST" id="loginForm">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            {{ __('Email') }}
                        </label>
                        <input id="email" name="email" type="email" autocomplete="email" required
                            value="{{ old('email') }}"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg  placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            {{ __('Password') }}
                        </label>
                        <div class="relative mt-1">
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary pr-10">
                            <button type="button" onclick="togglePassword('password', this)" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none">
                                <i class="ri-eye-line text-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox"
                            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            {{ __('Ingat saya') }}
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="{{ route('password.request') }}"
                            class="font-medium text-primary hover:text-primary/80">
                            {{ __('Lupa password?') }}
                        </a>
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
                            <i class="ri-login-box-line text-primary/60 group-hover:text-primary/80"></i>
                        </span>
                        {{ __('Masuk') }}
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
</body>

</html>
