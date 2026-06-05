@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <x-breadcrumb>
                <x-slot name="items">
                    <x-breadcrumb-item href="{{ route('admin.laporan.index') }}" title="Laporan Tryout" />
                    <x-breadcrumb-item href="{{ route('admin.laporan.show', $tryout->tryout_id) }}" title="Detail Laporan" />
                    <x-breadcrumb-item href="" title="Snapshot Proctoring" />
                </x-slot>
            </x-breadcrumb>
            <h2 class="mt-4 text-2xl font-bold">Snapshot Proctoring</h2>
            <p class="text-gray-500">{{ $tryout->name }}</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            @if(($summary['webcam'] ?? 0) + ($summary['screen'] ?? 0) > 0)
                <form method="POST" action="{{ route('admin.laporan.proctoring-snapshots.destroy-all', $tryout) }}"
                    onsubmit="return confirm('Hapus semua gambar snapshot proctoring untuk tryout ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-500 px-4 py-2 text-white hover:bg-red-600 sm:w-auto">
                        <i class="ri-delete-bin-6-line"></i>
                        Hapus Semua Gambar
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.laporan.show', $tryout->tryout_id) }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">
                <i class="ri-arrow-left-line"></i>
                Kembali ke Laporan
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Total Snapshot</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ ($summary['webcam'] ?? 0) + ($summary['screen'] ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Webcam</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $summary['webcam'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Screen</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $summary['screen'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Total Pindah Tab</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalTabSwitchCount }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.laporan.proctoring-snapshots', $tryout) }}"
        class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 sm:flex-row">
        <input type="text" name="search" value="{{ $search }}"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
            placeholder="Cari nama atau email user">
        <button type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
            <i class="ri-search-line"></i>
            Cari User
        </button>
        @if($search !== '')
            <a href="{{ route('admin.laporan.proctoring-snapshots', $tryout) }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                Reset
            </a>
        @endif
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Waktu Terakhir</th>
                        <th class="px-4 py-3 text-center">Kamera</th>
                        <th class="px-4 py-3 text-center">Screen</th>
                        <th class="px-4 py-3 text-center">Pindah Tab</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($snapshotAttempts as $attempt)
                        <tr class="align-top">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-gray-900">{{ $attempt['user']?->name ?? 'User' }}</p>
                                <p class="text-xs text-gray-500">{{ $attempt['user']?->email }}</p>
                                <p class="mt-1 max-w-[220px] truncate text-[11px] text-gray-400" title="{{ $attempt['attempt_token'] }}">
                                    {{ $attempt['attempt_token'] }}
                                </p>
                            </td>
                            <td class="px-4 py-4 text-gray-600">
                                {{ $attempt['latest_captured_at']?->timezone('Asia/Jakarta')->format('d M Y H:i:s') ?? '-' }}
                            </td>
                            <td class="px-4 py-4 text-center font-semibold text-gray-900">{{ $attempt['webcam_count'] }}</td>
                            <td class="px-4 py-4 text-center font-semibold text-gray-900">{{ $attempt['screen_count'] }}</td>
                            <td class="px-4 py-4 text-center">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $attempt['tab_switch_count'] > 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $attempt['tab_switch_count'] }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                @php
                                    $modalId = 'snapshot-modal-' . $snapshotAttempts->firstItem() . '-' . $loop->index;
                                @endphp
                                <button type="button" data-open-snapshot-modal="{{ $modalId }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-primary px-3 py-2 text-xs font-semibold text-primary hover:bg-primary/5">
                                    <i class="ri-image-line"></i>
                                    Lihat Snapshot
                                </button>
                                <div id="{{ $modalId }}" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-gray-950/70 px-4 py-6">
                                    <div class="max-h-[90vh] w-full max-w-6xl overflow-hidden rounded-xl bg-white shadow-2xl">
                                        <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 text-left">
                                            <div>
                                                <p class="text-lg font-bold text-gray-900">Detail Snapshot</p>
                                                <p class="text-sm text-gray-500">{{ $attempt['user']?->name ?? 'User' }} - {{ $attempt['user']?->email }}</p>
                                                <p class="mt-1 text-xs text-gray-400">{{ $attempt['snapshot_count'] }} gambar, {{ number_format($attempt['total_size'] / 1024, 1) }} KB</p>
                                            </div>
                                            <button type="button" data-close-snapshot-modal="{{ $modalId }}"
                                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900">
                                                <i class="ri-close-line text-xl"></i>
                                            </button>
                                        </div>
                                        <div class="max-h-[calc(90vh-96px)] overflow-y-auto bg-gray-50 p-5 text-left">
                                            <div class="space-y-4">
                                                @foreach($attempt['captures'] as $captureIndex => $capture)
                                                    <div class="rounded-lg border border-gray-200 bg-white p-3">
                                                        <div class="mb-3 flex items-center justify-between gap-3">
                                                            <p class="text-sm font-semibold text-gray-800">Snapshot {{ $captureIndex + 1 }}</p>
                                                            <p class="text-xs text-gray-500">
                                                                {{ $capture['captured_at']?->timezone('Asia/Jakarta')->format('d M Y H:i:s') ?? '-' }}
                                                            </p>
                                                        </div>
                                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                                            @foreach(['webcam' => 'Foto Kamera', 'screen' => 'Screen'] as $type => $label)
                                                                @php
                                                                    $snapshot = $capture['snapshots'][$type] ?? null;
                                                                @endphp
                                                                <div class="overflow-hidden rounded-lg border border-gray-200">
                                                                    <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-3 py-2">
                                                                        <span class="text-xs font-semibold text-gray-700">{{ $label }}</span>
                                                                        @if($snapshot)
                                                                            <span class="text-[11px] text-gray-500">{{ number_format($snapshot->file_size / 1024, 1) }} KB</span>
                                                                        @else
                                                                            <span class="text-[11px] text-gray-400">Kosong</span>
                                                                        @endif
                                                                    </div>
                                                                    @if($snapshot)
                                                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($snapshot->file_path) }}" target="_blank">
                                                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($snapshot->file_path) }}"
                                                                                alt="{{ $label }}"
                                                                                class="h-56 w-full bg-gray-100 object-cover">
                                                                        </a>
                                                                        <form method="POST" action="{{ route('admin.laporan.proctoring-snapshots.destroy', [$tryout, $snapshot]) }}"
                                                                            class="p-2"
                                                                            onsubmit="return confirm('Hapus gambar ini?')">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit"
                                                                                class="w-full rounded-lg border border-red-500 px-3 py-2 text-xs font-semibold text-red-500 transition-colors hover:bg-red-500 hover:text-white">
                                                                                <i class="ri-delete-bin-line mr-1"></i>
                                                                                Hapus Gambar
                                                                            </button>
                                                                        </form>
                                                                    @else
                                                                        <div class="flex h-56 items-center justify-center bg-gray-50 text-xs text-gray-400">
                                                                            Tidak ada gambar
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <i class="ri-camera-off-line text-4xl text-gray-300"></i>
                                <p class="mt-3 font-semibold text-gray-700">Belum ada snapshot</p>
                                <p class="text-sm text-gray-500">Snapshot akan muncul setelah peserta mengerjakan ujian dengan webcam atau screen check aktif.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($snapshotAttempts->hasPages())
    <div class="rounded-lg border border-gray-200 bg-white p-4">
        {{ $snapshotAttempts->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function openModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        document.querySelectorAll('[data-open-snapshot-modal]').forEach(button => {
            button.addEventListener('click', function() {
                openModal(button.dataset.openSnapshotModal);
            });
        });

        document.querySelectorAll('[data-close-snapshot-modal]').forEach(button => {
            button.addEventListener('click', function() {
                closeModal(button.dataset.closeSnapshotModal);
            });
        });

        document.querySelectorAll('[id^="snapshot-modal-"]').forEach(modal => {
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal(modal.id);
                }
            });
        });

        document.addEventListener('keydown', function(event) {
            if (event.key !== 'Escape') return;
            document.querySelectorAll('[id^="snapshot-modal-"].flex').forEach(modal => {
                closeModal(modal.id);
            });
        });
    });
</script>
@endpush
