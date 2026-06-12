<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\IndividualPurchase;
use App\Models\Material;
use App\Models\Package;
use App\Models\TesKoran;
use App\Models\Tryout;
use App\Models\User;
use App\Models\UserMaterialAccess;
use App\Models\UserPackageAcces;
use App\Models\UserTryoutAccess;
use App\Services\PurchaseAccessDuration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AksesController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'packages');
        $canManageTesKoran = $request->user()?->hasPermission('tes_koran', 'view') ?? false;

        if ($tab === 'tes_koran' && !$canManageTesKoran) {
            $tab = 'packages';
        }
        
        // Get items based on tab
        $items = match($tab) {
            'packages' => Package::withCount([
                'userAccess',
                'userAccess as active_users_count' => fn($q) => $q->where('status', 'active')
                    ->where(fn($query) => $query->whereNull('end_date')->orWhere('end_date', '>', Carbon::now())),
                'userAccess as expired_users_count' => fn($q) => $q->where('status', 'expired')->orWhere('end_date', '<', Carbon::now()),
            ])->get(),
            'videos' => Material::where('type', 'video')->where('is_active', true)->withCount('userAccess')->get(),
            'documents' => Material::where('type', 'document')->where('is_active', true)->withCount('userAccess')->get(),
            'live' => Material::where('type', 'live_session')->where('is_active', true)->withCount('userAccess')->get(),
            'tryouts' => Tryout::where('is_active', true)->withCount('userAccess')->get(),
            'tes_koran' => TesKoran::where('is_active', true)
                ->withCount([
                    'individualPurchases as user_access_count' => fn($q) => $q->where('status', IndividualPurchase::STATUS_APPROVED),
                ])
                ->get(),
            default => collect(),
        };
        
        // Pending requests (only for packages)
        $pendingRequests = UserPackageAcces::where('requirement_status', 'pending')
            ->with(['user', 'package'])
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.pages.akses.index', compact(
            'tab', 'items', 'pendingRequests'
        ));
    }

    /**
     * Show access management for specific item
     */
    public function manage(Request $request)
    {
        $type = $request->get('type', 'package');
        $itemId = $request->get('item_id');
        $normalizedType = rtrim($type, 's');
        if ($normalizedType === 'live') $normalizedType = 'live_session';

        abort_if(
            $normalizedType === 'tes_koran' && !($request->user()?->hasPermission('tes_koran', 'view') ?? false),
            403
        );
        
        if (!$itemId) {
            return redirect()->route('admin.akses.index');
        }
        
        // Get item details
        $item = match($normalizedType) {
            'package' => Package::findOrFail($itemId),
            'video', 'document', 'live_session' => Material::findOrFail($itemId),
            'tryout' => Tryout::findOrFail($itemId),
            'tes_koran' => TesKoran::findOrFail($itemId),
            default => abort(404),
        };
        
        // Get users with access
        $usersWithAccess = match($normalizedType) {
            'package' => UserPackageAcces::where('package_id', $itemId)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get(),
            'video', 'document', 'live_session' => UserMaterialAccess::where('material_id', $itemId)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get(),
            'tryout' => UserTryoutAccess::where('tryout_id', $itemId)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get(),
            'tes_koran' => IndividualPurchase::where('purchasable_type', TesKoran::class)
                ->where('purchasable_id', $itemId)
                ->where('status', IndividualPurchase::STATUS_APPROVED)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get(),
            default => collect(),
        };
        
        // Get all users (with pagination and search)
        $search = $request->get('search');
        $usersQuery = User::where('role', 'user')
            ->where('status', 'aktif')
            ->when($search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name');
        
        $allUsers = $usersQuery->paginate(20)->withQueryString();
        
        // Mark users who already have access
        $accessUserIds = $usersWithAccess->pluck('user_id')->toArray();
        foreach ($allUsers as $user) {
            $user->has_access = in_array($user->id, $accessUserIds);
        }
        
        return view('admin.pages.akses.manage', compact(
            'type', 'item', 'usersWithAccess', 'allUsers', 'search'
        ));
    }

    /**
     * Grant access to user
     */
    public function grant(Request $request)
    {
        $request->validate([
            'type' => 'required|in:package,packages,video,videos,document,documents,live,live_session,tryout,tryouts,tes_koran',
            'item_id' => 'required|integer',
            'user_id' => 'required|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'access_type' => 'required|in:free,paid',
        ]);
        
        $type = $request->type;
        $itemId = $request->item_id;
        $userId = $request->user_id;
        
        // Normalize type
        $normalizedType = rtrim($type, 's');
        if ($normalizedType === 'live') $normalizedType = 'live_session';

        abort_if(
            $normalizedType === 'tes_koran' && !($request->user()?->hasPermission('tes_koran', 'update') ?? false),
            403
        );
        
        // Check if already has access
        $hasAccess = match($normalizedType) {
            'package' => UserPackageAcces::where('package_id', $itemId)->where('user_id', $userId)->exists(),
            'video', 'document', 'live_session' => UserMaterialAccess::where('material_id', $itemId)->where('user_id', $userId)->exists(),
            'tryout' => UserTryoutAccess::where('tryout_id', $itemId)->where('user_id', $userId)->exists(),
            'tes_koran' => IndividualPurchase::where('purchasable_type', TesKoran::class)
                ->where('purchasable_id', $itemId)
                ->where('user_id', $userId)
                ->where('status', IndividualPurchase::STATUS_APPROVED)
                ->exists(),
            default => true,
        };
        
        if ($hasAccess) {
            return response()->json(['success' => false, 'message' => 'User sudah memiliki akses']);
        }
        
        // Grant access
        match($normalizedType) {
            'package' => $this->grantPackageAccess($userId, $itemId, $request),
            'video', 'document', 'live_session' => $this->grantMaterialAccess($userId, $itemId, $request),
            'tryout' => $this->grantTryoutAccess($userId, $itemId, $request),
            'tes_koran' => $this->grantTesKoranAccess($userId, $itemId, $request),
            default => null,
        };
        
        return response()->json(['success' => true, 'message' => 'Akses berhasil diberikan']);
    }

    /**
     * Revoke access from user
     */
    public function revoke(Request $request)
    {
        $request->validate([
            'type' => 'required|in:package,packages,video,videos,document,documents,live,live_session,tryout,tryouts,tes_koran',
            'item_id' => 'required|integer',
            'user_id' => 'required|exists:users,id',
        ]);
        
        $type = $request->type;
        $itemId = $request->item_id;
        $userId = $request->user_id;
        
        // Normalize type
        $normalizedType = rtrim($type, 's');
        if ($normalizedType === 'live') $normalizedType = 'live_session';

        abort_if(
            $normalizedType === 'tes_koran' && !($request->user()?->hasPermission('tes_koran', 'delete') ?? false),
            403
        );
        
        match($normalizedType) {
            'package' => UserPackageAcces::where('package_id', $itemId)->where('user_id', $userId)->delete(),
            'video', 'document', 'live_session' => UserMaterialAccess::where('material_id', $itemId)->where('user_id', $userId)->delete(),
            'tryout' => UserTryoutAccess::where('tryout_id', $itemId)->where('user_id', $userId)->delete(),
            'tes_koran' => IndividualPurchase::where('purchasable_type', TesKoran::class)
                ->where('purchasable_id', $itemId)
                ->where('user_id', $userId)
                ->delete(),
            default => null,
        };
        
        return response()->json(['success' => true, 'message' => 'Akses berhasil dicabut']);
    }

    private function grantPackageAccess($userId, $packageId, $request)
    {
        $package = Package::findOrFail($packageId);
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : PurchaseAccessDuration::expiresAt($package, $startDate);
        
        UserPackageAcces::create([
            'user_id' => $userId,
            'package_id' => $packageId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $endDate && $endDate->isPast() ? 'expired' : 'active',
            'payment_amount' => $request->access_type === 'paid' ? 1 : 0,
            'payment_status' => $request->access_type === 'paid' ? 'paid' : 'free',
            'created_by' => Auth::id(),
        ]);
    }

    private function grantMaterialAccess($userId, $materialId, $request)
    {
        $material = Material::findOrFail($materialId);
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now();

        UserMaterialAccess::create([
            'user_id' => $userId,
            'material_id' => $materialId,
            'access_type' => $request->access_type,
            'access_source' => 'direct',
            'status' => 'in_progress',
            'started_at' => $startDate,
            'expires_at' => $request->end_date
                ? Carbon::parse($request->end_date)
                : PurchaseAccessDuration::expiresAt($material, $startDate),
        ]);
    }

    private function grantTryoutAccess($userId, $tryoutId, $request)
    {
        $tryout = Tryout::findOrFail($tryoutId);
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now();

        UserTryoutAccess::create([
            'user_id' => $userId,
            'tryout_id' => $tryoutId,
            'access_type' => $request->access_type,
            'access_source' => 'direct',
            'status' => 'not_started',
            'assigned_at' => $startDate,
            'expires_at' => $request->end_date
                ? Carbon::parse($request->end_date)
                : PurchaseAccessDuration::expiresAt($tryout, $startDate),
        ]);
    }

    private function grantTesKoranAccess($userId, $tesKoranId, $request)
    {
        $tesKoran = TesKoran::findOrFail($tesKoranId);
        $approvedAt = now();

        IndividualPurchase::create([
            'user_id' => $userId,
            'purchasable_type' => TesKoran::class,
            'purchasable_id' => $tesKoranId,
            'price' => $request->access_type === 'paid' ? ($tesKoran->price ?? 0) : 0,
            'admin_fee' => 0,
            'total_amount' => $request->access_type === 'paid' ? ($tesKoran->price ?? 0) : 0,
            'payment_method' => 'direct',
            'status' => IndividualPurchase::STATUS_APPROVED,
            'transaction_id' => 'DIRECT-TES-KORAN-' . $tesKoranId . '-' . $userId . '-' . time(),
            'approved_at' => $approvedAt,
            'access_expires_at' => $request->end_date
                ? Carbon::parse($request->end_date)
                : PurchaseAccessDuration::expiresAt($tesKoran, $approvedAt),
            'approved_by' => Auth::id(),
        ]);
    }

    // Legacy methods for backward compatibility
    public function show($package_id)
    {
        return redirect()->route('admin.akses.manage', ['type' => 'package', 'item_id' => $package_id]);
    }

    public function approveRequest(Request $request, UserPackageAcces $access)
    {
        if ($access->requirement_status !== 'pending') {
            return redirect()->back()->with('error', 'Pengajuan tidak tersedia atau sudah diproses.');
        }

        $access->loadMissing('package');
        $startDate = Carbon::now();

        $access->update([
            'start_date' => $startDate,
            'end_date' => $access->package
                ? PurchaseAccessDuration::expiresAt($access->package, $startDate)
                : $startDate->copy()->addDays(30),
            'status' => 'active',
            'payment_amount' => 0,
            'payment_status' => 'free',
            'requirement_status' => 'approved',
            'created_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Pengajuan akses berhasil disetujui.');
    }

    public function rejectRequest(Request $request, UserPackageAcces $access)
    {
        if ($access->requirement_status !== 'pending') {
            return redirect()->back()->with('error', 'Pengajuan tidak tersedia atau sudah diproses.');
        }

        $access->update([
            'status' => 'pending',
            'payment_status' => 'conditional',
            'requirement_status' => 'rejected',
            'requirement_review_notes' => $request->review_notes ?? 'Pengajuan ditolak oleh admin.',
        ]);

        return redirect()->back()->with('success', 'Pengajuan akses berhasil ditolak.');
    }
}
