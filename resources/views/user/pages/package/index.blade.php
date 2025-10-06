@extends('user.layout.user')
@section('title', 'Paket Pembelian')
@section('content')
<div class="dashboard">
    <x-page-desc title="Paket " description="Pilihan paket gratis hingga berbayar"></x-page-desc>
    <div class="flex flex-col md:flex-row md:items-center justify-between">
        <div class="flex justify-start gap-2 mt-4">
            <div id="btn-kelas" class="tab-btn px-6 py-1.5 bg-primary text-white rounded-xl cursor-pointer">
                Kelas
            </div>
            <div id="btn-tryout"
                class="tab-btn px-6 py-1.5 border border-primary text-primary rounded-xl cursor-pointer">
                Tryout
            </div>
            <div id="btn-sertifikasi"
                class="tab-btn px-6 py-1.5 border border-primary text-primary rounded-xl cursor-pointer">
                Sertifikasi
            </div>
        </div>

        <a href="{{ route('user.package.riwayatPembelian') }}" class="text-blue-600 underline mt-3 md:mt-0">Riwayat
            Pembelian</a>
    </div>

    <!-- Kelas Package -->
    <div id="kelas-package" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6 text-gray-600">
        @forelse($kelasPackages as $package)
        <div class="bg-white px-5 py-5 shadow rounded-lg">
            <div class="w-full h-32 bg-gray-300 rounded-xl mb-4 overflow-hidden">
                @if($package->image)
                <img src="{{ Storage::url($package->image) }}" alt="{{ $package->name }}"
                    class="w-full h-full object-cover">
                @else
                <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                    <i class="ri-image-line text-3xl text-gray-400"></i>
                </div>
                @endif
            </div>
            <p class="text-lg font-bold text-black">{{ $package->name }}</p>
            <p class="font-light">{{ $package->description }}</p>
            @if ($package->price == 0)
            <p class="font-bold text-black">Gratis</p>
            @else
            <p class="font-bold text-black">Rp {{ number_format($package->price, 0, ',', '.') }}</p>
            @endif

            <div class="flex flex-col mt-4 gap-3 font-light">
                @if($package->features)
                @foreach (json_decode($package->features) as $feature)
                <span>
                    <i class="ri-checkbox-circle-fill text-green-500"></i>
                    {{ $feature }}
                </span>
                @endforeach
                @endif
            </div>

            <div class="mt-4">
                @if($package->user_access_count > 0)
                <a href="{{ route('user.package.bimbel', $package->package_id) }}"
                    class="w-full bg-green-600 text-white px-4 py-3 rounded-lg font-bold text-center block">
                    SUDAH DIBELI
                </a>
                @else
                <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST"
                    class="buy-package-form">
                    @csrf
                    <button type="submit" class="w-full bg-primary text-white px-4 py-3 rounded-lg font-bold">
                        {{ $package->price == 0 ? 'AMBIL GRATIS' : 'BELI SEKARANG' }}
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-8">
            <p class="text-gray-500">Belum ada paket kelas tersedia</p>
        </div>
        @endforelse
    </div>

    <!-- Tryout Package -->
    <div id="tryout-package" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6 text-gray-600 hidden">
        @forelse($tryoutPackages as $package)
        <div class="flex flex-col justify-between bg-white px-5 py-5 shadow rounded-lg">
            <div>
                <div class="w-full h-32 bg-gray-300 rounded-xl mb-4 overflow-hidden">
                    @if($package->image)
                    <img src="{{ Storage::url($package->image) }}" alt="{{ $package->name }}"
                        class="w-full h-full object-cover">
                    @else
                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                        <i class="ri-image-line text-3xl text-gray-400"></i>
                    </div>
                    @endif
                </div>
                <p class="text-lg font-bold text-black">{{ $package->name }}</p>
                <p class="font-light">{{ $package->description }}</p>
                @if ($package->price == 0)
                <p class="font-bold text-black">Gratis</p>
                @else
                <p class="font-bold text-black">Rp {{ number_format($package->price, 0, ',', '.') }}</p>
                @endif

                <div class="flex flex-col mt-4 gap-3 font-light">
                    @if($package->features)
                    @foreach (json_decode($package->features) as $feature)
                    <span>
                        <i class="ri-checkbox-circle-fill text-green-500"></i>
                        {{ $feature }}
                    </span>
                    @endforeach
                    @endif
                </div>
            </div>

            <div class="mt-4">
                @if($package->user_access_count > 0)
                <a href="{{ route('user.package.tryout', $package->package_id) }}"
                    class="w-full bg-green-600 text-white px-4 py-3 rounded-lg font-bold text-center block">
                    SUDAH DIBELI
                </a>
                @else
                <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST"
                    class="buy-package-form">
                    @csrf
                    <button type="submit" class="w-full bg-primary text-white px-4 py-3 rounded-lg font-bold">
                        {{ $package->price == 0 ? 'AMBIL GRATIS' : 'BELI SEKARANG' }}
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-8">
            <p class="text-gray-500">Belum ada paket tryout tersedia</p>
        </div>
        @endforelse
    </div>

    <!-- Sertifikasi Package -->
    <div id="sertifikasi-package"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6 text-gray-600 hidden">
        @forelse($sertifikasiPackages as $package)
        <div class="bg-white px-5 py-5 shadow rounded-lg">
            <div class="w-full h-32 bg-gray-300 rounded-xl mb-4 overflow-hidden">
                @if($package->image)
                <img src="{{ Storage::url($package->image) }}" alt="{{ $package->name }}"
                    class="w-full h-full object-cover">
                @else
                <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                    <i class="ri-image-line text-3xl text-gray-400"></i>
                </div>
                @endif
            </div>
            <p class="text-lg font-bold text-black">{{ $package->name }}</p>
            <p class="font-light">{{ $package->description }}</p>
            @if ($package->price == 0)
            <p class="font-bold text-black">Gratis</p>
            @else
            <p class="font-bold text-black">Rp {{ number_format($package->price, 0, ',', '.') }}</p>
            @endif

            <div class="flex flex-col mt-4 gap-3 font-light">
                @if($package->features)
                @foreach (json_decode($package->features) as $feature)
                <span>
                    <i class="ri-checkbox-circle-fill text-green-500"></i>
                    {{ $feature }}
                </span>
                @endforeach
                @endif
            </div>

            <div class="mt-4">
                @if($package->user_access_count > 0)
                <a href="{{ route('user.package.bimbel', $package->package_id) }}"
                    class="w-full bg-green-600 text-white px-4 py-3 rounded-lg font-bold text-center block">
                    SUDAH DIBELI
                </a>
                @else
                <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST"
                    class="buy-package-form">
                    @csrf
                    <button type="submit" class="w-full bg-primary text-white px-4 py-3 rounded-lg font-bold">
                        {{ $package->price == 0 ? 'AMBIL GRATIS' : 'BELI SEKARANG' }}
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-8">
            <p class="text-gray-500">Belum ada paket sertifikasi tersedia</p>
        </div>
        @endforelse
    </div>

</div>

<!-- Loading Modal -->
<div id="loadingModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white p-6 rounded-lg">
        <div class="flex items-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mr-3"></div>
            <p>Memproses pembayaran...</p>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        const tabs = ['kelas', 'tryout', 'sertifikasi'];

        function activateTab(active) {
            tabs.forEach(tab => {
                const container = $(`#${tab}-package`);
                const button = $(`#btn-${tab}`);

                if (tab === active) {
                    container.removeClass('hidden').addClass('grid');
                    button.addClass('bg-primary text-white').removeClass('border border-primary text-primary');
                } else {
                    container.addClass('hidden').removeClass('grid');
                    button.removeClass('bg-primary text-white').addClass('border border-primary text-primary');
                }
            });
        }

        // Tab click events
        tabs.forEach(tab => {
            $(`#btn-${tab}`).click(function() {
                activateTab(tab);
            });
        });

        // Handle buy package form submission
        $('.buy-package-form').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const button = form.find('button[type="submit"]');
            const originalText = button.text();

            // Show loading state
            button.prop('disabled', true).text('Memproses...');
            $('#loadingModal').removeClass('hidden').addClass('flex');

            // Submit form
            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function(response) {
                    // Hide loading
                    $('#loadingModal').addClass('hidden').removeClass('flex');

                    if (response.redirect_url) {
                        // Redirect to Xendit payment page
                        window.location.href = response.redirect_url;
                    } else if (response.success) {
                        // For free packages, reload page or show success
                        location.reload();
                    }
                },
                error: function(xhr) {
                    // Hide loading
                    $('#loadingModal').addClass('hidden').removeClass('flex');
                    button.prop('disabled', false).text(originalText);

                    let errorMessage = 'Terjadi kesalahan. Silakan coba lagi.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    alert(errorMessage);
                }
            });
        });

        // Initialize first tab
        activateTab('kelas');
    });
</script>
@endsection

@section('styles')
<style>
    .buy-package-form button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>
@endsection