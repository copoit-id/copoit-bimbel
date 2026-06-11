<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
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
            ]),
            'tryouts' => $this->tryoutOptions(),
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
            'tryouts' => $this->tryoutOptions(),
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
        ], [
            'code.regex' => 'Kode hanya boleh berisi huruf, angka, strip, atau underscore.',
            'tryout_id.required' => 'Tryout wajib dipilih untuk diskon non-voucher.',
            'ends_at.required' => 'Tanggal selesai wajib diisi untuk diskon non-voucher.',
            'ends_at.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
        ]);
    }

    private function normalizeValidatedData(Request $request, array $validated, ?Discount $discount = null): array
    {
        $validated['application_type'] = $validated['application_type'] ?: Discount::TYPE_VOUCHER;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_public'] = $validated['application_type'] === Discount::TYPE_VOUCHER
            ? $request->boolean('is_public')
            : false;

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
            ->select('tryout_id', 'name', 'type_tryout')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
