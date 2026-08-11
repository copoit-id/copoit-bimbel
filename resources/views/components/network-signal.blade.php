@props([
    'interval' => 45000,
    'compactOnMobile' => false,
])

<div data-network-signal
    data-ping-url="{{ route('user.network-ping') }}"
    data-interval="{{ $interval }}"
    class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600"
    role="status"
    aria-live="polite">
    <span class="flex items-end gap-0.5" aria-hidden="true">
        <span data-network-bar="1" class="h-1.5 w-1 rounded-sm bg-gray-300"></span>
        <span data-network-bar="2" class="h-2.5 w-1 rounded-sm bg-gray-300"></span>
        <span data-network-bar="3" class="h-3.5 w-1 rounded-sm bg-gray-300"></span>
        <span data-network-bar="4" class="h-4.5 w-1 rounded-sm bg-gray-300"></span>
    </span>
    <span data-network-label @class(['hidden sm:inline' => $compactOnMobile])>Mengecek koneksi…</span>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                const states = {
                    checking: { label: 'Mengecek koneksi…', level: 0, color: 'bg-gray-300', text: 'text-gray-600' },
                    offline: { label: 'Tidak ada koneksi', level: 0, color: 'bg-red-400', text: 'text-red-600' },
                    critical: { label: 'Koneksi sangat lambat', level: 1, color: 'bg-red-600', text: 'text-red-700' },
                    weak: { label: 'Koneksi lemah', level: 1, color: 'bg-red-500', text: 'text-red-600' },
                    fair: { label: 'Koneksi cukup', level: 2, color: 'bg-yellow-500', text: 'text-yellow-700' },
                    stable: { label: 'Koneksi stabil', level: 3, color: 'bg-green-500', text: 'text-green-700' },
                    excellent: { label: 'Koneksi sangat stabil', level: 4, color: 'bg-green-600', text: 'text-green-700' },
                };

                function initialiseSignal(element) {
                    const pingUrl = element.dataset.pingUrl;
                    const interval = Math.max(5000, Number(element.dataset.interval) || 45000);
                    const label = element.querySelector('[data-network-label]');
                    const bars = Array.from(element.querySelectorAll('[data-network-bar]'));
                    let timer = null;
                    let connectionChangeTimer = null;
                    let checking = false;
                    let currentState = 'checking';
                    const networkInformation = navigator.connection || navigator.mozConnection || navigator.webkitConnection;

                    function render(stateName, latency = null) {
                        const state = states[stateName];
                        if (!state || !label) return;

                        if (currentState !== stateName) {
                            element.classList.remove('text-gray-600', 'text-red-600', 'text-red-700', 'text-yellow-700', 'text-green-700');
                            element.classList.add(state.text);
                            bars.forEach((bar, index) => {
                                bar.classList.remove('bg-gray-300', 'bg-red-400', 'bg-red-500', 'bg-red-600', 'bg-yellow-500', 'bg-green-500', 'bg-green-600');
                                bar.classList.add(index < state.level ? state.color : 'bg-gray-300');
                            });
                            currentState = stateName;
                        }

                        label.textContent = latency === null ? state.label : `${state.label} (${Math.round(latency)} ms)`;
                        element.setAttribute('aria-label', label.textContent);
                        window.dispatchEvent(new CustomEvent('network-signal:update', {
                            detail: {
                                state: stateName,
                                label: state.label,
                                latency,
                                blocking: ['offline', 'critical'].includes(stateName),
                            },
                        }));
                    }

                    function stateForLatency(latency) {
                        if (latency < 150) return 'excellent';
                        if (latency < 300) return 'stable';
                        if (latency < 700) return 'fair';
                        if (latency < 2000) return 'weak';
                        return 'critical';
                    }

                    function scheduleNextCheck() {
                        window.clearTimeout(timer);
                        if (!document.hidden) {
                            timer = window.setTimeout(checkConnection, interval);
                        }
                    }

                    async function checkConnection() {
                        if (checking || document.hidden) return;
                        window.clearTimeout(timer);
                        if (!navigator.onLine) {
                            render('offline');
                            scheduleNextCheck();
                            return;
                        }

                        checking = true;
                        if (currentState === 'checking') {
                            render('checking');
                        }
                        const controller = new AbortController();
                        const timeout = window.setTimeout(() => controller.abort(), 5000);
                        const startedAt = performance.now();

                        try {
                            const response = await fetch(pingUrl, {
                                cache: 'no-store',
                                credentials: 'same-origin',
                                signal: controller.signal,
                            });
                            const latency = performance.now() - startedAt;

                            render(response.ok ? stateForLatency(latency) : 'offline', response.ok ? latency : null);
                        } catch (error) {
                            render('offline');
                        } finally {
                            window.clearTimeout(timeout);
                            checking = false;
                            scheduleNextCheck();
                        }
                    }

                    function queueConnectionCheck() {
                        window.clearTimeout(connectionChangeTimer);
                        connectionChangeTimer = window.setTimeout(checkConnection, 300);
                    }

                    window.addEventListener('online', queueConnectionCheck);
                    window.addEventListener('offline', () => render('offline'));
                    window.addEventListener('focus', queueConnectionCheck);
                    window.addEventListener('network-signal:check', queueConnectionCheck);
                    networkInformation?.addEventListener?.('change', queueConnectionCheck);
                    document.addEventListener('visibilitychange', () => {
                        if (document.hidden) {
                            window.clearTimeout(timer);
                            return;
                        }

                        queueConnectionCheck();
                    });

                    checkConnection();
                }

                function initialiseAllSignals() {
                    document.querySelectorAll('[data-network-signal]').forEach(initialiseSignal);
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initialiseAllSignals, { once: true });
                } else {
                    initialiseAllSignals();
                }
            })();
        </script>
    @endpush
@endonce
