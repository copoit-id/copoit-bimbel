<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tentor;
use App\Services\TentorAccountService;
use App\Services\TutorProfilePhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

        $roleOptions = $this->getRoleOptions();

        return view('admin.pages.tentor.index', compact('tentors', 'roleOptions'));
    }

    public function create(): View
    {
        return view('admin.pages.tentor.create', ['account' => null]);
    }

    public function store(Request $request, TentorAccountService $tentorAccountService): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $validated['is_active'] = $request->boolean('is_active', true);

        DB::transaction(function () use ($validated, $tentorAccountService): void {
            $tentor = Tentor::create($this->tentorPayload($validated));
            $tentorAccountService->sync($tentor, $validated);
        });

        return redirect()
            ->route('admin.tentors.index')
            ->with('success', 'Tutor berhasil ditambahkan.');
    }

    public function edit(Tentor $tentor): View
    {
        $tentor->load('user:id,name,email,username,status');

        return view('admin.pages.tentor.edit', [
            'tentor' => $tentor,
            'account' => $tentor->user,
        ]);
    }

    public function update(Request $request, Tentor $tentor, TentorAccountService $tentorAccountService): RedirectResponse
    {
        $validated = $this->validatedData($request, $tentor, true);
        $validated['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($tentor, $validated, $tentorAccountService): void {
            $tentor->update($this->tentorPayload($validated));
            $tentorAccountService->sync($tentor->fresh('user'), $validated);
        });

        return redirect()
            ->route('admin.tentors.index', $request->query())
            ->with('success', 'Tutor berhasil diperbarui.');
    }

    public function destroy(
        Tentor $tentor,
        TutorProfilePhotoService $photoService
    ): RedirectResponse {
        if ($tentor->user_id) {
            return back()->with('error', 'Tutor terhubung ke akun login. Ubah role akun tersebut dari Manajemen User terlebih dahulu.');
        }

        if ($tentor->classes()->exists()
            || $tentor->schedules()->exists()
            || $tentor->bookingRequests()->exists()) {
            return back()->with('error', 'Tutor masih dipakai di kelas, jadwal, atau booking. Nonaktifkan Tutor jika tidak digunakan lagi.');
        }

        $photoPath = $tentor->profile_photo_path;
        $tentor->delete();
        $photoService->delete($photoPath);

        return redirect()
            ->route('admin.tentors.index')
            ->with('success', 'Tutor berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Tentor $tentor = null, bool $isUpdate = false): array
    {
        $tentorId = $tentor?->id ?? 'NULL';
        $accountId = $tentor?->user_id ?? 'NULL';

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('tentors', 'email')->ignore($tentorId),
                Rule::unique('users', 'email')->ignore($accountId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'expertise' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($accountId),
            ],
            'password' => [$isUpdate && $tentor?->user_id ? 'nullable' : 'required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function tentorPayload(array $validated): array
    {
        return collect($validated)->only([
            'name',
            'email',
            'phone',
            'expertise',
            'bio',
            'is_active',
        ])->all();
    }

    private function getRoleOptions(): array
    {
        return Role::query()
            ->whereNotIn('slug', ['super_admin', 'tutor'])
            ->orderBy('name')
            ->pluck('name', 'slug')
            ->toArray();
    }
}
