<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Tryout;
use App\Models\User;
use App\Models\UserTryoutAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AksesController extends Controller
{
    public function index()
    {
        $tryouts = Tryout::orderBy('tryout_id')->get();
        $now = Carbon::now();

        $accessCounts = UserTryoutAccess::selectRaw('tryout_id, count(*) as total')
            ->groupBy('tryout_id')
            ->pluck('total', 'tryout_id')
            ->all();

        $activeCounts = UserTryoutAccess::selectRaw('tryout_id, count(*) as total')
            ->where('status', 'active')
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', $now);
            })
            ->groupBy('tryout_id')
            ->pluck('total', 'tryout_id')
            ->all();

        $expiredCounts = UserTryoutAccess::selectRaw('tryout_id, count(*) as total')
            ->where(function ($query) use ($now) {
                $query->where('status', 'expired')
                    ->orWhere('end_date', '<', $now);
            })
            ->groupBy('tryout_id')
            ->pluck('total', 'tryout_id')
            ->all();

        $packages = $tryouts->map(function (Tryout $tryout) use ($accessCounts, $activeCounts, $expiredCounts) {
            $tryout->setAttribute('package_id', $tryout->tryout_id);
            $tryout->setAttribute('type_package', $tryout->type_tryout);
            $tryout->setAttribute('status', $tryout->is_active ? 'active' : 'inactive');
            $tryout->setAttribute('user_access_count', $accessCounts[$tryout->tryout_id] ?? 0);
            $tryout->setAttribute('active_users_count', $activeCounts[$tryout->tryout_id] ?? 0);
            $tryout->setAttribute('expired_users_count', $expiredCounts[$tryout->tryout_id] ?? 0);
            return $tryout;
        });

        $totalPackages = $packages->count();
        $totalUserAccess = UserTryoutAccess::count();
        $activeAccess = UserTryoutAccess::where('status', 'active')
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', $now);
            })
            ->count();
        $expiredAccess = UserTryoutAccess::where(function ($query) use ($now) {
            $query->where('status', 'expired')
                ->orWhere('end_date', '<', $now);
        })->count();

        $pendingRequests = collect();
        $pendingRequestCount = 0;

        return view('admin.pages.akses.index', compact(
            'packages',
            'totalPackages',
            'totalUserAccess',
            'activeAccess',
            'expiredAccess',
            'pendingRequests',
            'pendingRequestCount'
        ));
    }

    public function approveRequest(Request $request, $access)
    {
        return redirect()->route('admin.akses.index')
            ->with('error', 'Pengajuan akses paket tidak digunakan pada skema ini.');
    }

    public function rejectRequest(Request $request, $access)
    {
        return redirect()->route('admin.akses.index')
            ->with('error', 'Pengajuan akses paket tidak digunakan pada skema ini.');
    }

    public function show($package_id)
    {
        try {
            $package = Tryout::findOrFail($package_id);
            $package->setAttribute('package_id', $package->tryout_id);
            $package->setAttribute('type_package', $package->type_tryout);
            $package->setAttribute('status', $package->is_active ? 'active' : 'inactive');
            $package->setAttribute('price', 0);

            $userAccesses = UserTryoutAccess::where('tryout_id', $package_id)
                ->with('user') // Make sure to load the user relationship
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function (UserTryoutAccess $access) {
                    $access->setAttribute('user_package_access_id', $access->id);
                    $startDate = $access->start_date ?? optional($access->granted_at)->toDateString() ?? $access->created_at?->toDateString();
                    $endDate = $access->end_date ?? ($startDate ? Carbon::parse($startDate)->addDays(30)->toDateString() : Carbon::now()->addDays(30)->toDateString());
                    $access->start_date = $startDate;
                    $access->end_date = $endDate;
                    $access->status = $access->status ?? 'active';
                    return $access;
                });

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
            $package = Tryout::findOrFail($package_id);
            $successCount = 0;
            $errorUsers = [];

            foreach ($request->user_ids as $userId) {
                // Check if user already has access
                $existingAccess = UserTryoutAccess::where('tryout_id', $package_id)
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
                UserTryoutAccess::create([
                    'user_id' => $userId,
                    'tryout_id' => $package_id,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'status' => $status,
                    'payment_amount' => $request->payment_amount ?: 0,
                    'payment_status' => $request->payment_status,
                    'notes' => $request->notes,
                    'created_by' => Auth::id(),
                    'granted_by' => Auth::id(),
                    'granted_at' => now(),
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
            $package = Tryout::findOrFail($package_id);
            $package->setAttribute('package_id', $package->tryout_id);
            $user = User::findOrFail($user_id);

            $userAccess = UserTryoutAccess::where('tryout_id', $package_id)
                ->where('user_id', $user_id)
                ->firstOrFail();

            if (!$userAccess->start_date) {
                $userAccess->start_date = optional($userAccess->granted_at)->toDateString() ?? $userAccess->created_at?->toDateString() ?? Carbon::now()->toDateString();
            }
            if (!$userAccess->end_date) {
                $userAccess->end_date = Carbon::parse($userAccess->start_date)->addDays(30)->toDateString();
            }

            $userAccess->start_date = Carbon::parse($userAccess->start_date);
            $userAccess->end_date = Carbon::parse($userAccess->end_date);
            $userAccess->is_expired = $userAccess->end_date->isPast();
            $userAccess->days_remaining = (int) $userAccess->end_date->diffInDays(Carbon::now(), false);
            $userAccess->is_active = $userAccess->status === 'active' && !$userAccess->is_expired;

            // Update status berdasarkan tanggal saat ini
            if ($userAccess->end_date && Carbon::parse($userAccess->end_date)->isPast() && $userAccess->status === 'active') {
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
            $package = Tryout::findOrFail($package_id);
            $package->setAttribute('package_id', $package->tryout_id);
            $package->setAttribute('type_package', $package->type_tryout);
            $package->setAttribute('status', $package->is_active ? 'active' : 'inactive');
            $package->setAttribute('price', 0);

            // Get users yang belum memiliki akses ke tryout ini
            $availableUsers = User::whereNotIn('id', function ($query) use ($package_id) {
                $query->select('user_id')
                    ->from('user_tryout_accesses')
                    ->where('tryout_id', $package_id);
            })
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
            $userAccess = UserTryoutAccess::where('tryout_id', $package_id)
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
            $userAccess = UserTryoutAccess::where('tryout_id', $package_id)
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
            $userAccess = UserTryoutAccess::where('tryout_id', $package_id)
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

    public function bulkDestroy(Request $request, $package_id)
    {
        $validated = $request->validate([
            'access_ids' => ['required', 'array', 'min:1'],
            'access_ids.*' => ['integer'],
        ], [], [
            'access_ids' => 'Akses yang dipilih',
        ]);

        $deleted = UserTryoutAccess::where('tryout_id', $package_id)
            ->whereIn('id', $validated['access_ids'])
            ->delete();

        return redirect()
            ->route('admin.akses.show', $package_id)
            ->with('success', "Berhasil menghapus {$deleted} akses.");
    }
}
