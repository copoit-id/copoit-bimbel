<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $clientBranding['name'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <script>
        window.MathJax = {
            skipStartupTypeset: true,
            tex2jax: {
                inlineMath: [
                    ['$', '$'],
                    ['\\(', '\\)']
                ],
                displayMath: [
                    ['$$', '$$'],
                    ['\\[', '\\]']
                ],
                processEscapes: true
            },
            messageStyle: 'none',
            showMathMenu: false,
            'HTML-CSS': {
                availableFonts: ['TeX']
            }
        };
    </script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.9/MathJax.js?config=TeX-AMS_HTML"></script>
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
</head>

<body>
    @include('components.login-as-header')
    @include('user.components.navbar')
    @include('user.components.sidebar')

    <div class="p-3 md:p-4 sm:ml-64 pt-4 mt-14">
        @yield('content')
    </div>
    <div class="sm:ml-64">
        @include('user.components.footer')
    </div>
    @include('user.components.floating-whatsapp')
    @include('components.flash-alert')

    {{-- jquery --}}
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
    @yield('scripts')
    @stack('scripts')
</body>

</html>
