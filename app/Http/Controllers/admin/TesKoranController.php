<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\TesKoran;
use App\Models\TesKoranResult;
use Illuminate\Http\Request;

class TesKoranController extends Controller
{
    public function index()
    {
        $tesKorans = TesKoran::orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.pages.tes-koran.index', compact('tesKorans'));
    }

    public function create()
    {
        return view('admin.pages.tes-koran.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'test_type' => 'required|in:pauli,kraepelin',
            'direction' => 'required|in:top_to_bottom,bottom_to_top',
            'duration_minutes' => 'required|integer|min:1|max:180',
            'columns_count' => 'required|integer|min:5|max:50',
            'rows_count' => 'required|integer|min:5|max:20',
        ]);

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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'test_type' => 'required|in:pauli,kraepelin',
            'direction' => 'required|in:top_to_bottom,bottom_to_top',
            'duration_minutes' => 'required|integer|min:1|max:180',
            'columns_count' => 'required|integer|min:5|max:50',
            'rows_count' => 'required|integer|min:5|max:20',
            'is_active' => 'boolean',
        ]);

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
}
