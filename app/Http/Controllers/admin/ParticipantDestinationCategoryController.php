<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use App\Models\ParticipantDestinationCategory;
use App\Services\OfficialParticipantDestinationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function downloadImportTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Universitas', 'Jurusan', 'Status', 'Urutan'],
            ['Universitas Indonesia', 'Ilmu Komputer', 'aktif', 1],
            ['Universitas Indonesia', 'Sistem Informasi', 'aktif', 2],
            ['Institut Teknologi Bandung', 'Teknik Informatika', 'aktif', 1],
        ]);
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFEFF6FF');
        $sheet->freezePane('A2');

        foreach (['A' => 34, 'B' => 34, 'C' => 14, 'D' => 12] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'template-import-universitas-jurusan.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        try {
            $rows = IOFactory::load($request->file('excel_file')->getRealPath())
                ->getActiveSheet()
                ->toArray(null, true, true, false);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'File Excel tidak dapat dibaca. Gunakan file .xlsx atau .xls yang valid.');
        }

        if (count($rows) < 2) {
            return back()->with('error', 'File Excel belum memiliki data untuk diimpor.');
        }

        $headers = collect($rows[0])
            ->map(fn ($header): string => $this->normalizeImportHeader((string) $header))
            ->all();
        $columns = [
            'institution' => $this->findImportColumn($headers, ['universitas', 'university', 'instansi', 'institusi']),
            'program' => $this->findImportColumn($headers, ['jurusan', 'program_studi', 'prodi', 'program']),
            'status' => $this->findImportColumn($headers, ['status']),
            'sort_order' => $this->findImportColumn($headers, ['urutan', 'sort_order', 'nomor_urut']),
        ];

        if ($columns['institution'] === null || $columns['program'] === null) {
            return back()->with('error', 'Header wajib: Universitas dan Jurusan. Unduh template agar format sesuai.');
        }

        $records = [];
        $duplicateRowsInFile = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $offset => $row) {
            $line = $offset + 2;
            $institution = trim((string) ($row[$columns['institution']] ?? ''));
            $program = trim((string) ($row[$columns['program']] ?? ''));
            $status = $this->parseImportStatus((string) ($columns['status'] === null ? '' : ($row[$columns['status']] ?? '')));
            $sortOrder = $this->parseImportSortOrder((string) ($columns['sort_order'] === null ? '' : ($row[$columns['sort_order']] ?? '')));
            $rowErrors = [];

            if ($institution === '' && $program === '') {
                continue;
            }

            if ($institution === '') {
                $rowErrors[] = "Baris {$line}: Universitas wajib diisi.";
            }

            if (mb_strlen($institution) > 255 || mb_strlen($program) > 255) {
                $rowErrors[] = "Baris {$line}: Universitas dan jurusan maksimal 255 karakter.";
            }

            if ($status === null) {
                $rowErrors[] = "Baris {$line}: Status harus aktif atau nonaktif.";
            }

            if ($sortOrder === null) {
                $rowErrors[] = "Baris {$line}: Urutan harus berupa angka dari 0 sampai 999999.";
            }

            $errors = [...$errors, ...$rowErrors];

            if (count($errors) >= 10) {
                break;
            }

            if ($rowErrors !== []) {
                continue;
            }

            $recordKey = $this->normalizedDestinationImportKey($institution, $program);

            if (isset($records[$recordKey])) {
                $duplicateRowsInFile++;

                continue;
            }

            $records[$recordKey] = [
                'institution' => $institution,
                'program' => $program,
                'is_active' => $status,
                'sort_order' => $sortOrder,
            ];
        }

        if ($errors !== []) {
            return back()->with('error', "Impor dibatalkan.\n".implode("\n", $errors));
        }

        if ($records === []) {
            return back()->with('error', 'Tidak ada data valid yang dapat diimpor.');
        }

        try {
            [$created, $skippedExisting] = DB::transaction(function () use ($records): array {
                $created = 0;
                $skippedExisting = 0;
                $parents = ParticipantDestinationCategory::query()
                    ->root()
                    ->get(['id', 'name', 'slug', 'is_active', 'sort_order'])
                    ->keyBy(fn (ParticipantDestinationCategory $category): string => $this->normalizeImportValue($category->name));
                $existingParentKeys = $parents->keys()->flip();
                $usedRootSlugs = $parents
                    ->pluck('slug')
                    ->map(fn (string $slug): string => Str::lower($slug))
                    ->flip()
                    ->all();
                $institutionRows = collect($records)
                    ->keyBy(fn (array $record): string => $this->normalizeImportValue($record['institution']));

                foreach ($institutionRows as $key => $record) {
                    $parent = $parents->get($key);
                    $attributes = [
                        'is_active' => $record['is_active'],
                        'sort_order' => $record['sort_order'],
                    ];

                    if (! $parent) {
                        $parent = ParticipantDestinationCategory::create([
                            'name' => $record['institution'],
                            'slug' => $this->makeImportSlug($record['institution'], $usedRootSlugs),
                            ...$attributes,
                        ]);
                        $parents->put($key, $parent);
                        $created++;
                    }
                }

                $children = ParticipantDestinationCategory::query()
                    ->whereIn('parent_id', $parents->pluck('id'))
                    ->get(['id', 'parent_id', 'name', 'slug', 'is_active', 'sort_order']);
                $childrenByKey = $children->keyBy(fn (ParticipantDestinationCategory $category): string => $category->parent_id.'|'.$this->normalizeImportValue($category->name));
                $usedChildSlugs = $children
                    ->groupBy('parent_id')
                    ->map(fn ($items): array => $items->pluck('slug')->map(fn (string $slug): string => Str::lower($slug))->flip()->all())
                    ->all();
                $programRows = collect($records)
                    ->filter(fn (array $record): bool => $record['program'] !== '')
                    ->mapWithKeys(function (array $record) use ($parents): array {
                        $parent = $parents->get($this->normalizeImportValue($record['institution']));

                        return [$parent->id.'|'.$this->normalizeImportValue($record['program']) => $record];
                    });

                foreach ($programRows as $key => $record) {
                    [$parentId] = explode('|', $key, 2);
                    $child = $childrenByKey->get($key);
                    $attributes = [
                        'is_active' => $record['is_active'],
                        'sort_order' => $record['sort_order'],
                    ];

                    if ($child) {
                        $skippedExisting++;

                        continue;
                    }

                    $usedChildSlugs[$parentId] ??= [];
                    $child = ParticipantDestinationCategory::create([
                        'parent_id' => (int) $parentId,
                        'name' => $record['program'],
                        'slug' => $this->makeImportSlug($record['program'], $usedChildSlugs[$parentId]),
                        ...$attributes,
                    ]);
                    $childrenByKey->put($key, $child);
                    $created++;
                }

                foreach ($records as $record) {
                    if ($record['program'] === ''
                        && isset($existingParentKeys[$this->normalizeImportValue($record['institution'])])) {
                        $skippedExisting++;
                    }
                }

                return [$created, $skippedExisting];
            });
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Impor gagal diproses. Tidak ada data yang disimpan.');
        }

        $skippedDuplicates = $duplicateRowsInFile + $skippedExisting;
        $message = "Impor selesai: {$created} data baru ditambahkan.";

        if ($skippedDuplicates > 0) {
            $message .= " {$skippedDuplicates} data duplikat dilewati.";
        }

        return redirect()
            ->route('admin.participant-destination-categories.index')
            ->with('success', $message);
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

    private function normalizeImportHeader(string $header): string
    {
        return Str::of($header)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    private function findImportColumn(array $headers, array $acceptedHeaders): ?int
    {
        foreach ($acceptedHeaders as $header) {
            $index = array_search($header, $headers, true);

            if ($index !== false) {
                return $index;
            }
        }

        return null;
    }

    private function parseImportStatus(string $value): ?bool
    {
        $value = Str::lower(trim($value));

        return match ($value) {
            '', 'aktif', 'active', '1', 'ya', 'yes' => true,
            'nonaktif', 'inactive', '0', 'tidak', 'no' => false,
            default => null,
        };
    }

    private function parseImportSortOrder(string $value): ?int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        if (! preg_match('/^\d+$/', $value)) {
            return null;
        }

        $sortOrder = (int) $value;

        return $sortOrder <= 999999 ? $sortOrder : null;
    }

    private function normalizeImportValue(string $value): string
    {
        return Str::lower(trim(preg_replace('/\s+/', ' ', $value)));
    }

    private function normalizedDestinationImportKey(string $institution, string $program): string
    {
        return $this->normalizeImportValue($institution).'|'.$this->normalizeImportValue($program);
    }

    private function makeImportSlug(string $name, array &$usedSlugs): string
    {
        $baseSlug = Str::slug($name) ?: 'kategori';
        $slug = $baseSlug;
        $counter = 2;

        while (isset($usedSlugs[Str::lower($slug)])) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        $usedSlugs[Str::lower($slug)] = true;

        return $slug;
    }

}
