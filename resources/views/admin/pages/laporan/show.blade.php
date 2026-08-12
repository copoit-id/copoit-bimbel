@extends('admin.layout.admin')
@section('title', 'Detail Laporan Tryout')

@php
    use Carbon\Carbon;
@endphp

@section('content')
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <x-breadcrumb>
            <x-slot name="items">
                <x-breadcrumb-item href="{{ route('admin.laporan.index') }}" title="Laporan Tryout" />
                <x-breadcrumb-item href="" title="Detail Tryout" />
            </x-slot>
        </x-breadcrumb>
        <div class="flex flex-wrap gap-2">
            @if ($leaderboardPackageId ?? false)
                <a href="{{ route('admin.leaderboard.show', [$leaderboardPackageId, $tryout->tryout_id]) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary transition hover:bg-primary hover:text-white">
                    <i class="ri-trophy-line"></i> Leaderboard
                </a>
            @endif
            <a href="{{ route('admin.tryout.preview', $tryout->tryout_id) }}"
                class="inline-flex items-center gap-2 rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary transition hover:bg-primary hover:text-white">
                <i class="ri-eye-line"></i> Preview Tryout
            </a>
            @if ($hasSnapshotProctoring)
                <a href="{{ route('admin.laporan.proctoring-snapshots', $tryout->tryout_id) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary transition hover:bg-primary hover:text-white">
                    <i class="ri-camera-line"></i> Snapshot
                </a>
            @endif
        </div>
    </div>

    <x-page-desc title="{{ $tryout->name }}" />

    <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-border bg-white p-5">
            <p class="text-sm text-gray-500">Subtest</p>
            <p class="text-3xl font-bold text-gray-900">{{ $statistics['total_subtests'] }}</p>
            <p class="text-sm text-gray-400">{{ $statistics['total_questions'] }} soal</p>
        </div>
        <div class="rounded-xl border border-border bg-white p-5">
            <p class="text-sm text-gray-500">Peserta</p>
            <p class="text-3xl font-bold text-gray-900">{{ $statistics['total_participants'] }}</p>
            <p class="text-sm text-gray-400">{{ $statistics['completion_rate'] }}% selesai</p>
        </div>
        <div class="rounded-xl border border-border bg-white p-5">
            <p class="text-sm text-gray-500">Rata-rata skor</p>
            <p class="text-3xl font-bold text-gray-900">{{ $statistics['average_score'] }}</p>
            <p class="text-sm text-gray-400">Tertinggi {{ $statistics['highest_score'] }}</p>
        </div>
    </div>

    <section class="mt-6 rounded-xl border border-border bg-white p-5 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Peserta Tryout</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-900">Ringkasan Pengerjaan</h3>
                <p class="mt-1 text-sm text-gray-500">Menampilkan attempt terakhir setiap peserta beserta hasil seluruh subtest.</p>
            </div>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                <div class="relative w-full sm:w-72">
                    <input type="search" id="participant-search" placeholder="Cari peserta..."
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/10">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
                <a href="{{ route('admin.laporan.show', array_filter([$tryout->tryout_id, 'ranking' => $showLiveScore ? null : 1], fn ($value) => $value !== null)) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-primary px-4 py-2.5 text-sm font-semibold text-primary transition hover:bg-primary hover:text-white">
                    <i class="{{ $showLiveScore ? 'ri-eye-off-line' : 'ri-bar-chart-grouped-line' }}"></i>
                    {{ $showLiveScore ? 'Sembunyikan Peringkat' : 'Tampilkan Peringkat Nilai' }}
                </a>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto rounded-lg border border-gray-100">
            <table class="w-full min-w-[1050px] text-left text-sm text-gray-600">
                <thead class="border-b border-gray-100 bg-gray-50 text-xs uppercase text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Peserta</th>
                        @foreach ($tryout->tryoutDetails as $detail)
                            <th class="px-4 py-3 text-center">
                                {{ strtoupper($detail->type_subtest) }}
                                <span class="mt-1 block normal-case text-[10px] font-normal text-gray-400">Skor · B / S / K</span>
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-center">Total</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($participants as $participant)
                        @php
                            $user = $participant['user'];
                            $attempt = $participant['latest_attempt'];
                            $subtests = $participant['subtests'];
                            $lastFinished = $participant['last_finished'] ? Carbon::parse($participant['last_finished']) : null;
                            $statusLabel = match ($participant['status']) {
                                'sedang_mengerjakan' => 'Sedang Mengerjakan',
                                'selesai' => 'Selesai',
                                default => 'Belum Selesai',
                            };
                            $statusClass = match ($participant['status']) {
                                'sedang_mengerjakan' => 'border-amber-200 bg-amber-50 text-amber-700',
                                'selesai' => 'border-green-200 bg-green-50 text-green-700',
                                default => 'border-gray-200 bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <tr data-participant-row data-name="{{ strtolower($user->name ?? '') }}" class="border-b border-gray-100 transition hover:bg-gray-50/70">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-gray-900">{{ $user->name ?? 'User' }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $user->email ?? '-' }}</p>
                                <p class="mt-1 text-[11px] text-gray-400">
                                    Attempt terakhir{{ $participant['total_attempts'] > 1 ? ' · Riwayat '.$participant['total_attempts'].'x' : '' }}
                                    {{ $lastFinished ? ' · '.$lastFinished->format('d M Y H:i') : '' }}
                                </p>
                            </td>
                            @foreach ($subtests as $subtest)
                                <td class="px-4 py-4 text-center">
                                    <p class="font-bold text-gray-900">{{ $subtest['score'] }}</p>
                                    <p class="mt-1 whitespace-nowrap text-xs font-semibold">
                                        <span class="text-green-600">{{ $subtest['correct'] }}</span>
                                        <span class="text-gray-300">/</span>
                                        <span class="text-red-500">{{ $subtest['wrong'] }}</span>
                                        <span class="text-gray-300">/</span>
                                        <span class="text-gray-500">{{ $subtest['unanswered'] }}</span>
                                    </p>
                                </td>
                            @endforeach
                            <td class="px-4 py-4 text-center">
                                <p class="font-bold text-primary">{{ $participant['latest_score'] }}</p>
                                <p class="mt-1 whitespace-nowrap text-xs font-semibold">
                                    <span class="text-green-600">{{ $participant['total_correct'] }}</span>
                                    <span class="text-gray-300">/</span>
                                    <span class="text-red-500">{{ $participant['total_wrong'] }}</span>
                                    <span class="text-gray-300">/</span>
                                    <span class="text-gray-500">{{ $participant['total_unanswered'] }}</span>
                                </p>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('admin.laporan.attempt', [$tryout->tryout_id, $attempt->attempt_token]) }}"
                                        class="inline-flex items-center gap-1 rounded-lg border border-primary px-3 py-2 text-xs font-semibold text-primary transition hover:bg-primary hover:text-white">
                                        <i class="ri-file-list-3-line"></i> Detail
                                    </a>
                                    @if (! in_array($attempt->attempt_status, ['completed', 'pending_release']))
                                        <button type="button" data-open-time-modal
                                            data-time-url="{{ route('admin.laporan.add-time', [$tryout->tryout_id, $user->id]) }}"
                                            data-user-name="{{ $user->name }}" data-extra-minutes="{{ $participant['extra_minutes'] ?? 0 }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-primary/50 px-3 py-2 text-xs font-semibold text-primary transition hover:bg-primary hover:text-white">
                                            <i class="ri-time-line"></i> Waktu
                                        </button>
                                    @endif
                                    <form action="{{ route('admin.laporan.reset-attempt', [$tryout->tryout_id, $attempt->attempt_token]) }}" method="POST"
                                        onsubmit="return confirm('Reset attempt ini? Semua jawaban pada attempt ini akan dihapus.');">
                                        @csrf
                                        <button type="submit" title="Reset attempt"
                                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-500 hover:text-white">
                                            <i class="ri-refresh-line"></i><span class="sr-only">Reset</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 4 + $tryout->tryoutDetails->count() }}" class="px-6 py-12 text-center text-gray-500">
                                Belum ada peserta untuk tryout ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-gray-500"><span class="font-semibold text-green-600">B</span> benar · <span class="font-semibold text-red-500">S</span> salah · <span class="font-semibold text-gray-500">K</span> kosong</p>
    </section>

    @if ($showLiveScore)
        <section class="mt-6 rounded-xl border border-border bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Live Score</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">Peringkat Nilai Per Subtest</h3>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ $publicLiveScoreUrl }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary transition hover:bg-primary hover:text-white">
                        <i class="ri-external-link-line"></i> Public Link
                    </a>
                    <button type="button" id="copy-live-score-link" data-link="{{ $publicLiveScoreUrl }}" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90">
                        <i class="ri-link-m"></i> Copy Link
                    </button>
                </div>
            </div>
            <div class="mt-5 overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <th class="px-4 py-3">No</th><th class="px-4 py-3">Nama</th>
                            @foreach ($liveScore['subtests'] ?? [] as $subtest)
                                <th class="px-4 py-3 text-center">{{ $subtest['label'] }}</th>
                            @endforeach
                            <th class="px-4 py-3 text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($liveScore['rows'] ?? [] as $row)
                            <tr class="border-b border-gray-100">
                                <td class="px-4 py-3 font-semibold text-gray-800">{{ $row['rank'] }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $row['name'] }}</td>
                                @foreach ($liveScore['subtests'] ?? [] as $subtest)
                                    @php $scoreValue = $row['scores'][$subtest['tryout_detail_id']] ?? null; @endphp
                                    <td class="px-4 py-3 text-center font-semibold {{ is_null($scoreValue) ? 'text-gray-400' : 'text-gray-800' }}">{{ is_null($scoreValue) ? '-' : rtrim(rtrim(number_format((float) $scoreValue, 2, '.', ''), '0'), '.') }}</td>
                                @endforeach
                                <td class="px-4 py-3 text-center font-bold text-gray-900">{{ rtrim(rtrim(number_format((float) ($row['total'] ?? 0), 2, '.', ''), '0'), '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ 3 + count($liveScore['subtests'] ?? []) }}" class="px-4 py-8 text-center text-gray-500">Belum ada data live score yang tersubmit.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div id="time-modal" class="fixed inset-0 z-50 hidden items-center justify-center px-4">
        <div class="absolute inset-0 bg-black/50" data-close-time-modal></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <div><p class="text-sm text-gray-500">Tambah waktu</p><h3 id="time-modal-user" class="text-lg font-semibold text-gray-900"></h3></div>
                <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-600" data-close-time-modal>&times;</button>
            </div>
            <form id="time-modal-form" method="POST" class="mt-5">
                @csrf
                <label class="mb-2 block text-sm font-semibold text-gray-700">Tambahan menit</label>
                <input id="time-modal-input" type="number" name="extra_minutes" min="0" max="300" class="w-full rounded-lg border border-gray-200 px-4 py-2" placeholder="Contoh: 15">
                <p class="mt-2 text-xs text-gray-500">Isi 0 untuk menghapus tambahan waktu.</p>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" data-close-time-modal class="rounded-lg border border-gray-200 px-4 py-2 text-gray-600 hover:bg-gray-50">Batal</button>
                    <button type="submit" class="rounded-lg bg-primary px-4 py-2 font-semibold text-white hover:bg-primary/90">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('participant-search')?.addEventListener('input', (event) => {
                const term = event.target.value.toLowerCase();
                document.querySelectorAll('[data-participant-row]').forEach((row) => {
                    row.classList.toggle('hidden', !(row.dataset.name || '').includes(term));
                });
            });

            const timeModal = document.getElementById('time-modal');
            document.querySelectorAll('[data-open-time-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    document.getElementById('time-modal-form').action = button.dataset.timeUrl;
                    document.getElementById('time-modal-user').textContent = button.dataset.userName;
                    document.getElementById('time-modal-input').value = button.dataset.extraMinutes || 0;
                    timeModal.classList.remove('hidden');
                    timeModal.classList.add('flex');
                });
            });
            document.querySelectorAll('[data-close-time-modal]').forEach((button) => button.addEventListener('click', () => {
                timeModal.classList.add('hidden');
                timeModal.classList.remove('flex');
            }));

            document.getElementById('copy-live-score-link')?.addEventListener('click', async (event) => {
                const button = event.currentTarget;
                try {
                    await navigator.clipboard.writeText(button.dataset.link || '');
                    button.innerHTML = '<i class="ri-check-line"></i> Link Tersalin';
                    setTimeout(() => { button.innerHTML = '<i class="ri-link-m"></i> Copy Link'; }, 1500);
                } catch (_) {
                    button.textContent = 'Gagal menyalin';
                }
            });
        });
    </script>
@endsection
