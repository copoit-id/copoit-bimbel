@extends('user.layout.new-user')

@section('title', 'Dashboard')

@section('content')
@php
$user = auth()->user();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$isGuest = !$user;

// Convert hex primary color to RGB for opacity adjustments
$primaryHex = str_replace('#', '', $primaryColor);
if (strlen($primaryHex) == 3) {
    $r = hexdec(substr($primaryHex, 0, 1) . substr($primaryHex, 0, 1));
    $g = hexdec(substr($primaryHex, 1, 1) . substr($primaryHex, 1, 1));
    $b = hexdec(substr($primaryHex, 2, 1) . substr($primaryHex, 2, 1));
} else {
    $r = hexdec(substr($primaryHex, 0, 2));
    $g = hexdec(substr($primaryHex, 2, 2));
    $b = hexdec(substr($primaryHex, 4, 2));
}
$primaryRgb = "$r, $g, $b";
@endphp

<!-- Welcome Card -->
<div class="relative overflow-hidden rounded-[2rem] p-6 mb-6 border border-gray-100" style="background-color: rgba({{ $primaryRgb }}, 0.055); box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.02);">
    <!-- Ambient glowing mesh backdrops using the brand primary color to maintain brand consistency without clashing -->
    <div class="absolute -left-16 -top-16 w-64 h-64 rounded-full blur-3xl pointer-events-none" style="background-color: {{ $primaryColor }}; opacity: 0.07;"></div>
    <div class="absolute -right-16 -bottom-16 w-64 h-64 rounded-full blur-3xl pointer-events-none" style="background-color: {{ $primaryColor }}; opacity: 0.05;"></div>
    
    <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        <!-- Left details -->
        <div class="lg:col-span-7 flex flex-col justify-between">
            <div class="space-y-6">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                        Halo, {{ $isGuest ? 'Pejuang PTN' : $user->name }}! 👋
                    </h1>
                    <p class="text-gray-500 font-medium mt-1">Mau Belajar Apa Hari Ini?</p>
                </div>

                <!-- Inner Cards Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Target PTN Card -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 flex flex-col justify-between relative overflow-hidden group min-h-[160px] shadow-[0_8px_30px_rgb(0,0,0,0.035)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] transition-all duration-300">
                        <!-- Decorative university watermark -->
                        <div class="absolute right-2 bottom-2 pointer-events-none" style="color: {{ $primaryColor }}; opacity: 0.06;">
                            <i class="ri-bank-line text-8xl"></i>
                        </div>
                        
                        <div class="relative z-10">
                            <div class="flex items-center gap-2 text-gray-500 font-semibold text-xs mb-3">
                                <div class="w-6 h-6 rounded-lg flex items-center justify-center" style="background-color: {{ $primaryColor }}15; color: {{ $primaryColor }};">
                                    <i class="ri-graduation-cap-line text-sm"></i>
                                </div>
                                <span class="font-bold text-gray-600">Target PTN</span>
                            </div>
                            
                            @php
                                $targetUniversity = null;
                                $targetMajor = null;
                                if (!$isGuest) {
                                    if ($user->participantDestinationCategory) {
                                        $targetUniversity = $user->participantDestinationCategory->parent->name ?? $user->participantDestinationCategory->name;
                                        $targetMajor = $user->participantDestinationCategory->parent ? $user->participantDestinationCategory->name : null;
                                    } else {
                                        $targetUniversity = $user->participant_destination_institution_name;
                                        $targetMajor = $user->participant_destination_program_name;
                                    }
                                }
                            @endphp

                            <h3 class="font-extrabold text-gray-950 text-base leading-snug truncate">
                                {{ $targetUniversity ?: 'Belum Memilih PTN' }}
                            </h3>
                            <p class="text-xs text-gray-500 font-semibold mt-0.5 truncate">
                                {{ $targetMajor ?: 'Pilih jurusan impianmu' }}
                            </p>
                        </div>

                        <div class="relative z-10 pt-4 mt-auto">
                            @if($isGuest)
                                <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 text-xs font-bold text-white rounded-xl transition-opacity hover:opacity-90 shadow-sm shadow-emerald-500/20" style="background-color: {{ $primaryColor }}">
                                    Masuk / Daftar
                                </a>
                            @else
                                <a href="{{ route('user.profile.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-bold rounded-xl transition-colors border" style="color: {{ $primaryColor }}; background-color: {{ $primaryColor }}10; border-color: {{ $primaryColor }}25;">
                                    Ubah Target
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Peluang Stack -->
                    <div class="flex flex-col gap-3">
                        <!-- SNBP Card -->
                        <div class="bg-white rounded-2xl p-4 border border-gray-100 flex items-center justify-between transition-all hover:bg-slate-50/50 shadow-[0_8px_30px_rgb(0,0,0,0.035)]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                    <i class="ri-line-chart-line text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm">SNBP</h4>
                                    <p class="text-[10px] text-gray-400 font-semibold">Tingkat Peluang</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 text-xs font-bold text-emerald-700 bg-emerald-50 rounded-lg">
                                Tinggi
                            </span>
                        </div>

                        <!-- SNBT Card -->
                        <div class="bg-white rounded-2xl p-4 border border-gray-100 flex items-center justify-between transition-all hover:bg-slate-50/50 shadow-[0_8px_30px_rgb(0,0,0,0.035)]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                                    <i class="ri-pulse-line text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm">SNBT</h4>
                                    <p class="text-[10px] text-gray-400 font-semibold">Tingkat Peluang</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 text-xs font-bold text-amber-700 bg-amber-50 rounded-lg">
                                Sedang
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right banner image -->
        <div class="lg:col-span-5">
            <div class="relative overflow-hidden rounded-2xl h-full min-h-[220px] flex flex-col justify-end p-6 text-white group border" style="border-color: {{ $primaryColor }}15;">
                <!-- Image background -->
                <img src="{{ asset('img/dashboard_statistics_banner.png') }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Statistik PTN">
                <!-- Dark/gradient overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/35 to-transparent"></div>
                
                <!-- Content over image -->
                <div class="relative z-10 space-y-2">
                    <a href="{{ route('statistics') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs sm:text-sm transition-all w-full justify-center sm:w-auto">
                        Cek Statistik SNBP & SNBT
                        <i class="ri-arrow-right-line text-sm"></i>
                    </a>
                    <p class="text-xs text-gray-200 font-medium">Temukan PTN terbaik yang sesuai dengan peluangmu</p>
                </div>
            </div>
        </div>
    </div>
</div>

@if(!$isGuest)
<!-- Akses Cepat -->
<div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
    <a href="{{ route('user.material.videos') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg transition-all group">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white mb-3" style="background-color: {{ $primaryColor }}">
            <i class="ri-video-line text-xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Video Materi</h3>
    </a>
    
    <a href="{{ route('user.package.tryout.list') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg transition-all group">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white mb-3" style="background-color: {{ $primaryColor }}">
            <i class="ri-file-list-3-line text-xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Tryout</h3>
    </a>
    
    <a href="{{ route('user.package.my') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg transition-all group">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white mb-3" style="background-color: {{ $primaryColor }}">
            <i class="ri-road-map-line text-xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Paket Saya</h3>
    </a>
    
    <a href="{{ route('user.package.index') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg transition-all group">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white mb-3" style="background-color: {{ $primaryColor }}">
            <i class="ri-store-3-line text-xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Beli Paket</h3>
    </a>

    <a href="{{ route('user.class-schedule.index') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg transition-all group">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white mb-3" style="background-color: {{ $primaryColor }}">
            <i class="ri-calendar-check-line text-xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Jadwal Kelas</h3>
    </a>

    <a href="{{ route('user.billing.index') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg transition-all group">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white mb-3" style="background-color: {{ $primaryColor }}">
            <i class="ri-bill-line text-xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Tagihan</h3>
    </a>
</div>

@php
    $hasUnpaid = ($unpaidInvoices ?? collect())->isNotEmpty();
    $hasSessions = ($upcomingClassSessions ?? collect())->isNotEmpty();
@endphp

@if($hasUnpaid)
<div class="bg-white rounded-2xl p-6 border border-gray-100 mb-6">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-red-50 text-red-500">
                <i class="ri-wallet-3-line text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 text-lg">Pengingat Tagihan</h3>
                <p class="text-xs text-gray-400">Harap selesaikan pembayaran sebelum jatuh tempo</p>
            </div>
        </div>
        <a href="{{ route('user.billing.index') }}" class="text-sm font-semibold hover:underline flex items-center gap-1 shrink-0" style="color: {{ $primaryColor }}">
            Lihat semua <i class="ri-arrow-right-s-line text-base"></i>
        </a>
    </div>
    
    <div class="space-y-3">
        @foreach($unpaidInvoices as $invoice)
        <div class="group flex items-center justify-between rounded-2xl border border-gray-100 bg-gray-50/60 p-4 hover:bg-white hover:border-red-150 hover:shadow-lg hover:shadow-red-50/30 transition-all-300">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-105 transition-transform shrink-0">
                    <i class="ri-error-warning-line text-lg"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-bold text-gray-800 text-sm leading-snug truncate group-hover:text-red-650 transition-colors">{{ $invoice->title }}</p>
                    <p class="text-xs text-gray-450 mt-0.5">Jatuh tempo: <span class="font-semibold text-red-500">{{ $invoice->due_date->translatedFormat('d M Y') }}</span></p>
                </div>
            </div>
            <div class="text-right flex flex-col items-end gap-1.5 shrink-0 ml-3">
                <p class="font-extrabold text-gray-900 text-sm">Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</p>
                <a href="{{ route('user.billing.index') }}" class="px-3.5 py-1 text-[10px] font-bold text-white bg-red-500 hover:bg-red-650 rounded-lg transition-colors whitespace-nowrap">
                    Bayar
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@if($hasSessions)
<div class="bg-white rounded-2xl p-6 border border-gray-100 mb-6">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
                <i class="ri-calendar-todo-line text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 text-lg">Jadwal Terdekat</h3>
                <p class="text-xs text-gray-400">Ikuti kelas tepat waktu & jangan lupa absensi</p>
            </div>
        </div>
        <a href="{{ route('user.class-schedule.index') }}" class="text-sm font-semibold hover:underline flex items-center gap-1 shrink-0" style="color: {{ $primaryColor }}">
            Lihat semua <i class="ri-arrow-right-s-line text-base"></i>
        </a>
    </div>
    
    <div class="space-y-4">
        @foreach($upcomingClassSessions as $session)
        @php
            $attendance = $session->attendances->first();
            $setting = $session->schedule->attendanceSetting;
            $openAt = $session->start_at->copy()->subMinutes($setting?->open_minutes_before ?? 15);
            $closeAt = ($session->end_at ?? $session->start_at)->copy()->addMinutes($setting?->close_minutes_after ?? 30);
            $canAttend = now()->between($openAt, $closeAt) && !$attendance && $session->status === 'scheduled';
        @endphp
        <div class="group relative flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-gray-50/60 border border-gray-100 hover:border-gray-200 hover:bg-white hover:shadow-lg hover:shadow-gray-150/30 transition-all-300">
            <div class="flex items-center gap-4 min-w-0">
                <!-- Date Badge -->
                <div class="flex flex-col items-center justify-center w-14 h-14 shrink-0 rounded-xl bg-opacity-10 text-center" style="background-color: {{ $primaryColor }}15">
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color: {{ $primaryColor }}">{{ $session->start_at->translatedFormat('M') }}</span>
                    <span class="text-xl font-extrabold leading-none mt-1 text-gray-900">{{ $session->start_at->format('d') }}</span>
                </div>
                
                <!-- Info -->
                <div class="space-y-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Destination Category tags -->
                        @foreach($session->schedule->destinationCategories ?? [] as $cat)
                        <span class="px-2 py-0.5 text-[9px] font-bold rounded bg-gray-200 text-gray-655 uppercase tracking-wide">
                            {{ $cat->name }}
                        </span>
                        @endforeach
                        
                        @if($session->meeting_url)
                        <span class="px-2 py-0.5 text-[9px] font-bold rounded bg-blue-50 text-blue-600 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                            Online
                        </span>
                        @else
                        <span class="px-2 py-0.5 text-[9px] font-bold rounded bg-amber-50 text-amber-600 flex items-center gap-1">
                            Offline
                        </span>
                        @endif
                    </div>
                    
                    <h4 class="font-bold text-gray-800 text-sm leading-snug group-hover:text-primary transition-colors truncate max-w-sm md:max-w-md">
                        {{ $session->class->title ?? 'Kelas' }}
                    </h4>
                    
                    <div class="flex items-center gap-2.5 text-xs text-gray-500 flex-wrap">
                        <span class="flex items-center">
                            <i class="ri-calendar-event-line mr-1 text-gray-400"></i>
                            {{ $session->start_at->translatedFormat('l') }}
                        </span>
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                        <span class="flex items-center">
                            <i class="ri-time-line mr-1 text-gray-400"></i>
                            {{ $session->start_at->format('H:i') }}{{ $session->end_at ? ' - ' . $session->end_at->format('H:i') : '' }} WIB
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center gap-2.5 self-start sm:self-center w-full sm:w-auto shrink-0 sm:justify-end">
                @if($attendance)
                    <span class="px-3 py-1.5 text-xs font-semibold text-green-700 bg-green-50 rounded-xl flex items-center gap-1 shrink-0">
                        <i class="ri-checkbox-circle-fill text-base text-green-500"></i>
                        Hadir
                    </span>
                @elseif($canAttend)
                    @if(($setting?->mode ?? 'button') === 'button')
                        <form method="POST" action="{{ route('user.class-schedule.attend', $session) }}" class="w-full sm:w-auto shrink-0">
                            @csrf
                            <button class="w-full sm:w-auto px-4 py-2 text-xs font-bold text-white rounded-xl hover:opacity-90 transition-opacity bg-primary shadow-sm whitespace-nowrap">
                                Absen
                            </button>
                        </form>
                    @else
                        <a href="{{ route('user.class-schedule.index') }}" class="w-full sm:w-auto px-4 py-2 text-xs font-bold text-white rounded-xl hover:opacity-90 transition-opacity bg-primary shadow-sm text-center whitespace-nowrap shrink-0">
                            Kirim Foto
                        </a>
                    @endif
                @endif
                
                @if($session->meeting_url)
                    @php
                        $isLiveNow = now()->between($session->start_at->copy()->subMinutes(15), $session->end_at ?? $session->start_at->copy()->addHours(2));
                    @endphp
                    @if($isLiveNow)
                        <a href="{{ $session->meeting_url }}" target="_blank" class="w-full sm:w-auto px-4 py-2 text-xs font-bold text-white rounded-xl flex items-center justify-center gap-1.5 transition-all shadow-md hover:shadow-lg shadow-blue-500/20 hover:scale-105 whitespace-nowrap shrink-0" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)">
                            <i class="ri-video-chat-line text-base animate-bounce"></i>
                            Masuk Kelas
                        </a>
                    @else
                        <a href="{{ $session->meeting_url }}" target="_blank" class="w-full sm:w-auto px-4 py-2 text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl flex items-center justify-center gap-1.5 transition-colors whitespace-nowrap shrink-0">
                            <i class="ri-link-m text-base"></i>
                            Link
                        </a>
                    @endif
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Stats Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Progress Paket -->
    <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-800">Progress Belajar</h3>
            <a href="{{ route('user.package.my') }}" class="text-sm hover:underline" style="color: {{ $primaryColor }}">Lihat semua</a>
        </div>
        
        @if($activePackages->count() > 0)
        <div class="space-y-4">
            @foreach($activePackages->take(3) as $access)
            @php
            $pkg = $access->package;
            $progress = $packageProgress[$pkg->package_id] ?? 0;
            @endphp
            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white shrink-0" style="background-color: {{ $primaryColor }}">
                    <i class="ri-road-map-line text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-semibold text-gray-800 truncate">{{ $pkg->name }}</h4>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all" style="width: {{ $progress }}%; background-color: {{ $primaryColor }}"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-600 w-10 text-right">{{ $progress }}%</span>
                    </div>
                </div>
                <a href="{{ route('user.package.show', $pkg->package_id) }}" class="p-2 hover:bg-gray-200 rounded-lg transition-colors shrink-0" style="color: {{ $primaryColor }}">
                    <i class="ri-arrow-right-line"></i>
                </a>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="ri-road-map-line text-2xl text-gray-400"></i>
            </div>
            <p class="text-gray-400 text-sm mb-3">Belum ada paket aktif</p>
            <a href="{{ route('user.package.index') }}" class="inline-block px-4 py-2 rounded-lg text-sm font-medium text-white hover:opacity-90" style="background-color: {{ $primaryColor }}">
                Lihat Paket
            </a>
        </div>
        @endif
    </div>
    
    <!-- Akurasi -->
    <div class="bg-white rounded-2xl p-6 border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4">Akurasi Jawaban</h3>
        <div class="flex flex-col items-center">
            <div class="relative w-32 h-32 mb-3">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#e5e7eb" stroke-width="10"/>
                    <circle cx="50" cy="50" r="42" fill="none" stroke="{{ $primaryColor }}" stroke-width="10" 
                            stroke-dasharray="264" stroke-dashoffset="{{ 264 - (264 * $accuracyPercent / 100) }}"
                            stroke-linecap="round"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-2xl font-bold" style="color: {{ $primaryColor }}">{{ $accuracyPercent }}%</span>
                </div>
            </div>
            <p class="text-sm text-gray-500 text-center">
                {{ $totalCorrect ?? 0 }} benar dari {{ $totalAnswered ?? 0 }} soal
            </p>
        </div>
    </div>
</div>

<!-- Hasil Tryout Terakhir -->
@if($recentTryouts->count() > 0)
<div class="bg-white rounded-2xl p-6 border border-gray-100 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-gray-800">Hasil Tryout Terakhir</h3>
        <a href="{{ route('user.package.tryout.list') }}" class="text-sm hover:underline" style="color: {{ $primaryColor }}">Lihat semua</a>
    </div>
    
    <div class="space-y-3">
        @foreach($recentTryouts as $attempt)
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white shrink-0" style="background-color: {{ $primaryColor }}">
                    <i class="ri-file-list-3-line"></i>
                </div>
                <div>
                    <h5 class="font-semibold text-gray-800 text-sm">{{ $attempt->tryout->name ?? 'Tryout' }}</h5>
                    <p class="text-xs text-gray-400">{{ $attempt->created_at->diffForHumans() }}</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-lg font-bold" style="color: {{ $primaryColor }}">{{ $attempt->score ?? 0 }}</span>
                <span class="text-xs {{ ($attempt->is_passed ?? false) ? 'text-green-500' : 'text-red-500' }} block">
                    {{ ($attempt->is_passed ?? false) ? 'Lulus' : 'Belum Lulus' }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@else
<!-- Guest View -->
<div class="bg-white rounded-2xl p-6 border border-gray-100 mb-6">
    <h3 class="font-bold text-gray-800 mb-4">Paket Tersedia</h3>
    
    @if($publicPackages->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($publicPackages as $pkg)
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all group flex flex-col h-full">
            <!-- Package Image/Header -->
            <div class="h-32 relative overflow-hidden shrink-0" style="background: linear-gradient(135deg, {{ $primaryColor }}20 0%, {{ $primaryColor }}10 100%);">
                @if($pkg->image)
                @php
                    $pkgThumbExt = strtolower(pathinfo($pkg->image, PATHINFO_EXTENSION));
                    $pkgIsVideo = in_array($pkgThumbExt, ['mp4','webm','mov','m4v'], true);
                    $pkgThumbUrl = Storage::url($pkg->image);
                @endphp
                @if($pkgIsVideo)
                <video src="{{ $pkgThumbUrl }}" class="w-full h-full object-cover" controls preload="metadata" playsinline></video>
                @else
                <img src="{{ $pkgThumbUrl }}" alt="{{ $pkg->name }}" class="w-full h-full object-cover">
                @endif
                @else
                <div class="w-full h-full flex items-center justify-center">
                    <i class="ri-book-3-line text-6xl" style="color: {{ $primaryColor }}40"></i>
                </div>
                @endif

                @if($pkg->type_price == 'paid')
                <div class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-semibold" style="background-color: {{ $primaryColor }}; color: white;">
                    {{ $pkg->formatted_price }}
                </div>
                @else
                <div class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-semibold bg-green-500 text-white">
                    GRATIS
                </div>
                @endif
            </div>

            <!-- Content -->
            <div class="p-5 flex flex-col flex-1">
                <a href="{{ route('user.package.detail', $pkg->package_id) }}" class="block">
                    <h3 class="font-bold text-lg text-gray-800 mb-2 hover:text-primary transition-colors">{{ $pkg->name }}</h3>
                </a>
                <div class="text-gray-500 text-sm mb-4 line-clamp-2 plan-description">{!! $pkg->description ?? 'Paket pembelajaran' !!}</div>

                <!-- Features -->
                @if($pkg->features)
                @php $pkgFeatureList = json_decode($pkg->features, true); @endphp
                @if(!empty($pkgFeatureList))
                <div class="space-y-1.5 mb-4">
                    @foreach ($pkgFeatureList as $feature)
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="ri-checkbox-circle-fill mr-2 text-green"></i>
                        <span>{{ $feature }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
                @endif

                <!-- Action Button -->
                <a href="{{ route('user.package.detail', $pkg->package_id) }}"
                   class="block w-full py-2.5 text-center rounded-xl font-medium text-white hover:opacity-90 transition-opacity mt-auto pt-2"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-eye-line mr-1"></i>Lihat Detail
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <p class="text-gray-400 text-center py-8">Belum ada paket tersedia</p>
    @endif
</div>
@endif

<!-- Section: Komunitas Belajar -->
<div class="relative overflow-hidden rounded-[2rem] p-8 md:p-10 text-white mb-6" style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $primaryColor }}dd 100%);">
    <!-- Decorative light overlays -->
    <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
    <div class="absolute -left-20 -bottom-20 h-48 w-48 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>

    <div class="relative grid lg:grid-cols-12 gap-6 items-center z-10">
        <!-- Left: Content details -->
        <div class="lg:col-span-8 space-y-3.5">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-white font-extrabold uppercase tracking-widest text-[10px] sm:text-xs">
                <i class="ri-wechat-line text-sm"></i>
                Support System Pejuang PTN
            </div>
            <h3 class="text-xl sm:text-2xl.5 font-black tracking-tight leading-tight">Komunitas Pejuang PTN {{ $clientBranding['name'] ?? 'Copoit Academy' }}</h3>
            <p class="text-xs sm:text-sm text-slate-100/90 font-medium leading-relaxed max-w-2xl">
                Jangan berjuang sendirian! Bergabunglah di grup WhatsApp diskusi kami untuk berbagi soal, info pendaftaran PTN, konsultasi, serta webinar gratis bersama alumni terkemuka.
            </p>
        </div>

        <!-- Right: CTA -->
        <div class="lg:col-span-4 flex lg:justify-end">
            <a href="https://chat.whatsapp.com/DO0KNXJVyoyAWK31EOoo3H"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex w-full sm:w-auto items-center justify-center gap-2.5 rounded-xl bg-white hover:bg-slate-50 px-7 py-3.5 text-sm font-extrabold shadow-md hover:shadow-lg transition-all active:scale-98"
               style="color: {{ $primaryColor }}">
                <i class="ri-whatsapp-line text-lg text-emerald-500"></i>
                Gabung Grup Sekarang
            </a>
        </div>
    </div>
</div>

@section('styles')
<style>
.plan-description p { margin-bottom: 0.5rem; }
.plan-description p:last-child { margin-bottom: 0; }
.plan-description ul { list-style-type: disc; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.plan-description ol { list-style-type: decimal; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.plan-description a { color: var(--primary-color, #10b981); text-decoration: underline; }
.plan-description strong { font-weight: 600; }
.plan-description em { font-style: italic; }
</style>
@endsection
@endsection
