@once
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.9.1/dist/summernote-lite.min.css">
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.1/dist/summernote-lite.min.js"></script>
        <script>
            (() => {
                const SELECTORS = ['[data-summernote]', '.summernote', '.summernote-field', '#summernote'];
                const DEFAULT_TOOLBAR = [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ];

                const parseToolbar = (value) => {
                    if (!value) {
                        return null;
                    }

                    try {
                        const parsed = JSON.parse(value);
                        return Array.isArray(parsed) ? parsed : null;
                    } catch (error) {
                        console.warn('Toolbar Summernote tidak valid:', error);
                        return null;
                    }
                };

                const parseNumber = (value) => {
                    const parsed = parseInt(value, 10);
                    return Number.isFinite(parsed) ? parsed : null;
                };

                const parseBoolean = (value) => {
                    if (typeof value === 'boolean') {
                        return value;
                    }
                    if (typeof value === 'string') {
                        return value === 'true' || value === '1';
                    }
                    if (typeof value === 'number') {
                        return value === 1;
                    }
                    return false;
                };

                const initEditors = () => {
                    const $ = window.jQuery || window.$;
                    if (!$ || typeof $.fn?.summernote !== 'function') {
                        console.warn('Summernote tidak dimuat. Pastikan jQuery dan summernote-lite tersedia.');
                        return;
                    }

                    SELECTORS.forEach((selector) => {
                        $(selector).each(function () {
                            const $target = $(this);
                            if ($target.data('summernoteInitialized')) {
                                return;
                            }

                            const toolbar = parseToolbar($target.attr('data-toolbar')) || DEFAULT_TOOLBAR;
                            const height = parseNumber($target.data('height')) ?? 300;
                            const minHeight = parseNumber($target.data('minHeight')) ?? parseNumber($target.attr('data-min-height'));
                            const maxHeight = parseNumber($target.data('maxHeight')) ?? parseNumber($target.attr('data-max-height'));
                            const tabsize = parseNumber($target.data('tabsize')) ?? 2;
                            const focus = parseBoolean($target.data('focus'));

                            $target.summernote({
                                placeholder: $target.attr('placeholder') || '',
                                tabsize,
                                height,
                                minHeight: minHeight ?? null,
                                maxHeight: maxHeight ?? null,
                                focus,
                                toolbar
                            });

                            $target.data('summernoteInitialized', true);
                        });
                    });
                };

                const boot = () => {
                    initEditors();
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', boot, { once: true });
                } else {
                    boot();
                }

                document.addEventListener('turbo:load', boot);
                document.addEventListener('livewire:navigated', boot);
                window.initSummernoteFields = boot;
            })();
        </script>
    @endpush
@endonce
