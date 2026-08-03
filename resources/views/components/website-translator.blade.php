@php
    $translationEnabled = (bool) ($clientBranding['website_translation_enabled'] ?? true);
    $supportedLocales = collect($clientBranding['website_translation_locales'] ?? ['en', 'zh-CN'])
        ->filter(fn ($locale) => in_array($locale, ['en', 'zh-CN'], true))
        ->unique()
        ->values()
        ->all();
    $adminAssistantVisible = auth()->check()
        && ! auth()->user()?->isTutor()
        && (bool) config('client.branding.admin_assistant_enabled', false);
@endphp

@if($translationEnabled && $supportedLocales !== [])
    <div id="website-translation-control"
        class="notranslate fixed z-[99999] {{ $adminAssistantVisible ? 'bottom-24 right-5 md:bottom-24 md:right-6' : 'bottom-4 right-4' }}"
        translate="no">
        <div class="relative">
            <button id="website-translation-trigger" type="button" aria-expanded="false" aria-controls="website-translation-menu"
                class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs font-medium text-slate-700 shadow-md transition hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-primary/30">
                <i class="ri-translate-2 text-base" aria-hidden="true"></i>
                <span id="website-translation-label">Indonesia</span>
                <i id="website-translation-chevron" class="ri-arrow-up-s-line text-base" aria-hidden="true"></i>
            </button>
            <div id="website-translation-menu" class="absolute bottom-full right-0 mb-2 hidden min-w-36 overflow-hidden rounded-lg border border-slate-200 bg-white p-1 shadow-xl" role="menu">
                <button type="button" data-website-translation-locale="id" class="block w-full rounded-md px-2.5 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-100" role="menuitem">Indonesia</button>
                @if(in_array('en', $supportedLocales, true))
                    <button type="button" data-website-translation-locale="en" class="block w-full rounded-md px-2.5 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-100" role="menuitem">English</button>
                @endif
                @if(in_array('zh-CN', $supportedLocales, true))
                    <button type="button" data-website-translation-locale="zh-CN" class="block w-full rounded-md px-2.5 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-100" role="menuitem">中文（简体）</button>
                @endif
            </div>
        </div>
        <p id="website-translation-status" class="absolute bottom-full right-0 mb-12 hidden w-max max-w-64 rounded bg-white/95 px-2 py-1 text-xs text-slate-600 shadow"></p>
    </div>

    <div id="google_translate_element" class="notranslate" translate="no" aria-hidden="true"></div>

    <style>
        #google_translate_element,
        iframe.goog-te-banner-frame,
        .goog-te-banner-frame,
        .VIpgJd-ZVi9od-ORHb-OEVmcd,
        .VIpgJd-ZVi9od-xl07Ob-OEVmcd,
        .goog-te-gadget-icon,
        .goog-te-gadget > span,
        .goog-logo-link,
        #goog-gt-tt,
        .goog-te-balloon-frame {
            display: none !important;
        }

        html,
        body {
            top: 0 !important;
        }
    </style>

    <script>
        (() => {
            const sourceLocale = 'id';
            const supportedLocales = @json($supportedLocales);
            const trigger = document.getElementById('website-translation-trigger');
            const label = document.getElementById('website-translation-label');
            const chevron = document.getElementById('website-translation-chevron');
            const menu = document.getElementById('website-translation-menu');
            const status = document.getElementById('website-translation-status');
            const storageKey = 'website-translation-locale';
            const localeLabels = {
                id: 'Indonesia',
                en: 'English',
                'zh-CN': '中文（简体）',
            };
            const pendingTranslationClass = 'website-translation-pending';

            if (!trigger || !label || !menu) {
                return;
            }

            const showStatus = (message) => {
                if (!status) {
                    return;
                }

                status.textContent = message;
                status.classList.remove('hidden');
            };

            const hideStatus = () => status?.classList.add('hidden');

            const revealPage = () => {
                document.documentElement.classList.remove(pendingTranslationClass);
            };

            const getStoredLocale = () => {
                try {
                    const locale = window.localStorage.getItem(storageKey);

                    return locale === sourceLocale || supportedLocales.includes(locale) ? locale : sourceLocale;
                } catch (error) {
                    return sourceLocale;
                }
            };

            const writeTranslationCookie = (locale) => {
                const value = locale === sourceLocale ? '' : `/${sourceLocale}/${locale}`;
                const expires = locale === sourceLocale
                    ? 'expires=Thu, 01 Jan 1970 00:00:00 GMT;'
                    : 'max-age=31536000;';
                const cookie = `googtrans=${value};path=/;${expires}SameSite=Lax`;

                document.cookie = cookie;

                const hostParts = window.location.hostname.split('.');
                const isIpAddress = /^\d{1,3}(?:\.\d{1,3}){3}$/.test(window.location.hostname);
                if (hostParts.length > 1 && !isIpAddress && window.location.hostname !== 'localhost') {
                    document.cookie = `googtrans=${value};domain=.${hostParts.slice(-2).join('.')};path=/;${expires}SameSite=Lax`;
                }
            };

            const protectInteractiveContent = () => {
                document.querySelectorAll([
                    'script',
                    'style',
                    'textarea',
                    'input',
                    'select',
                    'option',
                    '[contenteditable="true"]',
                    '.cke',
                    '.ck-editor',
                    '.math-tex',
                    '.MathJax',
                    '.MathJax_Display',
                    '.MathJax_SVG',
                    '[data-translation-protected]',
                ].join(',')).forEach((element) => {
                    element.classList.add('notranslate');
                    element.setAttribute('translate', 'no');
                });
            };

            const hideGoogleTranslationChrome = () => {
                document.querySelectorAll([
                    'iframe.goog-te-banner-frame',
                    '.goog-te-banner-frame',
                    '.VIpgJd-ZVi9od-ORHb-OEVmcd',
                    '.VIpgJd-ZVi9od-xl07Ob-OEVmcd',
                    '#goog-gt-tt',
                    '.goog-te-balloon-frame',
                ].join(',')).forEach((element) => {
                    element.style.setProperty('display', 'none', 'important');
                });

                document.documentElement.style.setProperty('top', '0', 'important');
                document.body.style.setProperty('top', '0', 'important');
            };

            const activeLocale = getStoredLocale();
            label.textContent = localeLabels[activeLocale];
            protectInteractiveContent();
            hideGoogleTranslationChrome();

            new MutationObserver(hideGoogleTranslationChrome).observe(document.documentElement, {
                childList: true,
                subtree: true,
            });

            const closeMenu = () => {
                menu.classList.add('hidden');
                trigger.setAttribute('aria-expanded', 'false');
                chevron?.classList.replace('ri-arrow-down-s-line', 'ri-arrow-up-s-line');
            };

            trigger.addEventListener('click', () => {
                const isOpen = !menu.classList.contains('hidden');
                if (isOpen) {
                    closeMenu();

                    return;
                }

                menu.classList.remove('hidden');
                trigger.setAttribute('aria-expanded', 'true');
                chevron?.classList.replace('ri-arrow-up-s-line', 'ri-arrow-down-s-line');
            });

            document.addEventListener('click', (event) => {
                if (!document.getElementById('website-translation-control')?.contains(event.target)) {
                    closeMenu();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeMenu();
                }
            });

            menu.querySelectorAll('[data-website-translation-locale]').forEach((option) => {
                option.addEventListener('click', () => {
                    const locale = option.dataset.websiteTranslationLocale;
                if (locale !== sourceLocale && !supportedLocales.includes(locale)) {
                    return;
                }

                try {
                    window.localStorage.setItem(storageKey, locale);
                } catch (error) {
                    // Cookie remains the fallback when browser storage is unavailable.
                }

                writeTranslationCookie(locale);
                window.location.reload();
                });
            });

            if (activeLocale === sourceLocale) {
                hideStatus();
                revealPage();

                return;
            }

            showStatus('Menerjemahkan halaman…');
            window.websiteAutoTranslateInit = () => {
                try {
                    new window.google.translate.TranslateElement({
                        pageLanguage: sourceLocale,
                        includedLanguages: supportedLocales.join(','),
                        autoDisplay: false,
                    }, 'google_translate_element');
                    hideGoogleTranslationChrome();
                    hideStatus();

                    let attempts = 0;
                    const revealWhenTranslated = () => {
                        attempts += 1;
                        const translated = document.documentElement.classList.contains('translated-ltr')
                            || document.documentElement.classList.contains('translated-rtl');

                        if (translated || attempts >= 40) {
                            revealPage();

                            return;
                        }

                        window.setTimeout(revealWhenTranslated, 100);
                    };

                    revealWhenTranslated();
                } catch (error) {
                    showStatus('Terjemahan tidak dapat dimuat. Silakan coba lagi.');
                    revealPage();
                }
            };

            const script = document.createElement('script');
            script.src = 'https://translate.google.com/translate_a/element.js?cb=websiteAutoTranslateInit';
            script.async = true;
            script.onerror = () => {
                showStatus('Terjemahan tidak dapat dimuat. Silakan coba lagi.');
                revealPage();
            };
            document.head.appendChild(script);
        })();
    </script>
@endif
