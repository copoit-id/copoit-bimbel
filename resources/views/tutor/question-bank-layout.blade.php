@extends('tutor.layout')

{{-- Asset editor dipisahkan agar portal Tutor tetap ringan di halaman non-Bank Soal. --}}
@push('styles')
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
            'HTML-CSS': { availableFonts: ['TeX'] }
        };
    </script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.9/MathJax.js?config=TeX-AMS_HTML"></script>
    @include('admin.partials.summernote')
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"
        integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
    <script>
        (() => {
            let pendingAction = { action: '', method: 'POST' };
            let currentModalId = null;

            window.openConfirmModal = (id, action, method, message) => {
                const modal = document.getElementById(id);
                if (!modal) return;

                pendingAction = { action, method: method || 'POST' };
                currentModalId = id;
                const messageElement = document.getElementById(`${id}_message`);
                if (messageElement) messageElement.textContent = message || 'Apakah Anda yakin?';
                modal.style.setProperty('display', 'flex', 'important');
                document.body.classList.add('overflow-hidden');
            };

            const closeConfirmModal = () => {
                if (currentModalId) {
                    document.getElementById(currentModalId)?.style.setProperty('display', 'none', 'important');
                }
                document.body.classList.remove('overflow-hidden');
                currentModalId = null;
            };

            document.addEventListener('click', (event) => {
                if (event.target.closest('[data-confirm-cancel]')) return closeConfirmModal();
                if (!event.target.closest('[data-confirm-action]')) return;

                closeConfirmModal();
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = pendingAction.action;
                form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]').content}">`;
                if (['PUT', 'DELETE'].includes(pendingAction.method)) {
                    form.innerHTML += `<input type="hidden" name="_method" value="${pendingAction.method}">`;
                }
                document.body.appendChild(form);
                form.submit();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeConfirmModal();
            });
        })();

        window.renderMathJax = function () {
            if (window.MathJax?.Hub) {
                window.MathJax.Hub.Queue(['Typeset', window.MathJax.Hub]);
            } else if (window.MathJax?.typesetPromise) {
                window.MathJax.typesetPromise();
            }
        };

        document.addEventListener('DOMContentLoaded', function () {
            window.renderMathJax();

            if (!window.CKEDITOR) return;

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

            const ensureId = (textarea, index) => textarea.id || (textarea.id = `ckeditor-${index}`);
            const initialize = (selector, config, prefix) => {
                document.querySelectorAll(selector).forEach((textarea, index) => {
                    const id = ensureId(textarea, `${prefix}-${index}`);
                    if (!window.CKEDITOR.instances[id]) window.CKEDITOR.replace(id, config);
                });
            };

            initialize('textarea.ckeditor', {
                ...commonConfig,
                height: 300,
                toolbar: [
                    { name: 'math', items: ['Mathjax'] },
                    { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline'] },
                    { name: 'paragraph', items: ['NumberedList', 'BulletedList'] },
                    { name: 'insert', items: ['Table', 'Link', 'Image'] },
                    { name: 'tools', items: ['Source'] }
                ]
            }, 'question');

            initialize('textarea.ckeditor-option', {
                ...commonConfig,
                height: 220,
                toolbar: [
                    { name: 'math', items: ['Mathjax'] },
                    { name: 'basicstyles', items: ['Bold', 'Italic'] },
                    { name: 'insert', items: ['Image'] },
                    { name: 'tools', items: ['Source'] }
                ]
            }, 'option');
        });
    </script>
@endpush
