<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use App\Models\ParticipantDestinationCategory;
use App\Services\OfficialParticipantDestinationService;
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

        $profile = ClientProfile::query()->first();
        $officialApiEnabled = (bool) ($profile->participant_destination_api_enabled ?? false);

        return view('admin.pages.participant-destination-categories.index', compact(
            'categories',
            'parentOptions',
            'officialApiEnabled'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validateCategory($request);
        $validated['slug'] = $this->makeUniqueSlug($validated['name'], $validated['parent_id'] ?? null);
        $validated['is_active'] = $request->boolean('is_active', true);

        ParticipantDestinationCategory::create($validated);

        return redirect()
            ->route('admin.participant-destination-categories.index')
            ->with('success', !empty($validated['parent_id'])
                ? 'Sub tujuan berhasil ditambahkan.'
                : 'Instansi tujuan berhasil ditambahkan.');
    }

    public function update(Request $request, ParticipantDestinationCategory $participantDestinationCategory)
    {
        $validated = $this->validateCategory($request, $participantDestinationCategory);

        if ((int) ($validated['parent_id'] ?? 0) === (int) $participantDestinationCategory->id) {
            return back()
                ->withErrors(['parent_id' => 'Tujuan tidak bisa menjadi sub tujuan dirinya sendiri.'])
                ->withInput();
        }

        if ($participantDestinationCategory->children()->exists() && !empty($validated['parent_id'])) {
            return back()
                ->withErrors(['parent_id' => 'Instansi yang sudah memiliki sub tujuan tidak bisa dijadikan sub tujuan.'])
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
            ->with('success', !empty($validated['parent_id'])
                ? 'Sub tujuan berhasil diperbarui.'
                : 'Instansi tujuan berhasil diperbarui.');
    }

    public function destroy(ParticipantDestinationCategory $participantDestinationCategory)
    {
        $participantDestinationCategory->delete();

        return redirect()
            ->route('admin.participant-destination-categories.index')
            ->with('success', 'Tujuan / instansi berhasil dihapus.');
    }

    public function officialInstitutions(Request $request, OfficialParticipantDestinationService $destinationService)
    {
        $validated = $request->validate([
            'source' => ['required', Rule::in(['all', 'snbt', 'snbp'])],
        ]);

        return response()->json([
            'data' => $destinationService->institutions($validated['source']),
        ]);
    }

    public function officialPrograms(Request $request, OfficialParticipantDestinationService $destinationService)
    {
        $validated = $request->validate([
            'source' => ['required', Rule::in(['all', 'snbt', 'snbp'])],
            'ptn' => ['required', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'ptn_snbt' => ['nullable', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'ptn_snbp' => ['nullable', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);

        return response()->json([
            'data' => $destinationService->programs(
                $validated['source'],
                $validated['ptn'],
                $validated['ptn_snbt'] ?? null,
                $validated['ptn_snbp'] ?? null
            ),
        ]);
    }

    public function updateOfficialApiSetting(Request $request)
    {
        $profile = ClientProfile::query()->first() ?? new ClientProfile();
        $profile->participant_destination_api_enabled = $request->boolean('participant_destination_api_enabled');

        if (!$profile->exists) {
            $profile->nama_bimbel = config('client.branding.name', config('app.name', 'Copoit Academy'));
            $profile->logo = config('client.branding.logo', 'img/logo/logo-copoit.png');
            $profile->warna_primary = config('client.branding.primary_color', '#1C3259');
            $profile->warna_secondary = config('client.branding.secondary_color', '#F3F3F3');
        }

        $profile->save();

        return redirect()
            ->route('admin.participant-destination-categories.index')
            ->with('success', $profile->participant_destination_api_enabled
                ? 'Opsi peserta sekarang menampilkan gabungan data DB dan API resmi SNPMB.'
                : 'Opsi peserta sekarang hanya menampilkan data DB manual.');
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
