<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\TesKoran;
use App\Models\TesKoranResult;
use App\Services\PurchaseAccessDuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TesKoranController extends Controller
{
    public function index()
    {
        $tesKorans = TesKoran::withCount('sheets')
            ->orderBy('created_at', 'desc')
            ->withCount('results')
            ->paginate(20);

        return view('admin.pages.tes-koran.index', compact('tesKorans'));
    }

    public function create()
    {
        return view('admin.pages.tes-koran.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        $sheets = $this->validatedSheets($request, $validated);
        $this->syncBaseSettingsFromFirstSheet($validated, $sheets);

        $validated['price'] = (int) ($validated['price'] ?? 0);
        $validated['is_for_sale'] = $request->boolean('is_for_sale') && $validated['price'] > 0;
        $validated['is_displayed'] = $request->boolean('is_displayed', true);
        $validated['direction'] = $this->directionForTestType($validated['test_type']);
        $validated['duration_minutes'] = $this->totalDurationMinutes($validated, $sheets);
        $this->normalizeAccessDuration($validated);

        DB::transaction(function () use ($validated, $sheets) {
            $tesKoran = TesKoran::create($validated);
            $this->syncSheets($tesKoran, $sheets);
        });

        return redirect()->route('admin.tes-koran.index')
            ->with('success', 'Tes Koran berhasil ditambahkan.');
    }

    public function edit(TesKoran $tesKoran)
    {
        $tesKoran->load('sheets');

        return view('admin.pages.tes-koran.edit', compact('tesKoran'));
    }

    public function update(Request $request, TesKoran $tesKoran)
    {
        $validated = $this->validatedData($request);
        $sheets = $this->validatedSheets($request, $validated);
        $this->syncBaseSettingsFromFirstSheet($validated, $sheets);

        $validated['price'] = (int) ($validated['price'] ?? 0);
        $validated['is_for_sale'] = $request->boolean('is_for_sale') && $validated['price'] > 0;
        $validated['is_displayed'] = $request->boolean('is_displayed');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['direction'] = $this->directionForTestType($validated['test_type']);
        $validated['duration_minutes'] = $this->totalDurationMinutes($validated, $sheets);
        $this->normalizeAccessDuration($validated);

        DB::transaction(function () use ($tesKoran, $validated, $sheets) {
            $tesKoran->update($validated);
            $this->syncSheets($tesKoran, $sheets);
        });

        return redirect()->route('admin.tes-koran.index', $request->query())
            ->with('success', 'Tes Koran berhasil diperbarui.');
    }

    public function destroy(TesKoran $tesKoran)
    {
        $tesKoran->delete();

        return redirect()->route('admin.tes-koran.index')
            ->with('success', 'Tes Koran berhasil dihapus.');
    }

    public function toggle(TesKoran $tesKoran)
    {
        $tesKoran->update(['is_active' => !$tesKoran->is_active]);

        $status = $tesKoran->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Tes Koran berhasil {$status}.");
    }

    public function results(TesKoran $tesKoran)
    {
        $results = TesKoranResult::where('tes_koran_id', $tesKoran->id)
            ->with('user')
            ->orderBy('total_correct', 'desc')
            ->paginate(20);

        $statistics = [
            'total_participants' => TesKoranResult::where('tes_koran_id', $tesKoran->id)->distinct('user_id')->count(),
            'avg_correct' => TesKoranResult::where('tes_koran_id', $tesKoran->id)->avg('total_correct') ?? 0,
            'avg_accuracy' => TesKoranResult::where('tes_koran_id', $tesKoran->id)->avg('accuracy_score') ?? 0,
            'high_count' => TesKoranResult::where('tes_koran_id', $tesKoran->id)->where('final_result', 'tinggi')->count(),
            'medium_count' => TesKoranResult::where('tes_koran_id', $tesKoran->id)->where('final_result', 'sedang')->count(),
            'low_count' => TesKoranResult::where('tes_koran_id', $tesKoran->id)->where('final_result', 'rendah')->count(),
        ];

        return view('admin.pages.tes-koran.results', compact('tesKoran', 'results', 'statistics'));
    }

    public function preview(TesKoran $tesKoran)
    {
        $tesKoran->load('sheets');
        $sheets = $tesKoran->sheetConfigs()
            ->map(function (array $sheet) use ($tesKoran) {
                $sheet['columns'] = $tesKoran->generateColumnsForSheet($sheet);
                return $sheet;
            });

        return view('admin.pages.tes-koran.preview', compact('tesKoran', 'sheets'));
    }

    public function export(TesKoran $tesKoran): StreamedResponse
    {
        $results = TesKoranResult::where('tes_koran_id', $tesKoran->id)
            ->with('user')
            ->orderBy('total_correct', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Hasil Tes Koran');

        $sheet->fromArray([
            'Rank',
            'Nama',
            'Email',
            'Benar',
            'Salah',
            'Kosong',
            'Kecepatan',
            'Akurasi',
            'Stabilitas',
            'Hasil',
            'Selesai',
        ], null, 'A1');

        foreach ($results as $index => $result) {
            $sheet->fromArray([
                $index + 1,
                $result->user->name ?? 'Unknown',
                $result->user->email ?? '',
                $result->total_correct,
                $result->total_wrong,
                $result->total_skipped,
                round($result->speed_score, 2),
                round($result->accuracy_score, 2) . '%',
                ucfirst((string) $result->stability_status),
                ucfirst((string) $result->final_result),
                $result->finished_at?->format('d/m/Y H:i') ?? '-',
            ], null, 'A' . ($index + 2));
        }

        $filename = 'hasil-tes-koran-' . $tesKoran->id . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'test_type' => 'required|in:pauli,kraepelin',
            'logic_test_type' => 'required|in:standar,stan',
            'number_type' => 'required|in:satuan,puluhan,ratusan',
            'operation_type' => 'required|in:addition,subtraction,division',
            'column_duration_seconds' => 'required|integer|min:10|max:3600',
            'columns_count' => 'required|integer|min:5|max:50',
            'rows_count' => 'required|integer|min:5|max:20',
            'price' => 'nullable|integer|min:0',
            'is_for_sale' => 'boolean',
            'is_displayed' => 'boolean',
            'is_active' => 'boolean',
            'access_duration_unit' => 'nullable|in:forever,day,week,month,year',
            'access_duration_value' => 'nullable|integer|min:1|max:1200',
        ]);
    }

    private function validatedSheets(Request $request, array $validated): array
    {
        $request->validate([
            'sheets' => 'nullable|array|min:1|max:10',
            'sheets.*.name' => 'nullable|string|max:100',
            'sheets.*.number_type' => 'required_with:sheets|in:satuan,puluhan,ratusan',
            'sheets.*.operation_type' => 'required_with:sheets|in:addition,subtraction,division',
            'sheets.*.column_duration_seconds' => 'required_with:sheets|integer|min:10|max:3600',
            'sheets.*.columns_count' => 'required_with:sheets|integer|min:1|max:50',
            'sheets.*.rows_count' => 'required_with:sheets|integer|min:5|max:20',
        ]);

        $sheetInputs = collect($request->input('sheets', []))->values();

        if ($sheetInputs->isEmpty()) {
            $sheetInputs = collect([[
                'name' => 'Lembar 1',
                'number_type' => $validated['number_type'],
                'operation_type' => $validated['operation_type'],
                'column_duration_seconds' => $validated['column_duration_seconds'],
                'columns_count' => $validated['columns_count'],
                'rows_count' => $validated['rows_count'],
            ]]);
        }

        return $sheetInputs->map(fn ($sheet, int $index) => [
            'sheet_order' => $index + 1,
            'name' => $sheet['name'] ?: 'Lembar ' . ($index + 1),
            'number_type' => $sheet['number_type'] ?? 'satuan',
            'operation_type' => $sheet['operation_type'] ?? 'addition',
            'column_duration_seconds' => (int) ($sheet['column_duration_seconds'] ?? 60),
            'columns_count' => (int) ($sheet['columns_count'] ?? 30),
            'rows_count' => (int) ($sheet['rows_count'] ?? 10),
        ])->all();
    }

    private function syncBaseSettingsFromFirstSheet(array &$validated, array $sheets): void
    {
        $firstSheet = $sheets[0] ?? null;

        if (!$firstSheet) {
            return;
        }

        $validated['number_type'] = $firstSheet['number_type'];
        $validated['operation_type'] = $firstSheet['operation_type'];
        if (($validated['logic_test_type'] ?? 'standar') !== 'stan') {
            $validated['column_duration_seconds'] = $firstSheet['column_duration_seconds'];
        }
        $validated['columns_count'] = $firstSheet['columns_count'];
        $validated['rows_count'] = $firstSheet['rows_count'];
    }

    private function syncSheets(TesKoran $tesKoran, array $sheets): void
    {
        $tesKoran->sheets()->delete();

        foreach ($sheets as $sheet) {
            $tesKoran->sheets()->create($sheet);
        }
    }

    private function normalizeAccessDuration(array &$validated): void
    {
        if (!($validated['is_for_sale'] ?? false) || (int) ($validated['price'] ?? 0) <= 0) {
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

    private function totalDurationMinutes(array $validated, array $sheets = []): int
    {
        $logicTestType = $validated['logic_test_type'] ?? 'standar';

        if ($logicTestType === 'stan') {
            return (int) ceil((int) ($validated['column_duration_seconds'] ?? 60) / 60);
        }

        $sheetConfigs = $sheets ?: [[
            'column_duration_seconds' => $validated['column_duration_seconds'],
            'columns_count' => $validated['columns_count'],
        ]];

        $totalSeconds = collect($sheetConfigs)->sum(
            fn ($sheet) => (int) $sheet['column_duration_seconds'] * (int) $sheet['columns_count']
        );

        return (int) ceil($totalSeconds / 60);
    }

    private function directionForTestType(string $testType): string
    {
        return $testType === 'kraepelin' ? 'bottom_to_top' : 'top_to_bottom';
    }
}
