<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title }} - {{ $clientBranding['name'] }}</title>
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
    <x-website-translation-head />
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet" />
</head>

<body class="bg-gray-50 text-gray-800">
    <header class="bg-white border-b border-gray-100">
        <div class="max-w-5xl mx-auto px-4 py-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-3">
                <img src="{{ $clientBranding['logo_url'] }}" alt="{{ $clientBranding['name'] }} Logo" class="client-brand-logo h-12 w-12 object-contain">
                <span class="font-bold text-gray-900">{{ $clientBranding['name'] }}</span>
            </a>
            <nav class="flex flex-wrap items-center gap-3 text-sm font-medium text-gray-600">
                <a href="{{ route('public.terms') }}" class="hover:text-primary">Syarat dan Ketentuan</a>
                <a href="{{ route('public.payment-policy') }}" class="hover:text-primary">Kebijakan Pembayaran</a>
                <a href="{{ route('public.refund-policy') }}" class="hover:text-primary">Refund Policy</a>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-2 hover:bg-gray-50">
                    <i class="ri-login-box-line"></i>Login
                </a>
            </nav>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-10">
        <section class="bg-white border border-gray-100 rounded-2xl p-6 md:p-8 shadow-sm">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold text-primary mb-2">{{ $clientBranding['name'] }}</p>
                <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900">{{ $title }}</h1>
                <p class="mt-3 text-gray-600 leading-relaxed">{{ $subtitle }}</p>
                <p class="mt-4 text-sm text-gray-500">Terakhir diperbarui: {{ $updated }}</p>
            </div>

            <div class="mt-8 space-y-8">
                @foreach($sections as $section)
                <section class="border-t border-gray-100 pt-6">
                    <h2 class="text-lg font-bold text-gray-900">{{ $section['heading'] }}</h2>
                    <div class="mt-3 space-y-3 text-sm md:text-base text-gray-600 leading-relaxed">
                        @foreach($section['body'] as $paragraph)
                        <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </section>
                @endforeach
            </div>
        </section>
    </main>

    <footer class="max-w-5xl mx-auto px-4 pb-10 text-sm text-gray-500">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-t border-gray-200 pt-5">
            <p>&copy; {{ date('Y') }} {{ $clientBranding['name'] }}. Semua hak dilindungi.</p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('public.terms') }}" class="hover:text-primary">Terms</a>
                <a href="{{ route('public.payment-policy') }}" class="hover:text-primary">Payment</a>
                <a href="{{ route('public.refund-policy') }}" class="hover:text-primary">Refund</a>
            </div>
        </div>
    </footer>
    <x-website-translator />
</body>

</html>
