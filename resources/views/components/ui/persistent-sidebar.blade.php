@once
    <style>
        @media (min-width: 640px) {
            [data-persistent-sidebar] {
                top: 1rem !important;
                left: 1rem !important;
                height: calc(100vh - 2rem) !important;
                padding-top: 0 !important;
                border: 1px solid #e5e7eb !important;
                border-radius: 1rem;
                background: #ffffff !important;
                box-shadow: 0 5px 16px rgba(15, 23, 42, 0.06);
                transition: transform 320ms cubic-bezier(0.22, 1, 0.36, 1) !important;
                will-change: transform;
            }

            [data-persistent-sidebar] > div {
                padding: 0.75rem !important;
                background: transparent !important;
            }

            [data-persistent-sidebar][data-login-as-sidebar="true"] {
                top: calc(52px + 1rem) !important;
                height: calc(100vh - 68px) !important;
            }

            [data-persistent-sidebar] [data-sidebar-brand] {
                display: flex;
                position: sticky;
                top: 0;
                z-index: 10;
                align-items: center;
                gap: 0.75rem;
                padding: 0.5rem;
                border-radius: 0.85rem;
                background: color-mix(in srgb, var(--color-primary) 8%, #ffffff);
                color: #111827;
            }

            [data-persistent-sidebar] [data-sidebar-brand-mark] {
                display: flex;
                height: 2.5rem;
                width: 2.5rem;
                flex-shrink: 0;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                border-radius: 9999px;
                border: 2px solid color-mix(in srgb, var(--color-primary) 25%, #ffffff);
                background: #ffffff;
                color: var(--color-primary);
            }

            [data-persistent-sidebar] [data-sidebar-brand] p {
                color: #111827 !important;
            }

            [data-persistent-sidebar] [data-sidebar-brand] small,
            [data-persistent-sidebar] [data-sidebar-section-label] {
                color: #6b7280 !important;
            }

            [data-persistent-sidebar] [data-sidebar-divider] {
                margin: 0.9rem 0 0.7rem;
                border-color: #e5e7eb !important;
            }

            [data-persistent-sidebar] [data-persistent-sidebar-toggle]:not([data-persistent-sidebar-reopen]) {
                position: absolute;
                top: 4.25rem;
                right: -0.875rem;
                z-index: 20;
                display: inline-flex;
                height: 1.75rem !important;
                width: 1.75rem !important;
                align-items: center;
                justify-content: center;
                border: 1px solid #e5e7eb;
                border-radius: 0.65rem;
                background: #ffffff;
                color: #6b7280 !important;
                box-shadow: 0 2px 7px rgba(15, 23, 42, 0.1);
                transition: color 180ms ease, background-color 180ms ease, border-color 180ms ease;
            }

            [data-persistent-sidebar] [data-persistent-sidebar-toggle]:not([data-persistent-sidebar-reopen]):hover {
                border-color: color-mix(in srgb, var(--color-primary) 30%, #ffffff);
                background: color-mix(in srgb, var(--color-primary) 7%, #ffffff);
                color: var(--color-primary) !important;
            }

            [data-persistent-sidebar] a:not([data-sidebar-brand]),
            [data-persistent-sidebar] summary {
                color: #4b5563 !important;
            }

            [data-persistent-sidebar] a:not([data-sidebar-brand]):hover,
            [data-persistent-sidebar] summary:hover {
                background: color-mix(in srgb, var(--color-primary) 7%, #ffffff) !important;
                color: var(--color-primary) !important;
            }

            [data-persistent-sidebar] .bg-primary {
                background: var(--color-primary) !important;
                color: #ffffff !important;
            }

            [data-persistent-sidebar] .bg-primary *,
            [data-persistent-sidebar] .bg-primary .text-white {
                color: #ffffff !important;
            }

            [data-persistent-sidebar] .bg-primary\/10,
            [data-persistent-sidebar] .bg-white\/10 {
                background: color-mix(in srgb, var(--color-primary) 10%, #ffffff) !important;
                color: var(--color-primary) !important;
            }

            [data-persistent-sidebar] .bg-primary\/10 .text-white,
            [data-persistent-sidebar] .bg-primary\/10 .text-white\/80,
            [data-persistent-sidebar] .bg-white\/10 .text-white,
            [data-persistent-sidebar] .bg-white\/10 .text-white\/80 {
                color: var(--color-primary) !important;
            }

            [data-persistent-sidebar] .text-white\/80,
            [data-persistent-sidebar] .text-white\/70,
            [data-persistent-sidebar] .text-white\/50 {
                color: #6b7280 !important;
            }

            [data-persistent-sidebar] details > ul {
                margin-left: 1.25rem !important;
                padding-left: 0.5rem;
                border-left: 1px solid #d1d5db;
            }

            [data-persistent-sidebar] details > ul a {
                padding-left: 0.75rem !important;
            }

            [data-persistent-sidebar-content] {
                transition: margin-left 320ms cubic-bezier(0.22, 1, 0.36, 1), padding-left 320ms cubic-bezier(0.22, 1, 0.36, 1);
            }

            [data-persistent-sidebar-content="margin"] {
                margin-left: 18rem !important;
            }

            [data-persistent-sidebar-content="padding"] {
                padding-left: 18rem !important;
            }

            [data-persistent-navbar] {
                top: 1rem !important;
                left: 18rem !important;
                width: calc(100% - 19rem) !important;
                margin-top: 1rem;
                border-radius: 1rem;
                box-shadow: 0 3px 12px rgba(15, 23, 42, 0.05);
                transition: left 320ms cubic-bezier(0.22, 1, 0.36, 1), width 320ms cubic-bezier(0.22, 1, 0.36, 1);
            }

            [data-persistent-navbar][data-login-as-navbar="true"] {
                top: calc(52px + 1rem) !important;
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] {
                width: 5rem !important;
                transform: translateX(0) !important;
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar-content="margin"] {
                margin-left: 6rem !important;
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar-content="padding"] {
                padding-left: 6rem !important;
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-navbar] {
                left: 6rem !important;
                width: calc(100% - 7rem) !important;
            }

            [data-persistent-sidebar-reopen] {
                opacity: 0;
                pointer-events: none;
                transform: translateX(-0.75rem);
                transition: opacity 180ms ease, transform 320ms cubic-bezier(0.22, 1, 0.36, 1);
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar-reopen] {
                display: none;
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] [data-sidebar-brand] {
                justify-content: center;
                padding-inline: 0;
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] [data-sidebar-brand] > span:not([data-sidebar-brand-mark]),
            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] [data-sidebar-brand] > i,
            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] [data-sidebar-section-label] {
                display: none;
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] [data-sidebar-brand-mark] {
                height: 2.5rem;
                width: 2.5rem;
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] [data-sidebar-divider] {
                margin-inline: 0.25rem;
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] > div > div:not([data-sidebar-divider]) {
                justify-content: center;
                min-height: 0;
                height: 0;
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] > div > ul > li > a,
            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] > div > ul > li > details > summary {
                justify-content: center;
                min-height: 2.75rem;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] > div > ul > li > a > i,
            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] > div > ul > li > details > summary > span > i {
                display: inline-flex;
                width: 1.5rem;
                flex: 0 0 1.5rem;
                align-items: center;
                justify-content: center;
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] > div > ul > li > a > span,
            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] > div > ul > li > details > summary .ms-3,
            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] > div > ul > li > details > summary > i:last-child,
            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] details > ul {
                display: none;
            }
        }

        @media (max-width: 639px) {
            [data-persistent-navbar] {
                top: 0.5rem !important;
                width: calc(100% - 1rem) !important;
                margin: 0.5rem;
                border-radius: 0.875rem;
                box-shadow: 0 3px 12px rgba(15, 23, 42, 0.05);
            }

            [data-persistent-sidebar] {
                top: 0.5rem !important;
                left: 0.5rem !important;
                height: calc(100vh - 1rem) !important;
                width: calc(100% - 1rem) !important;
                max-width: 18rem;
                border: 1px solid #e5e7eb !important;
                border-radius: 0.875rem;
                background: #ffffff !important;
                box-shadow: 0 5px 16px rgba(15, 23, 42, 0.08);
            }

            [data-persistent-sidebar] > div {
                background: #ffffff !important;
                border-radius: inherit;
            }

            [data-persistent-sidebar] [data-sidebar-brand] {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                margin: 0.75rem;
                padding: 0.5rem;
                border-radius: 0.85rem;
                background: color-mix(in srgb, var(--color-primary) 8%, #ffffff);
            }

            [data-persistent-sidebar] [data-sidebar-brand-mark] {
                display: flex;
                height: 2.5rem;
                width: 2.5rem;
                flex-shrink: 0;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                border: 2px solid color-mix(in srgb, var(--color-primary) 25%, #ffffff);
                border-radius: 9999px;
                background: #ffffff;
            }

            [data-persistent-sidebar] [data-sidebar-brand] p {
                color: #111827 !important;
            }

            [data-persistent-sidebar] [data-sidebar-brand] small,
            [data-persistent-sidebar] [data-sidebar-section-label] {
                color: #6b7280 !important;
            }

            [data-persistent-sidebar] a:not([data-sidebar-brand]),
            [data-persistent-sidebar] summary {
                color: #4b5563 !important;
            }

            [data-persistent-sidebar] .bg-primary {
                background: var(--color-primary) !important;
                color: #ffffff !important;
            }

            [data-persistent-sidebar] .bg-primary * {
                color: #ffffff !important;
            }

            [data-persistent-sidebar] .bg-primary\/10,
            [data-persistent-sidebar] .bg-white\/10 {
                background: color-mix(in srgb, var(--color-primary) 10%, #ffffff) !important;
                color: var(--color-primary) !important;
            }

            [data-persistent-sidebar][data-login-as-sidebar="true"] {
                top: calc(52px + 0.5rem) !important;
                height: calc(100vh - 60px) !important;
            }

            [data-persistent-navbar][data-login-as-navbar="true"] {
                top: calc(52px + 0.5rem) !important;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            [data-persistent-sidebar],
            [data-persistent-sidebar-content],
            [data-persistent-navbar],
            [data-persistent-sidebar-reopen] {
                transition-duration: 1ms !important;
            }
        }
    </style>

    <script>
        (() => {
            const storageKey = 'bimbelhub.persistent-sidebar-state';
            const root = document.documentElement;

            const getStoredState = () => {
                try {
                    return window.localStorage.getItem(storageKey);
                } catch (error) {
                    return null;
                }
            };

            const applyState = (state, persist = false) => {
                const isCollapsed = state === 'collapsed';
                root.dataset.persistentSidebarState = isCollapsed ? 'collapsed' : 'expanded';

                document.querySelectorAll('[data-persistent-sidebar-toggle]').forEach((button) => {
                    button.setAttribute('aria-expanded', String(!isCollapsed));
                    button.setAttribute('aria-label', isCollapsed ? 'Perluas sidebar' : 'Ringkas sidebar');
                    const label = button.querySelector('[data-persistent-sidebar-toggle-label]');
                    if (label) label.textContent = isCollapsed ? 'Perluas sidebar' : 'Ringkas sidebar';
                    const icon = button.querySelector('[data-persistent-sidebar-toggle-icon]');
                    if (icon) {
                        icon.classList.toggle('ri-arrow-left-s-line', !isCollapsed);
                        icon.classList.toggle('ri-arrow-right-s-line', isCollapsed);
                    }
                });

                if (!persist) return;

                try {
                    window.localStorage.setItem(storageKey, isCollapsed ? 'collapsed' : 'expanded');
                } catch (error) {
                    // Sidebar tetap berfungsi bila browser menolak localStorage.
                }
            };

            applyState(getStoredState());

            document.addEventListener('DOMContentLoaded', () => {
                applyState(getStoredState());

                document.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-persistent-sidebar-toggle]');
                    if (!button) return;

                    const nextState = root.dataset.persistentSidebarState === 'collapsed'
                        ? 'expanded'
                        : 'collapsed';

                    applyState(nextState, true);
                });
            });
        })();
    </script>
@endonce
