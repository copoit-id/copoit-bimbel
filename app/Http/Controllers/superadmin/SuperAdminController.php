<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    public function index()
    {
        $admins = User::where('role', 'admin_demo')
            ->orderByDesc('created_at')
            ->get();

        return view('super-admin.admins.index', compact('admins'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'username' => 'nullable|string|max:255|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
            'expiry_type' => 'required|in:date,duration',
            'expires_at' => 'nullable|date',
            'duration_days' => 'nullable|integer|min:0|max:365',
            'duration_hours' => 'nullable|integer|min:0|max:720',
        ]);

        $expiresAt = null;
        if ($request->expiry_type === 'date') {
            if (!$request->filled('expires_at')) {
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
        if (!$username) {
            $username = strtolower(str_replace(' ', '', $request->name));
        }
        $baseUsername = $username;
        $suffix = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $suffix;
            $suffix++;
        }

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $username,
            'password' => Hash::make($request->password),
            'role' => 'admin_demo',
            'admin_expires_at' => $expiresAt,
            'status' => 'aktif',
            'email_verified_at' => now(),
        ]);

        $role = \App\Models\Role::where('slug', 'admin_demo')->first();
        if ($role) {
            $admin->roles()->syncWithoutDetaching([$role->id]);
        }

        return redirect()->route('super-admin.admins.index')
            ->with('success', 'Akun admin demo berhasil dibuat.');
    }

    public function update(Request $request, User $admin)
    {
        if ($admin->role !== 'admin_demo') {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $admin->id,
            'username' => 'nullable|string|max:255|unique:users,username,' . $admin->id,
            'password' => 'nullable|string|min:8|confirmed',
            'expiry_type' => 'required|in:date,duration',
            'expires_at' => 'nullable|date',
            'duration_days' => 'nullable|integer|min:0|max:365',
            'duration_hours' => 'nullable|integer|min:0|max:720',
        ]);

        $expiresAt = null;
        if ($request->expiry_type === 'date') {
            if (!$request->filled('expires_at')) {
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

        $username = $request->input('username') ?: $admin->username;
        $baseUsername = $username;
        $suffix = 1;
        while (User::where('username', $username)->where('id', '!=', $admin->id)->exists()) {
            $username = $baseUsername . $suffix;
            $suffix++;
        }

        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->username = $username;
        $admin->admin_expires_at = $expiresAt;

        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }

        $admin->save();

        return redirect()->route('super-admin.admins.index')
            ->with('success', 'Admin demo berhasil diperbarui.');
    }
}
