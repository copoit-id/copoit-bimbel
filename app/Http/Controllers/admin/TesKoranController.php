<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\TesKoran;
use App\Models\TesKoranResult;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TesKoranController extends Controller
{
    public function index()
    {
        $packages = Package::where('type_package', 'tes_koran')
            ->where('status', 'active')
            ->with('tesKorans')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.pages.tes-koran.index', compact('packages'));
    }

    public function create()
    {
        $packages = Package::where('type_package', 'tes_koran')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.pages.tes-koran.create', compact('packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,package_id',
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
        $packages = Package::where('type_package', 'tes_koran')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.pages.tes-koran.edit', compact('tesKoran', 'packages'));
    }

    public function update(Request $request, TesKoran $tesKoran)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,package_id',
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

    public function createPackage()
    {
        return view('admin.pages.tes-koran.create-package');
    }

    public function storePackage(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'type_price' => 'required|in:paid,free_unconditional,free_conditional',
            'duration' => 'nullable|integer|min:1',
        ]);

        $package = Package::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type_package' => 'tes_koran',
            'type_price' => $validated['type_price'],
            'price' => $validated['type_price'] === 'paid' ? ($validated['price'] ?? 0) : 0,
            'duration' => $validated['duration'] ?? 30,
            'status' => 'active',
            'is_active' => true,
        ]);

        return redirect()->route('admin.tes-koran.create')
            ->with('success', 'Paket Tes Koran berhasil dibuat. Sekarang tambahkan tes-nya.')
            ->with('package_id', $package->package_id);
    }
}
