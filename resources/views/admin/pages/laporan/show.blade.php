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
            <a href="{{ route('admin.laporan.tryout.export-excel', $tryout->tryout_id) }}"
                class="inline-flex items-center gap-2 rounded-lg bg-green px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700">
                <i class="ri-file-excel-line"></i> Laporan Excel
            </a>
            <a href="{{ route('admin.laporan.tryout.export-pdf', $tryout->tryout_id) }}"
                class="inline-flex items-center gap-2 rounded-lg bg-red px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                <i class="ri-file-pdf-line"></i> Laporan PDF
            </a>
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
            <a href="{{ route('admin.laporan.ranking', $tryout->tryout_id) }}"
                class="inline-flex items-center gap-2 rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary transition hover:bg-primary hover:text-white">
                <i class="ri-bar-chart-grouped-line"></i> Peringkat Nilai
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
            <div class="relative w-full lg:w-72">
                <input type="search" id="participant-search" placeholder="Cari peserta..."
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/10">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto rounded-lg border border-gray-100">
            <table class="w-full min-w-[1180px] text-left text-sm text-gray-600">
                <thead class="border-b border-gray-100 bg-gray-50 text-xs uppercase text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Peserta</th>
                        @foreach ($subtestDefinitions as $subtest)
                            <th class="px-4 py-3 text-center">
                                {{ $subtest['alias'] }}
                                <span class="mt-1 block max-w-32 normal-case text-[11px] font-medium text-gray-500">{{ $subtest['name'] }}</span>
                                <span class="mt-1 block normal-case text-[10px] font-normal text-gray-400">Skor · B / S / K</span>
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-center">Total</th>
                        <th class="px-4 py-3 text-center">Waktu Pengerjaan</th>
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
                            $startedAt = $attempt->started_at ? Carbon::parse($attempt->started_at) : null;
                            $finishedAt = $attempt->finished_at ? Carbon::parse($attempt->finished_at) : null;
                            $durationLabel = $startedAt && $finishedAt ? $startedAt->diffForHumans($finishedAt, true) : '-';
                            $statusLabel = match ($participant['status']) {
                                'sedang_mengerjakan' => 'Sedang Mengerjakan',
                                'selesai' => 'Selesai',
                                default => 'Belum Selesai',
                            };
                            $statusClass = match ($participant['status']) {
                                'sedang_mengerjakan' => 'border-primary/20 bg-primary/5 text-primary',
                                default => 'border-gray-200 bg-gray-50 text-gray-700',
                            };
                        @endphp
                        <tr data-participant-row data-name="{{ strtolower($user->name ?? '') }}" class="border-b border-gray-100 transition hover:bg-gray-50/70">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-gray-900">{{ $user->name ?? 'User' }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $user->email ?? '-' }}</p>
                                <p class="mt-1 text-[11px] text-gray-400">
                                    Attempt terakhir{{ $participant['total_attempts'] > 1 ? ' · Riwayat '.$participant['total_attempts'].'x' : '' }}
                                </p>
                            </td>
                            @foreach ($subtests as $subtest)
                                <td class="px-4 py-4 text-center">
                                    <p class="font-bold text-gray-900">{{ $subtest['score'] }}</p>
                                    <p class="mt-1 whitespace-nowrap text-xs font-medium text-gray-500">
                                        B {{ $subtest['correct'] }} · S {{ $subtest['wrong'] }} · K {{ $subtest['unanswered'] }}
                                    </p>
                                </td>
                            @endforeach
                            <td class="px-4 py-4 text-center">
                                <p class="font-bold text-primary">{{ $participant['latest_score'] }}</p>
                                <p class="mt-1 whitespace-nowrap text-xs font-medium text-gray-500">
                                    B {{ $participant['total_correct'] }} · S {{ $participant['total_wrong'] }} · K {{ $participant['total_unanswered'] }}
                                </p>
                            </td>
                            <td class="px-4 py-4">
                                <div class="min-w-[255px]">
                                    <p class="whitespace-nowrap text-[13px] font-medium text-gray-700">
                                        {{ $startedAt ? $startedAt->format('d M Y, H:i') : '-' }}
                                        <span class="mx-1.5 text-gray-300">—</span>
                                        {{ $finishedAt ? $finishedAt->format('d M Y, H:i') : '-' }}
                                    </p>
                                    <p class="mt-1 text-center text-xs text-gray-500">
                                        Durasi <span class="font-semibold text-gray-700">{{ $durationLabel }}</span>
                                    </p>
                                </div>
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
                            <td colspan="{{ 5 + $tryout->tryoutDetails->count() }}" class="px-6 py-12 text-center text-gray-500">
                                Belum ada peserta untuk tryout ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-gray-500"><span class="font-semibold text-gray-700">B</span> benar · <span class="font-semibold text-gray-700">S</span> salah · <span class="font-semibold text-gray-700">K</span> kosong</p>
    </section>

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

        });
    </script>
@endsection
