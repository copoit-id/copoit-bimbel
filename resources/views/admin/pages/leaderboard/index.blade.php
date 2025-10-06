@extends('admin.layout.admin')
@section('title', 'Leaderboard')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Leaderboard" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="Leaderboard - Pilih Tryout"></x-page-desc>

<div class="grid grid-cols-4 gap-4 mt-6 text-gray-600">
    @forelse($tryouts as $tryout)
    <div class="bg-white px-5 py-5 shadow rounded-lg">
        <p class="text-lg font-bold text-black text-center mb-4">{{ Str::limit($tryout['name'], 25) }}</p>
        <div class="flex flex-col gap-1">
            <span class="flex items-center justify-between">
                <p class="font-medium">Jumlah : </p>
                <p class="font-light">{{ $tryout['total_questions'] }} Soal</p>
            </span>
            <span class="flex items-center justify-between">
                <p class="font-medium">Durasi : </p>
                <p class="font-light">{{ $tryout['duration'] }} Menit</p>
            </span>
            <span class="flex items-center justify-between">
                <p class="font-medium">Kesulitan : </p>
                <p class="font-light">{{ $tryout['difficulty'] }}</p>
            </span>
            <span class="flex items-center justify-between">
                <p class="font-medium">Peserta : </p>
                <p class="font-light">{{ $tryout['participant_count'] }} Orang</p>
            </span>
            <span class="flex items-center justify-between">
                <p class="font-medium">Paket : </p>
                <div class="text-right">
                    @if($tryout['package_count'] > 1)
                    <p class="font-light text-xs text-blue-600 cursor-pointer"
                        onclick="showPackageDetails('{{ $tryout['tryout_id'] }}')"
                        title="Klik untuk melihat detail paket">
                        {{ $tryout['package_count'] }} Paket
                    </p>
                    @else
                    <p class="font-light text-xs">{{ Str::limit($tryout['package_name'], 15) }}</p>
                    @endif
                </div>
            </span>
        </div>
        <div class="flex gap-2 font-light">
            <a href="{{ route('admin.leaderboard.show', ['package_id' => $tryout['package_id'], 'tryout_id' => $tryout['tryout_id']]) }}"
                class="flex w-full justify-center bg-primary text-white px-4 py-2 rounded-lg mt-4 hover:bg-primary/90 transition-colors">
                <i class="ri-trophy-fill me-2"></i>Lihat Peringkat
            </a>
        </div>

        <!-- Hidden package details for tooltip -->
        <div id="package-details-{{ $tryout['tryout_id'] }}" class="hidden">
            @if($tryout['package_count'] > 1)
            <div class="mt-2 p-2 bg-gray-50 rounded text-xs">
                <p class="font-semibold mb-1">Paket yang menggunakan tryout ini:</p>
                @foreach($tryout['all_packages'] as $package)
                <div class="flex justify-between text-xs">
                    <span>{{ Str::limit($package['name'], 20) }}</span>
                    <span class="text-gray-500">({{ ucfirst($package['type']) }})</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="col-span-4 text-center py-12">
        <i class="ri-trophy-line text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-500 mb-2">Belum Ada Tryout Aktif</h3>
        <p class="text-gray-400 mb-4">Tidak ada tryout yang tersedia untuk ditampilkan leaderboard</p>
        <a href="{{ route('admin.package.index') }}"
            class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
            <i class="ri-add-line mr-2"></i>Kelola Paket
        </a>
    </div>
    @endforelse
</div>

<!-- Modal for Package Details -->
<div id="packageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Detail Paket</h3>
                <button onclick="closePackageModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            <div id="packageModalContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function showPackageDetails(tryoutId) {
    const packageDetails = document.getElementById('package-details-' + tryoutId);
    const modal = document.getElementById('packageModal');
    const modalContent = document.getElementById('packageModalContent');

    if (packageDetails) {
        modalContent.innerHTML = packageDetails.innerHTML;
        modal.classList.remove('hidden');
    }
}

function closePackageModal() {
    document.getElementById('packageModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('packageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePackageModal();
    }
});

console.log('Leaderboard scripts loaded');
</script>
@endsection
