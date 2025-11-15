<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $clientBranding['name'] }} - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.tiny.cloud/1/{{ env('TINYMCE_API_KEY') }}/tinymce/8/tinymce.min.js"
        referrerpolicy="origin"></script>

    @vite('resources/css/app.css')
    @include('components.branding-styles')
</head>

<body>
    @include('admin.components.navbar')
    @include('admin.components.sidebar')
    @include('components.flash-alert')


    <div class="p-6 md:p-12 sm:ml-64 mt-16 md:mt-10">
        @yield('content')
    </div>

    {{-- jquery --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            tinymce.init({
                selector: 'textarea.tinymce, textarea.tinymce-opsi', // gabungan
                height: 300, // default tinggi
                plugins: 'lists link table code',
                toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | code',
                setup: (editor) => {
                    editor.on('init', () => console.log("TinyMCE loaded for:", editor.id));
                }
            });
        });
    </script>
    @vite('resources/js/app.js')
    @stack('scripts')
    @yield('scripts')
</body>

</html>
