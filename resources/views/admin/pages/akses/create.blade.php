@extends('admin.layout.admin')
@section('title', 'Tambah Akses User')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.akses.index') }}" title="Akses User" />
            <x-breadcrumb-item href="{{ route('admin.akses.show', ['package_id' => $package->package_id]) }}"
                title="{{ $package->name }}" />
            <x-breadcrumb-item href="" title="Tambah Akses" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="Tambah Akses Manual - {{ $package->name }}"></x-page-desc>

@if($errors->any())
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
    <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="bg-white rounded-lg border border-border p-8 mt-6">
    <form action="{{ route('admin.akses.store', $package->package_id) }}" class="space-y-6" method="post">
        @csrf

        <!-- User Selection -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih User <span
                    class="text-red-500">*</span></label>

            <!-- Search Input -->
            <div class="relative mb-3">
                <input type="text" id="user_search" placeholder="Cari user berdasarkan nama atau email..."
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <i class="ri-search-line absolute left-3 top-3.5 text-gray-400"></i>
            </div>

            <!-- Select All Checkbox -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <input type="checkbox" id="select_all"
                        class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                    <label for="select_all" class="ml-2 text-sm font-medium text-gray-700">Pilih Semua</label>
                </div>
                <span id="selected_count" class="text-sm text-gray-600">0 user dipilih</span>
            </div>

            <!-- User List -->
            <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-lg">
                @forelse ($availableUsers as $user)
                <label
                    class="user-item flex items-center p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                        class="user-checkbox w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2 mr-3"
                        {{ in_array($user->id, old('user_ids', [])) ? 'checked' : '' }}>
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=6366f1&color=fff"
                        class="w-8 h-8 rounded-full mr-3">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 user-name">{{ $user->name }}</p>
                        <p class="text-sm text-gray-500 user-email">{{ $user->email }}</p>
                    </div>
                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">{{ ucfirst($user->status)
                        }}</span>
                </label>
                @empty
                <div class="p-8 text-center text-gray-500">
                    <i class="ri-user-line text-4xl text-gray-300 mb-2"></i>
                    <p>Semua user sudah memiliki akses ke paket ini</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Access Period -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai <span
                        class="text-red-500">*</span></label>
                <input type="date" id="start_date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Berakhir <span
                        class="text-red-500">*</span></label>
                <input type="date" id="end_date" name="end_date"
                    value="{{ old('end_date', date('Y-m-d', strtotime('+30 days'))) }}" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
        </div>

        <!-- Payment Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-2">Status Pembayaran <span
                        class="text-red-500">*</span></label>
                <select id="payment_status" name="payment_status" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">Pilih Status</option>
                    <option value="paid" {{ old('payment_status')=='paid' ? 'selected' : '' }}>Lunas</option>
                    <option value="pending" {{ old('payment_status')=='pending' ? 'selected' : '' }}>Pending</option>
                    <option value="free" {{ old('payment_status')=='free' ? 'selected' : '' }}>Gratis</option>
                    <option value="failed" {{ old('payment_status')=='failed' ? 'selected' : '' }}>Gagal</option>
                </select>
            </div>

            <div>
                <label for="payment_amount" class="block text-sm font-medium text-gray-700 mb-2">Jumlah
                    Pembayaran</label>
                <input type="number" id="payment_amount" name="payment_amount" value="{{ old('payment_amount', 0) }}"
                    min="0"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    placeholder="0">
            </div>
        </div>

        <!-- Notes -->
        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
            <textarea id="notes" name="notes" rows="3"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end space-x-2">
            <a href="{{ route('admin.akses.show', $package->package_id) }}"
                class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900">
                Batal
            </a>
            <button type="submit" id="submit_btn"
                class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5 text-center disabled:opacity-50"
                disabled>
                Berikan Akses
            </button>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userSearch = document.getElementById('user_search');
        const selectAll = document.getElementById('select_all');
        const selectedCount = document.getElementById('selected_count');
        const userCheckboxes = document.querySelectorAll('.user-checkbox');
        const userItems = document.querySelectorAll('.user-item');
        const submitBtn = document.getElementById('submit_btn');

        // Search functionality
        userSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();

            userItems.forEach(item => {
                const userName = item.querySelector('.user-name').textContent.toLowerCase();
                const userEmail = item.querySelector('.user-email').textContent.toLowerCase();

                if (userName.includes(searchTerm) || userEmail.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });

            updateSelectedCount();
        });

        // Select all functionality
        selectAll.addEventListener('change', function() {
            const visibleCheckboxes = Array.from(userCheckboxes).filter(cb =>
                cb.closest('.user-item').style.display !== 'none'
            );

            visibleCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });

            updateSelectedCount();
        });

        // Individual checkbox change
        userCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });

        function updateSelectedCount() {
            const visibleCheckboxes = Array.from(userCheckboxes).filter(cb =>
                cb.closest('.user-item').style.display !== 'none'
            );
            const selectedCheckboxes = visibleCheckboxes.filter(cb => cb.checked);

            selectedCount.textContent = `${selectedCheckboxes.length} user dipilih`;

            // Update select all checkbox state
            if (selectedCheckboxes.length === 0) {
                selectAll.indeterminate = false;
                selectAll.checked = false;
                submitBtn.disabled = true; // ❌ tidak ada user -> tombol mati
            } else if (selectedCheckboxes.length === visibleCheckboxes.length) {
                selectAll.indeterminate = false;
                selectAll.checked = true;
                submitBtn.disabled = false; // ✅ ada user -> tombol nyala
            } else {
                selectAll.indeterminate = true;
                submitBtn.disabled = false; // ✅ ada user -> tombol nyala
            }
        }

        // Initial count
        updateSelectedCount();
    });
</script>
@endsection