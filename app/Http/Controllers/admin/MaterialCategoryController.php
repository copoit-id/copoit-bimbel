<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\MaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaterialCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = MaterialCategory::query()
            ->root()
            ->with(['children' => fn($query) => $query->withCount('materials')])
            ->withCount('materials')
            ->ordered()
            ->get();
        $parentOptions = MaterialCategory::query()
            ->root()
            ->ordered()
            ->get();

        return view('admin.pages.material-category.index', compact('categories', 'parentOptions'));
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
            'parent_id' => [
                'nullable',
                Rule::exists('material_categories', 'category_id')->whereNull('parent_id'),
            ],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 255 karakter.',
            'order_number.integer' => 'Nomor urut harus berupa angka.',
            'order_number.min' => 'Nomor urut minimal 0.',
            'parent_id.exists' => 'Kategori utama tidak valid.',
        ]);

        // Set default order_number if not provided
        if (empty($validated['order_number'])) {
            $validated['order_number'] = MaterialCategory::max('order_number') + 1;
        }

        MaterialCategory::create($validated);

        return redirect()->route('admin.material.material-category.index')
            ->with('success', !empty($validated['parent_id'])
                ? 'Kategori materi berhasil ditambahkan.'
                : 'Kategori utama berhasil ditambahkan.');
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
            'parent_id' => [
                'nullable',
                Rule::exists('material_categories', 'category_id')->whereNull('parent_id'),
            ],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 255 karakter.',
            'order_number.integer' => 'Nomor urut harus berupa angka.',
            'order_number.min' => 'Nomor urut minimal 0.',
            'parent_id.exists' => 'Kategori utama tidak valid.',
        ]);

        if ((int) ($validated['parent_id'] ?? 0) === (int) $category->category_id) {
            return redirect()->route('admin.material.material-category.index')
                ->withErrors(['parent_id' => 'Kategori tidak bisa menjadi kategori materi dirinya sendiri.'])
                ->withInput();
        }

        if ($category->children()->exists() && !empty($validated['parent_id'])) {
            return redirect()->route('admin.material.material-category.index')
                ->withErrors(['parent_id' => 'Kategori utama yang sudah memiliki kategori materi tidak bisa dijadikan kategori materi.'])
                ->withInput();
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $category->update($validated);

        return redirect()->route('admin.material.material-category.index')
            ->with('success', !empty($validated['parent_id'])
                ? 'Kategori materi berhasil diperbarui.'
                : 'Kategori utama berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MaterialCategory $category)
    {
        // Check if category or its children have materials
        $childIds = $category->children()->pluck('category_id');
        $hasMaterials = $category->materials()->exists()
            || MaterialCategory::query()
                ->whereIn('category_id', $childIds)
                ->whereHas('materials')
                ->exists();

        if ($hasMaterials) {
            return redirect()->route('admin.material.material-category.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih memiliki materi.');
        }

        $category->delete();

        return redirect()->route('admin.material.material-category.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}
