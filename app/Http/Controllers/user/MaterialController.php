<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\IndividualPurchase;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MaterialProgressLog;
use App\Models\UserMaterialAccess;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaterialController extends Controller
{
    /**
     * Halaman utama materi - menampilkan SEMUA materi dengan status akses user
     * BISA diakses oleh GUEST
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $categoryId = $request->get('category');
        $search = trim((string) $request->get('search', ''));
        $sort = $request->get('sort', 'default');

        // Get categories with material count (only displayed materials)
        $categories = MaterialCategory::active()
            ->ordered()
            ->withCount(['materials' => function ($query) {
                $query->active()->where('is_displayed', true);
            }])
            ->get();

        // Get user's accessible material IDs (empty array for guest)
        $accessibleMaterialIds = $user ? $this->getUserAccessibleMaterialIds($user) : [];

        // Get ALL displayed materials with user's access status
        $materialsQuery = Material::active()
            ->where('is_displayed', true)
            ->with(['categories', 'packages'])
            ->with(['userAccess' => function ($query) use ($user) {
                if ($user) {
                    $query->where('user_id', $user->id);
                }
            }]);

        if ($categoryId) {
            $materialsQuery->byCategory($categoryId);
        }

        $this->applyMaterialFilters($materialsQuery, $search, $sort);

        $materials = $materialsQuery->paginate(12)->withQueryString();
        $pendingMaterialIds = $this->getPendingIndividualMaterialIds($user);

        // Mark each material with access status
        foreach ($materials as $material) {
            $material->has_access = $user && $material->canUserAccess($user->id);
            $material->is_pending_individual = in_array((int) $material->material_id, $pendingMaterialIds, true);
            $material->access_via_package = $material->packages->first();
        }

        // Get user's progress for owned materials (only for logged in user)
        $userProgress = $user ? UserMaterialAccess::byUser($user->id)
            ->with('material')
            ->latest()
            ->limit(5)
            ->get() : collect();

        // Stats
        $stats = [
            'total_accessible' => count($accessibleMaterialIds),
            'completed' => $user ? $userProgress->where('status', 'completed')->count() : 0,
            'in_progress' => $user ? $userProgress->where('status', 'in_progress')->count() : 0,
        ];

        return view('user.pages.material.new-index', compact(
            'categories',
            'materials',
            'userProgress',
            'stats',
            'accessibleMaterialIds',
            'categoryId',
            'search',
            'sort'
        ));
    }
    
    /**
     * List video materials - tampilkan SEMUA dengan status akses
     * BISA diakses oleh GUEST
     */
    public function videos(Request $request)
    {
        $user = Auth::user();
        $categoryId = $request->get('category');
        $search = trim((string) $request->get('search', ''));
        $sort = $request->get('sort', 'default');

        // Get categories for filter
        $categories = MaterialCategory::active()
            ->ordered()
            ->withCount(['materials' => function ($query) {
                $query->active()->where('is_displayed', true)->byType('video');
            }])
            ->get();

        $materialsQuery = Material::active()
            ->where('is_displayed', true)
            ->byType('video')
            ->with(['categories', 'packages', 'userAccess' => function ($query) use ($user) {
                if ($user) {
                    $query->where('user_id', $user->id);
                }
            }]);

        if ($categoryId) {
            $materialsQuery->byCategory($categoryId);
        }

        $this->applyMaterialFilters($materialsQuery, $search, $sort);

        $materials = $materialsQuery->paginate(12)->withQueryString();
        $pendingMaterialIds = $this->getPendingIndividualMaterialIds($user);

        foreach ($materials as $material) {
            $material->has_access = $user && $material->canUserAccess($user->id);
            $material->is_pending_individual = in_array((int) $material->material_id, $pendingMaterialIds, true);
            $material->access_via_package = $material->packages->first();
        }

        return view('user.pages.material.new-videos', compact('materials', 'categories', 'categoryId', 'search', 'sort'));
    }

    /**
     * List document/PDF materials
     */
    public function documents(Request $request)
    {
        $user = Auth::user();
        $categoryId = $request->get('category');
        $search = trim((string) $request->get('search', ''));
        $sort = $request->get('sort', 'default');

        // Get categories for filter
        $categories = MaterialCategory::active()
            ->ordered()
            ->withCount(['materials' => function ($query) {
                $query->active()->where('is_displayed', true)->byType('document');
            }])
            ->get();

        $materialsQuery = Material::active()
            ->where('is_displayed', true)
            ->byType('document')
            ->with(['categories', 'packages', 'userAccess' => function ($query) use ($user) {
                if ($user) {
                    $query->where('user_id', $user->id);
                }
            }]);

        if ($categoryId) {
            $materialsQuery->byCategory($categoryId);
        }

        $this->applyMaterialFilters($materialsQuery, $search, $sort);

        $materials = $materialsQuery->paginate(12)->withQueryString();
        $pendingMaterialIds = $this->getPendingIndividualMaterialIds($user);

        foreach ($materials as $material) {
            $material->has_access = $user && $material->canUserAccess($user->id);
            $material->is_pending_individual = in_array((int) $material->material_id, $pendingMaterialIds, true);
            $material->access_via_package = $material->packages->first();
        }

        return view('user.pages.material.new-documents', compact('materials', 'categories', 'categoryId', 'search', 'sort'));
    }

    /**
     * List live sessions
     */
    public function liveSessions(Request $request)
    {
        $user = Auth::user();
        $categoryId = $request->get('category');
        $search = trim((string) $request->get('search', ''));
        $sort = $request->get('sort', 'default');

        // Get categories for filter
        $categories = MaterialCategory::active()
            ->ordered()
            ->withCount(['materials' => function ($query) {
                $query->active()->where('is_displayed', true)->byType('live_session');
            }])
            ->get();

        $materialsQuery = Material::active()
            ->where('is_displayed', true)
            ->byType('live_session')
            ->with(['categories', 'packages', 'userAccess' => function ($query) use ($user) {
                if ($user) {
                    $query->where('user_id', $user->id);
                }
            }]);

        if ($categoryId) {
            $materialsQuery->byCategory($categoryId);
        }

        $this->applyMaterialFilters($materialsQuery, $search, $sort);

        $materials = $materialsQuery->paginate(12)->withQueryString();
        $pendingMaterialIds = $this->getPendingIndividualMaterialIds($user);

        foreach ($materials as $material) {
            $material->has_access = $user && $material->canUserAccess($user->id);
            $material->is_pending_individual = in_array((int) $material->material_id, $pendingMaterialIds, true);
            $material->access_via_package = $material->packages->first();
        }

        return view('user.pages.material.new-live-sessions', compact('materials', 'categories', 'categoryId', 'search', 'sort'));
    }

    /**
     * List materials by category
     * BISA diakses oleh GUEST
     */
    public function byCategory(Request $request, $categoryId)
    {
        $user = Auth::user();
        $category = MaterialCategory::active()->findOrFail($categoryId);
        $search = trim((string) $request->get('search', ''));
        $sort = $request->get('sort', 'default');

        // Get ALL displayed materials in this category for guest to browse
        $materialsQuery = Material::active()
            ->where('is_displayed', true)
            ->byCategory($categoryId)
            ->with(['categories', 'packages']);

        $this->applyMaterialFilters($materialsQuery, $search, $sort);

        $materials = $materialsQuery->paginate(12)->withQueryString();
        
        return view('user.pages.material.by-category', compact('category', 'materials', 'search', 'sort'));
    }
    
    /**
     * Detail materi & cek akses
     */
    public function show($materialId)
    {
        $user = Auth::user();
        $material = Material::active()
            ->with('categories')
            ->findOrFail($materialId);
        
        // Check access
        if (!$user->canAccessMaterial($materialId)) {
            ActivityLogger::log('material_access_denied', 'error', $user, [
                'material_id' => $materialId,
            ]);
            
            return redirect()->route('user.material.index')
                ->with('error', 'Anda tidak memiliki akses ke materi ini.');
        }
        
        // Get or create user access
        $userAccess = UserMaterialAccess::firstOrCreate(
            ['user_id' => $user->id, 'material_id' => $materialId],
            [
                'access_type' => 'subscription',
                'access_source' => 'direct',
                'status' => 'not_started',
            ]
        );

        $wasNotStarted = $userAccess->status === 'not_started';
        $userAccess->markAsStarted();
        $userAccess->refresh();

        if ($wasNotStarted) {
            MaterialProgressLog::create([
                'user_id' => $user->id,
                'material_id' => $materialId,
                'event_type' => 'started',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
        
        // Log view
        ActivityLogger::log('material_viewed', 'success', $user, [
            'material_id' => $materialId,
            'material_type' => $material->type,
        ]);
        
        // Get related materials
        $relatedMaterials = Material::active()
            ->where('material_id', '!=', $materialId)
            ->whereIn('material_id', $this->getUserAccessibleMaterialIds($user))
            ->whereHas('categories', function ($query) use ($material) {
                $query->whereIn('material_categories.category_id', $material->categories->pluck('category_id'));
            })
            ->limit(4)
            ->get();
        
        return view('user.pages.material.show', compact('material', 'userAccess', 'relatedMaterials'));
    }
    
    /**
     * Mulai belajar - catat log
     */
    public function start($materialId)
    {
        $user = Auth::user();
        $material = Material::active()->findOrFail($materialId);
        
        // Check access
        if (!$user->canAccessMaterial($materialId)) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }
        
        // Get or create user access
        $userAccess = UserMaterialAccess::firstOrCreate(
            ['user_id' => $user->id, 'material_id' => $materialId],
            [
                'access_type' => 'subscription',
                'access_source' => 'direct',
            ]
        );
        
        // Mark as started
        $userAccess->markAsStarted();
        
        // Log event
        MaterialProgressLog::create([
            'user_id' => $user->id,
            'material_id' => $materialId,
            'event_type' => 'started',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        
        ActivityLogger::log('material_started', 'success', $user, [
            'material_id' => $materialId,
        ]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Update progress (untuk video)
     */
    public function updateProgress(Request $request, $materialId)
    {
        $user = Auth::user();
        $material = Material::active()->findOrFail($materialId);
        
        // Check access
        if (!$user->canAccessMaterial($materialId)) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }
        
        $validated = $request->validate([
            'progress_seconds' => 'required|integer|min:0',
            'total_duration' => 'required|integer|min:1',
        ]);
        
        $percentage = min(100, round(($validated['progress_seconds'] / $validated['total_duration']) * 100));
        
        // Get or create user access
        $userAccess = UserMaterialAccess::firstOrCreate(
            ['user_id' => $user->id, 'material_id' => $materialId],
            [
                'access_type' => 'subscription',
                'access_source' => 'direct',
            ]
        );
        
        // Update progress
        $userAccess->updateProgress($percentage);
        
        // Log progress
        MaterialProgressLog::create([
            'user_id' => $user->id,
            'material_id' => $materialId,
            'event_type' => 'viewed',
            'progress_seconds' => $validated['progress_seconds'],
            'metadata' => [
                'total_duration' => $validated['total_duration'],
                'percentage' => $percentage,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        
        return response()->json([
            'success' => true,
            'progress_percentage' => $percentage,
            'status' => $userAccess->status,
        ]);
    }
    
    /**
     * Tandai selesai
     */
    public function complete($materialId)
    {
        $user = Auth::user();
        $material = Material::active()->findOrFail($materialId);
        
        // Check access
        if (!$user->canAccessMaterial($materialId)) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }
        
        // Get or create user access
        $userAccess = UserMaterialAccess::firstOrCreate(
            ['user_id' => $user->id, 'material_id' => $materialId],
            [
                'access_type' => 'subscription',
                'access_source' => 'direct',
            ]
        );
        
        // Mark as completed
        $userAccess->markAsCompleted();
        
        // Log event
        MaterialProgressLog::create([
            'user_id' => $user->id,
            'material_id' => $materialId,
            'event_type' => 'completed',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        
        ActivityLogger::log('material_completed', 'success', $user, [
            'material_id' => $materialId,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Materi berhasil diselesaikan!',
        ]);
    }
    
    /**
     * Get material IDs that user can access
     */
    private function getUserAccessibleMaterialIds($user): array
    {
        // Direct access
        $directAccessIds = $user->materialAccess()
            ->where('status', '!=', 'not_started')
            ->pluck('material_id')
            ->toArray();
        
        // Get user's active package IDs
        $activePackageIds = $user->userPackageAccess()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhere('end_date', '>', now());
            })
            ->pluck('package_id')
            ->toArray();
        
        // Access via package. Package assignment uses detail_packages.
        $packageAccessIds = \DB::table('detail_packages')
            ->whereIn('package_id', $activePackageIds)
            ->where('detailable_type', Material::class)
            ->pluck('detailable_id')
            ->toArray();
        
        return array_unique(array_merge($directAccessIds, $packageAccessIds));
    }

    private function getPendingIndividualMaterialIds($user): array
    {
        if (!$user) {
            return [];
        }

        return IndividualPurchase::query()
            ->where('user_id', $user->id)
            ->where('purchasable_type', Material::class)
            ->where('status', IndividualPurchase::STATUS_PENDING)
            ->pluck('purchasable_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function applyMaterialFilters($query, string $search, string $sort): void
    {
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        match ($sort) {
            'latest' => $query->orderBy('created_at', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            'name_asc' => $query->orderBy('title', 'asc'),
            'name_desc' => $query->orderBy('title', 'desc'),
            default => $query->ordered(),
        };
    }
}
