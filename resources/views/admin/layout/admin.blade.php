<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, proxy-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="turbo-cache-control" content="no-cache">
    <title>{{ $clientBranding['name'] }} - {{ auth()->user()?->isTutor() ? 'Tutor' : 'Admin' }} Dashboard</title>
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
    <x-website-translation-head />
    @include('admin.partials.summernote')
    @stack('styles')
    @yield('styles')
</head>

<body data-app-selects>
    @if ($isQuestionPickerMode)
        <x-question-bank.picker-context :tryout-detail="$questionPickerDetail" />
    @elseif ($isProgramSchedulePickerMode)
        <x-package.schedule-picker-context :package="$programSchedulePicker" />
    @endif
    @include('admin.components.navbar')
    @include('components.confirm-modal')
    <x-logout-confirm-modal />
    @include('admin.components.sidebar')
    @include('components.flash-alert')


    <div class="responsive-shell p-4 sm:p-6 md:p-12 sm:ml-64 {{ $isPickerMode ? 'mt-32 md:mt-32' : 'mt-16 md:mt-10' }}">
        @yield('content')
    </div>

    @if(config('client.branding.admin_assistant_enabled', false) && !auth()->user()?->isTutor())
        <x-admin.assistant />
    @endif

    <x-admin.interactive-tour />

    {{-- jquery --}}
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"
        integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous">
    </script>
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

        const removeLegacyCkeditorSecurityNotice = () => {
            document.querySelectorAll('.cke_notification, .cke_notification_message').forEach((element) => {
                const message = element.textContent || '';
                if (!message.includes('This CKEditor 4.22.1') || !message.includes('not secure')) {
                    return;
                }

                (element.closest('.cke_notification') || element).remove();
            });
        };

        const ckeditorNoticeObserver = new MutationObserver(removeLegacyCkeditorSecurityNotice);
        ckeditorNoticeObserver.observe(document.documentElement, { childList: true, subtree: true });
        CKEDITOR.on('instanceReady', removeLegacyCkeditorSecurityNotice);
        document.addEventListener('DOMContentLoaded', removeLegacyCkeditorSecurityNotice, { once: true });

        document.addEventListener('DOMContentLoaded', function () {
            initializeCKEditors();

            function initializeCKEditors() {
                Object.keys(CKEDITOR.instances).forEach(function (instanceName) {
                    CKEDITOR.instances[instanceName].destroy(true);
                });

                const commonConfig = {
                    plugins: 'basicstyles,toolbar,wysiwygarea,elementspath,mathjax,sourcearea,clipboard,undo,format,list,indent,blockquote,table,horizontalrule,link,image',
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
                        { name: 'insert', items: ['Table', 'Link', 'Image'] },
                        { name: 'tools', items: ['Source'] }
                    ]
                };

                const optionConfig = {
                    ...commonConfig,
                    height: 220,
                    toolbar: [
                        { name: 'math', items: ['Mathjax'] },
                        { name: 'basicstyles', items: ['Bold', 'Italic'] },
                        { name: 'insert', items: ['Image'] },
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

    <script>
    (function() {
        let pendingAction = { action: '', method: 'POST', formId: null };
        let currentModalId = null;

        function openConfirmModal(id, action, method, message, formId) {
            method = method || 'POST';
            pendingAction = { action, method, formId: formId || null };
            currentModalId = id;

            const messageEl = document.getElementById(id + '_message');
            if (messageEl) messageEl.textContent = message || 'Apakah Anda yakin?';

            const modal = document.getElementById(id);
            if (!modal) return;

            modal.style.display = 'flex';
            document.body.classList.add('overflow-hidden');
        }

        function closeConfirmModal() {
            if (currentModalId) {
                const modal = document.getElementById(currentModalId);
                if (modal) modal.style.display = 'none';
            }
            document.body.classList.remove('overflow-hidden');
            currentModalId = null;
        }

        function submitConfirmForm() {
            closeConfirmModal();

            if (pendingAction.formId) {
                const existingForm = document.getElementById(pendingAction.formId);
                if (existingForm) {
                    existingForm.requestSubmit();
                    return;
                }
            }

            if (pendingAction.method === 'GET') {
                window.location.href = pendingAction.action || '#';
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = pendingAction.action || '#';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name=csrf-token]').content;
            form.appendChild(csrfInput);

            if (pendingAction.method === 'PUT' || pendingAction.method === 'DELETE') {
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = pendingAction.method;
                form.appendChild(methodInput);
            }

            document.body.appendChild(form);
            form.submit();
        }

        document.addEventListener('click', function(e) {
            // Cancel button
            const cancelBtn = e.target.closest('[data-confirm-cancel]');
            if (cancelBtn) {
                closeConfirmModal();
                return;
            }

            // Confirm button
            const confirmBtn = e.target.closest('[data-confirm-action]');
            if (confirmBtn) {
                submitConfirmForm();
                return;
            }

            // Backdrop click
            const modal = e.target.closest('[data-modal-confirm]');
            if (modal && e.target === modal) {
                closeConfirmModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && currentModalId) {
                closeConfirmModal();
            }
        });

        window.openConfirmModal = openConfirmModal;
        window.closeConfirmModal = closeConfirmModal;
    })();
    </script>

    {{-- Keep sidebar links as normal anchors; only clean stale Alpine bindings on details. --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const detailsElements = document.querySelectorAll('#logo-sidebar details');
            detailsElements.forEach(function(details) {
                details.removeAttribute('x-data');
                details.removeAttribute('x-bind');
                details.removeAttribute('x-on:click');
            });
        });
        
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
        
        // Refresh CSRF token periodically to prevent session expiry issues
        @if(auth()->user()?->canAccessAdminPanel())
        setInterval(function() {
            fetch('{{ route("admin.dashboard") }}', {
                method: 'HEAD',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).catch(function() {});
        }, 300000); // Every 5 minutes
        @endif
    </script>
    @stack('scripts')
    <x-website-translator />
</body>

</html>
