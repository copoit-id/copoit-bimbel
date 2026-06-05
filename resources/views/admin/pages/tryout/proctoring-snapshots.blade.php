@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold">Snapshot Proctoring</h2>
            <p class="text-gray-500">{{ $tryout->name }}</p>
        </div>
        <a href="{{ route('admin.tryout.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Total Snapshot</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $snapshots->total() }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Webcam</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $summary['webcam'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Screen</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $summary['screen'] ?? 0 }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($snapshots as $snapshot)
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <img src="{{ \Illuminate\Support\Facades\Storage::url($snapshot->file_path) }}" alt="Snapshot {{ $snapshot->type }}"
                class="h-48 w-full bg-gray-100 object-cover">
            <div class="space-y-3 p-4">
                <div class="flex items-center justify-between gap-3">
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $snapshot->type === 'webcam' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                        {{ strtoupper($snapshot->type) }}
                    </span>
                    <span class="text-xs text-gray-500">{{ number_format($snapshot->file_size / 1024, 1) }} KB</span>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ $snapshot->user?->name ?? 'User' }}</p>
                    <p class="text-xs text-gray-500">{{ $snapshot->user?->email }}</p>
                </div>
                <p class="text-xs text-gray-500">
                    {{ $snapshot->captured_at?->timezone('Asia/Jakarta')->format('d M Y H:i:s') ?? '-' }}
                </p>
                <form method="POST" action="{{ route('admin.tryout.proctoring-snapshots.destroy', [$tryout, $snapshot]) }}"
                    onsubmit="return confirm('Hapus snapshot ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="w-full rounded-lg border border-red-500 px-4 py-2 text-sm font-semibold text-red-500 transition-colors hover:bg-red-500 hover:text-white">
                        <i class="ri-delete-bin-line mr-1"></i>
                        Hapus
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full rounded-lg border border-gray-200 bg-white p-10 text-center">
            <i class="ri-camera-off-line text-4xl text-gray-300"></i>
            <p class="mt-3 font-semibold text-gray-700">Belum ada snapshot</p>
            <p class="text-sm text-gray-500">Snapshot akan muncul setelah peserta mengerjakan ujian dengan webcam atau screen check aktif.</p>
        </div>
        @endforelse
    </div>

    @if($snapshots->hasPages())
    <div class="rounded-lg border border-gray-200 bg-white p-4">
        {{ $snapshots->links() }}
    </div>
    @endif
</div>
@endsection
