<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MaterialManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Material::with(['categories', 'creator']);

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        $materials = $query->orderBy('order_number', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.pages.material.index', compact('materials'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = MaterialCategory::active()->ordered()->get();
        return view('admin.pages.material.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:video,document,live_session',
            'content_url' => 'required|url|max:2048',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'duration_minutes' => 'nullable|integer|min:1',
            'order_number' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:material_categories,category_id',
            'price' => 'nullable|numeric|min:0',
        ], [
            'title.required' => 'Judul materi wajib diisi.',
            'title.max' => 'Judul materi maksimal 255 karakter.',
            'type.required' => 'Tipe materi wajib dipilih.',
            'type.in' => 'Tipe materi tidak valid.',
            'content_url.required' => 'URL konten wajib diisi.',
            'content_url.url' => 'URL konten tidak valid.',
            'content_url.max' => 'URL konten maksimal 2048 karakter.',
            'thumbnail.image' => 'Thumbnail harus berupa gambar.',
            'thumbnail.mimes' => 'Thumbnail harus berformat jpeg, png, jpg, atau gif.',
            'thumbnail.max' => 'Thumbnail maksimal 2MB.',
            'duration_minutes.integer' => 'Durasi harus berupa angka.',
            'duration_minutes.min' => 'Durasi minimal 1 menit.',
            'order_number.integer' => 'Nomor urut harus berupa angka.',
            'order_number.min' => 'Nomor urut minimal 0.',
            'category_id.exists' => 'Kategori tidak valid.',
            'price.numeric' => 'Harga harus berupa angka.',
            'price.min' => 'Harga minimal 0.',
        ]);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('materials/thumbnails', 'public');
            $validated['thumbnail_url'] = Storage::url($thumbnailPath);
        }

        // Set default order_number if not provided
        if (empty($validated['order_number'])) {
            $validated['order_number'] = Material::max('order_number') + 1;
        }

        // Set creator
        $validated['created_by'] = Auth::id();

        // Extract category_id (single, not array)
        $categoryId = $validated['category_id'] ?? null;
        unset($validated['category_id']);

        // Create material
        $material = Material::create($validated);

        // Attach single category
        if ($categoryId) {
            $material->categories()->attach($categoryId);
        }

        return redirect()->route('admin.material.index')
            ->with('success', 'Materi berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Material $material)
    {
        $categories = MaterialCategory::active()->ordered()->get();
        $selectedCategory = $material->categories->first()?->category_id;

        return view('admin.pages.material.edit', compact('material', 'categories', 'selectedCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:video,document,live_session',
            'content_url' => 'required|url|max:2048',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'duration_minutes' => 'nullable|integer|min:1',
            'order_number' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'category_id' => 'nullable|exists:material_categories,category_id',
            'price' => 'nullable|numeric|min:0',
        ], [
            'title.required' => 'Judul materi wajib diisi.',
            'title.max' => 'Judul materi maksimal 255 karakter.',
            'type.required' => 'Tipe materi wajib dipilih.',
            'type.in' => 'Tipe materi tidak valid.',
            'content_url.required' => 'URL konten wajib diisi.',
            'content_url.url' => 'URL konten tidak valid.',
            'content_url.max' => 'URL konten maksimal 2048 karakter.',
            'thumbnail.image' => 'Thumbnail harus berupa gambar.',
            'thumbnail.mimes' => 'Thumbnail harus berformat jpeg, png, jpg, atau gif.',
            'thumbnail.max' => 'Thumbnail maksimal 2MB.',
            'duration_minutes.integer' => 'Durasi harus berupa angka.',
            'duration_minutes.min' => 'Durasi minimal 1 menit.',
            'order_number.integer' => 'Nomor urut harus berupa angka.',
            'order_number.min' => 'Nomor urut minimal 0.',
            'category_id.exists' => 'Kategori tidak valid.',
            'price.numeric' => 'Harga harus berupa angka.',
            'price.min' => 'Harga minimal 0.',
        ]);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail if exists
            if ($material->thumbnail_url) {
                $oldPath = str_replace('/storage/', '', $material->thumbnail_url);
                Storage::disk('public')->delete($oldPath);
            }

            $thumbnailPath = $request->file('thumbnail')->store('materials/thumbnails', 'public');
            $validated['thumbnail_url'] = Storage::url($thumbnailPath);
        }

        // Handle is_active
        $validated['is_active'] = $request->boolean('is_active', true);

        // Extract category_id (single, not array)
        $categoryId = $validated['category_id'] ?? null;
        unset($validated['category_id']);

        // Update material
        $material->update($validated);

        // Sync single category
        if ($categoryId) {
            $material->categories()->sync([$categoryId]);
        } else {
            $material->categories()->detach();
        }

        return redirect()->route('admin.material.index')
            ->with('success', 'Materi berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Material $material)
    {
        // Delete thumbnail if exists
        if ($material->thumbnail_url) {
            $oldPath = str_replace('/storage/', '', $material->thumbnail_url);
            Storage::disk('public')->delete($oldPath);
        }

        $material->delete();

        return redirect()->route('admin.material.index')
            ->with('success', 'Materi berhasil dihapus.');
    }

    /**
     * Toggle active status
     */
    public function toggle(Material $material)
    {
        $material->update(['is_active' => !$material->is_active]);

        $status = $material->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.material.index')
            ->with('success', "Materi berhasil {$status}.");
    }
}
