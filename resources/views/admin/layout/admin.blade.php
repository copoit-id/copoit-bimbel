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
    <script src="https://cdn.ckeditor.com/4.22.1/full-all/ckeditor.js"></script>
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
            'HTML-CSS': {
                availableFonts: ['TeX']
            }
        };
    </script>
    <script defer
        src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.9/MathJax.js?config=TeX-AMS_HTML"></script>
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
    @stack('styles')
    @yield('styles')
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
        window.renderMathJax = function () {
            if (window.MathJax) {
                if (window.MathJax.Hub) {
                    MathJax.Hub.Queue(['Typeset', MathJax.Hub]);
                } else if (window.MathJax.typesetPromise) {
                    MathJax.typesetPromise();
                }
            }
        };

        document.addEventListener('DOMContentLoaded', function () {
            window.renderMathJax();
        });
    </script>
    <script>
        CKEDITOR.config.customConfig = '';
        CKEDITOR.config.skin = 'moono-lisa';
        CKEDITOR.config.resize_enabled = false;
        CKEDITOR.config.removeDialogTabs = 'image:advanced;link:advanced';

        document.addEventListener('DOMContentLoaded', function () {
            initializeCKEditors();

            function initializeCKEditors() {
                Object.keys(CKEDITOR.instances).forEach(function (instanceName) {
                    CKEDITOR.instances[instanceName].destroy(true);
                });

                const commonConfig = {
                    plugins: 'basicstyles,toolbar,wysiwygarea,elementspath,mathjax,sourcearea,clipboard,undo,format,list,indent,blockquote,table,horizontalrule,link',
                    extraPlugins: 'mathjax',
                    mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.9/MathJax.js?config=TeX-AMS_HTML',
                    mathJaxClass: 'math-tex',
                    removePlugins: 'elementspath,save,newpage,preview,print,templates,about,maximize,showblocks,magicline,pagebreak,iframe,flash,smiley,pagebreakutils,indent,indentlist,indentblock',
                    allowedContent: true,
                    forcePasteAsPlainText: false,
                    entities: false,
                    startupFocus: false,
                    toolbarStartupExpanded: true,
                    toolbarCanCollapse: false
                };

                const questionConfig = {
                    ...commonConfig,
                    height: 300,
                    toolbar: [
                        { name: 'math', items: ['Mathjax'] },
                        { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline'] },
                        { name: 'paragraph', items: ['NumberedList', 'BulletedList'] },
                        { name: 'insert', items: ['Table', 'Link'] },
                        { name: 'tools', items: ['Source'] }
                    ]
                };

                const optionConfig = {
                    ...commonConfig,
                    height: 220,
                    toolbar: [
                        { name: 'math', items: ['Mathjax'] },
                        { name: 'basicstyles', items: ['Bold', 'Italic'] },
                        { name: 'tools', items: ['Source'] }
                    ]
                };

                const ensureId = (textarea, index) => {
                    if (!textarea.id) {
                        textarea.id = `ckeditor-${index}`;
                    }
                    return textarea.id;
                };

                document.querySelectorAll('textarea.ckeditor').forEach((textarea, index) => {
                    const elementId = ensureId(textarea, index);
                    CKEDITOR.replace(elementId, questionConfig);
                });

                document.querySelectorAll('textarea.ckeditor-option').forEach((textarea, index) => {
                    const elementId = ensureId(textarea, `option-${index}`);
                    CKEDITOR.replace(elementId, optionConfig);
                });
            }
        });
    </script>
    @vite('resources/js/app.js')
    @stack('scripts')
    @yield('scripts')
</body>

</html>
