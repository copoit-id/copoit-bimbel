@once
    <style>
        @media (min-width: 640px) {
            [data-persistent-sidebar] {
                transition: transform 320ms cubic-bezier(0.22, 1, 0.36, 1) !important;
                will-change: transform;
            }

            [data-persistent-sidebar-content] {
                transition: margin-left 320ms cubic-bezier(0.22, 1, 0.36, 1), padding-left 320ms cubic-bezier(0.22, 1, 0.36, 1);
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar] {
                transform: translateX(-100%) !important;
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar-content="margin"] {
                margin-left: 0 !important;
            }

            html[data-persistent-sidebar-state="collapsed"] [data-persistent-sidebar-content="padding"] {
                padding-left: 0 !important;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            [data-persistent-sidebar],
            [data-persistent-sidebar-content] {
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
                    button.setAttribute('aria-label', isCollapsed ? 'Buka sidebar' : 'Tutup sidebar');
                    const label = button.querySelector('[data-persistent-sidebar-toggle-label]');
                    if (label) label.textContent = isCollapsed ? 'Buka sidebar' : 'Tutup sidebar';
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
