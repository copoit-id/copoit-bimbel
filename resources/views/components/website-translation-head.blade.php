@php
    $googleTranslationCookie = (string) request()->cookie('googtrans', '');
    $enabledLocales = collect($clientBranding['website_translation_locales'] ?? ['en', 'zh-CN', 'ja', 'ar', 'ko'])
        ->filter(fn ($locale) => in_array($locale, ['en', 'zh-CN', 'ja', 'ar', 'ko'], true))
        ->all();
    $translationPending = (bool) ($clientBranding['website_translation_enabled'] ?? false)
        && in_array(ltrim(str_replace('/id/', '', $googleTranslationCookie), '/'), $enabledLocales, true);
@endphp

@if($translationPending)
    <script>
        document.documentElement.classList.add('website-translation-pending');
    </script>
    <style>
        html.website-translation-pending body {
            visibility: hidden;
        }
    </style>
@endif
