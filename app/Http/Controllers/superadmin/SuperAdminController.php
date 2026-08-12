<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Rules\SafeName;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'all');
        $status = in_array($status, ['all', 'active', 'expired'], true) ? $status : 'all';

        $sort = $request->input('sort', 'latest');
        $sortOptions = [
            'latest' => 'Terbaru ditambahkan',
            'oldest' => 'Terlama ditambahkan',
            'name_asc' => 'Nama A-Z',
            'name_desc' => 'Nama Z-A',
            'expiry_asc' => 'Masa berlaku terdekat',
            'expiry_desc' => 'Masa berlaku terjauh',
        ];
        $sort = array_key_exists($sort, $sortOptions) ? $sort : 'latest';

        $now = now();
        $baseQuery = User::query()
            ->select(['id', 'name', 'email', 'phone', 'username', 'role', 'admin_expires_at', 'created_at'])
            ->where('role', 'admin_demo')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });

        $counts = [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('admin_expires_at', '>', $now)->count(),
            'expired' => (clone $baseQuery)->where(function ($query) use ($now) {
                $query->whereNull('admin_expires_at')
                    ->orWhere('admin_expires_at', '<=', $now);
            })->count(),
        ];

        $admins = (clone $baseQuery)
            ->when($status === 'active', fn ($query) => $query->where('admin_expires_at', '>', $now))
            ->when($status === 'expired', function ($query) use ($now) {
                $query->where(function ($query) use ($now) {
                    $query->whereNull('admin_expires_at')
                        ->orWhere('admin_expires_at', '<=', $now);
                });
            })
            ->tap(function ($query) use ($sort) {
                match ($sort) {
                    'oldest' => $query->orderBy('created_at'),
                    'name_asc' => $query->orderBy('name'),
                    'name_desc' => $query->orderByDesc('name'),
                    'expiry_asc' => $query->orderBy('admin_expires_at'),
                    'expiry_desc' => $query->orderByDesc('admin_expires_at'),
                    default => $query->orderByDesc('created_at'),
                };
            })
            ->paginate(20)
            ->withQueryString();

        return view('super-admin.admins.index', compact('admins', 'counts', 'sortOptions', 'sort', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['phone' => $this->normalizeWhatsAppNumber($request->input('phone'))]);

        $request->validate([
            'name' => ['required', 'string', 'max:255', new SafeName],
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => ['required', 'string', 'regex:/^628[0-9]{7,13}$/'],
            'username' => 'nullable|string|max:255|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
            'expiry_type' => 'required|in:date,duration',
            'expires_at' => 'nullable|date',
            'duration_days' => 'nullable|integer|min:0|max:365',
            'duration_hours' => 'nullable|integer|min:0|max:720',
        ], [
            'phone.required' => 'Nomor WhatsApp peminta wajib diisi.',
            'phone.regex' => 'Masukkan nomor WhatsApp aktif, contoh 081234567890.',
        ]);

        $expiresAt = null;
        if ($request->expiry_type === 'date') {
            if (! $request->filled('expires_at')) {
                return back()->withErrors(['expires_at' => 'Tanggal berakhir wajib diisi.'])->withInput();
            }
            $expiresAt = Carbon::parse($request->expires_at, 'Asia/Jakarta');
        } else {
            $days = (int) $request->input('duration_days', 0);
            $hours = (int) $request->input('duration_hours', 0);
            if ($days <= 0 && $hours <= 0) {
                return back()->withErrors(['duration_days' => 'Isi durasi hari atau jam.'])->withInput();
            }
            $expiresAt = Carbon::now('Asia/Jakarta')->addDays($days)->addHours($hours);
        }

        $username = $request->input('username');
        if (! $username) {
            $username = strtolower(str_replace(' ', '', $request->name));
        }
        $baseUsername = $username;
        $suffix = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.$suffix;
            $suffix++;
        }

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $username,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'admin_demo',
            'admin_expires_at' => $expiresAt,
            'status' => 'aktif',
            'email_verified_at' => now(),
        ]);

        $role = Role::where('slug', 'admin_demo')->first();
        if ($role) {
            $admin->roles()->syncWithoutDetaching([$role->id]);
        }

        return redirect()->route('super-admin.admins.index')
            ->with('success', 'Akun admin demo berhasil dibuat.');
    }

    /**
     * Update the account details of an existing demo admin.
     *
     * Access expiry is deliberately handled by extend() so this action cannot
     * accidentally create an account or change the access period.
     */
    public function update(Request $request, User $admin): RedirectResponse
    {
        if ($admin->role !== 'admin_demo') {
            abort(404);
        }

        $request->merge(['phone' => $this->normalizeWhatsAppNumber($request->input('phone'))]);

        $request->validate([
            'name' => ['required', 'string', 'max:255', new SafeName],
            'email' => 'required|email|max:255|unique:users,email,'.$admin->id,
            'phone' => ['required', 'string', 'regex:/^628[0-9]{7,13}$/'],
            'username' => 'nullable|string|max:255|unique:users,username,'.$admin->id,
            'password' => 'nullable|string|min:8|confirmed',
        ], [
            'phone.required' => 'Nomor WhatsApp peminta wajib diisi.',
            'phone.regex' => 'Masukkan nomor WhatsApp aktif, contoh 081234567890.',
        ]);

        $username = $request->input('username') ?: $admin->username;

        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->phone = $request->phone;
        $admin->username = $username;

        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }

        $admin->save();

        return redirect()->route('super-admin.admins.index')
            ->with('success', 'Admin demo berhasil diperbarui.');
    }

    /**
     * Extend only the access period of an existing demo admin.
     */
    public function extend(Request $request, User $admin): RedirectResponse
    {
        if ($admin->role !== 'admin_demo') {
            abort(404);
        }

        $request->validate([
            'expiry_type' => 'required|in:date,duration',
            'expires_at' => 'nullable|date',
            'duration_days' => 'nullable|integer|min:0|max:365',
            'duration_hours' => 'nullable|integer|min:0|max:720',
        ]);

        if ($request->expiry_type === 'date') {
            if (! $request->filled('expires_at')) {
                return back()->withErrors(['expires_at' => 'Tanggal berakhir wajib diisi.'])->withInput();
            }

            $expiresAt = Carbon::parse($request->expires_at, 'Asia/Jakarta');
            if ($expiresAt->lte(Carbon::now('Asia/Jakarta'))) {
                return back()->withErrors(['expires_at' => 'Tanggal berakhir harus di masa depan.'])->withInput();
            }
        } else {
            $days = (int) $request->input('duration_days', 0);
            $hours = (int) $request->input('duration_hours', 0);
            if ($days <= 0 && $hours <= 0) {
                return back()->withErrors(['duration_days' => 'Isi durasi hari atau jam.'])->withInput();
            }

            $now = Carbon::now('Asia/Jakarta');
            $baseExpiry = $admin->admin_expires_at?->copy()->setTimezone('Asia/Jakarta');
            $expiresAt = ($baseExpiry && $baseExpiry->gt($now) ? $baseExpiry : $now)
                ->addDays($days)
                ->addHours($hours);
        }

        $admin->update(['admin_expires_at' => $expiresAt]);

        return redirect()->route('super-admin.admins.index')
            ->with('success', 'Masa akses admin demo berhasil diperpanjang.');
    }

    private function normalizeWhatsAppNumber(?string $phone): string
    {
        $normalized = preg_replace('/\D+/', '', (string) $phone) ?: '';

        if (str_starts_with($normalized, '0')) {
            return '62'.substr($normalized, 1);
        }

        return str_starts_with($normalized, '8') ? '62'.$normalized : $normalized;
    }
}
