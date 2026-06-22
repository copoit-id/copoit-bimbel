<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ParticipantDestinationCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

    public function importSnpmb(Request $request)
    {
        $validated = $request->validate([
            'source' => ['required', Rule::in(['all', 'snbt', 'snbp'])],
        ]);

        @set_time_limit(300);

        $sources = $validated['source'] === 'all'
            ? ['snbt', 'snbp']
            : [$validated['source']];

        $createdInstitutions = 0;
        $updatedInstitutions = 0;
        $createdPrograms = 0;
        $updatedPrograms = 0;
        $failedPrograms = 0;
        $failedInstitutions = 0;

        foreach ($sources as $source) {
            $ptnEndpoint = $source === 'snbt'
                ? 'https://snpmb.id/proxy-ptn-sb.php'
                : 'https://snpmb.id/proxy-ptn-sn.php';
            $prodiEndpoint = $source === 'snbt'
                ? 'https://snpmb.id/proxy-prodi-sb.php'
                : 'https://snpmb.id/proxy-prodi-sn.php';

            try {
                $ptnResponse = Http::timeout(20)->retry(2, 500)->get($ptnEndpoint);
            } catch (\Throwable $e) {
                report($e);
                $failedInstitutions++;
                continue;
            }

            if (! $ptnResponse->successful() || ! is_array($ptnResponse->json())) {
                $failedInstitutions++;
                continue;
            }

            $ptnList = collect($ptnResponse->json())
                ->filter(fn($ptn) => is_array($ptn) && !empty($ptn['id_ptn']) && !empty($ptn['nama']))
                ->values();

            foreach ($ptnList as $ptnIndex => $ptn) {
                [$institution, $wasCreated] = $this->updateOrCreateDestination(
                    name: trim((string) $ptn['nama']),
                    parentId: null,
                    sortOrder: ($ptnIndex + 1) * 10
                );

                $wasCreated ? $createdInstitutions++ : $updatedInstitutions++;

                try {
                    $prodiResponse = Http::timeout(20)->retry(2, 500)->get($prodiEndpoint, [
                        'ptn' => $ptn['id_ptn'],
                    ]);
                } catch (\Throwable $e) {
                    report($e);
                    $failedPrograms++;
                    continue;
                }

                if (! $prodiResponse->successful() || ! is_array($prodiResponse->json())) {
                    $failedPrograms++;
                    continue;
                }

                foreach ($prodiResponse->json() as $prodiIndex => $prodi) {
                    if (! is_array($prodi) || empty($prodi['nama'])) {
                        continue;
                    }

                    $programName = trim(implode(' ', array_filter([
                        $prodi['jenjang'] ?? null,
                        $prodi['nama'] ?? null,
                    ])));

                    if ($programName === '') {
                        continue;
                    }

                    [, $programWasCreated] = $this->updateOrCreateDestination(
                        name: $programName,
                        parentId: $institution->id,
                        sortOrder: ($prodiIndex + 1) * 10
                    );

                    $programWasCreated ? $createdPrograms++ : $updatedPrograms++;
                }
            }
        }

        if ($failedInstitutions > 0 && $createdInstitutions === 0 && $updatedInstitutions === 0) {
            return redirect()
                ->route('admin.participant-destination-categories.index')
                ->with('error', 'Gagal mengambil daftar PTN dari SNPMB.');
        }

        return redirect()
            ->route('admin.participant-destination-categories.index')
            ->with(
                'success',
                "Import SNPMB selesai. Instansi baru: {$createdInstitutions}, instansi diperbarui: {$updatedInstitutions}, prodi baru: {$createdPrograms}, prodi diperbarui: {$updatedPrograms}, gagal ambil daftar PTN: {$failedInstitutions}, gagal ambil prodi: {$failedPrograms}."
            );
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

    private function updateOrCreateDestination(string $name, ?int $parentId, int $sortOrder): array
    {
        $slug = Str::slug($name) ?: 'tujuan';
        $existing = ParticipantDestinationCategory::query()
            ->where('parent_id', $parentId)
            ->where('slug', $slug)
            ->first();

        if ($existing) {
            $existing->update([
                'name' => $name,
                'sort_order' => $existing->sort_order ?: $sortOrder,
                'is_active' => true,
            ]);

            return [$existing, false];
        }

        return [
            ParticipantDestinationCategory::create([
                'parent_id' => $parentId,
                'name' => $name,
                'slug' => $slug,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]),
            true,
        ];
    }
}
