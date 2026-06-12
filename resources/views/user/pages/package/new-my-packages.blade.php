@extends('user.layout.new-user')

@section('title', 'Paket Saya')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$tesKoranEnabled = $clientBranding['tes_koran_enabled'] ?? true;
$user = auth()->user();
$currentTab = request('tab', 'packages');

if (!$tesKoranEnabled && $currentTab === 'tes-koran') {
    $currentTab = 'packages';
}

// Get user's accessible data for each tab
$accessiblePackageIds = $user->userPackageAccess()
    ->where('status', 'active')
    ->where(function ($q) {
        $q->whereNull('end_date')->orWhere('end_date', '>', now());
    })
    ->pluck('package_id')
    ->toArray();

// Get materials via packages
$accessibleMaterialIds = \DB::table('package_materials')
    ->whereIn('package_id', $accessiblePackageIds)
    ->pluck('material_id')
    ->toArray();

$myMaterials = \App\Models\Material::whereIn('material_id', $accessibleMaterialIds)
    ->with(['userAccess' => function($q) use ($user) {
        $q->where('user_id', $user->id);
    }])
    ->get();

$videoMaterials = $myMaterials->where('type', 'video');
$documentMaterials = $myMaterials->where('type', 'document');

// Get tryouts via active packages and direct tryout access.
$directTryoutIds = \App\Models\UserTryoutAccess::where('user_id', $user->id)
    ->where(function ($q) {
        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
    })
    ->pluck('tryout_id')
    ->toArray();

$myTryouts = \App\Models\Tryout::where(function($q) use ($accessiblePackageIds, $directTryoutIds) {
    $q->whereHas('packages', function($packageQuery) use ($accessiblePackageIds) {
        $packageQuery->whereIn('packages.package_id', $accessiblePackageIds);
    })
    ->orWhereIn('tryout_id', $directTryoutIds);
})
->with([
    'packages' => function($q) use ($accessiblePackageIds) {
        $q->whereIn('packages.package_id', $accessiblePackageIds);
    },
    'userAnswers' => function($q) use ($user) {
        $q->where('user_id', $user->id);
    },
])
->get();

$myTesKorans = $tesKoranEnabled ? \App\Models\TesKoran::where(function($q) use ($accessiblePackageIds, $user) {
    $q->whereHas('packages', function($packageQuery) use ($accessiblePackageIds) {
        $packageQuery->whereIn('packages.package_id', $accessiblePackageIds);
    })
    ->orWhereHas('individualPurchases', function($purchaseQuery) use ($user) {
        $purchaseQuery->where('user_id', $user->id)
            ->where('status', \App\Models\IndividualPurchase::STATUS_APPROVED)
            ->where(function ($query) {
                $query->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            });
    });
})
->with(['results' => function($q) use ($user) {
    $q->where('user_id', $user->id);
}])
->get() : collect();
@endphp

<style>
.tab-active {
    background-color: {{ $primaryColor }} !important;
    color: white !important;
    border-color: {{ $primaryColor }} !important;
}
</style>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Paket Saya</h1>
        <p class="text-gray-500 mt-1">Lanjutkan perjalanan belajarmu</p>
    </div>
    <a href="{{ route('user.package.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity text-white" style="background-color: {{ $primaryColor }}">
        <i class="ri-store-3-line mr-1"></i>Beli Paket
    </a>
</div>

<!-- Tabs Navigation -->
<div class="bg-white rounded-2xl p-2 mb-6 border border-gray-100 inline-flex flex-wrap gap-1">
    <a href="{{ route('user.package.my') }}?tab=packages" 
       class="px-5 py-2.5 rounded-xl font-medium transition-all text-sm {{ $currentTab === 'packages' ? 'tab-active' : 'text-gray-600 hover:bg-gray-50' }}">
        <i class="ri-folder-3-line mr-1"></i>Paket
    </a>
    <a href="{{ route('user.package.my') }}?tab=videos" 
       class="px-5 py-2.5 rounded-xl font-medium transition-all text-sm {{ $currentTab === 'videos' ? 'tab-active' : 'text-gray-600 hover:bg-gray-50' }}">
        <i class="ri-video-line mr-1"></i>Video
    </a>
    <a href="{{ route('user.package.my') }}?tab=documents" 
       class="px-5 py-2.5 rounded-xl font-medium transition-all text-sm {{ $currentTab === 'documents' ? 'tab-active' : 'text-gray-600 hover:bg-gray-50' }}">
        <i class="ri-file-text-line mr-1"></i>Dokumen
    </a>
    <a href="{{ route('user.package.my') }}?tab=tryouts" 
       class="px-5 py-2.5 rounded-xl font-medium transition-all text-sm {{ $currentTab === 'tryouts' ? 'tab-active' : 'text-gray-600 hover:bg-gray-50' }}">
        <i class="ri-file-list-3-line mr-1"></i>Tryout
    </a>
    @if($tesKoranEnabled)
    <a href="{{ route('user.package.my') }}?tab=tes-koran"
       class="px-5 py-2.5 rounded-xl font-medium transition-all text-sm {{ $currentTab === 'tes-koran' ? 'tab-active' : 'text-gray-600 hover:bg-gray-50' }}">
        <i class="ri-file-edit-line mr-1"></i>Tes Koran
    </a>
    @endif
</div>

{{-- ==================== TAB: PACKAGES ==================== --}}
@if($currentTab === 'packages')
    @if($activePackages->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($activePackages as $access)
        @php
        $package = $access->package;
        
        // Load materials and tryouts with counts
        $package->loadCount(['materials', 'tryouts']);
        $materials = $package->materials;
        $tryouts = $package->tryouts;
        $tesKorans = $tesKoranEnabled ? $package->tesKorans : collect();
        $totalItems = $materials->count() + $tryouts->count() + $tesKorans->count();
        
        // Calculate actual progress
        $completedCount = 0;
        
        // Check completed materials
        foreach ($materials as $material) {
            $progress = \App\Models\MaterialProgressLog::where('user_id', $user->id)
                ->where('material_id', $material->material_id)
                ->where('is_completed', true)
                ->first();
            if ($progress) {
                $completedCount++;
            }
        }
        
        // Check completed tryouts
        foreach ($tryouts as $tryout) {
            $attempt = \App\Models\UserAnswer::where('user_id', $user->id)
                ->where('tryout_id', $tryout->tryout_id)
                ->where('status', 'completed')
                ->first();
            if ($attempt) {
                $completedCount++;
            }
        }

        foreach ($tesKorans as $tesKoran) {
            $attempt = \App\Models\TesKoranResult::where('user_id', $user->id)
                ->where('tes_koran_id', $tesKoran->id)
                ->where('status', 'completed')
                ->first();
            if ($attempt) {
                $completedCount++;
            }
        }
        
        $progressPercent = $totalItems > 0 ? round(($completedCount / $totalItems) * 100) : 0;
        @endphp
        <div class="bg-white rounded-2xl overflow-hidden border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all">
            {{-- Package Image --}}
            <div class="relative h-40 bg-gray-100 overflow-hidden">
                @if($package->image)
                    @php
                        $pkgExt = strtolower(pathinfo($package->image, PATHINFO_EXTENSION));
                        $isPkgVideo = in_array($pkgExt, ['mp4','webm','mov','m4v'], true);
                        $pkgUrl = Storage::url($package->image);
                    @endphp
                    @if($isPkgVideo)
                    <video src="{{ $pkgUrl }}" class="w-full h-full object-cover" controls preload="metadata" playsinline></video>
                    @else
                    <img src="{{ $pkgUrl }}" alt="{{ $package->name }}" class="w-full h-full object-cover">
                    @endif
                @else
                    <div class="w-full h-full flex items-center justify-center bg-gray-100">
                        <i class="ri-image-line text-4xl text-gray-300"></i>
                    </div>
                @endif
                <span class="absolute top-3 right-3 px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Aktif</span>
            </div>
            
            <div class="p-5">
            <h3 class="font-bold text-gray-800 mb-1">{{ $package->name }}</h3>
            <div class="text-sm text-gray-400 mb-4 line-clamp-2">{!! $package->description ?? 'Paket pembelajaran lengkap' !!}</div>
            
            <!-- Progress -->
            <div class="mb-4">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-500">Progress Belajar</span>
                    <span class="font-semibold" style="color: {{ $primaryColor }}">{{ $progressPercent }}%</span>
                </div>
                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all" style="width: {{ $progressPercent }}%; background-color: {{ $primaryColor }}"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1">{{ $completedCount }}/{{ $totalItems }} selesai</p>
            </div>
            
            <!-- Meta info -->
            <div class="flex items-center justify-between text-sm text-gray-400 mb-4">
                <span><i class="ri-calendar-line mr-1"></i>{{ $access->end_date ? $access->end_date->format('d M Y') : 'Lifetime' }}</span>
                <span><i class="ri-book-open-line mr-1"></i>{{ $totalItems }} Item</span>
            </div>
            
            <!-- Actions -->
            <a href="{{ route('user.package.show', $package->package_id) }}" 
               class="block w-full py-2.5 text-white text-center rounded-xl font-medium hover:opacity-90 transition-opacity"
               style="background-color: {{ $primaryColor }}">
                <i class="ri-play-circle-line mr-1"></i>Lanjutkan Belajar
            </a>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-16">
        <div class="w-24 h-24 rounded-full mx-auto mb-4 flex items-center justify-center" style="background-color: {{ $primaryColor }}10">
            <i class="ri-package-line text-4xl" style="color: {{ $primaryColor }}"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada paket aktif</h3>
        <p class="text-gray-400 text-sm mb-6">Yuk, pilih paket belajar yang sesuai dengan kebutuhanmu!</p>
        <a href="{{ route('user.package.index') }}" class="inline-flex items-center px-6 py-3 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
            <i class="ri-store-3-line mr-2"></i>Lihat Paket
        </a>
    </div>
    @endif

{{-- ==================== TAB: VIDEOS ==================== --}}
@elseif($currentTab === 'videos')
    @if($videoMaterials->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($videoMaterials as $material)
        @php
        $userAccess = $material->userAccess->first();
        @endphp
        <div class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all group">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 bg-red-100 text-red-500 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="{{ $material->type_icon }} text-2xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">{{ $material->type_label }}</span>
                        @if($material->duration_minutes)
                        <span class="text-xs text-gray-400">{{ $material->formatted_duration }}</span>
                        @endif
                    </div>
                    <h3 class="font-medium text-gray-800 text-sm line-clamp-2">{{ $material->title }}</h3>
                    <p class="text-xs text-gray-400 mt-1 line-clamp-1">{{ $material->description ?? 'Tidak ada deskripsi' }}</p>
                </div>
            </div>
            
            <div class="mt-4 pt-3 border-t flex items-center justify-between">
                @if($userAccess && $userAccess->is_completed)
                <span class="text-xs flex items-center gap-1" style="color: {{ $primaryColor }}">
                    <i class="ri-check-line"></i>Selesai
                </span>
                @elseif($userAccess && $userAccess->is_in_progress)
                <div class="flex items-center gap-2 flex-1">
                    <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full" style="width: {{ $userAccess->progress_percentage }}%; background-color: {{ $primaryColor }}"></div>
                    </div>
                    <span class="text-xs text-gray-500">{{ $userAccess->progress_percentage }}%</span>
                </div>
                @else
                <span class="text-xs text-gray-400">Belum dimulai</span>
                @endif
                
                <a href="{{ route('user.material.show', $material->material_id) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-medium text-white hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-play-circle-line mr-1"></i>Lihat
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-16">
        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-video-line text-4xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada video</h3>
        <p class="text-gray-400 text-sm mb-6">Video pembelajaran akan muncul di sini setelah kamu memiliki akses.</p>
        <a href="{{ route('user.package.index') }}" class="inline-flex items-center px-6 py-3 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
            <i class="ri-store-3-line mr-2"></i>Lihat Paket
        </a>
    </div>
    @endif

{{-- ==================== TAB: DOCUMENTS ==================== --}}
@elseif($currentTab === 'documents')
    @if($documentMaterials->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($documentMaterials as $material)
        @php
        $userAccess = $material->userAccess->first();
        @endphp
        <div class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all group">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 bg-blue-100 text-blue-500 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="{{ $material->type_icon }} text-2xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">{{ $material->type_label }}</span>
                    </div>
                    <h3 class="font-medium text-gray-800 text-sm line-clamp-2">{{ $material->title }}</h3>
                    <p class="text-xs text-gray-400 mt-1 line-clamp-1">{{ $material->description ?? 'Tidak ada deskripsi' }}</p>
                </div>
            </div>
            
            <div class="mt-4 pt-3 border-t flex items-center justify-between">
                @if($userAccess && $userAccess->is_completed)
                <span class="text-xs flex items-center gap-1" style="color: {{ $primaryColor }}">
                    <i class="ri-check-line"></i>Selesai
                </span>
                @else
                <span class="text-xs text-gray-400">Belum dibaca</span>
                @endif
                
                <a href="{{ route('user.material.show', $material->material_id) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-medium text-white hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-eye-line mr-1"></i>Baca
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-16">
        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-file-text-line text-4xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada dokumen</h3>
        <p class="text-gray-400 text-sm mb-6">Dokumen pembelajaran akan muncul di sini setelah kamu memiliki akses.</p>
        <a href="{{ route('user.package.index') }}" class="inline-flex items-center px-6 py-3 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
            <i class="ri-store-3-line mr-2"></i>Lihat Paket
        </a>
    </div>
    @endif

{{-- ==================== TAB: TRYOUTS ==================== --}}
@elseif($currentTab === 'tryouts')
    @if($myTryouts->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($myTryouts as $tryout)
        @php
        $userAnswers = $tryout->userAnswers;
        $hasAttempts = $userAnswers->count() > 0;
        $isInProgress = $userAnswers->where('status', 'in_progress')->count() > 0;
        $totalQuestions = $tryout->getTotalQuestionsAttribute();
        $totalDuration = $tryout->getTotalDurationAttribute();
        $packageId = $tryout->packages->first()?->package_id;
        if (!$packageId) {
            $packageId = \DB::table('detail_packages')
                ->join('user_package_access', 'detail_packages.package_id', '=', 'user_package_access.package_id')
                ->where('detail_packages.detailable_type', \App\Models\Tryout::class)
                ->where('detail_packages.detailable_id', $tryout->tryout_id)
                ->where('user_package_access.user_id', $user->id)
                ->where('user_package_access.status', 'active')
                ->where(function ($q) {
                    $q->whereNull('user_package_access.end_date')
                        ->orWhere('user_package_access.end_date', '>', now());
                })
                ->value('detail_packages.package_id');
        }
        $packageId = $packageId ?: 'free';
        @endphp
        <div class="bg-white rounded-2xl p-5 border border-gray-100 hover:shadow-md hover:-translate-y-0.5 transition-all">
            <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 {{ $hasAttempts ? ($isInProgress ? 'bg-yellow-100' : 'bg-green-100') : 'bg-gray-100' }} rounded-xl flex items-center justify-center"
                     style="{{ !$hasAttempts ? 'background-color: ' . $primaryColor . '20' : '' }}">
                    <i class="ri-file-list-3-line text-xl {{ $hasAttempts ? ($isInProgress ? 'text-yellow-600' : 'text-green-600') : '' }}"
                       style="{{ !$hasAttempts ? 'color: ' . $primaryColor : '' }}"></i>
                </div>
                @if($isInProgress)
                <span class="px-2.5 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full font-medium">Sedang Dikerjakan</span>
                @elseif($hasAttempts)
                <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Sudah Dikerjakan</span>
                @else
                <span class="px-2.5 py-1 bg-blue-100 text-blue-700 text-xs rounded-full font-medium">Belum Dikerjakan</span>
                @endif
            </div>

            <h3 class="font-bold text-gray-800 mb-2 line-clamp-2">{{ $tryout->name }}</h3>

            <div class="space-y-2 mb-4">
                <div class="flex items-center text-sm text-gray-500">
                    <i class="ri-question-line mr-2 text-gray-400"></i>
                    <span>{{ $totalQuestions }} Soal</span>
                </div>
                <div class="flex items-center text-sm text-gray-500">
                    <i class="ri-time-line mr-2 text-gray-400"></i>
                    <span>{{ $totalDuration }} Menit</span>
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex gap-2">
                <a href="{{ route('user.tryout.lobby', ['id_package' => $packageId, 'id_tryout' => $tryout->tryout_id]) }}"
                   class="flex-1 py-2.5 text-white text-center rounded-xl text-sm font-medium hover:opacity-90 transition-opacity"
                   style="background-color: {{ $isInProgress ? '#f59e0b' : $primaryColor }}">
                    <i class="ri-play-circle-line mr-1"></i>{{ $isInProgress ? 'Lanjutkan' : 'Kerjakan' }}
                </a>
                @if($hasAttempts)
                <a href="{{ route('user.package.tryout.riwayat', ['id_package' => $packageId, 'id_tryout' => $tryout->tryout_id]) }}"
                   class="flex-1 py-2.5 text-center rounded-xl text-sm font-medium border-2 hover:opacity-90 transition-colors"
                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                    <i class="ri-history-line mr-1"></i>Riwayat
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-16">
        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-file-list-3-line text-4xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada tryout</h3>
        <p class="text-gray-400 text-sm mb-6">Tryout akan muncul di sini setelah kamu memiliki akses.</p>
        <a href="{{ route('user.package.index') }}" class="inline-flex items-center px-6 py-3 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
            <i class="ri-store-3-line mr-2"></i>Lihat Paket
        </a>
    </div>
    @endif

{{-- ==================== TAB: TES KORAN ==================== --}}
@elseif($tesKoranEnabled && $currentTab === 'tes-koran')
    @if($myTesKorans->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($myTesKorans as $tesKoran)
        @php
        $results = $tesKoran->results;
        $hasAttempts = $results->count() > 0;
        $latestResult = $results->sortByDesc('created_at')->first();
        @endphp
        <div class="bg-white rounded-2xl p-5 border border-gray-100 hover:shadow-md hover:-translate-y-0.5 transition-all">
            <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 {{ $hasAttempts ? 'bg-green-100' : 'bg-gray-100' }} rounded-xl flex items-center justify-center"
                     style="{{ !$hasAttempts ? 'background-color: ' . $primaryColor . '20' : '' }}">
                    <i class="ri-file-edit-line text-xl {{ $hasAttempts ? 'text-green-600' : '' }}"
                       style="{{ !$hasAttempts ? 'color: ' . $primaryColor : '' }}"></i>
                </div>
                @if($hasAttempts)
                <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Sudah Dikerjakan</span>
                @else
                <span class="px-2.5 py-1 bg-blue-100 text-blue-700 text-xs rounded-full font-medium">Belum Dikerjakan</span>
                @endif
            </div>

            <h3 class="font-bold text-gray-800 mb-2 line-clamp-2">{{ $tesKoran->name }}</h3>

            <div class="space-y-2 mb-4">
                <div class="flex items-center text-sm text-gray-500">
                    <i class="ri-file-list-line mr-2 text-gray-400"></i>
                    <span>{{ ucfirst($tesKoran->test_type) }}</span>
                </div>
                <div class="flex items-center text-sm text-gray-500">
                    <i class="ri-time-line mr-2 text-gray-400"></i>
                    <span>{{ $tesKoran->column_duration_seconds ?? 60 }} Detik/Kolom</span>
                </div>
                @if($latestResult)
                <div class="flex items-center text-sm text-gray-500">
                    <i class="ri-bar-chart-line mr-2 text-gray-400"></i>
                    <span>Benar {{ $latestResult->total_correct }}</span>
                </div>
                @endif
            </div>

            <a href="{{ route('user.tes-koran.show', $tesKoran) }}"
               class="block w-full py-2.5 text-white text-center rounded-xl text-sm font-medium hover:opacity-90 transition-opacity"
               style="background-color: {{ $primaryColor }}">
                <i class="ri-play-circle-line mr-1"></i>{{ $hasAttempts ? 'Kerjakan Lagi' : 'Kerjakan' }}
            </a>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-16">
        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-file-edit-line text-4xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada tes koran</h3>
        <p class="text-gray-400 text-sm mb-6">Tes Koran akan muncul di sini setelah kamu memiliki akses.</p>
        <a href="{{ route('user.package.index') }}" class="inline-flex items-center px-6 py-3 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
            <i class="ri-store-3-line mr-2"></i>Lihat Paket
        </a>
    </div>
    @endif
@endif
@endsection
