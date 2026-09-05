<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\IndividualPurchase;
use App\Models\Material;
use App\Models\Package;
use App\Models\StudyGroup;
use App\Models\TesKoran;
use App\Models\Tryout;
use App\Models\User;
use App\Models\UserClassAccess;
use App\Models\UserMaterialAccess;
use App\Models\UserPackageAcces;
use App\Models\UserTryoutAccess;
use App\Services\PlanModuleService;
use App\Services\PurchaseAccessDuration;
use App\Support\Pagination;
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
        $canUseStudyGroupAccess = $this->canUseStudyGroupAccess($request);

        if ($tab === 'tes_koran' && ! $canManageTesKoran) {
            $tab = 'packages';
        }

        // Get items based on tab
        $items = match ($tab) {
            'packages' => Package::withCount([
                'userAccess',
                'userAccess as active_users_count' => fn ($q) => $q->where('status', 'active')
                    ->where(fn ($query) => $query->whereNull('end_date')->orWhere('end_date', '>', Carbon::now())),
                'userAccess as expired_users_count' => fn ($q) => $q->where('status', 'expired')->orWhere('end_date', '<', Carbon::now()),
                'userAccess as pending_requests_count' => fn ($q) => $q->where('requirement_status', 'pending'),
            ])->get(),
            'videos' => Material::where('type', 'video')->where('is_active', true)->withCount('userAccess')->get(),
            'documents' => Material::where('type', 'document')->where('is_active', true)->withCount('userAccess')->get(),
            'live' => Material::where('type', 'live_session')->where('is_active', true)->withCount('userAccess')->get(),
            'classes' => ClassModel::withCount('userAccess')->orderBy('schedule_time', 'desc')->get(),
            'tryouts' => Tryout::where('is_active', true)->withCount('userAccess')->get(),
            'tes_koran' => TesKoran::where('is_active', true)
                ->withCount([
                    'individualPurchases as user_access_count' => fn ($q) => $q->where('status', IndividualPurchase::STATUS_APPROVED),
                ])
                ->get(),
            default => collect(),
        };

        $pendingRequestType = $tab === 'packages' ? 'package' : 'individual';
        $itemIds = $items->map(fn ($item) => $this->itemIdForTab($tab, $item))->filter()->values();

        if ($pendingRequestType === 'individual') {
            $pendingRequestCounts = $this->individualPendingCountsForTab($tab, $itemIds);

            foreach ($items as $item) {
                $item->pending_requests_count = $pendingRequestCounts->get($this->itemIdForTab($tab, $item), 0);
            }
        }

        return view('admin.pages.akses.index', compact(
            'tab',
            'items',
            'canUseStudyGroupAccess'
        ));
    }

    public function requests(Request $request)
    {
        $type = $request->get('type', 'packages');
        $itemId = $request->integer('item_id');
        $normalizedType = rtrim($type, 's');
        if ($normalizedType === 'live') {
            $normalizedType = 'live_session';
        }
        if ($normalizedType === 'classe') {
            $normalizedType = 'class';
        }

        abort_if(! $itemId, 404);
        abort_if(
            $normalizedType === 'tes_koran' && ! ($request->user()?->hasPermission('tes_koran', 'view') ?? false),
            403
        );

        $item = match ($normalizedType) {
            'package' => Package::findOrFail($itemId),
            'video', 'document', 'live_session' => Material::findOrFail($itemId),
            'classe', 'class' => ClassModel::findOrFail($itemId),
            'tryout' => Tryout::findOrFail($itemId),
            'tes_koran' => TesKoran::findOrFail($itemId),
            default => abort(404),
        };

        $pendingRequestType = $normalizedType === 'package' ? 'package' : 'individual';
        $pendingRequests = $pendingRequestType === 'package'
            ? $this->packagePendingRequests($itemId)
            : $this->individualPendingRequests($type, $itemId, collect([$itemId]));

        return view('admin.pages.akses.requests', compact(
            'type',
            'item',
            'itemId',
            'pendingRequestType',
            'pendingRequests'
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
        if ($normalizedType === 'live') {
            $normalizedType = 'live_session';
        }
        if ($normalizedType === 'classe') {
            $normalizedType = 'class';
        }

        abort_if(
            $normalizedType === 'tes_koran' && ! ($request->user()?->hasPermission('tes_koran', 'view') ?? false),
            403
        );

        if (! $itemId) {
            return redirect()->route('admin.akses.index');
        }

        // Get item details
        $item = match ($normalizedType) {
            'package' => Package::findOrFail($itemId),
            'video', 'document', 'live_session' => Material::findOrFail($itemId),
            'class' => ClassModel::findOrFail($itemId),
            'tryout' => Tryout::findOrFail($itemId),
            'tes_koran' => TesKoran::findOrFail($itemId),
            default => abort(404),
        };

        // Get users with access
        $usersWithAccess = match ($normalizedType) {
            'package' => UserPackageAcces::where('package_id', $itemId)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get(),
            'video', 'document', 'live_session' => UserMaterialAccess::where('material_id', $itemId)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get(),
            'class' => UserClassAccess::where('class_id', $itemId)
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
        $search = trim((string) $request->get('search', ''));
        $school = trim((string) $request->get('school', ''));
        $usersQuery = User::where('status', 'aktif')
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.mb_strtolower($search).'%';
                $query->where(function ($userQuery) use ($term): void {
                    $userQuery->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$term]);
                });
            })
            ->when($school !== '', function ($query) use ($school): void {
                $query->whereRaw('LOWER(origin_institution) LIKE ?', ['%'.mb_strtolower($school).'%']);
            })
            ->orderBy('name');

        $allUsers = $usersQuery->paginate(Pagination::perPage(20))->withQueryString();

        // Mark users who already have active access, while allowing expired access to be extended.
        $accessByUserId = $usersWithAccess->keyBy('user_id');
        foreach ($allUsers as $user) {
            $access = $accessByUserId->get($user->id);
            $user->access_status = $this->accessStatusFor($normalizedType, $access);
            $user->has_access = $user->access_status === 'active';
        }

        $canUseStudyGroupAccess = $this->canUseStudyGroupAccess($request);
        $studyGroups = $canUseStudyGroupAccess
            ? StudyGroup::query()
                ->where('is_active', true)
                ->with(['users' => function ($query) {
                    $query->where('users.status', 'aktif')->orderBy('users.name');
                }])
                ->orderBy('name')
                ->get()
            : collect();

        return view('admin.pages.akses.manage', compact(
            'type', 'item', 'usersWithAccess', 'allUsers', 'search', 'school', 'studyGroups', 'canUseStudyGroupAccess'
        ));
    }

    /**
     * Grant access to user
     */
    public function grant(Request $request)
    {
        $request->validate([
            'type' => 'required|in:package,packages,video,videos,document,documents,live,live_session,class,classes,tryout,tryouts,tes_koran',
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
        if ($normalizedType === 'live') {
            $normalizedType = 'live_session';
        }
        if ($normalizedType === 'classe') {
            $normalizedType = 'class';
        }

        abort_if(
            $normalizedType === 'tes_koran' && ! ($request->user()?->hasPermission('tes_koran', 'update') ?? false),
            403
        );

        // Check if already has access
        $hasAccess = $this->hasActiveAccess($normalizedType, (int) $userId, (int) $itemId);

        if ($hasAccess) {
            return response()->json(['success' => false, 'message' => 'User sudah memiliki akses']);
        }

        $this->grantAccessToUser($normalizedType, (int) $userId, (int) $itemId, $request);

        return response()->json(['success' => true, 'message' => 'Akses berhasil diberikan']);
    }

    public function grantStudyGroup(Request $request)
    {
        abort_unless($this->canUseStudyGroupAccess($request), 404);

        $request->validate([
            'type' => 'required|in:package,packages,video,videos,document,documents,live,live_session,class,classes,tryout,tryouts,tes_koran',
            'item_id' => 'required|integer',
            'study_group_id' => 'required|exists:study_groups,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'access_type' => 'required|in:free,paid',
        ]);

        $normalizedType = rtrim($request->type, 's');
        if ($normalizedType === 'live') {
            $normalizedType = 'live_session';
        }
        if ($normalizedType === 'classe') {
            $normalizedType = 'class';
        }

        abort_if(
            $normalizedType === 'tes_koran' && ! ($request->user()?->hasPermission('tes_koran', 'update') ?? false),
            403
        );

        $groupUserIds = StudyGroup::findOrFail($request->integer('study_group_id'))
            ->users()
            ->whereIn('users.id', $request->input('user_ids', []))
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $granted = 0;
        $skipped = 0;

        DB::transaction(function () use ($groupUserIds, $normalizedType, $request, &$granted, &$skipped) {
            foreach ($groupUserIds as $userId) {
                if ($this->hasActiveAccess($normalizedType, $userId, $request->integer('item_id'))) {
                    $skipped++;

                    continue;
                }

                $this->grantAccessToUser($normalizedType, $userId, $request->integer('item_id'), $request);
                $granted++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "{$granted} user berhasil diberi akses. {$skipped} user dilewati karena sudah punya akses.",
        ]);
    }

    private function hasActiveAccess(string $normalizedType, int $userId, int $itemId): bool
    {
        return match ($normalizedType) {
            'package' => UserPackageAcces::where('package_id', $itemId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', Carbon::now());
                })
                ->exists(),
            'video', 'document', 'live_session' => UserMaterialAccess::where('material_id', $itemId)
                ->where('user_id', $userId)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', Carbon::now());
                })
                ->exists(),
            'class' => UserClassAccess::where('class_id', $itemId)
                ->where('user_id', $userId)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', Carbon::now());
                })
                ->exists(),
            'tryout' => UserTryoutAccess::where('tryout_id', $itemId)
                ->where('user_id', $userId)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', Carbon::now());
                })
                ->exists(),
            'tes_koran' => IndividualPurchase::where('purchasable_type', TesKoran::class)
                ->where('purchasable_id', $itemId)
                ->where('user_id', $userId)
                ->where('status', IndividualPurchase::STATUS_APPROVED)
                ->exists(),
            default => true,
        };
    }

    private function canUseStudyGroupAccess(Request $request): bool
    {
        return app(PlanModuleService::class)->allows('study_group')
            && ($request->user()?->hasPermission('study_group', 'view') ?? false);
    }

    private function grantAccessToUser(string $normalizedType, int $userId, int $itemId, Request $request): void
    {
        match ($normalizedType) {
            'package' => $this->grantPackageAccess($userId, $itemId, $request),
            'video', 'document', 'live_session' => $this->grantMaterialAccess($userId, $itemId, $request),
            'class' => $this->grantClassAccess($userId, $itemId, $request),
            'tryout' => $this->grantTryoutAccess($userId, $itemId, $request),
            'tes_koran' => $this->grantTesKoranAccess($userId, $itemId, $request),
            default => null,
        };
    }

    /**
     * Revoke access from user
     */
    public function revoke(Request $request)
    {
        $request->validate([
            'type' => 'required|in:package,packages,video,videos,document,documents,live,live_session,class,classes,tryout,tryouts,tes_koran',
            'item_id' => 'required|integer',
            'user_id' => 'required|exists:users,id',
        ]);

        $type = $request->type;
        $itemId = $request->item_id;
        $userId = $request->user_id;

        // Normalize type
        $normalizedType = rtrim($type, 's');
        if ($normalizedType === 'live') {
            $normalizedType = 'live_session';
        }
        if ($normalizedType === 'classe') {
            $normalizedType = 'class';
        }

        abort_if(
            $normalizedType === 'tes_koran' && ! ($request->user()?->hasPermission('tes_koran', 'delete') ?? false),
            403
        );

        match ($normalizedType) {
            'package' => UserPackageAcces::where('package_id', $itemId)->where('user_id', $userId)->delete(),
            'video', 'document', 'live_session' => UserMaterialAccess::where('material_id', $itemId)->where('user_id', $userId)->delete(),
            'class' => UserClassAccess::where('class_id', $itemId)->where('user_id', $userId)->delete(),
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

        UserPackageAcces::updateOrCreate(
            [
                'user_id' => $userId,
                'package_id' => $packageId,
            ],
            [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $endDate && $endDate->isPast() ? 'expired' : 'active',
                'payment_amount' => $request->access_type === 'paid' ? 1 : 0,
                'payment_status' => $request->access_type === 'paid' ? 'paid' : 'free',
                'requirement_status' => 'none',
                'created_by' => Auth::id(),
            ]
        );
    }

    private function accessStatusFor(string $type, $access): string
    {
        if (! $access) {
            return 'none';
        }

        if ($type === 'package') {
            if ($access->status === 'active' && (is_null($access->end_date) || $access->end_date->isFuture())) {
                return 'active';
            }

            return 'expired';
        }

        if (in_array($type, ['video', 'document', 'live_session', 'class', 'tryout'], true)) {
            $expiresAt = $access->expires_at ?? null;

            if (is_null($expiresAt) || Carbon::parse($expiresAt)->isFuture()) {
                return 'active';
            }

            return 'expired';
        }

        return 'active';
    }

    private function grantClassAccess($userId, $classId, $request)
    {
        $class = ClassModel::findOrFail($classId);
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now();
        $accessType = $request->access_type === 'paid' ? 'purchased' : 'free';

        UserClassAccess::updateOrCreate(
            [
                'user_id' => $userId,
                'class_id' => $classId,
            ],
            [
                'access_type' => $accessType,
                'access_source' => 'direct',
                'status' => 'active',
                'started_at' => $startDate,
                'expires_at' => $request->end_date
                    ? Carbon::parse($request->end_date)
                    : PurchaseAccessDuration::expiresAt($class, $startDate),
            ]
        );
    }

    private function grantMaterialAccess($userId, $materialId, $request)
    {
        $material = Material::findOrFail($materialId);
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now();
        $accessType = $request->access_type === 'paid' ? 'purchased' : 'free';

        UserMaterialAccess::create([
            'user_id' => $userId,
            'material_id' => $materialId,
            'access_type' => $accessType,
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
        $accessType = $request->access_type === 'paid' ? 'purchased' : 'free';

        UserTryoutAccess::updateOrCreate(
            [
                'user_id' => $userId,
                'tryout_id' => $tryoutId,
            ],
            [
                'access_type' => $accessType,
                'access_source' => 'direct',
                'status' => 'not_started',
                'expires_at' => $request->end_date
                    ? Carbon::parse($request->end_date)
                    : PurchaseAccessDuration::expiresAt($tryout, $startDate),
            ]
        );
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
            'transaction_id' => 'DIRECT-TES-KORAN-'.$tesKoranId.'-'.$userId.'-'.time(),
            'approved_at' => $approvedAt,
            'access_expires_at' => $request->end_date
                ? Carbon::parse($request->end_date)
                : PurchaseAccessDuration::expiresAt($tesKoran, $approvedAt),
            'approved_by' => Auth::id(),
        ]);
    }

    private function packagePendingRequests(?int $packageId = null)
    {
        return UserPackageAcces::where('requirement_status', 'pending')
            ->when($packageId, fn ($query) => $query->where('package_id', $packageId))
            ->with([
                'user',
                'package',
                'bookingRequests' => fn ($query) => $query
                    ->with('tentor:id,name')
                    ->latest('created_at'),
            ])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    private function individualPendingRequests(string $tab, ?int $itemId = null, $itemIds = null)
    {
        $purchasableType = $this->individualPurchasableTypeForTab($tab);
        $itemIds = collect($itemIds)->filter()->values();

        if (! $purchasableType || (! $itemId && $itemIds->isEmpty())) {
            return collect();
        }

        return IndividualPurchase::with(['user', 'purchasable'])
            ->where('purchasable_type', $purchasableType)
            ->where('status', IndividualPurchase::STATUS_PENDING)
            ->when($itemId, fn ($query) => $query->where('purchasable_id', $itemId))
            ->when(! $itemId, fn ($query) => $query->whereIn('purchasable_id', $itemIds))
            ->orderBy('created_at', 'asc')
            ->get();
    }

    private function individualPendingCountsForTab(string $tab, $itemIds)
    {
        $purchasableType = $this->individualPurchasableTypeForTab($tab);
        $itemIds = collect($itemIds)->filter()->values();

        if (! $purchasableType || $itemIds->isEmpty()) {
            return collect();
        }

        return IndividualPurchase::where('purchasable_type', $purchasableType)
            ->where('status', IndividualPurchase::STATUS_PENDING)
            ->whereIn('purchasable_id', $itemIds)
            ->select('purchasable_id', DB::raw('count(*) as total'))
            ->groupBy('purchasable_id')
            ->pluck('total', 'purchasable_id');
    }

    private function individualPurchasableTypeForTab(string $tab): ?string
    {
        return match ($tab) {
            'video', 'videos', 'document', 'documents', 'live', 'live_session' => Material::class,
            'class', 'classes' => ClassModel::class,
            'tryout', 'tryouts' => Tryout::class,
            'tes_koran' => TesKoran::class,
            default => null,
        };
    }

    private function itemIdForTab(string $tab, $item): ?int
    {
        $itemId = $item->package_id ?? $item->material_id ?? $item->class_id ?? $item->tryout_id ?? $item->id ?? null;

        return $itemId ? (int) $itemId : null;
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
