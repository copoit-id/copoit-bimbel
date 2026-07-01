<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Tentor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TentorController extends Controller
{
    public function index(Request $request): View
    {
        $tentors = Tentor::query()
            ->withCount(['classes', 'schedules'])
            ->when($request->filled('search'), fn ($query) => $query->search((string) $request->input('search')))
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'active');
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pages.tentor.index', compact('tentors'));
    }

    public function create(): View
    {
        return view('admin.pages.tentor.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $validated['is_active'] = $request->boolean('is_active', true);

        Tentor::create($validated);

        return redirect()
            ->route('admin.tentors.index')
            ->with('success', 'Tentor berhasil ditambahkan.');
    }

    public function edit(Tentor $tentor): View
    {
        return view('admin.pages.tentor.edit', compact('tentor'));
    }

    public function update(Request $request, Tentor $tentor): RedirectResponse
    {
        $validated = $this->validatedData($request, $tentor);
        $validated['is_active'] = $request->boolean('is_active');

        $tentor->update($validated);

        return redirect()
            ->route('admin.tentors.index', $request->query())
            ->with('success', 'Tentor berhasil diperbarui.');
    }

    public function destroy(Tentor $tentor): RedirectResponse
    {
        if ($tentor->classes()->exists() || $tentor->schedules()->exists()) {
            return back()->with('error', 'Tentor masih dipakai di kelas atau jadwal. Nonaktifkan tentor jika tidak digunakan lagi.');
        }

        $tentor->delete();

        return redirect()
            ->route('admin.tentors.index')
            ->with('success', 'Tentor berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Tentor $tentor = null): array
    {
        $tentorId = $tentor?->id ?? 'NULL';

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:tentors,email,' . $tentorId],
            'phone' => ['nullable', 'string', 'max:30'],
            'expertise' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
