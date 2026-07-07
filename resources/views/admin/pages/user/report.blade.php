@extends('admin.layout.admin')
@section('title', 'Laporan Pengguna')
@section('styles')
<style>
    .user-report-print {
        color: #1f2937;
    }

    .user-report-print * {
        overflow-wrap: anywhere;
    }

    @media print {
        @page {
            size: A4;
            margin: 12mm;
        }

        html,
        body {
            width: auto !important;
            min-height: auto !important;
            background: #ffffff !important;
            color: #111827 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body > nav,
        body > aside,
        .no-print,
        [role="alert"] {
            display: none !important;
        }

        body > div[class~="p-6"],
        body > div[class~="md:p-12"],
        body > div[class~="sm:ml-64"] {
            margin: 0 !important;
            padding: 0 !important;
        }

        .user-report-print {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            font-size: 11px;
            line-height: 1.45;
        }

        .user-report-print .print-card,
        .user-report-print .print-section,
        .user-report-print .print-row,
        .user-report-print .print-list-item {
            break-inside: avoid;
            page-break-inside: avoid;
            box-shadow: none !important;
        }

        .user-report-print .print-card,
        .user-report-print .print-section {
            border: 1px solid #d1d5db !important;
            border-radius: 8px !important;
            background: #ffffff !important;
            padding: 14px !important;
        }

        .user-report-print .print-header {
            display: flex !important;
            align-items: center !important;
            gap: 14px !important;
        }

        .user-report-print .print-avatar {
            width: 56px !important;
            height: 56px !important;
            flex: 0 0 56px !important;
        }

        .user-report-print .print-meta {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 6px 14px !important;
        }

        .user-report-print .print-stats {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 8px !important;
        }

        .user-report-print .print-two-column {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 10px !important;
        }

        .user-report-print .print-row {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 10px !important;
        }

        .user-report-print .print-list-item {
            display: flex !important;
            align-items: flex-start !important;
            justify-content: flex-start !important;
            gap: 10px !important;
        }

        .user-report-print h1 {
            font-size: 18px !important;
            line-height: 1.25 !important;
            margin: 0 0 10px !important;
        }

        .user-report-print h2 {
            font-size: 17px !important;
            line-height: 1.25 !important;
        }

        .user-report-print h3 {
            font-size: 13px !important;
            line-height: 1.3 !important;
            margin-bottom: 8px !important;
        }

        .user-report-print p {
            margin: 0 !important;
        }

        .user-report-print .text-3xl {
            font-size: 22px !important;
            line-height: 1.2 !important;
        }

        .user-report-print .text-2xl {
            font-size: 17px !important;
            line-height: 1.25 !important;
        }

        .user-report-print .text-lg {
            font-size: 13px !important;
            line-height: 1.3 !important;
        }

        .user-report-print .mt-6 {
            margin-top: 10px !important;
        }

        .user-report-print .mb-4 {
            margin-bottom: 8px !important;
        }

        .user-report-print .space-y-4 > :not([hidden]) ~ :not([hidden]) {
            margin-top: 8px !important;
        }
    }
</style>
@endsection
@section('content')
@php use Illuminate\Support\Str; @endphp

<div class="no-print flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.user.index') }}" title="Manajemen User" />
            <x-breadcrumb-item href="" title="Laporan User" />
        </x-slot>
    </x-breadcrumb>
    <div class="flex gap-2">
        <button type="button" data-download-report
            class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
            <i class="ri-download-line"></i>
            Download Laporan
        </button>
    </div>
</div>

<div class="user-report-print">
<x-page-desc title="Detail Aktivitas - {{ $user->name }}"></x-page-desc>

<div class="print-section bg-white rounded-lg border border-border p-6 mt-6">
    <div class="print-header flex items-center gap-6">
        <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=6366f1&color=fff&size=100"
            class="print-avatar w-20 h-20 rounded-full">
        <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-800">{{ $user->name }}</h2>
            <p class="text-gray-600">{{ $user->email }}</p>
            <div class="print-meta flex items-center gap-4 mt-2">
                <span class="flex items-center gap-1 text-sm text-gray-500">
                    <i class="ri-calendar-line"></i>
                    Bergabung: {{ $user->created_at->format('d M Y') }}
                </span>
                <span class="flex items-center gap-1 text-sm text-gray-500">
                    <i class="ri-time-line"></i>
                    Terakhir aktif: {{ $user->updated_at->diffForHumans() }}
                </span>
                <span class="px-3 py-1 border border-green-700 bg-green-100 text-green-700 rounded-full text-sm">
                    {{ $user->userAnswers->where('created_at', '>', now()->subDays(30))->count() > 0 ? 'Aktif' : 'Tidak Aktif' }}
                </span>
            </div>
        </div>
    </div>
</div>

<div class="print-stats grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
    <div class="print-card bg-white border border-border rounded-lg p-6">
        <p class="text-sm text-gray-600">Total Tryout</p>
        <p class="text-3xl font-bold text-gray-800">{{ $statistics['total_tryouts'] }}</p>
    </div>
    <div class="print-card bg-white border border-border rounded-lg p-6">
        <p class="text-sm text-gray-600">Rata-rata Nilai</p>
        <p class="text-3xl font-bold text-gray-800">{{ $statistics['avg_score'] }}</p>
    </div>
    <div class="print-card bg-white border border-border rounded-lg p-6">
        <p class="text-sm text-gray-600">Sertifikat</p>
        <p class="text-3xl font-bold text-gray-800">{{ $statistics['total_certificates'] }}</p>
    </div>
    <div class="print-card bg-white border border-border rounded-lg p-6">
        <p class="text-sm text-gray-600">Waktu Belajar</p>
        <p class="text-3xl font-bold text-gray-800">{{ $statistics['study_hours'] }}h</p>
    </div>
</div>

<div class="print-two-column grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <div class="print-section bg-white border border-border rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Riwayat Tryout Terbaru</h3>
        <div class="space-y-4">
            @forelse($recentTryouts as $tryout)
            <div class="print-row flex items-center justify-between p-3 bg-gray-50 rounded-lg">
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
    <div class="print-section bg-white border border-border rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Sertifikat</h3>
        <div class="space-y-4">
            @forelse($certificates as $certificate)
            <div class="print-list-item flex items-center gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <i class="ri-award-line text-amber-600"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-800">{{ $certificate['name'] }}</p>
                    <p class="text-sm text-gray-500">{{ $certificate['date']->format('d M Y') }}</p>
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

@if($activities->isNotEmpty())
<div class="print-section bg-white border border-border rounded-lg p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Timeline Aktivitas</h3>
    <div class="space-y-4">
        @foreach($activities as $activity)
        <div class="print-list-item flex items-start gap-3">
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
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelector('[data-download-report]')?.addEventListener('click', () => window.print());
    });
</script>
@endsection
