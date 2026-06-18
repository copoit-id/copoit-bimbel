<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Kecermatan;
use App\Models\KecermatanColumn;
use App\Services\KecermatanQuestionGenerator;
use App\Services\PurchaseAccessDuration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KecermatanController extends Controller
{
    public function index(): View
    {
        $kecermatans = Kecermatan::withCount(['columns', 'attempts'])
            ->latest()
            ->paginate(20);

        return view('admin.pages.kecermatan.index', compact('kecermatans'));
    }

    public function create(): View
    {
        return view('admin.pages.kecermatan.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedKecermatan($request);
        $columns = $this->validatedColumns($request, $validated['type']);
        $this->normalizeAccessDuration($validated, $request);

        $kecermatan = DB::transaction(function () use ($validated, $columns) {
            $kecermatan = Kecermatan::create($validated);

            foreach ($columns as $columnData) {
                $column = $kecermatan->columns()->create($columnData);
                $column->load('kecermatan');
                app(KecermatanQuestionGenerator::class)->regenerate($column);
            }

            return $kecermatan;
        });

        return redirect()->route('admin.kecermatan.edit', $kecermatan)
            ->with('success', 'Kecermatan berhasil dibuat dan soal otomatis digenerate.');
    }

    public function edit(Kecermatan $kecermatan): View
    {
        $kecermatan->load(['columns.questions']);

        return view('admin.pages.kecermatan.form', compact('kecermatan'));
    }

    public function update(Request $request, Kecermatan $kecermatan): RedirectResponse
    {
        $validated = $this->validatedKecermatan($request);
        $this->normalizeAccessDuration($validated, $request);

        $kecermatan->update($validated);

        return redirect()->route('admin.kecermatan.edit', array_merge(request()->query(), ['kecermatan' => $kecermatan->id]))
            ->with('success', 'Kecermatan berhasil diperbarui.');
    }

    public function destroy(Kecermatan $kecermatan): RedirectResponse
    {
        $kecermatan->delete();

        return redirect()->route('admin.kecermatan.index')
            ->with('success', 'Kecermatan berhasil dihapus.');
    }

    public function storeColumn(Request $request, Kecermatan $kecermatan, KecermatanQuestionGenerator $generator): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'duration_seconds' => ['required', 'integer', 'min:5', 'max:3600'],
            'questions_count' => ['required', 'integer', 'min:1', 'max:500'],
            'column_type' => ['required', 'in:simbol,huruf,angka,campuran'],
            'references' => ['nullable', 'array', 'size:5'],
            'references.*' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['sort_order'] = (int) ($validated['sort_order'] ?? ($kecermatan->columns()->max('sort_order') + 1));
        $validated['references'] = $kecermatan->type === 'kecermatan_polri'
            ? array_values($validated['references'] ?? [])
            : null;

        if ($kecermatan->type === 'kecermatan_polri' && count($this->filledReferences($validated['references'] ?? [])) !== 5) {
            return back()->withInput()->with('error', 'Kecermatan POLRI wajib memiliki 5 referensi A-E.');
        }

        DB::transaction(function () use ($kecermatan, $validated, $generator) {
            $column = $kecermatan->columns()->create($validated);
            $column->load('kecermatan');
            $generator->regenerate($column);
        });

        return redirect()->route('admin.kecermatan.edit', $kecermatan)
            ->with('success', 'Kolom kecermatan berhasil dibuat dan soal otomatis digenerate.');
    }

    public function destroyColumn(Kecermatan $kecermatan, KecermatanColumn $column): RedirectResponse
    {
        abort_unless($column->kecermatan_id === $kecermatan->id, 404);

        $column->delete();

        return redirect()->route('admin.kecermatan.edit', $kecermatan)
            ->with('success', 'Kolom kecermatan berhasil dihapus.');
    }

    public function preview(Kecermatan $kecermatan): View
    {
        $kecermatan->load(['columns.questions']);

        return view('admin.pages.kecermatan.preview', compact('kecermatan'));
    }

    private function validatedKecermatan(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:kecermatan_polri,kecermatan_tni'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'integer', 'min:0'],
            'is_for_sale' => ['boolean'],
            'is_displayed' => ['boolean'],
            'is_active' => ['boolean'],
            'access_duration_unit' => ['nullable', 'in:forever,day,week,month,year'],
            'access_duration_value' => ['nullable', 'integer', 'min:1', 'max:1200'],
        ]);
    }

    private function validatedColumns(Request $request, string $kecermatanType): array
    {
        $request->validate([
            'columns' => ['required', 'array', 'min:1'],
            'columns.*.name' => ['required', 'string', 'max:255'],
            'columns.*.duration_seconds' => ['required', 'integer', 'min:5', 'max:3600'],
            'columns.*.questions_count' => ['required', 'integer', 'min:1', 'max:500'],
            'columns.*.column_type' => ['required', 'in:simbol,huruf,angka,campuran'],
            'columns.*.references' => ['nullable', 'array', 'size:5'],
            'columns.*.references.*' => ['nullable', 'string', 'max:255'],
        ]);

        return collect($request->input('columns', []))
            ->values()
            ->map(function (array $column, int $index) use ($kecermatanType) {
                $references = array_values($column['references'] ?? []);

                if ($kecermatanType === 'kecermatan_polri' && count($this->filledReferences($references)) !== 5) {
                    throw ValidationException::withMessages([
                        'columns.' . $index . '.references' => 'Setiap kolom POLRI wajib memiliki 5 referensi A-E.',
                    ]);
                }

                return [
                    'name' => $column['name'],
                    'sort_order' => $index + 1,
                    'duration_seconds' => (int) $column['duration_seconds'],
                    'questions_count' => (int) $column['questions_count'],
                    'column_type' => $column['column_type'],
                    'references' => $kecermatanType === 'kecermatan_polri' ? $references : null,
                ];
            })
            ->all();
    }

    private function normalizeAccessDuration(array &$validated, Request $request): void
    {
        $validated['price'] = (int) ($validated['price'] ?? 0);
        $validated['is_for_sale'] = $request->boolean('is_for_sale') && $validated['price'] > 0;
        $validated['is_displayed'] = $request->boolean('is_displayed', false);
        $validated['is_active'] = $request->boolean('is_active', false);

        if (!$validated['is_for_sale']) {
            $validated['access_duration_unit'] = 'forever';
            $validated['access_duration_value'] = null;

            return;
        }

        $unit = PurchaseAccessDuration::normalizedUnit($validated['access_duration_unit'] ?? 'forever');
        $validated['access_duration_unit'] = $unit;
        $validated['access_duration_value'] = PurchaseAccessDuration::normalizedValue(
            $unit,
            $validated['access_duration_value'] ?? null
        );
    }

    private function filledReferences(array $references): array
    {
        return array_values(array_filter($references, fn ($reference) => trim((string) $reference) !== ''));
    }
}
