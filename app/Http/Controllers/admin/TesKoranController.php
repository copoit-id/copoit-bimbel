<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\TesKoran;
use App\Models\TesKoranResult;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TesKoranController extends Controller
{
    public function index()
    {
        $tesKorans = TesKoran::orderBy('created_at', 'desc')
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

        $validated['price'] = (int) ($validated['price'] ?? 0);
        $validated['is_for_sale'] = $request->boolean('is_for_sale') && $validated['price'] > 0;
        $validated['is_displayed'] = $request->boolean('is_displayed', true);
        $validated['direction'] = $this->directionForTestType($validated['test_type']);
        $validated['duration_minutes'] = $this->totalDurationMinutes($validated);

        TesKoran::create($validated);

        return redirect()->route('admin.tes-koran.index')
            ->with('success', 'Tes Koran berhasil ditambahkan.');
    }

    public function edit(TesKoran $tesKoran)
    {
        return view('admin.pages.tes-koran.edit', compact('tesKoran'));
    }

    public function update(Request $request, TesKoran $tesKoran)
    {
        $validated = $this->validatedData($request);

        $validated['price'] = (int) ($validated['price'] ?? 0);
        $validated['is_for_sale'] = $request->boolean('is_for_sale') && $validated['price'] > 0;
        $validated['is_displayed'] = $request->boolean('is_displayed');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['direction'] = $this->directionForTestType($validated['test_type']);
        $validated['duration_minutes'] = $this->totalDurationMinutes($validated);

        $tesKoran->update($validated);

        return redirect()->route('admin.tes-koran.index')
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
        $columns = $tesKoran->generateColumns($tesKoran->columns_count);

        return view('admin.pages.tes-koran.preview', compact('tesKoran', 'columns'));
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
            'number_type' => 'required|in:satuan,puluhan,ratusan',
            'operation_type' => 'required|in:addition,subtraction,division',
            'column_duration_seconds' => 'required|integer|min:10|max:3600',
            'columns_count' => 'required|integer|min:5|max:50',
            'rows_count' => 'required|integer|min:5|max:20',
            'price' => 'nullable|integer|min:0',
            'is_for_sale' => 'boolean',
            'is_displayed' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }

    private function totalDurationMinutes(array $validated): int
    {
        return (int) ceil(($validated['column_duration_seconds'] * $validated['columns_count']) / 60);
    }

    private function directionForTestType(string $testType): string
    {
        return $testType === 'kraepelin' ? 'bottom_to_top' : 'top_to_bottom';
    }
}
