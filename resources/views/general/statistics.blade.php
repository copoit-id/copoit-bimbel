@extends('general.layout')

@section('title', $title)

@php
    $selectionPath = $selectionPath ?? 'snbp';
    $selectionLabel = $selectionLabel ?? strtoupper($selectionPath);
    $quotaField = $quotaField ?? 'daya_tampung_snbp';
    $ptnDataUrl = $ptnDataUrl ?? route('statistics.proxy.ptn');
    $prodiDataUrl = $prodiDataUrl ?? route('statistics.proxy.prodi');
@endphp

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
    /* Custom scrollbar for PTN list */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #cbd5e1;
    }
</style>
@endpush

@section('content')
<section class="relative overflow-hidden bg-slate-950 py-14 text-white sm:py-16">
    <!-- Background overlay patterns -->
    <div class="absolute inset-0 opacity-[0.03] bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="absolute -top-40 -right-40 h-96 w-96 rounded-full bg-primary/10 blur-3xl"></div>
    <div class="absolute -bottom-40 -left-40 h-96 w-96 rounded-full bg-primary/10 blur-3xl"></div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-[11px] font-medium text-primary-light border border-primary/20">
            <i class="ri-line-chart-line"></i>
            Analisis Daya Tampung & Keketatan {{ $selectionLabel }}
        </span>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">
            Statistik PTN {{ $selectionLabel }} & Keketatan Prodi
        </h1>
        <p class="mt-3 max-w-2xl mx-auto text-sm sm:text-base leading-relaxed text-slate-400">
            Pantau kuota daya tampung terbaru dan persentase keketatan penerimaan program studi untuk menyusun strategi kelulusan pilihan kuliahmu.
        </p>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="ptnStats()">
    <!-- Main layout grid -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
        
        <!-- Sidebar: PTN List (Left Column) -->
        <div class="lg:col-span-4 flex flex-col gap-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 space-y-4">
                <h3 class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                    <i class="ri-search-2-line text-primary"></i>
                    Cari Universitas
                </h3>
                
                <!-- Search Input -->
                <div class="relative">
                    <input type="text" 
                           x-model="searchQuery" 
                           @input="applyFilters()"
                           placeholder="Masukkan nama universitas..." 
                           class="w-full rounded-xl border border-slate-200 pl-10 pr-4 py-2.5 text-xs focus:border-primary focus:ring-2 focus:ring-primary/20 text-slate-800 placeholder-slate-400">
                    <i class="ri-search-line absolute left-3.5 top-3 text-slate-400 text-sm"></i>
                </div>

                <!-- Filter Tabs -->
                <div class="space-y-1.5 pt-1">
                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">Tipe PTN</span>
                    <div class="flex flex-wrap gap-1.5">
                        <button @click="filterType = 'all'; applyFilters()" 
                                :class="filterType === 'all' ? 'bg-primary text-white border-primary' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border-slate-200/60'"
                                class="rounded-lg border px-2.5 py-1 text-xs font-semibold transition-all">
                            Semua
                        </button>
                        <button @click="filterType = 'ptnbh'; applyFilters()" 
                                :class="filterType === 'ptnbh' ? 'bg-primary text-white border-primary' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border-slate-200/60'"
                                class="rounded-lg border px-2.5 py-1 text-xs font-semibold transition-all">
                            PTN-BH
                        </button>
                        <button @click="filterType = 'vokasi'; applyFilters()" 
                                :class="filterType === 'vokasi' ? 'bg-primary text-white border-primary' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border-slate-200/60'"
                                class="rounded-lg border px-2.5 py-1 text-xs font-semibold transition-all">
                            Vokasi
                        </button>
                        <button @click="filterType = 'ptkin'; applyFilters()" 
                                :class="filterType === 'ptkin' ? 'bg-primary text-white border-primary' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border-slate-200/60'"
                                class="rounded-lg border px-2.5 py-1 text-xs font-semibold transition-all">
                            PTKIN
                        </button>
                    </div>
                </div>
            </div>

            <!-- List scroll container -->
            <div class="rounded-2xl border border-slate-200 bg-white p-2 flex-1 min-h-[400px] lg:max-h-[600px] flex flex-col">
                <!-- Loader inside list -->
                <div x-show="isLoadingPtn" class="flex flex-col items-center justify-center p-12 text-slate-400 flex-1">
                    <svg class="animate-spin h-7 w-7 text-primary mb-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-xs font-medium">Memuat data PTN...</p>
                </div>

                <!-- Error loading PTN -->
                <div x-show="ptnError" class="p-8 text-center flex-1 flex flex-col items-center justify-center text-slate-400" style="display: none;">
                    <i class="ri-error-warning-line text-3xl text-rose-500 mb-2"></i>
                    <p class="text-xs font-semibold text-slate-700" x-text="ptnError"></p>
                    <button @click="init()" class="mt-3 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-white hover:bg-primary/90 transition-colors">
                        Coba Lagi
                    </button>
                </div>

                <!-- Scrollable list of PTNs -->
                <div x-show="!isLoadingPtn && !ptnError" class="overflow-y-auto custom-scrollbar flex-1 space-y-0.5 p-1">
                    <template x-for="ptn in filteredPtnList" :key="ptn.id_ptn">
                        <button @click="selectPtn(ptn)"
                                :class="selectedPtn && selectedPtn.id_ptn === ptn.id_ptn ? 'bg-primary/5 border-primary/20 text-primary font-semibold' : 'border-transparent text-slate-600 hover:bg-slate-50/60'"
                                class="w-full text-left rounded-xl border px-4 py-3 transition-all duration-200 flex items-center justify-between gap-3 group">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800 group-hover:text-primary transition-colors leading-tight" x-text="ptn.nama"></p>
                                <p class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                                    <i class="ri-map-pin-line"></i>
                                    <span x-text="ptn.provinsi[0]?.nama_prov1 || 'Indonesia'"></span>
                                </p>
                            </div>
                            <i class="ri-arrow-right-s-line text-slate-400 group-hover:translate-x-0.5 transition-transform"></i>
                        </button>
                    </template>

                    <!-- Empty result state -->
                    <div x-show="filteredPtnList.length === 0" class="text-center py-16 text-slate-400" style="display: none;">
                        <i class="ri-find-replace-line text-3xl mb-1.5 block"></i>
                        <p class="text-xs font-semibold">Tidak ditemukan PTN</p>
                        <p class="text-[11px] mt-0.5 text-slate-400">Gunakan kata kunci pencarian yang lain.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard View: Program Studi (Right Column) -->
        <div class="lg:col-span-8">
            
            <!-- Default Welcome State (No PTN selected) -->
            <div x-show="!selectedPtn" class="rounded-2xl border border-slate-200 bg-white p-12 text-center min-h-[500px] flex flex-col justify-center items-center gap-4">
                <div class="h-16 w-16 rounded-full bg-slate-50 border border-slate-200 text-slate-400 flex items-center justify-center">
                    <i class="ri-dashboard-3-line text-3xl text-primary/60"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900">Analisis Keketatan Penerimaan</h3>
                <p class="text-xs text-slate-500 max-w-sm">
                    Pilih salah satu Perguruan Tinggi Negeri di sidebar kiri untuk menampilkan rincian daya tampung kuota program studi serta kalkulasi keketatan persaingannya.
                </p>
                <div class="mt-2 flex flex-wrap gap-1.5 justify-center max-w-lg">
                    <span class="text-[11px] font-medium text-slate-500 bg-slate-50 border border-slate-100 px-3 py-1 rounded-md flex items-center gap-1"><i class="ri-check-line text-emerald-500"></i> Kuota {{ $selectionLabel }}</span>
                    <span class="text-[11px] font-medium text-slate-500 bg-slate-50 border border-slate-100 px-3 py-1 rounded-md flex items-center gap-1"><i class="ri-check-line text-emerald-500"></i> Histori Peminat</span>
                    <span class="text-[11px] font-medium text-slate-500 bg-slate-50 border border-slate-100 px-3 py-1 rounded-md flex items-center gap-1"><i class="ri-check-line text-emerald-500"></i> Persentase Lolos</span>
                </div>
            </div>

            <!-- Active Dashboard State (PTN Selected) -->
            <div x-show="selectedPtn" class="space-y-5" style="display: none;">
                
                <!-- PTN Header Info Card -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-1">
                            <span class="inline-flex items-center gap-1 rounded bg-slate-50 px-2 py-0.5 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                                Universitas Terpilih
                            </span>
                            <h2 class="text-xl font-bold text-slate-800" x-text="selectedPtn?.nama"></h2>
                            <p class="text-xs text-slate-400 flex items-center gap-1">
                                <i class="ri-map-pin-2-line"></i>
                                <span x-text="selectedPtn?.alamat || 'Alamat tidak tersedia'"></span>
                            </p>
                        </div>
                        <template x-if="selectedPtn?.web">
                            <a :href="selectedPtn.web" 
                               target="_blank" 
                               rel="noopener noreferrer" 
                               class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline bg-primary/5 px-3 py-1.5 rounded-lg border border-primary/20 transition-all">
                                <i class="ri-global-line"></i>
                                Website PMB
                            </a>
                        </template>
                    </div>

                    <!-- Type Badges Row -->
                    <div class="flex flex-wrap gap-1.5 pt-3 border-t border-slate-100/80">
                        <template x-if="selectedPtn?.is_ptnbh === 1">
                            <span class="rounded bg-indigo-50 border border-indigo-100/50 px-2 py-0.5 text-[10px] font-medium text-indigo-600">PTN-BH</span>
                        </template>
                        <template x-if="selectedPtn?.is_vokasi === 1">
                            <span class="rounded bg-sky-50 border border-sky-100/50 px-2 py-0.5 text-[10px] font-medium text-sky-600">Vokasi</span>
                        </template>
                        <template x-if="selectedPtn?.is_ptkin === 1">
                            <span class="rounded bg-teal-50 border border-teal-100/50 px-2 py-0.5 text-[10px] font-medium text-teal-600">PTKIN</span>
                        </template>
                        <template x-if="selectedPtn?.is_akademik === 1">
                            <span class="rounded bg-emerald-50 border border-emerald-100/50 px-2 py-0.5 text-[10px] font-medium text-emerald-600">Akademik</span>
                        </template>
                    </div>
                </div>

                <!-- Prodi Loading State -->
                <div x-show="isLoadingProdi" class="rounded-2xl border border-slate-200 bg-white p-20 text-center flex flex-col items-center justify-center">
                    <svg class="animate-spin h-8 w-8 text-primary mb-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <h4 class="text-sm font-semibold text-slate-800">Memuat Program Studi...</h4>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="'Mengunduh data prodi untuk ' + selectedPtn?.nama"></p>
                </div>

                <!-- Prodi Error State -->
                <div x-show="prodiError" class="rounded-2xl border border-slate-200 bg-white p-16 text-center flex flex-col items-center justify-center text-slate-400" style="display: none;">
                    <i class="ri-error-warning-line text-4xl text-rose-500 mb-2"></i>
                    <h4 class="text-sm font-semibold text-slate-800">Request Gagal</h4>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="prodiError"></p>
                    <button @click="selectPtn(selectedPtn)" class="mt-4 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-white hover:bg-primary/90 transition-colors">
                        Muat Ulang
                    </button>
                </div>

                <!-- Prodi Content Container -->
                <div x-show="!isLoadingProdi && !prodiError" class="space-y-4" style="display: none;">
                    
                    <!-- Search & Sort Row -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-between items-stretch sm:items-center bg-white p-4 rounded-2xl border border-slate-200">
                        <div class="relative flex-1">
                            <input type="text" 
                                   x-model="prodiSearchQuery" 
                                   @input="applyProdiFilters()"
                                   placeholder="Cari program studi (cth: Kedokteran, Sipil)..." 
                                   class="w-full rounded-xl border border-slate-200 pl-9 pr-4 py-2 text-xs focus:border-primary focus:ring-2 focus:ring-primary/20 text-slate-800 placeholder-slate-400">
                            <i class="ri-search-line absolute left-3 top-2.5 text-slate-400 text-sm"></i>
                        </div>
                        
                        <!-- Sort Controls -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-400 shrink-0 font-medium">Urutkan:</span>
                            <div class="flex items-center rounded-xl border border-slate-200/80 p-0.5 bg-slate-50">
                                <button @click="sortBy = 'keketatan'; sortDirection = 'asc'; applyProdiFilters()" 
                                        :class="sortBy === 'keketatan' ? 'bg-white text-primary font-semibold border-slate-250' : 'text-slate-500 hover:bg-slate-100 border-transparent'"
                                        class="rounded-lg border px-2.5 py-1 text-xs transition-all flex items-center gap-0.5">
                                    Keketatan
                                    <i class="ri-arrow-up-line text-2xs" x-show="sortBy === 'keketatan' && sortDirection === 'asc'"></i>
                                </button>
                                <button @click="sortBy = 'daya_tampung'; sortDirection = 'desc'; applyProdiFilters()" 
                                        :class="sortBy === 'daya_tampung' ? 'bg-white text-primary font-semibold border-slate-250' : 'text-slate-500 hover:bg-slate-100 border-transparent'"
                                        class="rounded-lg border px-2.5 py-1 text-xs transition-all flex items-center gap-0.5">
                                    Kuota
                                    <i class="ri-arrow-down-line text-2xs" x-show="sortBy === 'daya_tampung' && sortDirection === 'desc'"></i>
                                </button>
                                <button @click="sortBy = 'peminat'; sortDirection = 'desc'; applyProdiFilters()" 
                                        :class="sortBy === 'peminat' ? 'bg-white text-primary font-semibold border-slate-250' : 'text-slate-500 hover:bg-slate-100 border-transparent'"
                                        class="rounded-lg border px-2.5 py-1 text-xs transition-all flex items-center gap-0.5">
                                    Peminat
                                    <i class="ri-arrow-down-line text-2xs" x-show="sortBy === 'peminat' && sortDirection === 'desc'"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Prodi List Grid / Table -->
                    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                        
                        <!-- Table Head (Desktop) -->
                        <div class="hidden md:grid grid-cols-12 gap-4 bg-slate-50/50 px-6 py-3 border-b border-slate-100 text-[11px] font-medium uppercase tracking-wider text-slate-400">
                            <div class="col-span-2">Kode</div>
                            <div class="col-span-4">Program Studi</div>
                            <div class="col-span-2 text-center">Kuota (2025)</div>
                            <div class="col-span-2 text-center">Peminat Terbaru</div>
                            <div class="col-span-2 text-center">Keketatan</div>
                        </div>

                        <!-- Table Body Rows -->
                        <div class="divide-y divide-slate-100/70">
                            <template x-for="prodi in filteredProdiList" :key="prodi.id_prodi">
                                <div class="transition-colors duration-150 hover:bg-slate-50/40">
                                    
                                    <!-- Row Content Trigger -->
                                    <button @click="openProdiDetail(prodi)"
                                            class="w-full text-left px-5 py-3.5 grid grid-cols-1 md:grid-cols-12 gap-2.5 md:gap-4 items-center focus:outline-none select-none group">
                                        
                                        <!-- Code -->
                                        <div class="col-span-1 md:col-span-2">
                                            <span class="text-xs font-mono font-medium text-slate-400 bg-slate-50/70 border border-slate-200/50 px-1.5 py-0.5 rounded" x-text="prodi.kode_prodi"></span>
                                        </div>

                                        <!-- Name & Jenjang Badge -->
                                        <div class="col-span-1 md:col-span-4 space-y-1.5">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-semibold text-slate-800 leading-snug group-hover:text-primary transition-colors" x-text="prodi.nama"></span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="inline-flex items-center rounded bg-slate-100/80 px-1.5 py-0.5 text-[9px] font-semibold text-slate-500 uppercase tracking-wide" x-text="prodi.jenjang"></span>
                                                <template x-if="prodi.is_new === 1">
                                                    <span class="rounded bg-emerald-50 border border-emerald-100/50 px-1.5 py-0.5 text-[9px] font-semibold text-emerald-700 tracking-wide">BARU</span>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Daya Tampung -->
                                        <div class="col-span-1 md:col-span-2 text-left md:text-center flex md:block justify-between items-center text-xs">
                                            <span class="text-slate-400 font-medium md:hidden">Kuota 2025:</span>
                                            <span class="font-semibold text-slate-700 text-sm" x-text="prodi.latest_daya_tampung"></span>
                                        </div>

                                        <!-- Peminat -->
                                        <div class="col-span-1 md:col-span-2 text-left md:text-center flex md:block justify-between items-center text-xs">
                                            <span class="text-slate-400 font-medium md:hidden">Peminat terbaru:</span>
                                            <span class="font-semibold text-slate-600 text-sm" x-text="prodi.latest_peminat || '0'"></span>
                                        </div>

                                        <!-- Keketatan badge -->
                                        <div class="col-span-1 md:col-span-2 text-left md:text-center flex md:block justify-between items-center">
                                            <span class="text-slate-400 font-medium md:hidden">Keketatan:</span>
                                            <span :class="getKeketatanLabel(prodi.keketatan).class"
                                                  class="inline-flex items-center rounded-md border px-2 py-0.5 text-[11px] font-medium leading-none"
                                                  x-text="getKeketatanLabel(prodi.keketatan).text">
                                            </span>
                                        </div>
                                    </button>

                                </div>
                            </template>
                        </div>

                        <!-- Empty prodi list result -->
                        <div x-show="filteredProdiList.length === 0" class="text-center py-16 text-slate-400" style="display: none;">
                            <i class="ri-survey-line text-4xl mb-1.5 block"></i>
                            <p class="text-xs font-semibold">Tidak ditemukan program studi</p>
                            <p class="text-[11px] mt-0.5 text-slate-400">Sesuaikan kata kunci pencarian program studi Anda.</p>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
    
    <!-- Modal Dialog Popup (Detail Prodi Statistics) -->
    <div x-show="isProdiModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" 
         style="display: none;"
         role="dialog"
         aria-modal="true">
        
        <!-- Backdrop Overlay -->
        <div x-show="isProdiModalOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeProdiDetail()"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        
        <!-- Modal Panel Box -->
        <div x-show="isProdiModalOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative w-full max-w-2xl transform rounded-2xl bg-white p-6 border-2 border-slate-200 transition-all flex flex-col max-h-[85vh] sm:max-h-[90vh]">
            
            <!-- Close Icon Top Right -->
            <button @click="closeProdiDetail()" 
                    class="absolute right-4 top-4 rounded-lg p-1.5 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
            
            <!-- Header -->
            <div class="border-b border-slate-100 pb-4 pr-8">
                <span class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 uppercase tracking-wide" x-text="selectedProdi?.jenjang"></span>
                <h3 class="text-lg sm:text-xl font-bold text-slate-900 mt-2 leading-tight" x-text="selectedProdi?.nama"></h3>
                <p class="text-sm text-slate-500 mt-1.5 font-mono">
                    Kode Prodi: <span class="text-slate-750 font-semibold" x-text="selectedProdi?.kode_prodi"></span>
                </p>
            </div>
            
            <!-- Body (Scrollable content) -->
            <div class="overflow-y-auto custom-scrollbar py-5 space-y-6 flex-1 pr-1">
                <div class="space-y-6">
                    
                    <!-- Stats Table History -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                            <i class="ri-history-line"></i>
                            Riwayat Daya Tampung & Peminat
                        </h4>
                        <div class="rounded-xl border border-slate-100 bg-white overflow-hidden">
                           <table class="w-full text-left border-collapse text-xs sm:text-sm">
                               <thead>
                                   <tr class="bg-slate-50/50 border-b border-slate-100 font-semibold text-slate-500">
                                       <th class="px-3 py-2.5">Tahun</th>
                                       <th class="px-3 py-2.5 text-center">Kuota</th>
                                       <th class="px-3 py-2.5 text-center">Peminat</th>
                                       <th class="px-3 py-2.5 text-center">Keketatan</th>
                                   </tr>
                               </thead>
                               <tbody class="divide-y divide-slate-100/50 font-medium text-slate-650 text-xs sm:text-sm">
                                   <template x-for="hist in selectedProdi?.history_daya_tampung" :key="hist.tahun">
                                       <tr class="hover:bg-slate-50/30">
                                           <td class="px-3 py-2.5 text-slate-800 font-semibold" x-text="hist.tahun"></td>
                                           <td class="px-3 py-2.5 text-center" x-text="hist.daya_tampung"></td>
                                           <td class="px-3 py-2.5 text-center" x-text="hist.peminat"></td>
                                           <td class="px-3 py-2.5 text-center font-semibold text-slate-900"
                                               :class="hist.peminat > 0 ? (hist.daya_tampung / hist.peminat < 0.05 ? 'text-rose-600' : (hist.daya_tampung / hist.peminat < 0.1 ? 'text-amber-600' : 'text-emerald-600')) : ''"
                                               x-text="hist.peminat > 0 ? ((hist.daya_tampung / hist.peminat) * 100).toFixed(2) + '%' : 'N/A'">
                                           </td>
                                       </tr>
                                   </template>
                               </tbody>
                           </table>
                        </div>
                    </div>

                    <!-- Visual Chart (HTML Bars) -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                            <i class="ri-bar-chart-fill"></i>
                            Grafik Tren Peminat (2021 - 2025)
                        </h4>
                        <div class="rounded-xl border border-slate-100 bg-white p-4 space-y-4">
                            <div class="space-y-2.5" x-data="{
                                maxPeminat() {
                                    if (!selectedProdi || !selectedProdi.history_daya_tampung) return 1;
                                    return Math.max(...selectedProdi.history_daya_tampung.map(h => h.peminat || 1));
                                }
                            }">
                                <template x-for="hist in selectedProdi?.history_daya_tampung" :key="hist.tahun">
                                    <div class="flex items-center gap-3">
                                        <span class="w-10 text-xs sm:text-sm font-semibold text-slate-500 text-left" x-text="hist.tahun"></span>
                                        
                                        <div class="flex-1 h-6 bg-primary/10 rounded-lg overflow-hidden relative border border-primary/20">
                                            <!-- Background text (visible on the light track) -->
                                            <span class="absolute inset-y-0 left-3.5 flex items-center text-xs font-semibold text-primary/80"
                                                  x-text="hist.peminat + ' Peminat'"></span>
                                            
                                            <!-- Filled bar (covers the background text) -->
                                            <div class="absolute inset-y-0 left-0 bg-primary transition-all duration-500 rounded-r-md overflow-hidden"
                                                 :style="'width: ' + ((hist.peminat / maxPeminat()) * 100) + '%'">
                                                <!-- Foreground text (visible only on the dark primary bar) -->
                                                <span class="absolute inset-y-0 left-3.5 flex items-center text-xs font-semibold text-white whitespace-nowrap"
                                                      x-text="hist.peminat + ' Peminat'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
           
            <!-- Footer -->
            <div class="border-t border-slate-100 pt-4 flex justify-end">
                <button @click="closeProdiDetail()" 
                        class="rounded-xl bg-slate-100 hover:bg-slate-200/80 text-slate-700 px-6 py-2.5 text-xs sm:text-sm font-semibold transition-colors">
                    Tutup
                </button>
            </div>
            
        </div>
    </div>
</section>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('ptnStats', () => ({
        ptnDataUrl: @js($ptnDataUrl),
        prodiDataUrl: @js($prodiDataUrl),
        quotaField: @js($quotaField),
        ptnList: [],
        filteredPtnList: [],
        selectedPtn: null,
        prodiList: [],
        filteredProdiList: [],
        
        searchQuery: '',
        prodiSearchQuery: '',
        filterType: 'all', 
        sortBy: 'keketatan', 
        sortDirection: 'asc',
        
        isLoadingPtn: false,
        isLoadingProdi: false,
        ptnError: null,
        prodiError: null,
        
        prodiCache: {}, 
        isProdiModalOpen: false,
        selectedProdi: null,

        async init() {
            this.isLoadingPtn = true;
            this.ptnError = null;
            try {
                const res = await fetch(this.ptnDataUrl);
                if (!res.ok) throw new Error('Gagal memuat data PTN');
                this.ptnList = await res.json();
                
                // Sort PTN list alphabetically by default
                this.ptnList.sort((a, b) => a.nama.localeCompare(b.nama));
                
                this.applyFilters();
            } catch (err) {
                console.error(err);
                this.ptnError = 'Gagal memuat data dari server SNPMB. Pastikan Anda terhubung ke internet.';
            } finally {
                this.isLoadingPtn = false;
            }
        },

        applyFilters() {
            let list = [...this.ptnList];

            // Apply search query
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(ptn => ptn.nama.toLowerCase().includes(q));
            }

            // Apply institution type filter
            if (this.filterType === 'ptnbh') {
                list = list.filter(ptn => ptn.is_ptnbh === 1);
            } else if (this.filterType === 'vokasi') {
                list = list.filter(ptn => ptn.is_vokasi === 1);
            } else if (this.filterType === 'ptkin') {
                list = list.filter(ptn => ptn.is_ptkin === 1);
            } else if (this.filterType === 'akademik') {
                list = list.filter(ptn => ptn.is_akademik === 1);
            }

            this.filteredPtnList = list;
        },

        async selectPtn(ptn) {
            this.selectedPtn = ptn;
            this.prodiList = [];
            this.filteredProdiList = [];
            this.prodiSearchQuery = '';
            this.selectedProdi = null;
            this.isProdiModalOpen = false;
            this.prodiError = null;

            const ptnId = ptn.id_ptn;

            // Check Cache
            if (this.prodiCache[ptnId]) {
                this.prodiList = this.prodiCache[ptnId];
                this.applyProdiFilters();
                return;
            }

            this.isLoadingProdi = true;
            try {
                const res = await fetch(`${this.prodiDataUrl}?ptn=${ptnId}`);
                if (!res.ok) throw new Error('Gagal memuat program studi');
                const data = await res.json();
                
                // Parse and enrich prodi data
                this.prodiList = data.map(prodi => {
                    const history = prodi.history_daya_tampung || [];
                    
                    // Sort history by year ascending just to make sure
                    history.sort((a, b) => a.tahun - b.tahun);
                    
                    // Find latest year info (e.g. 2025)
                    const latest = history.find(h => h.tahun === 2025) || history[history.length - 1] || null;
                    
                    const dayaTampung = latest ? latest.daya_tampung : parseInt(prodi[this.quotaField] || 0);
                    const peminat = latest ? latest.peminat : 0;
                    const keketatan = peminat > 0 ? (dayaTampung / peminat) * 100 : 0;

                    return {
                        ...prodi,
                        latest_daya_tampung: dayaTampung,
                        latest_peminat: peminat,
                        keketatan: keketatan
                    };
                });

                // Cache it
                this.prodiCache[ptnId] = this.prodiList;
                this.applyProdiFilters();
            } catch (err) {
                console.error(err);
                this.prodiError = 'Gagal memuat daftar program studi dari server SNPMB.';
            } finally {
                this.isLoadingProdi = false;
            }
        },

        applyProdiFilters() {
            let list = [...this.prodiList];

            // Apply search query
            if (this.prodiSearchQuery.trim() !== '') {
                const q = this.prodiSearchQuery.toLowerCase();
                list = list.filter(prodi => prodi.nama.toLowerCase().includes(q) || String(prodi.kode_prodi).includes(q));
            }

            // Apply sorting
            list.sort((a, b) => {
                let valA, valB;
                
                if (this.sortBy === 'nama') {
                    valA = a.nama;
                    valB = b.nama;
                } else if (this.sortBy === 'daya_tampung') {
                    valA = a.latest_daya_tampung;
                    valB = b.latest_daya_tampung;
                } else if (this.sortBy === 'peminat') {
                    valA = a.latest_peminat;
                    valB = b.latest_peminat;
                } else if (this.sortBy === 'keketatan') {
                    valA = a.keketatan || 100;
                    valB = b.keketatan || 100;
                }

                if (typeof valA === 'string') {
                    return this.sortDirection === 'asc' 
                        ? valA.localeCompare(valB) 
                        : valB.localeCompare(valA);
                } else {
                    return this.sortDirection === 'asc'
                        ? valA - valB
                        : valB - valA;
                }
            });

            this.filteredProdiList = list;
        },

        openProdiDetail(prodi) {
            this.selectedProdi = prodi;
            this.isProdiModalOpen = true;
            document.body.classList.add('overflow-hidden');
        },

        closeProdiDetail() {
            this.isProdiModalOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        getKeketatanLabel(keketatan) {
            if (keketatan === 0) return { text: 'N/A', class: 'bg-slate-50 text-slate-400 border-slate-200/50' };
            if (keketatan < 5) return { text: `${keketatan.toFixed(2)}%`, class: 'bg-rose-50/70 text-rose-600 border-rose-100/50' };
            if (keketatan < 10) return { text: `${keketatan.toFixed(2)}%`, class: 'bg-amber-50/70 text-amber-600 border-amber-100/50' };
            return { text: `${keketatan.toFixed(2)}%`, class: 'bg-emerald-50/70 text-emerald-600 border-emerald-100/50' };
        }
    }));
});
</script>
@endsection
