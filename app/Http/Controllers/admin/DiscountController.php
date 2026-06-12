<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Material;
use App\Models\Package;
use App\Models\TesKoran;
use App\Models\Tryout;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', Discount::TYPE_VOUCHER);
        if (!in_array($tab, [Discount::TYPE_VOUCHER, Discount::TYPE_PACKAGE_TRYOUT], true)) {
            $tab = Discount::TYPE_VOUCHER;
        }

        $discounts = Discount::query()
            ->with('tryout')
            ->where('application_type', $tab)
            ->latest()
            ->paginate(15);

        return view('admin.pages.discounts.index', compact('discounts', 'tab'));
    }

    public function create(Request $request)
    {
        $tab = $request->get('tab', Discount::TYPE_VOUCHER);
        if (!in_array($tab, [Discount::TYPE_VOUCHER, Discount::TYPE_PACKAGE_TRYOUT], true)) {
            $tab = Discount::TYPE_VOUCHER;
        }

        return view('admin.pages.discounts.create', [
            'discount' => new Discount([
                'application_type' => $tab,
                'discount_type' => 'percent',
                'discount_value' => 0,
                'min_purchase_amount' => 0,
                'per_user_limit' => 1,
                'is_active' => true,
                'is_public' => false,
                'applicable_purchase_types' => ['package'],
            ]),
            'tryouts' => $this->automaticTryoutOptions(),
            'saleTryouts' => $this->tryoutOptions(),
            'packages' => $this->packageOptions(),
            'materials' => $this->materialOptions(),
            'tesKorans' => $this->tesKoranOptions(),
            'tab' => $tab,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        $validated = $this->normalizeValidatedData($request, $validated);
        $validated['min_purchase_amount'] = $validated['min_purchase_amount'] ?? 0;

        Discount::create($validated);

        return redirect()
            ->route('admin.discounts.index', ['tab' => $validated['application_type']])
            ->with('success', $validated['application_type'] === Discount::TYPE_VOUCHER ? 'Voucher berhasil dibuat.' : 'Diskon berhasil dibuat.');
    }

    public function edit(Discount $discount)
    {
        return view('admin.pages.discounts.edit', [
            'discount' => $discount,
            'tryouts' => $this->automaticTryoutOptions(),
            'saleTryouts' => $this->tryoutOptions(),
            'packages' => $this->packageOptions(),
            'materials' => $this->materialOptions(),
            'tesKorans' => $this->tesKoranOptions(),
            'tab' => $discount->application_type ?: Discount::TYPE_VOUCHER,
        ]);
    }

    public function update(Request $request, Discount $discount)
    {
        $validated = $this->validatedData($request, $discount);
        $validated = $this->normalizeValidatedData($request, $validated, $discount);
        $validated['min_purchase_amount'] = $validated['min_purchase_amount'] ?? 0;

        $discount->update($validated);

        return redirect()
            ->route('admin.discounts.index', ['tab' => $validated['application_type']])
            ->with('success', $validated['application_type'] === Discount::TYPE_VOUCHER ? 'Voucher berhasil diperbarui.' : 'Diskon berhasil diperbarui.');
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();

        return redirect()
            ->route('admin.discounts.index', ['tab' => $discount->application_type ?: Discount::TYPE_VOUCHER])
            ->with('success', 'Diskon berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Discount $discount = null): array
    {
        $applicationType = $request->input('application_type', Discount::TYPE_VOUCHER);
        $isVoucher = $applicationType === Discount::TYPE_VOUCHER;
        $salePackageIds = $this->packageOptions()->pluck('package_id')->map(fn ($id) => (int) $id)->all();
        $saleTryoutIds = $this->tryoutOptions()->pluck('tryout_id')->map(fn ($id) => (int) $id)->all();
        $saleMaterialIds = $this->materialOptions()->pluck('material_id')->map(fn ($id) => (int) $id)->all();
        $saleTesKoranIds = $this->tesKoranOptions()->pluck('id')->map(fn ($id) => (int) $id)->all();

        return $request->validate([
            'application_type' => ['required', Rule::in([Discount::TYPE_VOUCHER, Discount::TYPE_PACKAGE_TRYOUT])],
            'code' => [
                $isVoucher ? 'required' : 'nullable',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('discounts', 'code')->ignore($discount?->id),
            ],
            'tryout_id' => [
                $isVoucher ? 'nullable' : 'required',
                'integer',
                Rule::exists('tryouts', 'tryout_id'),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'discount_type' => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'min_purchase_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => [$isVoucher ? 'nullable' : 'required', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'applicable_package_ids' => ['nullable', 'array'],
            'applicable_package_ids.*' => ['integer', Rule::in($salePackageIds)],
            'applicable_tryout_ids' => ['nullable', 'array'],
            'applicable_tryout_ids.*' => ['integer', Rule::in($saleTryoutIds)],
            'applicable_material_ids' => ['nullable', 'array'],
            'applicable_material_ids.*' => ['integer', Rule::in($saleMaterialIds)],
            'applicable_tes_koran_ids' => ['nullable', 'array'],
            'applicable_tes_koran_ids.*' => ['integer', Rule::in($saleTesKoranIds)],
        ], [
            'code.regex' => 'Kode hanya boleh berisi huruf, angka, strip, atau underscore.',
            'tryout_id.required' => 'Tryout wajib dipilih untuk diskon non-voucher.',
            'ends_at.required' => 'Tanggal selesai wajib diisi untuk diskon non-voucher.',
            'ends_at.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
            'applicable_package_ids.*.in' => 'Paket yang dipilih harus paket berbayar aktif.',
            'applicable_tryout_ids.*.in' => 'Tryout yang dipilih harus tryout yang dijual.',
            'applicable_material_ids.*.in' => 'Materi yang dipilih harus materi yang dijual.',
            'applicable_tes_koran_ids.*.in' => 'Tes koran yang dipilih harus tes koran yang dijual.',
        ]);
    }

    private function normalizeValidatedData(Request $request, array $validated, ?Discount $discount = null): array
    {
        $validated['application_type'] = $validated['application_type'] ?: Discount::TYPE_VOUCHER;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_public'] = $validated['application_type'] === Discount::TYPE_VOUCHER
            ? $request->boolean('is_public')
            : false;

        foreach ([
            'applicable_package_ids' => 'package',
            'applicable_tryout_ids' => 'tryout',
            'applicable_material_ids' => 'material',
            'applicable_tes_koran_ids' => 'tes_koran',
        ] as $field => $purchaseType) {
            $validated[$field] = array_values(array_unique(array_map('intval', $validated[$field] ?? [])));
        }

        $validated['applicable_purchase_types'] = [];
        if (!empty($validated['applicable_package_ids'])) {
            $validated['applicable_purchase_types'][] = 'package';
        }
        if (!empty($validated['applicable_tryout_ids'])) {
            $validated['applicable_purchase_types'][] = 'tryout';
        }
        if (!empty($validated['applicable_material_ids'])) {
            $validated['applicable_purchase_types'][] = 'material';
        }
        if (!empty($validated['applicable_tes_koran_ids'])) {
            $validated['applicable_purchase_types'][] = 'tes_koran';
        }

        if (empty($validated['applicable_purchase_types'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'scope_items' => 'Pilih minimal satu item untuk scope voucher/diskon.',
            ]);
        }

        if ($validated['application_type'] === Discount::TYPE_VOUCHER) {
            $validated['code'] = Discount::normalizeCode($validated['code']);
            $validated['tryout_id'] = null;
            return $validated;
        }

        $validated['code'] = $discount?->code ?: 'AUTO-' . $validated['tryout_id'] . '-' . Str::upper(Str::random(8));
        $validated['min_purchase_amount'] = 0;
        $validated['usage_limit'] = null;
        $validated['per_user_limit'] = null;

        return $validated;
    }

    private function tryoutOptions()
    {
        return Tryout::query()
            ->select('tryout_id', 'name', 'type_tryout', 'price')
            ->where('is_active', true)
            ->where('is_for_sale', true)
            ->where('price', '>', 0)
            ->orderBy('name')
            ->get();
    }

    private function automaticTryoutOptions()
    {
        return Tryout::query()
            ->select('tryout_id', 'name', 'type_tryout')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function packageOptions()
    {
        return Package::query()
            ->select('package_id', 'name', 'type_price', 'price')
            ->where('status', 'active')
            ->where('type_price', 'paid')
            ->where('price', '>', 0)
            ->orderBy('name')
            ->get();
    }

    private function materialOptions()
    {
        return Material::query()
            ->select('material_id', 'title', 'type', 'price')
            ->where('is_active', true)
            ->where('is_for_sale', true)
            ->where('price', '>', 0)
            ->orderBy('title')
            ->get();
    }

    private function tesKoranOptions()
    {
        return TesKoran::query()
            ->select('id', 'name', 'test_type', 'price')
            ->where('is_active', true)
            ->where('is_for_sale', true)
            ->where('price', '>', 0)
            ->orderBy('name')
            ->get();
    }
}
