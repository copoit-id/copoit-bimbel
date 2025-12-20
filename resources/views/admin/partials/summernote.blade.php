@once
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css">
        <style>
            .note-editor.note-frame {
                border-color: #e5e7eb;
                border-radius: 0.75rem;
            }

            .note-toolbar.card-header {
                border-bottom: 1px solid #e5e7eb;
            }

            .note-editor.note-frame .note-statusbar {
                border-top: 1px solid #e5e7eb;
            }

            .note-icon-math {
                font-size: 1rem;
                font-weight: 600;
                line-height: 1;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof $ === 'undefined' || typeof $.fn.summernote === 'undefined') {
                    console.warn('Summernote assets not loaded');
                    return;
                }

                const renderMath = (target, attempt = 0) => {
                    if (!target) {
                        return;
                    }

                    if (window.renderMathContent) {
                        window.renderMathContent(target);
                        return;
                    }

                    if (attempt < 10) {
                        setTimeout(() => renderMath(target, attempt + 1), 200);
                    } else {
                        console.warn('Math renderer belum siap, gagal menampilkan LaTeX di editor.');
                    }
                };

                const latexButton = function (context) {
                    const ui = $.summernote.ui;
                    const $editor = context.layoutInfo.editor;
                    return ui.button({
                        contents: '<span class="note-icon-math">∑</span>',
                        tooltip: 'Sisipkan LaTeX',
                        click: function () {
                            context.invoke('editor.saveRange');
                            const latexInput = window.prompt('Masukkan kode LaTeX (contoh: \\frac{a}{b})', '');
                            if (latexInput !== null) {
                                const trimmed = latexInput.trim();
                                if (trimmed !== '') {
                                    const node = `<span class="math-tex">\\\\(${trimmed}\\\\)</span>&nbsp;`;
                                    context.invoke('editor.restoreRange');
                                    context.invoke('editor.pasteHTML', node);
                                    const editable = $editor.find('.note-editable')[0];
                                    renderMath(editable);
                                    return;
                                }
                            }
                            context.invoke('editor.restoreRange');
                        }
                    }).render();
                };

                $.summernote.options = $.summernote.options || {};
                $.summernote.options.buttons = $.summernote.options.buttons || {};
                $.summernote.options.buttons.latex = latexButton;

                $('.summernote-field').each(function () {
                    const $editor = $(this);
                    const height = parseInt($editor.data('height'), 10) || 260;

                    $editor.summernote({
                        height: height,
                        placeholder: $editor.attr('placeholder') || '',
                        dialogsInBody: true,
                        tabsize: 2,
                        toolbar: [
                            ['style', ['style']],
                            ['font', ['bold', 'italic', 'underline', 'clear']],
                            ['fontname', ['fontname']],
                            ['fontsize', ['fontsize']],
                            ['color', ['color']],
                            ['para', ['ul', 'ol', 'paragraph']],
                            ['insert', ['link', 'picture', 'video', 'table', 'hr', 'latex']],
                            ['view', ['fullscreen', 'codeview', 'help']]
                        ],
                        buttons: {
                            latex: latexButton
                        },
                        callbacks: {
                            onInit: function () {
                                const editable = $editor.next('.note-editor').find('.note-editable')[0];
                                renderMath(editable);
                            },
                            onChange: function () {
                                const editable = $editor.next('.note-editor').find('.note-editable')[0];
                                renderMath(editable);
                            }
                        }
                    });
                });
            });
        </script>
    @endpush
@endonce
