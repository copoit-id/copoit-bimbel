<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tryout;
use App\Models\User;
use App\Models\UserPackageAcces;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AksesController extends Controller
{
    public function index()
    {
        // Ambil semua paket dengan statistik akses user
        $packages = Package::withCount([
            'userAccess',
            'userAccess as active_users_count' => function ($query) {
                $query->where('status', 'active')
                    ->where('end_date', '>', Carbon::now());
            },
            'userAccess as expired_users_count' => function ($query) {
                $query->where('status', 'expired')
                    ->orWhere('end_date', '<', Carbon::now());
            }
        ])->get();

        // Statistik total
        $totalPackages = $packages->count();
        $totalUserAccess = UserPackageAcces::count();
        $activeAccess = UserPackageAcces::where('status', 'active')
            ->where('end_date', '>', Carbon::now())
            ->count();
        $expiredAccess = UserPackageAcces::where('status', 'expired')
            ->orWhere('end_date', '<', Carbon::now())
            ->count();

        return view('admin.pages.akses.index', compact(
            'packages',
            'totalPackages',
            'totalUserAccess',
            'activeAccess',
            'expiredAccess',
        ));
    }

    public function show($package_id)
    {
        try {
            $package = Package::findOrFail($package_id);

            // Get user accesses for this package with user details
            $userAccesses = UserPackageAcces::where('package_id', $package_id)
                ->with('user') // Make sure to load the user relationship
                ->orderBy('created_at', 'desc')
                ->get();

            return view('admin.pages.akses.show', compact('package', 'userAccesses'));
        } catch (\Exception $e) {
            return redirect()->route('admin.akses.index')
                ->with('error', 'Package not found');
        }
    }

    public function store(Request $request, $package_id)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'payment_status' => 'required|in:paid,pending,failed,free',
            'payment_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $package = Package::findOrFail($package_id);
            $successCount = 0;
            $errorUsers = [];

            foreach ($request->user_ids as $userId) {
                // Check if user already has access
                $existingAccess = UserPackageAcces::where('package_id', $package_id)
                    ->where('user_id', $userId)
                    ->first();

                if ($existingAccess) {
                    $user = User::find($userId);
                    $errorUsers[] = $user->name;
                    continue;
                }

                // Tentukan status berdasarkan tanggal
                $endDate = Carbon::parse($request->end_date);
                $status = $endDate->isPast() ? 'expired' : 'active';

                // Create new access
                UserPackageAcces::create([
                    'user_id' => $userId,
                    'package_id' => $package_id,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'status' => $status,
                    'payment_amount' => $request->payment_amount ?: 0,
                    'payment_status' => $request->payment_status,
                    'notes' => $request->notes,
                    'created_by' => 1, // nanti diganti dengan auth()->id()
                ]);

                $successCount++;
            }

            $message = "Berhasil memberikan akses kepada {$successCount} user";
            if (!empty($errorUsers)) {
                $message .= ". User yang sudah memiliki akses: " . implode(', ', $errorUsers);
            }

            return redirect()->route('admin.akses.show', $package_id)
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memberikan akses: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function detail($package_id, $user_id)
    {
        try {
            $package = Package::findOrFail($package_id);
            $user = User::findOrFail($user_id);

            $userAccess = UserPackageAcces::where('package_id', $package_id)
                ->where('user_id', $user_id)
                ->firstOrFail();

            // Update status berdasarkan tanggal saat ini
            if ($userAccess->end_date->isPast() && $userAccess->status === 'active') {
                $userAccess->update(['status' => 'expired']);
            }

            // Simulasi aktivitas terbaru user (nanti bisa diganti dengan data real)
            $recentActivities = collect([
                (object)[
                    'activity' => 'Login ke sistem',
                    'time' => Carbon::now()->subHours(rand(1, 24)),
                    'type' => 'login',
                    'icon' => 'ri-login-circle-line',
                    'color' => 'text-green-600'
                ],
                (object)[
                    'activity' => 'Mengerjakan tryout ' . $package->name,
                    'time' => Carbon::now()->subHours(rand(1, 48)),
                    'type' => 'tryout',
                    'icon' => 'ri-file-text-line',
                    'color' => 'text-blue-600'
                ],
                (object)[
                    'activity' => 'Mengikuti kelas online',
                    'time' => Carbon::now()->subDays(rand(1, 7)),
                    'type' => 'class',
                    'icon' => 'ri-video-line',
                    'color' => 'text-purple-600'
                ],
                (object)[
                    'activity' => 'Download materi pembelajaran',
                    'time' => Carbon::now()->subDays(rand(1, 10)),
                    'type' => 'download',
                    'icon' => 'ri-download-line',
                    'color' => 'text-orange-600'
                ]
            ])->take(5);

            return view('admin.pages.akses.detail', compact(
                'package',
                'user',
                'userAccess',
                'recentActivities'
            ));
        } catch (\Exception $e) {
            return redirect()->route('admin.akses.show', $package_id)
                ->with('error', 'Data tidak ditemukan');
        }
    }

    public function create($package_id)
    {
        try {
            $package = Package::findOrFail($package_id);

            // Get users yang belum memiliki akses ke paket ini
            $availableUsers = User::whereNotIn('id', function ($query) use ($package_id) {
                $query->select('user_id')
                    ->from('user_package_access')
                    ->where('package_id', $package_id);
            })
                ->where('role', 'user')
                ->where('status', 'aktif')
                ->orderBy('name')
                ->get();

            return view('admin.pages.akses.create', compact('package', 'availableUsers'));
        } catch (\Exception $e) {
            return redirect()->route('admin.akses.index')
                ->with('error', 'Paket tidak ditemukan');
        }
    }

    public function extendAccess(Request $request, $package_id, $user_id)
    {
        $request->validate([
            'end_date' => 'required|date|after:today',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $userAccess = UserPackageAcces::where('package_id', $package_id)
                ->where('user_id', $user_id)
                ->firstOrFail();

            $userAccess->update([
                'end_date' => $request->end_date,
                'status' => 'active',
                'notes' => $request->notes ?: $userAccess->notes,
            ]);

            return redirect()->back()
                ->with('success', 'Akses berhasil diperpanjang');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperpanjang akses: ' . $e->getMessage());
        }
    }

    public function revokeAccess($package_id, $user_id)
    {
        try {
            $userAccess = UserPackageAcces::where('package_id', $package_id)
                ->where('user_id', $user_id)
                ->firstOrFail();

            $userAccess->update([
                'status' => 'suspended',
                'end_date' => Carbon::now(),
            ]);

            return redirect()->back()
                ->with('success', 'Akses berhasil dicabut');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mencabut akses: ' . $e->getMessage());
        }
    }

    public function toggleStatus($package_id, $user_id)
    {
        try {
            $userAccess = UserPackageAcces::where('package_id', $package_id)
                ->where('user_id', $user_id)
                ->firstOrFail();

            $newStatus = $userAccess->status === 'active' ? 'suspended' : 'active';

            $userAccess->update(['status' => $newStatus]);

            $message = $newStatus === 'active' ? 'Akses berhasil diaktifkan' : 'Akses berhasil disuspend';

            return redirect()->back()
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat mengubah status akses');
        }
    }
}
