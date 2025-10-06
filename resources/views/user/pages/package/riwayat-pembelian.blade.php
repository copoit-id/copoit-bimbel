@extends('user.layout.user')
@section('title', 'Paket Pembelian')
@section('content')
<div class="package-bimbel bg-white p-4 rounded-lg border border-border">
    <div class="flex items-center justify-between">
        <x-page-desc title="Riwayat Pembelian"></x-page-desc>
        <a href="{{ route('user.package.riwayatPembelianAktif') }}" id="btn-my-package"
            class="px-6 py-1 border hover:bg-primary hover:text-white border-primary flex justify-center text-primary rounded-xl">
            Paket Aktif Saya
        </a>
    </div>

    <div class="relative overflow-x-auto mt-4 pb-16">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-cemter">#</th>
                    <th scope="col" class="px-6 py-3">Nama Paket</th>
                    <th scope="col" class="px-6 py-3 text-center">Status Pembelian</th>
                    <th scope="col" class="px-6 py-3 text-center">Durasi</th>
                    <th scope="col" class="px-6 py-3 text-center">Tangal Pembelian</th>
                    <th scope="col" class="px-6 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                <tr class="bg-white border-b border-dashed border-gray-200 text-grey3">
                    <td class="px-6 py-4 text-center">{{ $loop->iteration }}</td>
                    <td class="px-6 py-4">
                        <p>{{ $payment->package->name ?? 'Paket Tidak Ditemukan' }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            @if($payment->status === 'success')
                            <span
                                class="flex items-center gap-1 border border-green bg-green-light px-4 py-0.5 rounded-xl">
                                <i class="ri-checkbox-circle-fill text-green text-lg"></i>
                                <span class="text-green">Selesai</span>
                            </span>
                            @elseif($payment->status === 'pending')
                            <span
                                class="flex items-center gap-1 border border-yellow-500 bg-yellow-100 px-4 py-0.5 rounded-xl">
                                <i class="ri-time-line text-yellow-600 text-lg"></i>
                                <span class="text-yellow-600">Pending</span>
                            </span>
                            @elseif($payment->status === 'failed')
                            <span class="flex items-center gap-1 border border-red bg-red-light px-4 py-0.5 rounded-xl">
                                <i class="ri-close-circle-fill text-red text-lg"></i>
                                <span class="text-red">Gagal</span>
                            </span>
                            @else
                            <span
                                class="flex items-center gap-1 border border-gray-400 bg-gray-100 px-4 py-0.5 rounded-xl">
                                <i class="ri-question-line text-gray-600 text-lg"></i>
                                <span class="text-gray-600">{{ ucfirst($payment->status) }}</span>
                            </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                        $userAccess = \App\Models\UserPackageAcces::where('user_id', $payment->user_id)
                        ->where('package_id', $payment->package_id)
                        ->where('status', 'active')
                        ->first();

                        if ($userAccess && $userAccess->end_date) {
                        $startDate = \Carbon\Carbon::parse($userAccess->start_date);
                        $endDate = \Carbon\Carbon::parse($userAccess->end_date);
                        $duration = $startDate->diffInDays($endDate);

                        if ($duration >= 365) {
                        $durationText = floor($duration / 365) . ' Tahun';
                        } elseif ($duration >= 30) {
                        $durationText = floor($duration / 30) . ' Bulan';
                        } else {
                        $durationText = $duration . ' Hari';
                        }
                        } else {
                        $durationText = '-';
                        }
                        @endphp
                        {{ $durationText }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        {{ $payment->created_at->format('d F Y, H:i') }} WIB
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            @if($payment->status === 'success' && $payment->package)
                            @if($payment->package->type_package === 'tryout')
                            <a href="{{ route('user.package.tryout', $payment->package->package_id) }}"
                                class="flex items-center gap-2 border border-primary px-4 py-1 rounded-xl">
                                <i class="ri-file-list-3-line text-primary"></i>
                                <span class="text-primary">Lihat Tryout</span>
                            </a>
                            @elseif($payment->package->type_package === 'bimbel')
                            <div class="relative dropdown-container">
                                <button type="button"
                                    class="dropdown-button flex items-center gap-2 border border-primary px-4 py-1 rounded-xl hover:bg-primary hover:text-white transition-colors"
                                    data-dropdown-id="dropdown-{{ $payment->payment_id }}">
                                    <i class="ri-eye-line text-primary"></i>
                                    <span class="text-primary">Lihat Paket</span>
                                    <svg class="w-3 h-3 ml-1 transition-transform dropdown-arrow"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="m1 1 4 4 4-4" />
                                    </svg>
                                </button>
                                <div id="dropdown-{{ $payment->payment_id }}"
                                    class="dropdown-menu hidden absolute top-full left-0 mt-1 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                    <a href="{{ route('user.package.bimbel', $payment->package->package_id) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-gray-700 hover:bg-gray-100 first:rounded-t-lg">
                                        <i class="ri-book-open-line text-sm"></i>
                                        <span class="text-sm">Kelas</span>
                                    </a>
                                    <a href="{{ route('user.package.tryout', $payment->package->package_id) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-gray-700 hover:bg-gray-100 last:rounded-b-lg">
                                        <i class="ri-file-list-3-line text-sm"></i>
                                        <span class="text-sm">Tryout</span>
                                    </a>
                                </div>
                            </div>
                            @elseif($payment->package->type_package === 'sertifikasi')
                            <span class="flex items-center gap-2 border border-gray-400 px-4 py-1 rounded-xl">
                                <i class="ri-award-line text-gray-400"></i>
                                <span class="text-gray-400">Sertifikasi (Segera)</span>
                            </span>
                            @else
                            <span class="flex items-center gap-2 border border-gray-400 px-4 py-1 rounded-xl">
                                <i class="ri-eye-line text-gray-400"></i>
                                <span class="text-gray-400">Tidak Tersedia</span>
                            </span>
                            @endif
                            @elseif($payment->status === 'pending')
                            <span class="flex items-center gap-2 border border-yellow-500 px-4 py-1 rounded-xl">
                                <i class="ri-time-line text-yellow-600"></i>
                                <span class="text-yellow-600">Menunggu</span>
                            </span>
                            @else
                            <span class="flex items-center gap-2 border border-gray-400 px-4 py-1 rounded-xl">
                                <i class="ri-close-line text-gray-400"></i>
                                <span class="text-gray-400">Tidak Tersedia</span>
                            </span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class="ri-shopping-bag-line text-4xl text-gray-300 mb-2"></i>
                            <p>Belum ada riwayat pembelian</p>
                            <a href="{{ route('user.package.index') }}" class="text-primary hover:underline mt-2">
                                Beli paket sekarang
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    console.log('Dropdown script loaded');

    // Get all dropdown buttons
    const dropdownButtons = document.querySelectorAll('.dropdown-button');

    dropdownButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            console.log('Dropdown button clicked');

            const dropdownId = this.getAttribute('data-dropdown-id');
            const dropdown = document.getElementById(dropdownId);
            const arrow = this.querySelector('.dropdown-arrow');

            if (!dropdown) {
                console.error('Dropdown not found:', dropdownId);
                return;
            }

            // Close all other dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu.id !== dropdownId) {
                    menu.classList.add('hidden');
                }
            });

            // Reset all other arrows
            document.querySelectorAll('.dropdown-arrow').forEach(arr => {
                if (arr !== arrow) {
                    arr.classList.remove('rotate-180');
                }
            });

            // Toggle current dropdown
            dropdown.classList.toggle('hidden');
            arrow.classList.toggle('rotate-180');

            console.log('Dropdown toggled:', dropdownId, dropdown.classList.contains('hidden') ? 'closed' : 'open');
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown-container')) {
            console.log('Clicked outside, closing all dropdowns');

            document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
                dropdown.classList.add('hidden');
            });

            document.querySelectorAll('.dropdown-arrow').forEach(arrow => {
                arrow.classList.remove('rotate-180');
            });
        }
    });
});
</script>
@endsection

@section('styles')
<style>
    /* Dropdown animation */
    .dropdown-menu {
        animation: slideDown 0.2s ease-out;
        transform-origin: top;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px) scaleY(0.9);
        }

        to {
            opacity: 1;
            transform: translateY(0) scaleY(1);
        }
    }

    /* Arrow transition */
    .dropdown-arrow {
        transition: transform 0.2s ease;
    }

    /* Hover effects for dropdown button */
    .dropdown-button:hover .ri-eye-line {
        color: white;
    }

    .dropdown-button:hover .text-primary {
        color: white;
    }

    .dropdown-button:hover .dropdown-arrow path {
        stroke: white;
    }

    /* Ensure dropdown is above other elements */
    .dropdown-container {
        z-index: 20;
    }

    .dropdown-menu {
        z-index: 30;
    }
</style>
@endsection
