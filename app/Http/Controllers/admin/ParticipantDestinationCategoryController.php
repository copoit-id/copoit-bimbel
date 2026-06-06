<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ParticipantDestinationCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ParticipantDestinationCategoryController extends Controller
{
    public function index()
    {
        $categories = ParticipantDestinationCategory::query()
            ->root()
            ->with(['children' => fn($query) => $query->withCount('users')])
            ->withCount('users')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $parentOptions = ParticipantDestinationCategory::query()
            ->root()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.pages.participant-destination-categories.index', compact('categories', 'parentOptions'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateCategory($request);
        $validated['slug'] = $this->makeUniqueSlug($validated['name'], $validated['parent_id'] ?? null);
        $validated['is_active'] = $request->boolean('is_active', true);

        ParticipantDestinationCategory::create($validated);

        return redirect()
            ->route('admin.participant-destination-categories.index')
            ->with('success', 'Kategori tujuan peserta berhasil ditambahkan.');
    }

    public function update(Request $request, ParticipantDestinationCategory $participantDestinationCategory)
    {
        $validated = $this->validateCategory($request, $participantDestinationCategory);

        if ((int) ($validated['parent_id'] ?? 0) === (int) $participantDestinationCategory->id) {
            return back()
                ->withErrors(['parent_id' => 'Kategori tidak bisa menjadi subkategori dirinya sendiri.'])
                ->withInput();
        }

        if ($participantDestinationCategory->children()->exists() && !empty($validated['parent_id'])) {
            return back()
                ->withErrors(['parent_id' => 'Kategori yang sudah memiliki subkategori tidak bisa dijadikan subkategori.'])
                ->withInput();
        }

        $validated['slug'] = $this->makeUniqueSlug(
            $validated['name'],
            $validated['parent_id'] ?? null,
            $participantDestinationCategory->id
        );
        $validated['is_active'] = $request->boolean('is_active');

        $participantDestinationCategory->update($validated);

        return redirect()
            ->route('admin.participant-destination-categories.index')
            ->with('success', 'Kategori tujuan peserta berhasil diperbarui.');
    }

    public function destroy(ParticipantDestinationCategory $participantDestinationCategory)
    {
        $participantDestinationCategory->delete();

        return redirect()
            ->route('admin.participant-destination-categories.index')
            ->with('success', 'Kategori tujuan peserta berhasil dihapus.');
    }

    private function validateCategory(Request $request, ?ParticipantDestinationCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                Rule::exists('participant_destination_categories', 'id')->whereNull('parent_id'),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);
    }

    private function makeUniqueSlug(string $name, ?int $parentId = null, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'kategori';
        $slug = $baseSlug;
        $counter = 2;

        while (
            ParticipantDestinationCategory::query()
                ->where('parent_id', $parentId)
                ->where('slug', $slug)
                ->when($ignoreId, fn($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
