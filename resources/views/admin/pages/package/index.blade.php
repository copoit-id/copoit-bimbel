@extends('admin.layout.admin')

@section('content')
@php
    $canManageTesKoran = auth()->user()?->hasPermission('tes_koran', 'view') ?? false;
@endphp
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Manajemen Paket</h2>
            <p class="text-gray-500">Kelola paket bimbel dan tryout</p>
        </div>
        
        {{-- Button dengan Cek Plan Quota --}}
        {{-- Tooltip position 'bottom' karena button di atas halaman --}}
        <x-plan-quota-button 
            feature="package"
            href="{{ route('admin.package.create') }}"
            icon="ri-add-line"
            label="Tambah Paket"
            variant="primary"
            size="md"
            tooltipPosition="bottom" />
    </div>

    {{-- Alert Quota Status --}}
    @php
        $packageQuota = $planQuota['package'] ?? \App\Services\PlanQuotaService::canCreatePackage();
    @endphp
    
    @if(!$packageQuota['allowed'])
        <div class="rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700">
            <div class="flex items-center gap-2">
                <i class="ri-information-line text-lg"></i>
                <div>
                    <p class="font-medium">Kuota Plan Terpenuhi</p>
                    <p>{{ $packageQuota['reason'] }} Silakan hubungi administrator untuk upgrade plan.</p>
                </div>
            </div>
        </div>
    @elseif($packageQuota['limit'] > 0 && $packageQuota['current'] >= $packageQuota['limit'] - 2)
        {{-- Warning jika hampir penuh --}}
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-700">
            <div class="flex items-center gap-2">
                <i class="ri-alert-line text-lg"></i>
                <div>
                    <p class="font-medium">Kuota Hampir Penuh</p>
                    <p>Anda telah menggunakan {{ $packageQuota['current'] }} dari {{ $packageQuota['limit'] }} paket. Segera upgrade plan untuk menambah lebih banyak paket.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Package List -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($packages as $package)
        <div class="bg-white px-5 py-5 shadow rounded-lg flex flex-col justify-between">
            <div class="flex flex-col items-start">
                <!-- Package Image -->
                <div class="w-full h-32 bg-gray-300 rounded-xl mb-4 overflow-hidden">
                    @if($package->image)
                        @php
                            $thumbExt = strtolower(pathinfo($package->image, PATHINFO_EXTENSION));
                            $thumbIsVideo = in_array($thumbExt, ['mp4','webm','mov','m4v'], true);
                            $thumbUrl = Storage::url($package->image);
                        @endphp
                    @if($thumbIsVideo)
                    <video src="{{ $thumbUrl }}" class="w-full h-full object-cover" controls preload="metadata" playsinline></video>
                        @else
                            <img src="{{ $thumbUrl }}" alt="{{ $package->name }}"
                                class="w-full h-full object-cover">
                        @endif
                    @else
                        <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                            <i class="ri-image-line text-3xl text-gray-400"></i>
                        </div>
                    @endif
                </div>

                <div class="flex justify-between items-center w-full">
                    <p class="text-primary bg-primary/10 px-4 py-1 rounded-full mb-2 capitalize">
                        <i class="ri-book-marked-line me-1"></i>{{ $package->type_package }}
                    </p>
                    <!-- Status Badge -->
                    <div class="h-full">
                        @if($package->status === 'active')
                        <div class=" bg-green-100 text-green-700 rounded-full px-4 py-1"><i
                                class="ri-check-fill me-1"></i>Aktif</div>
                        @else
                        <div class=" bg-gray-100 text-gray-700 rounded-full px-4 py-1"><i
                                class="ri-close-fill me-1"></i>Tidak Aktif</div>
                        @endif
                    </div>

                </div>


                <p class="text-lg font-bold text-black">{{ $package->name }}</p>
                <p class="font-light text-gray-600 mb-2">{{ Str::limit($package->description, 80) }}</p>
                @php
                    $typePriceLabel = [
                        'paid' => 'Berbayar',
                        'free_unconditional' => 'Gratis Tanpa Syarat',
                        'free_conditional' => 'Gratis Bersyarat'
                    ][$package->type_price] ?? ucfirst(str_replace('_', ' ', $package->type_price));
                @endphp
                @if ($package->type_price === 'paid')
                <p class="font-bold text-black">Rp {{ number_format($package->price, 0, ',', '.') }}</p>
                @elseif ($package->type_price === 'free_conditional')
                <p class="font-bold text-orange-600">{{ $typePriceLabel }}</p>
                @else
                <p class="font-bold text-green-600">{{ $typePriceLabel }}</p>
                @endif

                @if($package->type_price === 'free_conditional' && $package->conditional_requirement)
                <p class="text-xs text-gray-500 mt-1">Syarat: {{ Str::limit($package->conditional_requirement, 80) }}</p>
                @endif

                @php
                    $features = [];

                    if ($package->features) {
                        $decodedFeatures = json_decode($package->features, true);
                        $features = is_array($decodedFeatures)
                            ? $decodedFeatures
                            : preg_split('/\r\n|\r|\n/', $package->features);
                        $features = array_values(array_filter(array_map(
                            fn ($feature) => trim((string) $feature),
                            $features
                        )));
                    }
                @endphp

                <div class="flex flex-col mt-4 gap-2 font-light">
                    @foreach ($features as $feature)
                    <span class="text-sm">
                        <i class="ri-checkbox-circle-fill text-green"></i>
                        {{ $feature }}
                    </span>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-wrap gap-2 mt-4">
                @if ($package->type_package == 'bimbel')
                <a href="{{ route('admin.package.material.index', ['package_id' => $package->package_id]) }}"
                    class="flex-1 min-w-[4.5rem] inline-flex items-center justify-center text-center bg-primary text-white px-3 py-2 rounded-lg text-sm hover:bg-primary/90">
                    Materi
                </a>
                <a href="{{ route('admin.package.tryout.index', ['package_id' => $package->package_id]) }}"
                    class="flex-1 min-w-[4.5rem] inline-flex items-center justify-center text-center bg-primary text-white px-3 py-2 rounded-lg text-sm hover:bg-primary/90">
                    Tryout
                </a>
                <a href="{{ route('admin.package.class.index', ['package_id' => $package->package_id]) }}"
                    class="flex-1 min-w-[4.5rem] inline-flex items-center justify-center text-center bg-primary text-white px-3 py-2 rounded-lg text-sm hover:bg-primary/90">
                    Kelas
                </a>
                @if($canManageTesKoran)
                <a href="{{ route('admin.package.tes-koran.index', ['package_id' => $package->package_id]) }}"
                    class="flex-1 min-w-[4.5rem] inline-flex items-center justify-center text-center bg-primary text-white px-3 py-2 rounded-lg text-sm hover:bg-primary/90">
                    Tes Koran
                </a>
                @endif
                @elseif ($package->type_package == 'tryout')
                <a href="{{ route('admin.package.tryout.index', ['package_id' => $package->package_id]) }}"
                    class="flex-1 min-w-[4.5rem] inline-flex items-center justify-center text-center bg-primary text-white px-3 py-2 rounded-lg text-sm hover:bg-primary/90">
                    Tryout
                </a>
                @elseif ($package->type_package == 'sertifikasi')
                <a href="{{ route('admin.package.tryout.index', ['package_id' => $package->package_id]) }}"
                    class="flex-1 min-w-[4.5rem] inline-flex items-center justify-center text-center bg-primary text-white px-3 py-2 rounded-lg text-sm hover:bg-primary/90">
                    Sertifikasi
                </a>
                @elseif ($package->type_package == 'tes_koran' && $canManageTesKoran)
                <a href="{{ route('admin.package.tes-koran.index', ['package_id' => $package->package_id]) }}"
                    class="flex-1 min-w-[4.5rem] inline-flex items-center justify-center text-center bg-primary text-white px-3 py-2 rounded-lg text-sm hover:bg-primary/90">
                    Tes Koran
                </a>
                @endif

                {{-- Share Button --}}
                <button type="button" 
                    onclick="copyPackageLink({{ $package->package_id }})"
                    class="inline-flex items-center justify-center bg-gray-100 hover:bg-primary hover:text-white border border-primary text-primary px-3 py-2 rounded-lg text-sm transition-colors"
                    title="Copy link detail paket">
                    <i class="ri-share-line"></i>
                </button>

                <a href="{{ route('admin.package.edit', array_merge(request()->query(), ['package_id' => $package->package_id])) }}"
                    class="inline-flex items-center justify-center bg-gray-100 hover:bg-primary hover:text-white border border-primary text-primary px-3 py-2 rounded-lg text-sm">
                    <i class="ri-pencil-fill"></i>
                </a>

                <form action="{{ route('admin.package.destroy', $package->package_id) }}" method="POST" class="inline"
                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus paket ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center justify-center bg-red-100 hover:bg-red hover:text-white border border-red/90 text-red px-3 py-2 rounded-lg text-sm">
                        <i class="ri-delete-bin-fill"></i>
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Toast Notification --}}
<div id="toast-notification" class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-y-20 opacity-0 transition-all duration-300 z-50 flex items-center gap-2">
    <i class="ri-check-line text-xl"></i>
    <span id="toast-message">Link berhasil disalin!</span>
</div>

<script>
function copyPackageLink(packageId) {
    const baseUrl = '{{ url('/') }}';
    const link = baseUrl + '/user/paket/' + packageId + '/detail';
    
    navigator.clipboard.writeText(link).then(function() {
        showToast('Link detail paket berhasil disalin!');
    }).catch(function(err) {
        // Fallback untuk browser lama
        const textArea = document.createElement('textarea');
        textArea.value = link;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('Link detail paket berhasil disalin!');
    });
}

function showToast(message) {
    const toast = document.getElementById('toast-notification');
    const toastMessage = document.getElementById('toast-message');
    toastMessage.textContent = message;
    
    toast.classList.remove('translate-y-20', 'opacity-0');
    toast.classList.add('translate-y-0', 'opacity-100');
    
    setTimeout(function() {
        toast.classList.add('translate-y-20', 'opacity-0');
        toast.classList.remove('translate-y-0', 'opacity-100');
    }, 3000);
}
</script>
@endsection
