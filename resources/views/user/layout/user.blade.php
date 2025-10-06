<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>CPNS Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    @vite('resources/css/app.css')
</head>

<body>
    @include('user.components.navbar')
    @include('user.components.sidebar')

    <div class="p-8 md:p-12 sm:ml-64 mt-14">
        @yield('content')
    </div>
    @if (session('success') || session('error'))
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
        class="fixed top-6 right-6 z-50 max-w-sm w-full shadow-lg rounded-lg p-4 text-sm
            {{ session('success') ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300' }}">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="ri-information-line text-lg"></i>
                <span>
                    {{ session('success') ?? session('error') }}
                </span>
            </div>
            <button @click="show = false" class="text-xl leading-none hover:text-gray-600">
                &times;
            </button>
        </div>
    </div>
    @endif

    {{-- jquery --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>

    @vite('resources/js/app.js')
    @yield('scripts')
    @stack('scripts')
</body>

</html>
