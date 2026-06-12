<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ $clientBranding['name'] ?? 'Belajar' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        window.MathJax = {
            skipStartupTypeset: true,
            tex2jax: {
                inlineMath: [['$', '$'], ['\\(', '\\)']],
                displayMath: [['$$', '$$'], ['\\[', '\\]']],
                processEscapes: true
            },
            messageStyle: 'none',
            showMathMenu: false,
            'HTML-CSS': { availableFonts: ['TeX'] }
        };
    </script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.9/MathJax.js?config=TeX-AMS_HTML"></script>
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
    @yield('styles')
    @php
    $primaryColor = $clientBranding['primary_color'] ?? '#10b981';
    @endphp
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
        
        /* Smooth transitions */
        .transition-all-300 { transition: all 0.3s ease; }
        
        /* Card hover effects */
        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
        }
        
        /* Primary color utilities */
        .text-primary { color: {{ $primaryColor }} !important; }
        .bg-primary { background-color: {{ $primaryColor }} !important; }
        .border-primary { border-color: {{ $primaryColor }} !important; }
        .hover\:bg-primary:hover { background-color: {{ $primaryColor }} !important; }
        .hover\:text-primary:hover { color: {{ $primaryColor }} !important; }
        
        /* Active nav item */
        .nav-active {
            background-color: {{ $primaryColor }}15 !important;
            color: {{ $primaryColor }} !important;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    @include('components.login-as-header')
    @include('user.components.new-navbar')
    
    <main class="pt-20 pb-24 md:pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @yield('content')
        </div>
    </main>
    @include('user.components.floating-whatsapp')
    
    @include('components.flash-alert')
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script>
        window.renderMathJax = function() {
            if (window.MathJax) {
                if (window.MathJax.Hub) {
                    MathJax.Hub.Queue(['Typeset', MathJax.Hub]);
                } else if (window.MathJax.typesetPromise) {
                    MathJax.typesetPromise();
                }
            }
        };
        document.addEventListener('DOMContentLoaded', function() {
            window.renderMathJax();
        });
    </script>
    @vite('resources/js/app.js')
    @stack('scripts')
</body>
</html>
