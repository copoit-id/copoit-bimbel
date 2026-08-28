@php
    $tourPortal = request()->route('portal') ?: 'admin';
    $tourBaseUrl = url($tourPortal.'/tours');
@endphp

<div data-admin-interactive-tour data-tour-base-url="{{ $tourBaseUrl }}"
    data-current-route="{{ request()->route()?->getName() }}" class="hidden">
    <div data-admin-tour-overlay class="fixed inset-0 z-[1000] bg-slate-950/60"></div>

    <section data-admin-tour-card role="dialog" aria-modal="true" aria-labelledby="admin-tour-title"
        aria-describedby="admin-tour-body" tabindex="-1"
        class="fixed inset-x-4 bottom-4 z-[1002] max-w-sm rounded-2xl bg-white p-5 shadow-2xl sm:inset-x-auto sm:bottom-auto">
        <div class="mb-3 flex items-start justify-between gap-3">
            <p data-admin-tour-progress class="text-xs font-semibold uppercase tracking-wide text-primary"></p>
            <button type="button" data-admin-tour-close class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                aria-label="Tutup tutor navigasi">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <h2 id="admin-tour-title" data-admin-tour-title class="text-lg font-bold text-gray-900"></h2>
        <p id="admin-tour-body" data-admin-tour-body class="mt-2 text-sm leading-6 text-gray-600"></p>
        <div class="mt-5 flex items-center justify-between gap-3">
            <button type="button" data-admin-tour-skip class="text-sm font-semibold text-gray-500 hover:text-gray-800">
                Lewati tour
            </button>
            <button type="button" data-admin-tour-next
                class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                Lanjut
            </button>
        </div>
    </section>
</div>

@once
    @push('scripts')
        <script>
            (() => {
                class AdminInteractiveTour {
                    constructor(root) {
                        this.root = root;
                        this.baseUrl = root.dataset.tourBaseUrl.replace(/\/$/, '');
                        this.currentRoute = root.dataset.currentRoute;
                        this.card = root.querySelector('[data-admin-tour-card]');
                        this.title = root.querySelector('[data-admin-tour-title]');
                        this.body = root.querySelector('[data-admin-tour-body]');
                        this.progress = root.querySelector('[data-admin-tour-progress]');
                        this.nextButton = root.querySelector('[data-admin-tour-next]');
                        this.skipButton = root.querySelector('[data-admin-tour-skip]');
                        this.closeButton = root.querySelector('[data-admin-tour-close]');
                        this.active = false;
                        this.bind();
                        this.resume();
                    }

                    bind() {
                        document.querySelectorAll('[data-admin-tour-start]').forEach((button) => {
                            button.addEventListener('click', () => this.start(button.dataset.adminTourStart));
                        });
                        this.nextButton.addEventListener('click', () => this.advance());
                        this.skipButton.addEventListener('click', () => this.close('skipped'));
                        this.closeButton.addEventListener('click', () => this.close('dismissed'));
                        window.addEventListener('resize', () => this.positionCard());
                        window.addEventListener('scroll', () => this.positionCard(), true);
                    }

                    storageKey() {
                        return 'admin-interactive-tour';
                    }

                    csrfToken() {
                        return document.querySelector('meta[name="csrf-token"]')?.content || '';
                    }

                    async request(url, method = 'GET', body = null) {
                        const response = await fetch(url, {
                            method,
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: body ? JSON.stringify(body) : null,
                        });
                        if (!response.ok) {
                            throw new Error('Tutor Navigasi tidak tersedia untuk halaman ini.');
                        }
                        return response.json();
                    }

                    async load(key) {
                        const result = await this.request(this.baseUrl + '/' + encodeURIComponent(key));
                        this.tour = result.tour;
                        return this.tour;
                    }

                    async start(key) {
                        try {
                            await this.load(key);
                            const result = await this.request(this.baseUrl + '/' + encodeURIComponent(key) + '/start', 'POST');
                            this.openStep(result.current_step_id || this.tour.steps[0].id);
                        } catch (error) {
                            window.alert(error.message);
                        }
                    }

                    async resume() {
                        const saved = sessionStorage.getItem(this.storageKey());
                        if (!saved) return;

                        try {
                            const state = JSON.parse(saved);
                            await this.load(state.key);
                            if (state.version !== this.tour.version) {
                                this.clearResume();
                                return;
                            }
                            this.openStep(state.stepId);
                        } catch (error) {
                            this.clearResume();
                        }
                    }

                    openStep(stepId) {
                        const step = this.tour.steps.find((item) => item.id === stepId);
                        if (!step || step.route !== this.currentRoute) return;

                        const target = document.querySelector(step.target);
                        if (!target) {
                            this.clearResume();
                            window.alert('Halaman berubah. Silakan mulai ulang Tutor Navigasi.');
                            return;
                        }

                        this.step = step;
                        this.target = target;
                        this.persistResume(step.id);
                        this.active = true;
                        this.root.classList.remove('hidden');
                        this.title.textContent = step.title;
                        this.body.textContent = step.body;
                        this.progress.textContent = 'Langkah ' + (this.tour.steps.indexOf(step) + 1) + ' dari ' + this.tour.steps.length;
                        this.nextButton.textContent = step.type === 'complete' ? 'Selesai' : 'Lanjut';
                        this.nextButton.disabled = step.type === 'input_target' && !this.hasInputValue();
                        this.nextButton.classList.toggle('opacity-50', this.nextButton.disabled);
                        this.nextButton.classList.toggle('cursor-not-allowed', this.nextButton.disabled);
                        this.highlight();
                        this.installGuards();
                        target.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
                        this.positionCard();
                        setTimeout(() => this.card.focus(), 0);
                    }

                    persistResume(stepId) {
                        sessionStorage.setItem(this.storageKey(), JSON.stringify({
                            key: this.tour.key,
                            version: this.tour.version,
                            stepId,
                        }));
                    }

                    clearResume() {
                        sessionStorage.removeItem(this.storageKey());
                    }

                    hasInputValue() {
                        return ['INPUT', 'TEXTAREA', 'SELECT'].includes(this.target.tagName)
                            && String(this.target.value || '').trim() !== '';
                    }

                    highlight() {
                        this.previousFocus = document.activeElement;
                        this.previousOverflow = document.body.style.overflow;
                        document.body.style.overflow = 'hidden';
                        this.target.classList.add('admin-tour-target');
                    }

                    removeHighlight() {
                        this.target?.classList.remove('admin-tour-target');
                        document.body.style.overflow = this.previousOverflow || '';
                    }

                    installGuards() {
                        this.clickGuard = (event) => this.handleClick(event);
                        this.focusGuard = (event) => {
                            if (!this.active || this.card.contains(event.target) || this.target.contains(event.target)) return;
                            event.stopPropagation();
                            this.card.focus();
                        };
                        this.keyGuard = (event) => {
                            if (this.active && event.key === 'Escape') {
                                event.preventDefault();
                                if (window.confirm('Tutup Tutor Navigasi? Progres langkah ini akan dihentikan.')) {
                                    this.close('dismissed');
                                }
                            }
                        };
                        this.inputGuard = () => {
                            if (this.step.type === 'input_target') {
                                this.nextButton.disabled = !this.hasInputValue();
                                this.nextButton.classList.toggle('opacity-50', this.nextButton.disabled);
                                this.nextButton.classList.toggle('cursor-not-allowed', this.nextButton.disabled);
                            }
                        };
                        document.addEventListener('click', this.clickGuard, true);
                        document.addEventListener('submit', this.clickGuard, true);
                        document.addEventListener('focusin', this.focusGuard, true);
                        document.addEventListener('keydown', this.keyGuard, true);
                        this.target.addEventListener('input', this.inputGuard);
                        this.target.addEventListener('change', this.inputGuard);
                    }

                    removeGuards() {
                        document.removeEventListener('click', this.clickGuard, true);
                        document.removeEventListener('submit', this.clickGuard, true);
                        document.removeEventListener('focusin', this.focusGuard, true);
                        document.removeEventListener('keydown', this.keyGuard, true);
                        this.target?.removeEventListener('input', this.inputGuard);
                        this.target?.removeEventListener('change', this.inputGuard);
                    }

                    async handleClick(event) {
                        if (!this.active || this.card.contains(event.target)) return;

                        const targetClicked = this.target.contains(event.target);
                        if (this.step.type === 'click_target' && targetClicked) {
                            event.preventDefault();
                            event.stopImmediatePropagation();
                            const destination = event.target.closest('a')?.href || null;
                            await this.advance();
                            if (destination) window.location.assign(destination);
                            return;
                        }

                        if (!targetClicked || this.step.type !== 'input_target') {
                            event.preventDefault();
                            event.stopImmediatePropagation();
                        }
                    }

                    async advance() {
                        if (!this.active || this.nextButton.disabled) return;

                        try {
                            if (this.step.type === 'complete') {
                                await this.request(this.baseUrl + '/' + encodeURIComponent(this.tour.key) + '/complete', 'POST');
                                this.finish();
                                return;
                            }

                            const result = await this.request(
                                this.baseUrl + '/' + encodeURIComponent(this.tour.key) + '/steps/' + encodeURIComponent(this.step.id),
                                'POST',
                                { event: 'completed' }
                            );
                            this.removeGuards();
                            this.removeHighlight();
                            if (!result.current_step_id) {
                                this.finish();
                                return;
                            }

                            const nextStep = this.tour.steps.find((item) => item.id === result.current_step_id);
                            this.persistResume(result.current_step_id);
                            if (nextStep?.route === this.currentRoute) {
                                this.openStep(result.current_step_id);
                            } else {
                                this.finish(false);
                            }
                        } catch (error) {
                            window.alert(error.message);
                        }
                    }

                    async close(event) {
                        if (!this.active) return;
                        try {
                            await this.request(
                                this.baseUrl + '/' + encodeURIComponent(this.tour.key) + '/steps/' + encodeURIComponent(this.step.id),
                                'POST',
                                { event }
                            );
                        } catch (error) {
                            // UI must always recover even when progress cannot be saved.
                        }
                        this.finish();
                    }

                    finish(clearResume = true) {
                        this.removeGuards();
                        this.removeHighlight();
                        this.root.classList.add('hidden');
                        this.active = false;
                        if (clearResume) this.clearResume();
                        this.previousFocus?.focus?.();
                    }

                    positionCard() {
                        if (!this.active || !this.target || window.innerWidth < 640) return;

                        const rect = this.target.getBoundingClientRect();
                        const cardWidth = this.card.offsetWidth || 360;
                        const preferredLeft = rect.right + 16;
                        const left = preferredLeft + cardWidth < window.innerWidth - 16
                            ? preferredLeft
                            : Math.max(16, rect.left - cardWidth - 16);
                        const top = Math.max(16, Math.min(rect.top, window.innerHeight - this.card.offsetHeight - 16));
                        this.card.style.left = left + 'px';
                        this.card.style.top = top + 'px';
                        this.card.style.bottom = 'auto';
                    }
                }

                document.addEventListener('DOMContentLoaded', () => {
                    document.querySelectorAll('[data-admin-interactive-tour]').forEach((root) => {
                        new AdminInteractiveTour(root);
                    });
                });
            })();
        </script>
        <style>
            .admin-tour-target {
                position: relative !important;
                z-index: 1001 !important;
                outline: 4px solid rgb(59 130 246) !important;
                outline-offset: 4px !important;
                border-radius: 0.5rem;
                box-shadow: 0 0 0 9999px rgb(15 23 42 / 0.60) !important;
            }

            @media (prefers-reduced-motion: no-preference) {
                .admin-tour-target { animation: admin-tour-pulse 1.6s ease-in-out infinite; }
            }

            @keyframes admin-tour-pulse {
                50% { outline-color: rgb(147 197 253); }
            }
        </style>
    @endpush
@endonce
