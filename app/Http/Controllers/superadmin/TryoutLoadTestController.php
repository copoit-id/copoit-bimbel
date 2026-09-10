<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\Tryout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TryoutLoadTestController extends Controller
{
    public function index(): View
    {
        $tryouts = Tryout::withoutGlobalScopes()->orderBy('name')->get(['tryout_id', 'name']);
        $batches = collect(File::directories(storage_path('app/load-tests')))
            ->map(fn (string $path): string => basename($path))
            ->filter(fn (string $batch): bool => File::exists(storage_path("app/load-tests/{$batch}/users.csv")))
            ->sort()
            ->values();

        return view('super-admin.load-test.index', compact('tryouts', 'batches'));
    }

    public function create(Request $request): RedirectResponse
    {
        $data = $request->validate(['tryout_id' => ['required', 'integer', 'exists:tryouts,tryout_id'], 'count' => ['required', 'integer', 'min:1', 'max:2000'], 'batch' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9-]*$/']]);
        $batch = $data['batch'] ?: 'loadtest-t'.$data['tryout_id'].'-'.now()->format('Ymd-His');

        if (Artisan::call('test:create-tryout-users', ['--count' => $data['count'], '--tryout' => $data['tryout_id'], '--package' => 'free', '--batch' => $batch, '--force' => true]) !== 0) {
            return back()->withInput()->withErrors(['batch' => trim(Artisan::output()) ?: 'Batch gagal dibuat.']);
        }

        return back()->with('success', "Batch {$batch} berhasil dibuat. Unduh CSV sebelum menjalankan k6.");
    }

    public function download(string $batch): BinaryFileResponse
    {
        $path = $this->csvPath($batch);
        abort_unless(File::exists($path), 404);

        return response()->download($path, 'users-'.$batch.'.csv', ['Content-Type' => 'text/csv']);
    }

    public function reset(Request $request, string $batch): RedirectResponse
    {
        $data = $request->validate(['tryout_id' => ['required', 'integer', 'exists:tryouts,tryout_id']]);
        if (Artisan::call('test:reset-tryout-attempts', ['--batch' => $this->batch($batch), '--tryout' => $data['tryout_id'], '--force' => true]) !== 0) {
            return back()->withErrors(['batch' => trim(Artisan::output()) ?: 'Percobaan gagal direset.']);
        }

        return back()->with('success', 'Pengerjaan tryout pada batch berhasil direset; akun dan CSV tetap tersedia.');
    }

    public function destroy(string $batch): RedirectResponse
    {
        if (Artisan::call('test:delete-tryout-users', ['--batch' => $this->batch($batch), '--force' => true]) !== 0) {
            return back()->withErrors(['batch' => trim(Artisan::output()) ?: 'Batch gagal dihapus.']);
        }

        return back()->with('success', 'Batch load test beserta akun dan hasilnya berhasil dihapus.');
    }

    private function csvPath(string $batch): string
    {
        return storage_path('app/load-tests/'.$this->batch($batch).'/users.csv');
    }

    private function batch(string $batch): string
    {
        abort_unless($batch === Str::slug($batch) && $batch !== '' && strlen($batch) <= 40, 404);

        return $batch;
    }
}
