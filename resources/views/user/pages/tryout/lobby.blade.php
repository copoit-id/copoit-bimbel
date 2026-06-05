@extends('user.layout.tryout')
@section('title', 'Lobby')
@section('content')
    <div class="lobby flex justify-center items-center w-full min-h-screen text-black">
        <div class="w-3xl mx-auto p-8 mt-18 bg-white shadow rounded-lg flex justify-center items-center flex-col gap-2">
            <div class="rounded-lg text-center">
                <h1 class="text-2xl font-bold mb-4">{{ $tryout->name }}</h1>
                <p class="text-gray-600 mb-6">{{ $tryout->description }}</p>

                @if (isset($tryoutDetails) && $tryoutDetails->count() > 1)
                    <!-- SKD Full Information -->
                    {{-- <div class="bg-blue-50 border border-primary/10 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-primary mb-4">
                    <i class="ri-information-line mr-2"></i>Informasi SKD Full
                </h3>
                <div class="space-y-4">
                    @foreach ($tryoutDetails as $index => $detail)
                    <div class="flex justify-between items-center p-3 bg-white rounded-lg">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm font-semibold">
                                {{ $index + 1 }}
                            </div>
                            <div class="text-left">
                                <div class="font-semibold">{{ strtoupper($detail->type_subtest) }}</div>
                                <div class="text-sm text-gray-600">
                                    @if ($detail->type_subtest === 'twk')
                                    Tes Wawasan Kebangsaan
                                    @elseif($detail->type_subtest === 'tiu')
                                    Tes Intelegensi Umum
                                    @elseif($detail->type_subtest === 'tkp')
                                    Tes Karakteristik Pribadi
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            @php
                            $questionCount = \App\Models\Question::where('tryout_detail_id',
                            $detail->tryout_detail_id)->count();
                            @endphp
                            <div class="font-semibold">{{ $questionCount }} Soal</div>
                            <div class="text-sm text-gray-600">{{ $detail->duration }} Menit</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div> --}}

                    <!-- Total Information for SKD Full -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="flex items-center gap-3 bg-white p-4 rounded-lg border border-border mt-6">
                            <i
                                class="ri-book-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
                            <div>
                                <p class="text-[24px] font-bold">{{ $totalQuestions }}</p>
                                <p class="text-[12px] mt-[-6px] font-light">Total Soal</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-white p-4 rounded-lg border border-border mt-6">
                            <i
                                class="ri-book-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
                            <div>
                                <p class="text-[24px] font-bold">{{ $totalDuration }}</p>
                                <p class="text-[12px] mt-[-6px] font-light">Total Waktu (menit)</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-white p-4 rounded-lg border border-border mt-6">
                            <i
                                class="ri-book-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
                            <div>
                                <p class="text-[24px] font-bold">{{ $tryoutDetails->count() }}</p>
                                <p class="text-[12px] mt-[-6px] font-light">Jumlah Jumlah</p>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Single Subtest Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-primary">{{ $totalQuestions ?? 0 }}</div>
                            <div class="text-sm text-gray-600">Jumlah Soal</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-primary">{{ $totalDuration ?? 0 }}</div>
                            <div class="text-sm text-gray-600">Durasi (Menit)</div>
                        </div>
                    </div>
                @endif

                <div class="flex flex-col gap-2 mt-4">
                    <span class="flex items-center gap-2 justify-start">
                        <i class="ri-checkbox-circle-fill text-lg"></i>
                        <p>Tidak ada aktifitas lain di akun kamu selama mengerjakan tryout.</p>
                    </span>
                    <span class="flex items-center gap-2 justify-start">
                        <i class="ri-checkbox-circle-fill text-lg"></i>
                        <p>Pastikan koneksi internet stabil.</p>
                    </span>
                    <span class="flex items-center gap-2 justify-start">
                        <i class="ri-checkbox-circle-fill text-lg"></i>
                        <p>Jawab semua soal dengan teliti.</p>
                    </span>
                </div>

                @if($tryout->enable_webcam_check || $tryout->enable_screen_check)
                    <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4 text-left">
                        <div class="mb-3">
                            <h2 class="text-base font-semibold text-gray-900">Pengecekan Perangkat</h2>
                            <p class="text-sm text-gray-600">Selesaikan pengecekan yang diperlukan sebelum mulai tryout.</p>
                        </div>

                        <div class="space-y-3">
                            @if($tryout->enable_webcam_check)
                                <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-3">
                                    <span class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                        <i id="webcamCheckIcon" class="ri-checkbox-blank-circle-line text-lg text-gray-400"></i>
                                        Kamera
                                    </span>
                                    <button type="button" id="checkWebcamBtn"
                                        class="rounded-lg border border-primary px-3 py-1.5 text-sm font-semibold text-primary hover:bg-primary/5">
                                        Aktifkan Kamera
                                    </button>
                                </div>
                            @endif

                            @if($tryout->enable_screen_check)
                                <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-3">
                                    <span class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                        <i id="screenCheckIcon" class="ri-checkbox-blank-circle-line text-lg text-gray-400"></i>
                                        Share Screen
                                    </span>
                                    <button type="button" id="checkScreenBtn"
                                        class="rounded-lg border border-primary px-3 py-1.5 text-sm font-semibold text-primary hover:bg-primary/5">
                                        Aktifkan Screen
                                    </button>
                                </div>
                            @endif
                        </div>

                        <p id="proctoringLobbyError" class="mt-3 hidden rounded-lg bg-red/5 px-3 py-2 text-sm font-semibold text-red"></p>
                    </div>
                @endif

                <a href="{{ route('user.tryout.index', ['id_package' => $package ? $package->package_id : 'free', 'id_tryout' => $tryout->tryout_id, 'number' => 1]) }}"
                    id="startTryoutBtn"
                    class="mt-4 px-8 py-1.5 bg-primary flex justify-center text-white rounded-xl {{ ($tryout->enable_webcam_check || $tryout->enable_screen_check) ? 'pointer-events-none opacity-50' : '' }}"
                    aria-disabled="{{ ($tryout->enable_webcam_check || $tryout->enable_screen_check) ? 'true' : 'false' }}">
                    Mulai Tryout
                </a>

                @if($tryout->enable_webcam_check)
                    <video id="lobbyWebcamPreview" class="pointer-events-none fixed bottom-0 right-0 h-px w-px opacity-0" autoplay muted playsinline></video>
                @endif
                @if($tryout->enable_screen_check)
                    <video id="lobbyScreenPreview" class="pointer-events-none fixed bottom-0 right-0 h-px w-px opacity-0" autoplay muted playsinline></video>
                @endif
            </div>
        </div>
    </div>

    @if($tryout->enable_webcam_check || $tryout->enable_screen_check)
        <div id="tryoutFrameShell" class="fixed inset-0 z-[2147483000] hidden bg-white">
            <iframe id="tryoutFrame" title="Tryout" class="h-full w-full border-0" allow="camera; display-capture; fullscreen"></iframe>
        </div>

        <div id="proctoringResumeOverlay"
            class="fixed inset-0 z-[2147483647] hidden items-center justify-center bg-gray-950/85 px-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 text-center shadow-2xl">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10">
                    <i class="ri-shield-check-line text-3xl text-primary"></i>
                </div>
                <h3 class="mb-2 text-lg font-bold text-gray-900">Pengawasan Terhenti</h3>
                <p id="proctoringResumeMessage" class="text-sm leading-relaxed text-gray-600">
                    Aktifkan kembali pengawasan untuk melanjutkan tryout.
                </p>
                <button type="button" id="resumeProctoringBtn"
                    class="mt-5 w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary/90">
                    Aktifkan Kembali
                </button>
            </div>
        </div>
    @endif
@endsection
@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const proctoringSettings = {
                webcam: @json((bool) $tryout->enable_webcam_check),
                screen: @json((bool) $tryout->enable_screen_check),
            };
            const csrfToken = @json(csrf_token());
            const snapshotUrl = @json(route('user.tryout.proctoring-snapshot', [
                $package ? $package->package_id : 'free',
                $tryout->tryout_id
            ]));
            const checkState = {
                webcam: !proctoringSettings.webcam,
                screen: !proctoringSettings.screen,
            };
            const mediaStreams = {};
            const snapshotTimers = {};
            let examStarted = false;
            let examStartInProgress = false;
            let attemptToken = null;
            let suppressTrackEnded = false;
            const storageKey = 'tryout_proctoring_checked_{{ $tryout->tryout_id }}';
            const startButton = document.getElementById('startTryoutBtn');
            const errorBox = document.getElementById('proctoringLobbyError');
            const frameShell = document.getElementById('tryoutFrameShell');
            const tryoutFrame = document.getElementById('tryoutFrame');
            const resumeOverlay = document.getElementById('proctoringResumeOverlay');
            const resumeMessage = document.getElementById('proctoringResumeMessage');
            const resumeButton = document.getElementById('resumeProctoringBtn');

            function showError(message) {
                if (!errorBox) return;
                errorBox.textContent = message;
                errorBox.classList.remove('hidden');
            }

            function clearError() {
                if (!errorBox) return;
                errorBox.textContent = '';
                errorBox.classList.add('hidden');
            }

            function isStreamActive(stream) {
                return stream && stream.getVideoTracks().some(track => track.readyState === 'live');
            }

            function getRequiredTypeLabel(type) {
                return type === 'webcam' ? 'Kamera' : 'Share Screen';
            }

            function setChecked(type) {
                suppressTrackEnded = false;
                checkState[type] = true;
                const icon = document.getElementById(type === 'webcam' ? 'webcamCheckIcon' : 'screenCheckIcon');
                const button = document.getElementById(type === 'webcam' ? 'checkWebcamBtn' : 'checkScreenBtn');

                if (icon) {
                    icon.className = 'ri-checkbox-circle-fill text-lg text-green';
                }

                if (button) {
                    button.textContent = 'Aktif';
                    button.disabled = true;
                    button.classList.add('border-gray-200', 'bg-gray-100', 'text-gray-500');
                    button.classList.remove('border-primary', 'text-primary', 'hover:bg-primary/5');
                }

                updateStartButton();
            }

            function updateStartButton() {
                const ready = checkState.webcam && checkState.screen;
                if (!startButton) return;

                startButton.classList.toggle('pointer-events-none', !ready);
                startButton.classList.toggle('opacity-50', !ready);
                startButton.setAttribute('aria-disabled', ready ? 'false' : 'true');

                if (ready) {
                    sessionStorage.setItem(storageKey, JSON.stringify({
                        webcam: checkState.webcam,
                        screen: checkState.screen,
                        checkedAt: Date.now(),
                    }));
                }
            }

            function markInactive(type) {
                checkState[type] = false;
                const icon = document.getElementById(type === 'webcam' ? 'webcamCheckIcon' : 'screenCheckIcon');
                const button = document.getElementById(type === 'webcam' ? 'checkWebcamBtn' : 'checkScreenBtn');

                if (icon) {
                    icon.className = 'ri-checkbox-blank-circle-line text-lg text-gray-400';
                }

                if (button) {
                    button.textContent = type === 'webcam' ? 'Aktifkan Kamera' : 'Aktifkan Screen';
                    button.disabled = false;
                    button.classList.remove('border-gray-200', 'bg-gray-100', 'text-gray-500');
                    button.classList.add('border-primary', 'text-primary', 'hover:bg-primary/5');
                }

                updateStartButton();
            }

            function showResumeOverlay(type) {
                if (!examStarted || !resumeOverlay) return;

                if (resumeMessage) {
                    resumeMessage.textContent = `${getRequiredTypeLabel(type)} terhenti. Aktifkan kembali pengawasan untuk melanjutkan tryout.`;
                }

                resumeOverlay.dataset.type = type;
                resumeOverlay.classList.remove('hidden');
                resumeOverlay.classList.add('flex');
            }

            function hideResumeOverlay() {
                if (!resumeOverlay) return;
                resumeOverlay.classList.add('hidden');
                resumeOverlay.classList.remove('flex');
                delete resumeOverlay.dataset.type;
            }

            function clearSnapshotTimer(type) {
                if (!snapshotTimers[type]) return;
                clearInterval(snapshotTimers[type]);
                delete snapshotTimers[type];
            }

            function attachEndedHandler(type, stream) {
                stream.getVideoTracks().forEach(track => {
                    track.addEventListener('ended', function() {
                        if (suppressTrackEnded) return;
                        clearSnapshotTimer(type);
                        delete mediaStreams[type];
                        markInactive(type);
                        showResumeOverlay(type);
                    });
                });
            }

            async function attachPreview(type, stream) {
                const preview = document.getElementById(type === 'webcam' ? 'lobbyWebcamPreview' : 'lobbyScreenPreview');
                if (!preview) return;
                preview.srcObject = stream;
                await preview.play().catch(() => {});
            }

            async function activateStream(type) {
                if (isStreamActive(mediaStreams[type])) {
                    setChecked(type);
                    return mediaStreams[type];
                }

                const stream = type === 'webcam'
                    ? await navigator.mediaDevices.getUserMedia({
                        video: { width: { ideal: 640 }, height: { ideal: 360 }, facingMode: 'user' },
                        audio: false,
                    })
                    : await navigator.mediaDevices.getDisplayMedia({
                        video: { width: { ideal: 960 }, height: { ideal: 540 } },
                        selfBrowserSurface: 'exclude',
                        monitorTypeSurfaces: 'include',
                        audio: false,
                    });

                mediaStreams[type] = stream;
                attachEndedHandler(type, stream);
                await attachPreview(type, stream);
                setChecked(type);
                return stream;
            }

            function ensureRequiredStreamsActive() {
                clearError();

                if (proctoringSettings.webcam && !isStreamActive(mediaStreams.webcam)) {
                    markInactive('webcam');
                }

                if (proctoringSettings.screen && !isStreamActive(mediaStreams.screen)) {
                    markInactive('screen');
                }

                return (!proctoringSettings.webcam || isStreamActive(mediaStreams.webcam))
                    && (!proctoringSettings.screen || isStreamActive(mediaStreams.screen));
            }

            async function checkWebcam() {
                clearError();
                const button = document.getElementById('checkWebcamBtn');
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Mengaktifkan...';
                }

                try {
                    await activateStream('webcam');
                } catch (e) {
                    if (button) {
                        button.disabled = false;
                        button.textContent = 'Aktifkan Kamera';
                    }
                    showError('Kamera wajib diizinkan sebelum mulai tryout.');
                }
            }

            async function checkScreen() {
                clearError();
                const button = document.getElementById('checkScreenBtn');
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Mengaktifkan...';
                }

                try {
                    await activateStream('screen');
                } catch (e) {
                    if (button) {
                        button.disabled = false;
                        button.textContent = 'Aktifkan Screen';
                    }
                    showError('Screen sharing wajib diizinkan sebelum mulai tryout.');
                }
            }

            function getAttemptTokenFromFrame() {
                try {
                    const documentInFrame = tryoutFrame?.contentDocument;
                    const input = documentInFrame?.querySelector('input[name="attempt_token"]');
                    return input?.value || null;
                } catch (e) {
                    return null;
                }
            }

            function isFrameOnResultPage() {
                try {
                    return tryoutFrame?.contentWindow?.location?.pathname?.includes('/hasil') || false;
                } catch (e) {
                    return false;
                }
            }

            function getFrameLocation() {
                try {
                    const location = tryoutFrame?.contentWindow?.location;
                    if (!location || location.href === 'about:blank') return null;

                    return {
                        href: location.href,
                        pathname: location.pathname,
                    };
                } catch (e) {
                    return null;
                }
            }

            function isFrameOnTryoutPage() {
                const location = getFrameLocation();
                return location ? /\/tryout\/[^/]+$/.test(location.pathname) : false;
            }

            function promoteFrameToTop() {
                const location = getFrameLocation();
                if (!location) return;

                stopAllMonitoring();
                window.location.replace(location.href);
            }

            async function captureSnapshot(type) {
                const stream = mediaStreams[type];
                if (!attemptToken || !isStreamActive(stream)) return;

                const video = document.getElementById(type === 'webcam' ? 'lobbyWebcamPreview' : 'lobbyScreenPreview');
                if (!video || video.readyState < 2) return;

                const canvas = document.createElement('canvas');
                canvas.width = 480;
                canvas.height = 270;
                const context = canvas.getContext('2d');
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                const image = canvas.toDataURL('image/jpeg', 0.42);

                await fetch(snapshotUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        attempt_token: attemptToken,
                        type,
                        image,
                    }),
                });
            }

            function startSnapshotTimer(type) {
                if (!isStreamActive(mediaStreams[type]) || snapshotTimers[type]) return;

                setTimeout(() => {
                    captureSnapshot(type).catch(() => {});
                }, 1500);

                snapshotTimers[type] = setInterval(() => {
                    captureSnapshot(type).catch(() => {});
                }, 600000);
            }

            function stopAllMonitoring() {
                suppressTrackEnded = true;
                examStarted = false;
                hideResumeOverlay();
                Object.keys(snapshotTimers).forEach(clearSnapshotTimer);
                Object.values(mediaStreams).forEach(stream => {
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                    }
                });
                Object.keys(mediaStreams).forEach(type => delete mediaStreams[type]);
            }

            function startFrameMonitoring() {
                let retries = 0;
                const tokenReader = setInterval(() => {
                    if (isFrameOnResultPage()) {
                        clearInterval(tokenReader);
                        promoteFrameToTop();
                        return;
                    }

                    attemptToken = getAttemptTokenFromFrame();
                    if (attemptToken) {
                        clearInterval(tokenReader);
                        if (proctoringSettings.webcam) startSnapshotTimer('webcam');
                        if (proctoringSettings.screen) startSnapshotTimer('screen');
                    }

                    retries += 1;
                    if (retries > 40) {
                        clearInterval(tokenReader);
                    }
                }, 500);
            }

            async function startTryoutInFrame(event) {
                if (!proctoringSettings.webcam && !proctoringSettings.screen) return;

                event.preventDefault();
                if (!frameShell || !tryoutFrame || examStarted || examStartInProgress) return;

                const cleanTryoutUrl = startButton.href;
                const iframeTryoutUrl = new URL(cleanTryoutUrl, window.location.origin);
                iframeTryoutUrl.searchParams.set('lobby_proctoring', '1');

                examStartInProgress = true;
                startButton.classList.add('pointer-events-none', 'opacity-50');
                startButton.textContent = 'Menyiapkan Tryout...';

                try {
                    const ready = ensureRequiredStreamsActive();
                    if (!ready) {
                        showError('Kamera dan screen sharing perlu aktif ulang sebelum mulai tryout.');
                        return;
                    }
                } catch (e) {
                    showError('Kamera dan screen sharing perlu aktif ulang sebelum mulai tryout.');
                    return;
                } finally {
                    examStartInProgress = false;
                    startButton.textContent = 'Mulai Tryout';
                    updateStartButton();
                }

                examStarted = true;
                frameShell.classList.remove('hidden');
                tryoutFrame.addEventListener('load', function() {
                    if (isFrameOnResultPage()) {
                        promoteFrameToTop();
                        return;
                    }

                    if (!isFrameOnTryoutPage()) {
                        promoteFrameToTop();
                        return;
                    }

                    startFrameMonitoring();
                });
                tryoutFrame.src = iframeTryoutUrl.toString();
            }

            if ((proctoringSettings.webcam || proctoringSettings.screen) && !navigator.mediaDevices) {
                showError('Browser tidak mendukung pengecekan perangkat.');
            }

            if (proctoringSettings.screen && navigator.mediaDevices && !navigator.mediaDevices.getDisplayMedia) {
                showError('Browser tidak mendukung screen sharing.');
            }

            document.getElementById('checkWebcamBtn')?.addEventListener('click', checkWebcam);
            document.getElementById('checkScreenBtn')?.addEventListener('click', checkScreen);
            startButton?.addEventListener('click', startTryoutInFrame);
            resumeButton?.addEventListener('click', async function() {
                const type = resumeOverlay?.dataset.type;
                if (!type) return;

                resumeButton.disabled = true;
                resumeButton.textContent = 'Mengaktifkan...';

                try {
                    await activateStream(type);
                    startSnapshotTimer(type);
                    hideResumeOverlay();
                } catch (e) {
                    showError(`${getRequiredTypeLabel(type)} wajib aktif untuk melanjutkan tryout.`);
                } finally {
                    resumeButton.disabled = false;
                    resumeButton.textContent = 'Aktifkan Kembali';
                }
            });
            window.addEventListener('beforeunload', stopAllMonitoring);
            updateStartButton();
        });
    </script>
@endsection
@section('styles')
    <style>
        /* Add any additional styles if needed */
    </style>
@endsection
