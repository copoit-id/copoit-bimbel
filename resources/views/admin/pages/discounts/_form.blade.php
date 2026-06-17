@php
    $isEdit = $discount->exists;
    $isVoucher = old('application_type', $discount->application_type ?: \App\Models\Discount::TYPE_VOUCHER) === \App\Models\Discount::TYPE_VOUCHER;
@endphp

<input type="hidden" name="application_type" value="{{ $isVoucher ? \App\Models\Discount::TYPE_VOUCHER : \App\Models\Discount::TYPE_PACKAGE_TRYOUT }}">

<div class="bg-white p-6 rounded-lg border border-border mt-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @if($isVoucher)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Kode Voucher <span class="text-red-500">*</span></label>
            <input type="text" name="code" value="{{ old('code', $discount->code) }}" required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary uppercase"
                placeholder="CONTOH: HEMAT50">
            @error('code')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        @endif

        <div class="{{ $isVoucher ? '' : 'md:col-span-2' }}">
            <label class="block text-sm font-medium text-gray-700 mb-2">Nama</label>
            <input type="text" name="name" value="{{ old('name', $discount->name) }}"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                placeholder="{{ $isVoucher ? 'Promo awal bulan' : 'Diskon tryout pilihan' }}">
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

        <div class="{{ $isVoucher ? '' : 'hidden' }}">
            <label class="block text-sm font-medium text-gray-700 mb-2">Minimal Pembelian</label>
            <input type="number" name="min_purchase_amount" value="{{ old('min_purchase_amount', (int) ($discount->min_purchase_amount ?? 0)) }}" min="0"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            @error('min_purchase_amount')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="{{ $isVoucher ? '' : 'hidden' }}">
            <label class="block text-sm font-medium text-gray-700 mb-2">Limit Total Pemakaian</label>
            <input type="number" name="usage_limit" value="{{ old('usage_limit', $discount->usage_limit) }}" min="1"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                placeholder="Kosongkan jika tanpa batas">
            @error('usage_limit')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="{{ $isVoucher ? '' : 'hidden' }}">
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
            @unless($isVoucher)
            <p class="text-xs text-gray-500 mt-1">Kosongkan jika diskon mulai aktif sekarang.</p>
            @endunless
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Selesai Berlaku @unless($isVoucher)<span class="text-red-500">*</span>@endunless</label>
            <input type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($discount->ends_at)->format('Y-m-d\\TH:i')) }}" @required(!$isVoucher)
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            @error('ends_at')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
            <textarea name="description" rows="3"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                placeholder="{{ $isVoucher ? 'Catatan internal atau penjelasan promo' : 'Teks singkat yang menjelaskan diskon otomatis' }}">{{ old('description', $discount->description) }}</textarea>
            @error('description')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 border-t border-gray-200 pt-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Scope {{ $isVoucher ? 'Voucher' : 'Diskon' }}</h3>
        <p class="text-sm text-gray-500 mb-4">Pilih item spesifik yang boleh memakai {{ $isVoucher ? 'voucher' : 'diskon' }} ini.</p>

        @php
            $legacyTypes = $discount->applicable_purchase_types ?: [];
            $resolveSelectedIds = function (string $field, string $legacyType, $items, string $keyName) use ($discount, $legacyTypes) {
                $oldValue = old($field);
                if (is_array($oldValue)) {
                    return collect($oldValue)->map(fn($id) => (int) $id)->all();
                }

                $storedIds = $discount->{$field};
                if (!empty($storedIds)) {
                    return collect($storedIds)->map(fn($id) => (int) $id)->all();
                }

                if ($field === 'applicable_tryout_ids' && $discount->tryout_id) {
                    return [(int) $discount->tryout_id];
                }

                if ($discount->exists && in_array($legacyType, $legacyTypes, true)) {
                    return $items->pluck($keyName)->map(fn($id) => (int) $id)->all();
                }

                return [];
            };

            $scopeTabs = [
                'package' => [
                    'label' => 'Paket',
                    'field' => 'applicable_package_ids',
                    'items' => $packages,
                    'key' => 'package_id',
                    'name' => 'name',
                    'meta' => fn($item) => 'Rp ' . number_format((float) $item->price, 0, ',', '.'),
                    'empty' => 'Tidak ada paket berbayar aktif.',
                    'selected' => $resolveSelectedIds('applicable_package_ids', 'package', $packages, 'package_id'),
                ],
                'tryout' => [
                    'label' => 'Tryout',
                    'field' => 'applicable_tryout_ids',
                    'items' => $saleTryouts ?? $tryouts,
                    'key' => 'tryout_id',
                    'name' => 'name',
                    'meta' => fn($item) => strtoupper(str_replace('_', ' ', $item->type_tryout ?? '-')) . ' - Rp ' . number_format((float) $item->price, 0, ',', '.'),
                    'empty' => 'Tidak ada tryout yang dijual.',
                    'selected' => $resolveSelectedIds('applicable_tryout_ids', 'tryout', $saleTryouts ?? $tryouts, 'tryout_id'),
                ],
                'material' => [
                    'label' => 'Materi',
                    'field' => 'applicable_material_ids',
                    'items' => $materials,
                    'key' => 'material_id',
                    'name' => 'title',
                    'meta' => fn($item) => ucfirst(str_replace('_', ' ', $item->type ?? '-')) . ' - Rp ' . number_format((float) $item->price, 0, ',', '.'),
                    'empty' => 'Tidak ada materi yang dijual.',
                    'selected' => $resolveSelectedIds('applicable_material_ids', 'material', $materials, 'material_id'),
                ],
                'tes_koran' => [
                    'label' => 'Tes Koran',
                    'field' => 'applicable_tes_koran_ids',
                    'items' => $tesKorans,
                    'key' => 'id',
                    'name' => 'name',
                    'meta' => fn($item) => ucfirst($item->test_type ?? '-') . ' - Rp ' . number_format((float) $item->price, 0, ',', '.'),
                    'empty' => 'Tidak ada tes koran yang dijual.',
                    'selected' => $resolveSelectedIds('applicable_tes_koran_ids', 'tes_koran', $tesKorans, 'id'),
                ],
            ];
            $firstScopeTab = collect($scopeTabs)->first(fn($tab) => count($tab['selected']) > 0);
            $activeScopeTab = array_search($firstScopeTab, $scopeTabs, true) ?: 'package';
        @endphp

        @error('scope_items')<p class="text-sm text-red-600 mb-3">{{ $message }}</p>@enderror

        <div class="border border-gray-200 rounded-xl overflow-hidden">
            <div class="flex flex-wrap gap-2 bg-gray-50 p-3 border-b border-gray-200">
                @foreach($scopeTabs as $tabKey => $scopeTab)
                    <button type="button" data-discount-scope-tab="{{ $tabKey }}"
                        class="discount-scope-tab px-4 py-2 rounded-lg text-sm font-semibold transition {{ $activeScopeTab === $tabKey ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200' }}">
                        {{ $scopeTab['label'] }}
                        <span class="ml-1 text-xs" data-scope-count="{{ $tabKey }}">{{ count($scopeTab['selected']) }}</span>
                    </button>
                @endforeach
            </div>

            @foreach($scopeTabs as $tabKey => $scopeTab)
                <div data-discount-scope-panel="{{ $tabKey }}" class="discount-scope-panel p-4 {{ $activeScopeTab !== $tabKey ? 'hidden' : '' }}">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <div>
                            <p class="font-semibold text-gray-800">{{ $scopeTab['label'] }}</p>
                            <p class="text-xs text-gray-500">Centang item {{ strtolower($scopeTab['label']) }} yang boleh memakai promo ini.</p>
                        </div>
                        @if($scopeTab['items']->isNotEmpty())
                            <button type="button" data-scope-select-all="{{ $tabKey }}" class="text-xs font-semibold text-primary hover:underline">
                                Pilih semua
                            </button>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-72 overflow-y-auto">
                        @forelse($scopeTab['items'] as $item)
                            @php
                                $itemId = (int) $item->{$scopeTab['key']};
                            @endphp
                            <label class="flex items-start gap-2 rounded-lg border border-gray-200 p-3 hover:border-primary/50 transition">
                                <input type="checkbox"
                                    name="{{ $scopeTab['field'] }}[]"
                                    value="{{ $itemId }}"
                                    data-scope-item="{{ $tabKey }}"
                                    class="mt-1 rounded border-gray-300 text-primary focus:ring-primary"
                                    @checked(in_array($itemId, $scopeTab['selected'], true))>
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">{{ $item->{$scopeTab['name']} }}</span>
                                    <span class="block text-xs text-gray-500">{{ $scopeTab['meta']($item) }}</span>
                                </span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500 md:col-span-2">{{ $scopeTab['empty'] }}</p>
                        @endforelse
                    </div>

                    @error($scopeTab['field'])<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                    @error($scopeTab['field'] . '.*')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>
    </div>

    <script>
        (function () {
            const tabButtons = document.querySelectorAll('[data-discount-scope-tab]');
            const panels = document.querySelectorAll('[data-discount-scope-panel]');

            function setActiveScopeTab(tab) {
                tabButtons.forEach((button) => {
                    const active = button.getAttribute('data-discount-scope-tab') === tab;
                    button.classList.toggle('bg-primary', active);
                    button.classList.toggle('text-white', active);
                    button.classList.toggle('bg-white', !active);
                    button.classList.toggle('text-gray-700', !active);
                    button.classList.toggle('border', !active);
                    button.classList.toggle('border-gray-200', !active);
                    button.classList.toggle('hover:bg-gray-100', !active);
                });

                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.getAttribute('data-discount-scope-panel') !== tab);
                });
            }

            function updateScopeCount(tab) {
                const countEl = document.querySelector(`[data-scope-count="${tab}"]`);
                if (!countEl) return;
                countEl.textContent = document.querySelectorAll(`[data-scope-item="${tab}"]:checked`).length;
            }

            tabButtons.forEach((button) => {
                button.addEventListener('click', () => setActiveScopeTab(button.getAttribute('data-discount-scope-tab')));
            });

            document.querySelectorAll('[data-scope-item]').forEach((input) => {
                input.addEventListener('change', () => updateScopeCount(input.getAttribute('data-scope-item')));
            });

            document.querySelectorAll('[data-scope-select-all]').forEach((button) => {
                button.addEventListener('click', () => {
                    const tab = button.getAttribute('data-scope-select-all');
                    const inputs = document.querySelectorAll(`[data-scope-item="${tab}"]`);
                    const shouldCheck = Array.from(inputs).some((input) => !input.checked);
                    inputs.forEach((input) => {
                        input.checked = shouldCheck;
                    });
                    updateScopeCount(tab);
                });
            });
        })();
    </script>

    <div class="grid grid-cols-1 {{ $isVoucher ? 'md:grid-cols-2' : '' }} gap-4 mt-6">
        <label class="flex items-start gap-3 p-4 border border-gray-200 rounded-lg">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="mt-1" @checked(old('is_active', $discount->is_active))>
            <span>
                <span class="block font-medium text-gray-800">Aktif</span>
                <span class="block text-sm text-gray-500">{{ $isVoucher ? 'Kode bisa digunakan saat checkout.' : 'Diskon otomatis tampil dan dipakai selama periode aktif.' }}</span>
            </span>
        </label>

        @if($isVoucher)
        <label class="flex items-start gap-3 p-4 border border-gray-200 rounded-lg">
            <input type="hidden" name="is_public" value="0">
            <input type="checkbox" name="is_public" value="1" class="mt-1" @checked(old('is_public', $discount->is_public))>
            <span>
                <span class="block font-medium text-gray-800">Tampilkan ke peserta</span>
                <span class="block text-sm text-gray-500">Jika aktif, kode tampil sebagai promo publik.</span>
            </span>
        </label>
        @endif
    </div>

    <div class="flex justify-end gap-3 mt-6">
        <a href="{{ route('admin.discounts.index') }}" class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            Batal
        </a>
        <button type="submit" class="px-4 py-2.5 bg-primary text-white rounded-lg hover:bg-primary/90">
            {{ $isEdit ? 'Simpan Perubahan' : ($isVoucher ? 'Buat Voucher' : 'Buat Diskon') }}
        </button>
    </div>
</div>
