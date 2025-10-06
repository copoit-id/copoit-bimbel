@extends('admin.layout.admin')
@section('title', 'Detail Laporan User')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.laporan.index') }}" title="Laporan User" />
            <x-breadcrumb-item href="" title="Detail Laporan" />
        </x-slot>
    </x-breadcrumb>
    <div class="flex gap-2">
        <button class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
            <i class="ri-download-line"></i>
            Download Laporan
        </button>
    </div>
</div>
<x-page-desc title="Detail Laporan - {{ $user->name }}"></x-page-desc>

<!-- User Profile Card -->
<div class="bg-white rounded-lg border border-border p-6 mt-6">
    <div class="flex items-center gap-6">
        <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=6366f1&color=fff&size=100"
            class="w-20 h-20 rounded-full">
        <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-800">{{ $user->name }}</h2>
            <p class="text-gray-600">{{ $user->email }}</p>
            <div class="flex items-center gap-4 mt-2">
                <span class="flex items-center gap-1 text-sm text-gray-500">
                    <i class="ri-calendar-line"></i>
                    Bergabung: {{ $user->created_at->format('d M Y') }}
                </span>
                <span class="flex items-center gap-1 text-sm text-gray-500">
                    <i class="ri-time-line"></i>
                    Terakhir aktif: {{ $user->updated_at->diffForHumans() }}
                </span>
                <span class="px-3 py-1 border border-green-700 bg-green-100 text-green-700 rounded-full text-sm">
                    {{ $user->userAnswers->where('created_at', '>', now()->subDays(30))->count() > 0 ? 'Aktif' : 'Tidak
                    Aktif' }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-4 gap-4 mt-6">
    <div class="bg-white border border-border rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Tryout</p>
                <p class="text-3xl font-bold text-gray-800">{{ $statistics['total_tryouts'] }}</p>
            </div>
            <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                <i class="ri-file-list-line text-2xl text-primary"></i>
            </div>
        </div>
    </div>

    <div class="bg-white border border-border rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Rata-rata Nilai</p>
                <p class="text-3xl font-bold text-gray-800">{{ $statistics['avg_score'] }}</p>
            </div>
            <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                <i class="ri-bar-chart-line text-2xl text-primary"></i>
            </div>
        </div>
    </div>

    <div class="bg-white border border-border rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Sertifikat</p>
                <p class="text-3xl font-bold text-gray-800">{{ $statistics['total_certificates'] }}</p>
            </div>
            <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                <i class="ri-award-line text-2xl text-primary"></i>
            </div>
        </div>
    </div>

    <div class="bg-white border border-border rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Waktu Belajar</p>
                <p class="text-3xl font-bold text-gray-800">{{ $statistics['study_hours'] }}h</p>
            </div>
            <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                <i class="ri-time-line text-2xl text-primary"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-6 mt-6">
    <!-- Tryout History -->
    <div class="bg-white border border-border rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Riwayat Tryout Terbaru</h3>
        <div class="space-y-4">
            @forelse($recentTryouts as $tryout)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                    <p class="font-medium text-gray-800">{{ Str::limit($tryout['name'], 25) }}</p>
                    <p class="text-sm text-gray-500">{{ $tryout['date']->format('d M Y, H:i') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-gray-800">{{ $tryout['score'] }}</p>
                </div>
            </div>
            @empty
            <div class="text-center py-4 text-gray-500">
                <i class="ri-file-list-line text-2xl mb-2"></i>
                <p>Belum ada riwayat tryout</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Certificates -->
    <div class="bg-white border border-border rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Sertifikat yang Diperoleh</h3>
        <div class="space-y-4">
            @forelse($certificates as $certificate)
            <div class="flex items-center gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <i class="ri-award-line text-amber-600"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-800">{{ $certificate['name'] }}</p>
                    <p class="text-sm text-gray-500">Diperoleh {{ $certificate['date']->format('d M Y') }}</p>
                </div>
            </div>
            @empty
            <div class="text-center py-4 text-gray-500">
                <i class="ri-award-line text-2xl mb-2"></i>
                <p>Belum ada sertifikat</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Activity Timeline -->
@if($activities->isNotEmpty())
<div class="bg-white border border-border rounded-lg p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Timeline Aktivitas Terbaru</h3>
    <div class="space-y-4">
        @foreach($activities as $activity)
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 bg-{{ $activity['color'] }}-100 rounded-full flex items-center justify-center mt-1">
                <i class="{{ $activity['icon'] }} text-{{ $activity['color'] }}-600"></i>
            </div>
            <div class="flex-1">
                <p class="text-gray-800">{{ $activity['text'] }}</p>
                <p class="text-sm text-gray-500">{{ $activity['date']->format('d M Y, H:i') }}</p>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
    console.log('Detail laporan user scripts loaded');
</script>
@endsection
