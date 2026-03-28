<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\MaterialCategory;
use Illuminate\Http\Request;

class MaterialCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = MaterialCategory::ordered()->get();
        return view('admin.pages.material-category.index', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'order_number' => 'nullable|integer|min:0',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 255 karakter.',
            'order_number.integer' => 'Nomor urut harus berupa angka.',
            'order_number.min' => 'Nomor urut minimal 0.',
        ]);

        // Set default order_number if not provided
        if (empty($validated['order_number'])) {
            $validated['order_number'] = MaterialCategory::max('order_number') + 1;
        }

        MaterialCategory::create($validated);

        return redirect()->route('admin.material.category.index')
            ->with('success', 'Kategori materi berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MaterialCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'order_number' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 255 karakter.',
            'order_number.integer' => 'Nomor urut harus berupa angka.',
            'order_number.min' => 'Nomor urut minimal 0.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $category->update($validated);

        return redirect()->route('admin.material.category.index')
            ->with('success', 'Kategori materi berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MaterialCategory $category)
    {
        // Check if category has materials
        if ($category->materials()->count() > 0) {
            return redirect()->route('admin.material.category.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih memiliki materi.');
        }

        $category->delete();

        return redirect()->route('admin.material.category.index')
            ->with('success', 'Kategori materi berhasil dihapus.');
    }
}
