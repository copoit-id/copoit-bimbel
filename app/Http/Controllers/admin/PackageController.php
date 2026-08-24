<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\DetailPackage;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Package;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Tentor;
use App\Models\TesKoran;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Services\PlanModuleService;
use App\Services\PlanQuotaService;
use App\Services\PurchaseAccessDuration;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

Carbon::setLocale('id');
class PackageController extends Controller
{
    public function index(): View
    {
        $packages = Package::query()
            ->with('bookingRule:id,package_id,is_enabled')
            ->withCount(['schedules', 'classes', 'tryouts', 'materials'])
            ->latest('package_id')
            ->paginate(\App\Support\Pagination::perPage(12));

        return view('admin.pages.package.index', compact('packages'));
    }

    public function create()
    {
        // Cek quota package - backend validation
        $quotaCheck = PlanQuotaService::canCreatePackage();

        if (! $quotaCheck['allowed']) {
            return redirect()->route('admin.package.index')
                ->with('error', $quotaCheck['reason']);
        }

        $classes = ClassModel::all();
        $claimTryouts = Tryout::query()->orderBy('name')->get(['tryout_id', 'name', 'is_active']);

        return view('admin.pages.package.create', compact('classes', 'claimTryouts'));
    }

    public function store(Request $request)
    {
        try {
            // Cek quota package - backend validation (hindari bypass HTML)
            $quotaCheck = PlanQuotaService::canCreatePackage();

            if (! $quotaCheck['allowed']) {
                return redirect()->route('admin.package.index')
                    ->with('error', $quotaCheck['reason']);
            }

            $allowVideoThumbnail = config('client.branding.allow_video_thumbnail', false);

            $packageTypes = $this->availablePackageTypes();

            $validationRules = [
                'name' => 'required|string|max:255',
                'type_package' => 'required|in:'.implode(',', $packageTypes),
                'type_price' => 'required|in:paid,free_unconditional,free_conditional',
                'status' => 'required|in:active,inactive',
                'is_displayed' => 'boolean',
                'description' => 'nullable|string',
                'telegram_group_url' => 'nullable|url|max:255',
                'features' => 'nullable|array',
                'conditional_requirement' => 'nullable|string',
                'free_claim_requirement_type' => 'nullable|in:manual_proof,completed_tryout',
                'free_claim_tryout_id' => 'nullable|integer|exists:tryouts,tryout_id',
                'access_duration_unit' => 'required|in:forever,day,week,month,year',
                'access_duration_value' => 'nullable|integer|min:1|max:1200',
            ];

            $thumbnailRule = $allowVideoThumbnail
                ? 'nullable|file|mimetypes:image/jpeg,image/png,image/jpg,image/webp,video/mp4,video/quicktime,video/webm|max:51200'
                : 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048';

            $validationRules['image'] = $thumbnailRule;

            // Add price validation only if type_price is 'paid'
            if ($request->type_price === 'paid') {
                $validationRules['price'] = 'required|integer|min:1';
            } else {
                $validationRules['price'] = 'nullable|integer|min:0';
                if ($request->type_price === 'free_conditional') {
                    $validationRules['free_claim_requirement_type'] = 'required|in:manual_proof,completed_tryout';
                    $validationRules['conditional_requirement'] = $request->input('free_claim_requirement_type') === 'manual_proof'
                        ? 'required|string'
                        : 'nullable|string';
                    $validationRules['free_claim_tryout_id'] = $request->input('free_claim_requirement_type') === 'completed_tryout'
                        ? 'required|integer|exists:tryouts,tryout_id'
                        : 'nullable|integer|exists:tryouts,tryout_id';
                }
            }

            $validated = $request->validate($validationRules);
            $validated['is_displayed'] = $request->boolean('is_displayed', true);
            $this->normalizeAccessDuration($validated);

            if ($request->type_price !== 'paid') {
                $validated['price'] = 0;
            }

            $features = collect($validated['features'] ?? [])
                ->map(fn ($feature) => trim((string) $feature))
                ->filter()
                ->values()
                ->all();

            $validated['features'] = ! empty($features) ? json_encode($features) : null;

            $isTryoutClaim = $request->type_price === 'free_conditional'
                && ($validated['free_claim_requirement_type'] ?? null) === 'completed_tryout';
            $validated['conditional_requirement'] = $request->type_price === 'free_conditional' && ! $isTryoutClaim
                ? $validated['conditional_requirement']
                : null;
            $validated['free_claim_requirement_type'] = $request->type_price === 'free_conditional'
                ? ($validated['free_claim_requirement_type'] ?? 'manual_proof')
                : null;
            $validated['free_claim_tryout_id'] = $isTryoutClaim
                ? $validated['free_claim_tryout_id']
                : null;

            if ($request->hasFile('image')) {
                $validated['image'] = $request->file('image')->store('packages', 'public');
            }

            Package::create($validated);

            return redirect()->route('admin.package.index')->with('success', 'Paket berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan paket: '.$e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        try {
            $package = Package::findOrFail($id);
            $classes = ClassModel::all();
            $claimTryouts = Tryout::query()->orderBy('name')->get(['tryout_id', 'name', 'is_active']);

            return view('admin.pages.package.create', compact('package', 'classes', 'claimTryouts'));
        } catch (\Exception $e) {
            return redirect()->route('admin.package.index')
                ->with('error', 'Paket tidak ditemukan');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $allowVideoThumbnail = config('client.branding.allow_video_thumbnail', false);

            $package = Package::findOrFail($id);

            $packageTypes = $this->availablePackageTypes($package);

            $validationRules = [
                'name' => 'required|string|max:255',
                'type_package' => 'required|in:'.implode(',', $packageTypes),
                'type_price' => 'required|in:paid,free_unconditional,free_conditional',
                'status' => 'required|in:active,inactive',
                'is_displayed' => 'boolean',
                'description' => 'nullable|string',
                'telegram_group_url' => 'nullable|url|max:255',
                'features' => 'nullable|array',
                'conditional_requirement' => 'nullable|string',
                'free_claim_requirement_type' => 'nullable|in:manual_proof,completed_tryout',
                'free_claim_tryout_id' => 'nullable|integer|exists:tryouts,tryout_id',
                'access_duration_unit' => 'required|in:forever,day,week,month,year',
                'access_duration_value' => 'nullable|integer|min:1|max:1200',
            ];

            $thumbnailRule = $allowVideoThumbnail
                ? 'nullable|file|mimetypes:image/jpeg,image/png,image/jpg,image/webp,video/mp4,video/quicktime,video/webm|max:51200'
                : 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048';

            $validationRules['image'] = $thumbnailRule;

            // Add price validation only if type_price is 'paid'
            if ($request->type_price === 'paid') {
                $validationRules['price'] = 'required|integer|min:1';
            } else {
                $validationRules['price'] = 'nullable|integer|min:0';
                if ($request->type_price === 'free_conditional') {
                    $validationRules['free_claim_requirement_type'] = 'required|in:manual_proof,completed_tryout';
                    $validationRules['conditional_requirement'] = $request->input('free_claim_requirement_type') === 'manual_proof'
                        ? 'required|string'
                        : 'nullable|string';
                    $validationRules['free_claim_tryout_id'] = $request->input('free_claim_requirement_type') === 'completed_tryout'
                        ? 'required|integer|exists:tryouts,tryout_id'
                        : 'nullable|integer|exists:tryouts,tryout_id';
                }
            }

            $validated = $request->validate($validationRules);
            $validated['is_displayed'] = $request->boolean('is_displayed');
            $this->normalizeAccessDuration($validated);

            if ($request->type_price !== 'paid') {
                $validated['price'] = 0;
            }

            $features = collect($validated['features'] ?? [])
                ->map(fn ($feature) => trim((string) $feature))
                ->filter()
                ->values()
                ->all();

            $validated['features'] = ! empty($features) ? json_encode($features) : null;

            $isTryoutClaim = $request->type_price === 'free_conditional'
                && ($validated['free_claim_requirement_type'] ?? null) === 'completed_tryout';
            $validated['conditional_requirement'] = $request->type_price === 'free_conditional' && ! $isTryoutClaim
                ? $validated['conditional_requirement']
                : null;
            $validated['free_claim_requirement_type'] = $request->type_price === 'free_conditional'
                ? ($validated['free_claim_requirement_type'] ?? 'manual_proof')
                : null;
            $validated['free_claim_tryout_id'] = $isTryoutClaim
                ? $validated['free_claim_tryout_id']
                : null;

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($package->image && \Storage::disk('public')->exists($package->image)) {
                    \Storage::disk('public')->delete($package->image);
                }
                $validated['image'] = $request->file('image')->store('packages', 'public');
            }

            $package->update($validated);

            return redirect()->route('admin.package.index', $request->query())
                ->with('success', 'Paket berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui paket: '.$e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $package = Package::findOrFail($id);
            $package->delete();

            return redirect()->route('admin.package.index')
                ->with('success', 'Paket berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus paket: '.$e->getMessage());
        }
    }

    private function normalizeAccessDuration(array &$validated): void
    {
        $unit = PurchaseAccessDuration::normalizedUnit($validated['access_duration_unit'] ?? 'forever');

        $validated['access_duration_unit'] = $unit;
        $validated['access_duration_value'] = PurchaseAccessDuration::normalizedValue(
            $unit,
            $validated['access_duration_value'] ?? null
        );
    }

    /**
     * @return array<int, string>
     */
    private function availablePackageTypes(?Package $package = null): array
    {
        $planModules = app(PlanModuleService::class);
        $types = ['bimbel'];

        if ($planModules->allows('tryout')) {
            $types[] = 'tryout';
        }

        if ($planModules->allows('certification')) {
            $types[] = 'sertifikasi';
        }

        if ($planModules->allows('tes_koran')
            && (auth()->user()?->hasPermission('tes_koran', 'view') ?? false)) {
            $types[] = 'tes_koran';
        }

        if ($package?->type_package) {
            $types[] = $package->type_package;
        }

        return array_values(array_unique($types));
    }

    public function indexClass($package_id)
    {
        try {
            $package = Package::where('package_id', $package_id)->firstOrFail();

            // Get all classes with their package relationship status
            $classes = ClassModel::with(['tentor', 'detailPackages' => function ($query) use ($package_id) {
                $query->where('package_id', $package_id);
            }])
                ->orderByRaw('(SELECT COUNT(*) FROM detail_packages WHERE detailable_type = ? AND detailable_id = classes.class_id AND package_id = ?) DESC', [ClassModel::class, $package_id])
                ->orderBy('schedule_time', 'desc')
                ->paginate(\App\Support\Pagination::perPage(10));

            $selectedClassCount = DetailPackage::where('package_id', $package_id)
                ->where('detailable_type', ClassModel::class)
                ->count();

            return view('admin.pages.package.class.index', compact('package', 'classes', 'selectedClassCount'));
        } catch (\Exception $e) {
            return redirect()->route('admin.package.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    public function createClass($package_id)
    {
        try {
            $package = Package::where('package_id', $package_id)->first();
            $tentors = Tentor::active()->orderBy('name')->get(['id', 'name', 'expertise']);

            return view('admin.pages.package.class.create', compact('package', 'tentors'));
        } catch (\Exception $e) {
            return redirect()->route('admin.package.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    public function storeClass(Request $request, $package_id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'schedule_time' => 'required|date',
            'zoom_link' => 'nullable|url',
            'drive_link' => 'nullable|url',
            'tentor_id' => 'nullable|exists:tentors,id',
            'mentor' => 'nullable|string|max:255',
            'status' => 'required|in:upcoming,completed,cancelled',
            'price' => 'nullable|integer|min:0',
            'is_for_sale' => 'nullable|boolean',
            'is_displayed' => 'nullable|boolean',
            'type_price' => 'nullable|in:paid,free_unconditional,free_conditional',
            'conditional_requirement' => 'nullable|string',
            'access_duration_unit' => 'nullable|in:forever,day,week,month,year',
            'access_duration_value' => 'nullable|integer|min:1',
        ]);

        try {
            $tentor = $request->filled('tentor_id') ? Tentor::find($request->integer('tentor_id')) : null;
            $class = ClassModel::create([
                'package_id' => $package_id,
                'title' => $request->title,
                'schedule_time' => $request->schedule_time,
                'zoom_link' => $request->zoom_link,
                'drive_link' => $request->drive_link,
                'tentor_id' => $tentor?->id,
                'mentor' => $tentor?->name ?: $request->mentor,
                'status' => $request->status,
                'price' => $request->integer('price'),
                'is_for_sale' => $request->boolean('is_for_sale'),
                'is_displayed' => $request->boolean('is_displayed', true),
                'type_price' => $request->input('type_price', 'paid'),
                'conditional_requirement' => $request->input('conditional_requirement'),
                'access_duration_unit' => PurchaseAccessDuration::normalizedUnit($request->input('access_duration_unit')),
                'access_duration_value' => PurchaseAccessDuration::normalizedValue($request->input('access_duration_unit'), $request->input('access_duration_value')),
            ]);

            return redirect()->route('admin.package.class.index', $package_id)
                ->with('success', 'Kelas "'.$class->title.'" berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan kelas: '.$e->getMessage())
                ->withInput();
        }
    }

    public function indexMaterial($package_id)
    {
        try {
            $package = Package::where('package_id', $package_id)->firstOrFail();

            // Get all materials with their package relationship status
            $materials = Material::with(['detailPackages' => function ($query) use ($package_id) {
                $query->where('package_id', $package_id);
            }])
                ->orderByRaw('(SELECT COUNT(*) FROM detail_packages WHERE detailable_type = ? AND detailable_id = materials.material_id AND package_id = ?) DESC', [Material::class, $package_id])
                ->orderBy('created_at', 'desc')
                ->paginate(\App\Support\Pagination::perPage(15));

            $selectedMaterialCount = DetailPackage::where('package_id', $package_id)
                ->where('detailable_type', Material::class)
                ->count();

            return view('admin.pages.package.material.index', compact('package', 'materials', 'selectedMaterialCount'));
        } catch (\Exception $e) {
            return redirect()->route('admin.package.index')
                ->with('error', 'Paket tidak ditemukan');
        }
    }

    public function toggleMaterial(Request $request, $package_id, $material_id)
    {
        try {
            $package = Package::where('package_id', $package_id)->firstOrFail();
            $material = Material::findOrFail($material_id);

            // Check if already linked
            $existing = DetailPackage::where('package_id', $package_id)
                ->where('detailable_type', Material::class)
                ->where('detailable_id', $material_id)
                ->first();

            if ($request->has('selected')) {
                $shouldBeSelected = $request->boolean('selected');

                if ($shouldBeSelected && ! $existing) {
                    DetailPackage::create([
                        'package_id' => $package_id,
                        'detailable_type' => Material::class,
                        'detailable_id' => $material_id,
                    ]);
                    $message = 'Materi berhasil ditambahkan ke paket';
                } elseif (! $shouldBeSelected && $existing) {
                    $existing->delete();
                    $message = 'Materi berhasil dihapus dari paket';
                } else {
                    $message = $shouldBeSelected
                        ? 'Materi sudah ada di paket'
                        : 'Materi sudah tidak ada di paket';
                }
            } elseif ($existing) {
                $existing->delete();
                $message = 'Materi berhasil dihapus dari paket';
            } else {
                DetailPackage::create([
                    'package_id' => $package_id,
                    'detailable_type' => Material::class,
                    'detailable_id' => $material_id,
                ]);
                $message = 'Materi berhasil ditambahkan ke paket';
            }

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: '.$e->getMessage()], 500);
        }
    }

    public function indexTesKoran($package_id)
    {
        abort_unless(auth()->user()?->hasPermission('tes_koran', 'view'), 403);

        try {
            $package = Package::where('package_id', $package_id)->firstOrFail();

            $tesKorans = TesKoran::with(['detailPackages' => function ($query) use ($package_id) {
                $query->where('package_id', $package_id);
            }])
                ->orderByRaw('(SELECT COUNT(*) FROM detail_packages WHERE detailable_type = ? AND detailable_id = tes_korans.id AND package_id = ?) DESC', [TesKoran::class, $package_id])
                ->orderBy('created_at', 'desc')
                ->paginate(\App\Support\Pagination::perPage(10));

            $selectedTesKoranCount = DetailPackage::where('package_id', $package_id)
                ->where('detailable_type', TesKoran::class)
                ->count();

            return view('admin.pages.package.tes-koran.index', compact('package', 'tesKorans', 'selectedTesKoranCount'));
        } catch (\Exception $e) {
            return redirect()->route('admin.package.index')
                ->with('error', 'Paket tidak ditemukan');
        }
    }

    public function toggleTesKoran(Request $request, $package_id, $tes_koran_id)
    {
        abort_unless(auth()->user()?->hasPermission('tes_koran', 'update'), 403);

        try {
            $package = Package::where('package_id', $package_id)->firstOrFail();
            $tesKoran = TesKoran::findOrFail($tes_koran_id);

            $existing = DetailPackage::where('package_id', $package_id)
                ->where('detailable_type', TesKoran::class)
                ->where('detailable_id', $tes_koran_id)
                ->first();

            if ($request->has('selected')) {
                $shouldBeSelected = $request->boolean('selected');

                if ($shouldBeSelected && ! $existing) {
                    DetailPackage::create([
                        'package_id' => $package_id,
                        'detailable_type' => TesKoran::class,
                        'detailable_id' => $tes_koran_id,
                    ]);
                    $message = 'Tes Koran berhasil ditambahkan ke paket';
                } elseif (! $shouldBeSelected && $existing) {
                    $existing->delete();
                    $message = 'Tes Koran berhasil dihapus dari paket';
                } else {
                    $message = $shouldBeSelected
                        ? 'Tes Koran sudah ada di paket'
                        : 'Tes Koran sudah tidak ada di paket';
                }
            } elseif ($existing) {
                $existing->delete();
                $message = 'Tes Koran berhasil dihapus dari paket';
            } else {
                DetailPackage::create([
                    'package_id' => $package_id,
                    'detailable_type' => TesKoran::class,
                    'detailable_id' => $tes_koran_id,
                ]);
                $message = 'Tes Koran berhasil ditambahkan ke paket';
            }

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: '.$e->getMessage()], 500);
        }
    }

    public function indexTryout($package_id)
    {
        try {
            $package = Package::where('package_id', $package_id)->firstOrFail();

            // Get all tryouts with their package relationship status
            $tryouts = Tryout::with(['tryoutDetails.questions', 'detailPackages' => function ($query) use ($package_id) {
                $query->where('package_id', $package_id);
            }])
                ->orderByRaw('(SELECT COUNT(*) FROM detail_packages WHERE detailable_type = ? AND detailable_id = tryouts.tryout_id AND package_id = ?) DESC', [Tryout::class, $package_id])
                ->orderBy('created_at', 'desc')
                ->paginate(\App\Support\Pagination::perPage(10));

            $selectedTryoutCount = DetailPackage::where('package_id', $package_id)
                ->where('detailable_type', Tryout::class)
                ->count();

            return view('admin.pages.package.tryout.index', compact('package', 'tryouts', 'selectedTryoutCount'));
        } catch (\Exception $e) {
            return redirect()->route('admin.package.index')
                ->with('error', 'Paket tidak ditemukan');
        }
    }

    public function createTryout($packageId)
    {
        try {
            $package = Package::where('package_id', $packageId)->firstOrFail();
            $securityDefaults = PlanQuotaService::getDefaultProctoringSettings();
            $tryoutTypeOptions = $this->packageTryoutTypeOptions();

            return view('admin.pages.package.tryout.create', compact('package', 'securityDefaults', 'tryoutTypeOptions'));
        } catch (\Exception $e) {
            return redirect()->route('admin.package.index')
                ->with('error', 'Paket tidak ditemukan');
        }
    }

    public function storeTryout(Request $request, $package_id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type_tryout' => ['required', Rule::in(array_keys($this->packageTryoutTypeOptions()))],
            'duration_total' => 'required|integer|min:1',
            'passing_score_total' => 'required|numeric|min:0|max:100',
            'passing_type_twk' => 'nullable|in:score,percentage',
            'passing_type_tiu' => 'nullable|in:score,percentage',
            'passing_type_tkp' => 'nullable|in:score,percentage',
            'passing_type_general' => 'nullable|in:score,percentage',
            'passing_type_certification' => 'nullable|in:score,percentage',
            'section_break_duration' => 'nullable|integer|min:0|max:3600',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_certification' => 'boolean',
            'is_active' => 'boolean',
            'is_toefl' => 'boolean',
            'enable_anti_copy' => 'boolean',
            'enable_tab_switch_detection' => 'boolean',
            'enable_webcam_check' => 'boolean',
            'enable_screen_check' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);
        $securitySettings = PlanQuotaService::proctoringSettingsFromRequest($request);

        // Buat tryout baru
        $tryout = Tryout::create([
            'package_id' => $package_id,
            'name' => $request->name,
            'description' => $request->description,
            'type_tryout' => $request->type_tryout,
            'section_break_duration' => max(0, (int) $request->input('section_break_duration', 0)),
            'is_certification' => $request->has('is_certification'),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $request->has('is_active'),
            'is_toefl' => $request->has('is_toefl'),
            'enable_anti_copy' => $securitySettings['enable_anti_copy'],
            'enable_tab_switch_detection' => $securitySettings['enable_tab_switch_detection'],
            'enable_webcam_check' => $securitySettings['enable_webcam_check'],
            'enable_screen_check' => $securitySettings['enable_screen_check'],
        ]);

        if ($tryout && $tryout->type_tryout == 'skd_full') {
            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'twk',
                'duration' => $request->duration_twk,
                'passing_score' => $request->passing_score_twk,
                'passing_type' => $request->input('passing_type_twk', 'score'),
            ]);

            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'tiu',
                'duration' => $request->duration_tiu,
                'passing_score' => $request->passing_score_tiu,
                'passing_type' => $request->input('passing_type_tiu', 'score'),
            ]);

            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'tkp',
                'duration' => $request->duration_tkp,
                'passing_score' => $request->passing_score_tkp,
                'passing_type' => $request->input('passing_type_tkp', 'score'),
            ]);
        } elseif ($tryout && $tryout->type_tryout == 'certification') {
            // Create certification subtests: writing, reading, listening
            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'writing',
                'duration' => $request->duration_writing ?? 60,
                'passing_score' => $request->passing_score_writing ?? 60,
                'passing_type' => $request->input('passing_type_certification', 'score'),
            ]);

            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'reading',
                'duration' => $request->duration_reading ?? 60,
                'passing_score' => $request->passing_score_reading ?? 60,
                'passing_type' => $request->input('passing_type_certification', 'score'),
            ]);

            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'listening',
                'duration' => $request->duration_listening ?? 60,
                'passing_score' => $request->passing_score_listening ?? 60,
                'passing_type' => $request->input('passing_type_certification', 'score'),
            ]);
        } elseif ($tryout && $tryout->type_tryout == 'twk') {
            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'twk',
                'duration' => $request->duration_twk,
                'passing_score' => $request->passing_score_twk,
                'passing_type' => $request->input('passing_type_twk', 'score'),
            ]);
        } elseif ($tryout && $tryout->type_tryout == 'tiu') {
            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'tiu',
                'duration' => $request->duration_tiu,
                'passing_score' => $request->passing_score_tiu,
                'passing_type' => $request->input('passing_type_tiu', 'score'),
            ]);
        } elseif ($tryout && $tryout->type_tryout == 'tkp') {
            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'tkp',
                'duration' => $request->duration_tkp,
                'passing_score' => $request->passing_score_tkp,
                'passing_type' => $request->input('passing_type_tkp', 'score'),
            ]);
        } elseif ($tryout) {
            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => $tryout->type_tryout,
                'duration' => $request->duration_general,
                'passing_score' => $request->passing_score_general,
                'passing_type' => $request->input('passing_type_general', 'score'),
            ]);
        }

        return redirect()->route('admin.package.tryout.index', $package_id)
            ->with('success', 'Tryout "'.$tryout->name.'" berhasil ditambahkan');
    }

    private function packageTryoutTypeOptions(): array
    {
        $options = collect([
            'tiu' => 'TIU (Tes Intelegensi Umum)',
            'twk' => 'TWK (Tes Wawasan Kebangsaan)',
            'tkp' => 'TKP (Tes Karakteristik Pribadi)',
            'tpa' => 'TPA',
            'tbi' => 'TBI',
            'tob' => 'TOB',
            'skd_full' => 'SKD Full (TWK + TIU + TKP)',
            'general' => 'General',
            'certification' => 'Certification',
        ]);

        if (Schema::hasTable('material_categories') && Schema::hasColumn('material_categories', 'code')) {
            $dynamicOptions = MaterialCategory::query()
                ->with('parent')
                ->withCode()
                ->active()
                ->ordered()
                ->get()
                ->mapWithKeys(fn (MaterialCategory $category) => [
                    $category->code => $category->display_name ?: Str::headline($category->code),
                ]);

            $options = $options->merge($dynamicOptions);
        }

        return $options->filter()->all();
    }

    public function indexSoal($package_id, $tryout_detail_id)
    {
        try {
            // Handle standalone mode (dari manajemen tryout langsung)
            if ($package_id === 'standalone') {
                $package = (object) ['package_id' => 'standalone', 'name' => 'Manajemen Tryout'];
            } else {
                $package = Package::where('package_id', $package_id)->firstOrFail();
            }

            $tryout_detail = TryoutDetail::find($tryout_detail_id);
            $tryout = Tryout::with('tryoutDetails')->where('tryout_id', $tryout_detail->tryout_id)->first();
            $questions = Question::with('questionOptions')->where('tryout_detail_id', $tryout_detail_id)->get();

            return view('admin.pages.package.tryout.soal', compact('package', 'tryout', 'questions'));
        } catch (\Exception $e) {
            return redirect()->route('admin.tryout.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    public function createSoal($package_id, $tryout_detail_id)
    {
        try {
            // Handle standalone mode
            if ($package_id === 'standalone') {
                $package = (object) ['package_id' => 'standalone', 'name' => 'Manajemen Tryout'];
            } else {
                $package = Package::where('package_id', $package_id)->firstOrFail();
            }

            $tryout_detail = TryoutDetail::find($tryout_detail_id);
            $tryout = Tryout::with('tryoutDetails')->where('tryout_id', $tryout_detail->tryout_id)->first();

            return view('admin.pages.package.tryout.create-soal', compact('package', 'tryout'));
        } catch (\Exception $e) {
            return redirect()->route('admin.tryout.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    public function editSoal($package_id, $tryout_detail_id, $question_id)
    {
        try {
            $package = Package::where('package_id', $package_id)->firstOrFail();
            $tryout_detail = TryoutDetail::find($tryout_detail_id);
            $tryout = Tryout::with('tryoutDetails')->where('tryout_id', $tryout_detail->tryout_id)->first();
            $question = Question::with('questionOptions')->where('tryout_detail_id', $tryout_detail_id)->where('question_id', $question_id)->first();

            return view('admin.pages.package.tryout.create-soal', compact('package', 'tryout', 'question'));
        } catch (\Exception $e) {
            return redirect()->route('admin.package.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    public function storeSoal(Request $request, $package_id, $tryout_detail_id)
    {
        try {
            // Validation
            $request->validate([
                'question_text' => 'required|string',
                'option_a' => 'required|string',
                'option_b' => 'required|string',
                'option_c' => 'required|string',
                'option_d' => 'required|string',
                'option_e' => 'nullable|string',
                'correct_answer' => 'required|in:A,B,C,D,E',
                'explanation' => 'nullable|string',
                'sound' => 'nullable|file|mimes:mp3,wav,m4a|max:5120',
                'use_custom_scores' => 'nullable|boolean',
                'score_a' => 'nullable|numeric|min:0',
                'score_b' => 'nullable|numeric|min:0',
                'score_c' => 'nullable|numeric|min:0',
                'score_d' => 'nullable|numeric|min:0',
                'score_e' => 'nullable|numeric|min:0',
            ]);

            if ($request->correct_answer === 'E' && empty($request->option_e)) {
                return redirect()->back()
                    ->with('error', 'Pilihan E tidak boleh kosong jika dipilih sebagai jawaban benar')
                    ->withInput();
            }

            $tryoutDetail = TryoutDetail::findOrFail($tryout_detail_id);

            $soundPath = null;
            if ($request->hasFile('sound')) {
                $soundPath = $request->file('sound')->store('questions/audio', 'public');
            }

            $question = Question::create([
                'tryout_detail_id' => $tryout_detail_id,
                'question_type' => 'multiple_choice',
                'question_text' => $request->question_text,
                'sound' => $soundPath,
                'explanation' => $request->explanation,
                'default_weight' => 1.00,
                'custom_score' => $request->use_custom_scores ? 'yes' : 'no',
            ]);

            $options = [
                ['key' => 'A', 'text' => $request->option_a],
                ['key' => 'B', 'text' => $request->option_b],
                ['key' => 'C', 'text' => $request->option_c],
                ['key' => 'D', 'text' => $request->option_d],
            ];

            if (! empty($request->option_e)) {
                $options[] = ['key' => 'E', 'text' => $request->option_e];
            }

            foreach ($options as $index => $option) {
                $isCorrect = ($option['key'] === $request->correct_answer);

                $weight = 0;

                if ($request->use_custom_scores) {
                    $scoreField = 'score_'.strtolower($option['key']);
                    $weight = (float) ($request->$scoreField ?? 0);
                } else {
                    $weight = $isCorrect ? 1.00 : 0.00;
                }

                QuestionOption::create([
                    'question_id' => $question->question_id,
                    'option_text' => $option['text'],
                    'weight' => $weight,
                    'is_correct' => $isCorrect,
                ]);
            }

            $search_max_weight = QuestionOption::where('question_id', $question->question_id)->max('weight');
            Question::where('question_id', $question->question_id)->update(['default_weight' => $search_max_weight]);

            return redirect()->route('admin.package.tryout.soal', [$package_id, $tryout_detail_id])
                ->with('success', 'Soal berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan soal: '.$e->getMessage())
                ->withInput();
        }
    }

    public function updateSoal(Request $request, $package_id, $tryout_detail_id, $question_id)
    {
        try {
            $request->validate([
                'question_text' => 'required|string',
                'option_a' => 'required|string',
                'option_b' => 'required|string',
                'option_c' => 'required|string',
                'option_d' => 'required|string',
                'option_e' => 'nullable|string',
                'correct_answer' => 'required|in:A,B,C,D,E',
                'explanation' => 'nullable|string',
                'sound' => 'nullable|file|mimes:mp3,wav,m4a|max:5120',
                'use_custom_scores' => 'nullable|boolean',
                'score_a' => 'nullable|numeric|min:0',
                'score_b' => 'nullable|numeric|min:0',
                'score_c' => 'nullable|numeric|min:0',
                'score_d' => 'nullable|numeric|min:0',
                'score_e' => 'nullable|numeric|min:0',
            ]);

            if ($request->correct_answer === 'E' && empty($request->option_e)) {
                return redirect()->back()
                    ->with('error', 'Pilihan E tidak boleh kosong jika dipilih sebagai jawaban benar')
                    ->withInput();
            }

            $question = Question::where('question_id', $question_id)->firstOrFail();

            $soundPath = $question->sound;
            if ($request->hasFile('sound')) {
                $soundPath = $request->file('sound')->store('questions/audio', 'public');
            }

            $question->update([
                'question_text' => $request->question_text,
                'sound' => $soundPath,
                'explanation' => $request->explanation,
                'custom_score' => $request->use_custom_scores ? 'yes' : 'no',
            ]);

            $existingOptions = QuestionOption::where('question_id', $question_id)
                ->orderBy('question_option_id')
                ->get();

            $newOptions = [
                ['key' => 'A', 'text' => $request->option_a],
                ['key' => 'B', 'text' => $request->option_b],
                ['key' => 'C', 'text' => $request->option_c],
                ['key' => 'D', 'text' => $request->option_d],
            ];

            if (! empty($request->option_e)) {
                $newOptions[] = ['key' => 'E', 'text' => $request->option_e];
            }

            foreach ($newOptions as $index => $newOption) {
                $isCorrect = ($newOption['key'] === $request->correct_answer);

                $weight = 0;
                if ($request->use_custom_scores) {
                    $scoreField = 'score_'.strtolower($newOption['key']);
                    $weight = (float) ($request->$scoreField ?? 0);
                } else {
                    $weight = $isCorrect ? 1.00 : 0.00;
                }

                if (isset($existingOptions[$index])) {
                    $existingOptions[$index]->update([
                        'option_text' => $newOption['text'],
                        'weight' => $weight,
                        'is_correct' => $isCorrect,
                    ]);
                } else {
                    QuestionOption::create([
                        'question_id' => $question->question_id,
                        'option_text' => $newOption['text'],
                        'weight' => $weight,
                        'is_correct' => $isCorrect,
                    ]);
                }
            }

            if ($existingOptions->count() > count($newOptions)) {
                $optionsToDelete = $existingOptions->slice(count($newOptions));
                foreach ($optionsToDelete as $optionToDelete) {
                    $optionToDelete->delete();
                }
            }

            $maxWeight = QuestionOption::where('question_id', $question->question_id)->max('weight');
            $question->update(['default_weight' => $maxWeight]);

            return redirect()->route('admin.package.tryout.soal', [$package_id, $tryout_detail_id])
                ->with('success', 'Soal berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui soal: '.$e->getMessage())
                ->withInput();
        }
    }

    public function toggleClass(Request $request, $package_id, $class_id): JsonResponse
    {
        $validated = $request->validate([
            'selected' => ['required', 'boolean'],
        ]);

        try {
            $shouldBeSelected = (bool) $validated['selected'];
            $isSelected = DB::transaction(function () use (
                $package_id,
                $class_id,
                $shouldBeSelected
            ): bool {
                $package = Package::query()
                    ->whereKey($package_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $class = ClassModel::query()
                    ->whereKey($class_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $attributes = [
                    'package_id' => $package->package_id,
                    'detailable_type' => ClassModel::class,
                    'detailable_id' => $class->class_id,
                ];

                if ($shouldBeSelected) {
                    DetailPackage::query()->insertOrIgnore([
                        ...$attributes,
                        'order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DetailPackage::query()
                        ->where($attributes)
                        ->delete();
                }

                return DetailPackage::query()
                    ->where($attributes)
                    ->exists();
            }, 3);

            return response()->json([
                'success' => true,
                'selected' => $isSelected,
                'message' => $isSelected
                    ? 'Kelas Zoom berhasil ditambahkan ke paket.'
                    : 'Kelas Zoom berhasil dilepas dari paket.',
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: '.$e->getMessage()], 500);
        }
    }

    public function toggleTryout(Request $request, $package_id, $tryout_id)
    {
        try {
            $package = Package::findOrFail($package_id);
            $tryout = Tryout::findOrFail($tryout_id);

            $detailPackage = DetailPackage::where([
                'package_id' => $package_id,
                'detailable_type' => Tryout::class,
                'detailable_id' => $tryout_id,
            ])->first();

            if ($request->has('selected')) {
                $shouldBeSelected = $request->boolean('selected');

                if ($shouldBeSelected && ! $detailPackage) {
                    DetailPackage::create([
                        'package_id' => $package_id,
                        'detailable_type' => Tryout::class,
                        'detailable_id' => $tryout_id,
                        'order' => 0,
                    ]);
                    $message = 'Tryout berhasil ditambahkan ke paket';
                } elseif (! $shouldBeSelected && $detailPackage) {
                    $detailPackage->delete();
                    $message = 'Tryout berhasil dihapus dari paket';
                } else {
                    $message = $shouldBeSelected
                        ? 'Tryout sudah ada di paket'
                        : 'Tryout sudah tidak ada di paket';
                }
            } elseif ($detailPackage) {
                // Remove from package
                $detailPackage->delete();
                $message = 'Tryout berhasil dihapus dari paket';
            } else {
                // Add to package
                DetailPackage::create([
                    'package_id' => $package_id,
                    'detailable_type' => Tryout::class,
                    'detailable_id' => $tryout_id,
                    'order' => 0,
                ]);
                $message = 'Tryout berhasil ditambahkan ke paket';
            }

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: '.$e->getMessage()], 500);
        }
    }
}
