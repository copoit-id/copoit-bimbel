<x-confirm-modal
    id="logoutConfirmModal"
    title="Keluar dari akun?"
    message="Anda akan keluar dari akun ini."
    confirm-text="Ya, keluar"
    confirm-variant="danger" />

@once
    @push('scripts')
        <script>
            (() => {
                if (window.logoutConfirmModalBound) {
                    return;
                }

                window.logoutConfirmModalBound = true;

                let logoutForm = null;
                const modal = document.getElementById('logoutConfirmModal');

                const closeModal = () => {
                    if (!modal) {
                        return;
                    }

                    modal.style.setProperty('display', 'none', 'important');
                    document.body.classList.remove('overflow-hidden');
                    logoutForm = null;
                };

                document.addEventListener('click', (event) => {
                    const trigger = event.target.closest('[data-logout-confirm]');
                    if (trigger) {
                        const formId = trigger.dataset.logoutForm;
                        const form = formId ? document.getElementById(formId) : null;

                        if (!modal || !form) {
                            return;
                        }

                        event.preventDefault();
                        logoutForm = form;
                        modal.style.setProperty('display', 'flex', 'important');
                        document.body.classList.add('overflow-hidden');
                        return;
                    }

                    if (!modal || !modal.contains(event.target)) {
                        return;
                    }

                    // This modal reuses the generic confirm button attributes.
                    // Keep other layout-level confirm handlers from submitting
                    // their fallback action ("#") instead of the logout form.
                    event.stopImmediatePropagation();

                    if (event.target.closest('[data-confirm-cancel]') || event.target === modal) {
                        closeModal();
                        return;
                    }

                    if (event.target.closest('[data-confirm-action]') && logoutForm) {
                        const form = logoutForm;
                        closeModal();
                        form.requestSubmit();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && logoutForm) {
                        closeModal();
                    }
                });
            })();
        </script>
    @endpush
@endonce
