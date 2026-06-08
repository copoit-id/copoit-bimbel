<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiscountController extends Controller
{
    public function index()
    {
        $discounts = Discount::query()
            ->latest()
            ->paginate(15);

        return view('admin.pages.discounts.index', compact('discounts'));
    }

    public function create()
    {
        return view('admin.pages.discounts.create', [
            'discount' => new Discount([
                'discount_type' => 'percent',
                'discount_value' => 0,
                'min_purchase_amount' => 0,
                'per_user_limit' => 1,
                'is_active' => true,
                'is_public' => false,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        $validated['code'] = Discount::normalizeCode($validated['code']);
        $validated['min_purchase_amount'] = $validated['min_purchase_amount'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_public'] = $request->boolean('is_public');

        Discount::create($validated);

        return redirect()
            ->route('admin.discounts.index')
            ->with('success', 'Kode diskon berhasil dibuat.');
    }

    public function edit(Discount $discount)
    {
        return view('admin.pages.discounts.edit', compact('discount'));
    }

    public function update(Request $request, Discount $discount)
    {
        $validated = $this->validatedData($request, $discount);
        $validated['code'] = Discount::normalizeCode($validated['code']);
        $validated['min_purchase_amount'] = $validated['min_purchase_amount'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_public'] = $request->boolean('is_public');

        $discount->update($validated);

        return redirect()
            ->route('admin.discounts.index')
            ->with('success', 'Kode diskon berhasil diperbarui.');
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();

        return redirect()
            ->route('admin.discounts.index')
            ->with('success', 'Kode diskon berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Discount $discount = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('discounts', 'code')->ignore($discount?->id),
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
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'Kode hanya boleh berisi huruf, angka, strip, atau underscore.',
            'ends_at.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
        ]);
    }
}
