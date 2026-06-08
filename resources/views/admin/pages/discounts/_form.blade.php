@php
    $isEdit = $discount->exists;
@endphp

<div class="bg-white p-6 rounded-lg border border-border mt-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Kode Diskon <span class="text-red-500">*</span></label>
            <input type="text" name="code" value="{{ old('code', $discount->code) }}" required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary uppercase"
                placeholder="CONTOH: HEMAT50">
            @error('code')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nama</label>
            <input type="text" name="name" value="{{ old('name', $discount->name) }}"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                placeholder="Promo awal bulan">
            @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Diskon <span class="text-red-500">*</span></label>
            <select name="discount_type" id="discountType"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                <option value="percent" @selected(old('discount_type', $discount->discount_type) === 'percent')>Persen (%)</option>
                <option value="fixed" @selected(old('discount_type', $discount->discount_type) === 'fixed')>Nominal Rupiah</option>
            </select>
            @error('discount_type')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nilai Diskon <span class="text-red-500">*</span></label>
            <input type="number" name="discount_value" value="{{ old('discount_value', (int) $discount->discount_value) }}" min="0" required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            @error('discount_value')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Maksimal Diskon</label>
            <input type="number" name="max_discount_amount" value="{{ old('max_discount_amount', $discount->max_discount_amount !== null ? (int) $discount->max_discount_amount : '') }}" min="0"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                placeholder="Opsional, biasanya untuk diskon persen">
            @error('max_discount_amount')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Minimal Pembelian</label>
            <input type="number" name="min_purchase_amount" value="{{ old('min_purchase_amount', (int) ($discount->min_purchase_amount ?? 0)) }}" min="0"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            @error('min_purchase_amount')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Limit Total Pemakaian</label>
            <input type="number" name="usage_limit" value="{{ old('usage_limit', $discount->usage_limit) }}" min="1"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                placeholder="Kosongkan jika tanpa batas">
            @error('usage_limit')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Limit Per Akun</label>
            <input type="number" name="per_user_limit" value="{{ old('per_user_limit', $discount->per_user_limit) }}" min="1"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                placeholder="Kosongkan jika tanpa batas">
            @error('per_user_limit')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Mulai Berlaku</label>
            <input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($discount->starts_at)->format('Y-m-d\\TH:i')) }}"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            @error('starts_at')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Selesai Berlaku</label>
            <input type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($discount->ends_at)->format('Y-m-d\\TH:i')) }}"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            @error('ends_at')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
            <textarea name="description" rows="3"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                placeholder="Catatan internal atau penjelasan promo">{{ old('description', $discount->description) }}</textarea>
            @error('description')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
        <label class="flex items-start gap-3 p-4 border border-gray-200 rounded-lg">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="mt-1" @checked(old('is_active', $discount->is_active))>
            <span>
                <span class="block font-medium text-gray-800">Aktif</span>
                <span class="block text-sm text-gray-500">Kode bisa digunakan saat checkout.</span>
            </span>
        </label>

        <label class="flex items-start gap-3 p-4 border border-gray-200 rounded-lg">
            <input type="hidden" name="is_public" value="0">
            <input type="checkbox" name="is_public" value="1" class="mt-1" @checked(old('is_public', $discount->is_public))>
            <span>
                <span class="block font-medium text-gray-800">Tampilkan ke peserta</span>
                <span class="block text-sm text-gray-500">Jika aktif, kode tampil sebagai promo publik.</span>
            </span>
        </label>
    </div>

    <div class="flex justify-end gap-3 mt-6">
        <a href="{{ route('admin.discounts.index') }}" class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            Batal
        </a>
        <button type="submit" class="px-4 py-2.5 bg-primary text-white rounded-lg hover:bg-primary/90">
            {{ $isEdit ? 'Simpan Perubahan' : 'Buat Diskon' }}
        </button>
    </div>
</div>
