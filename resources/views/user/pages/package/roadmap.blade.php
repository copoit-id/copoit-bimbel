@extends('user.layout.new-user')

@section('title', $package->name)

@section('content')
@php
    $primaryColor = $clientBranding['primary_color'] ?? '#10b981';
    $videos = $roadmapItems->filter(fn ($item) => ($item['type'] ?? null) === 'material' && ($item['material_type'] ?? null) === 'video')->values();
    $documents = $roadmapItems->filter(fn ($item) => ($item['type'] ?? null) === 'material' && ($item['material_type'] ?? null) === 'document')->values();
    $liveSessions = $roadmapItems->filter(fn ($item) => ($item['type'] ?? null) === 'material' && ($item['material_type'] ?? null) === 'live_session')->values();
    $tryouts = $roadmapItems->where('type', 'tryout')->values();
    $tesKorans = $roadmapItems->where('type', 'tes_koran')->values();

    $sections = [
        ['key' => 'videos', 'title' => 'Video Materi', 'icon' => 'ri-video-line', 'items' => $videos, 'empty' => 'Belum ada video di paket ini.'],
        ['key' => 'documents', 'title' => 'Dokumen', 'icon' => 'ri-file-text-line', 'items' => $documents, 'empty' => 'Belum ada dokumen di paket ini.'],
        ['key' => 'live', 'title' => 'Live Session', 'icon' => 'ri-live-line', 'items' => $liveSessions, 'empty' => 'Belum ada live session di paket ini.'],
        ['key' => 'tryouts', 'title' => 'Tryout', 'icon' => 'ri-file-list-3-line', 'items' => $tryouts, 'empty' => 'Belum ada tryout di paket ini.'],
        ['key' => 'tes-koran', 'title' => 'Tes Koran', 'icon' => 'ri-file-edit-line', 'items' => $tesKorans, 'empty' => 'Belum ada tes koran di paket ini.'],
    ];
@endphp

<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('user.package.my') }}" class="p-2 rounded-xl bg-white border border-gray-100 text-gray-500 hover:text-gray-800">
            <i class="ri-arrow-left-line text-xl"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $package->name }}</h1>
            <p class="text-sm text-gray-500">{{ $completedCount }}/{{ $totalItems }} item selesai</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 border border-gray-100">
        <div class="flex items-center justify-between gap-4 mb-3">
            <div>
                <p class="text-sm text-gray-500">Progress Paket</p>
                <p class="text-3xl font-bold" style="color: {{ $primaryColor }}">{{ $progressPercent }}%</p>
            </div>
            @if($nextItem)
            <a href="{{ $nextItem['route'] }}" class="px-4 py-2.5 rounded-xl text-white text-sm font-semibold hover:opacity-90" style="background-color: {{ $primaryColor }}">
                <i class="ri-play-circle-line mr-1"></i>Lanjutkan
            </a>
            @endif
        </div>
        <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all" style="width: {{ $progressPercent }}%; background-color: {{ $primaryColor }}"></div>
        </div>
    </div>

    @foreach($sections as $section)
        @php $items = $section['items']; @endphp
        <section class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background-color: {{ $primaryColor }}15; color: {{ $primaryColor }}">
                        <i class="{{ $section['icon'] }} text-xl"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-800">{{ $section['title'] }}</h2>
                        <p class="text-xs text-gray-400">{{ $items->count() }} item</p>
                    </div>
                </div>
            </div>

            @if($items->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 p-5">
                    @foreach($items as $item)
                        @php
                            $cardClass = $item['is_completed'] ? 'border-green-200 bg-green-50/40' : ($item['is_in_progress'] ? 'border-yellow-200 bg-yellow-50/40' : 'border-gray-100');
                            $statusClass = $item['is_completed'] ? 'bg-green-100 text-green-700' : ($item['is_in_progress'] ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600');
                        @endphp
                        <a href="{{ $item['route'] }}" class="block rounded-xl border {{ $cardClass }} p-4 hover:shadow-md hover:-translate-y-0.5 transition-all">
                            <div class="flex items-start gap-3">
                                <div class="w-11 h-11 rounded-xl bg-white border border-gray-100 flex items-center justify-center shrink-0" style="color: {{ $primaryColor }}">
                                    <i class="{{ $item['icon'] }} text-xl"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusClass }}">{{ $item['status_text'] }}</span>
                                    </div>
                                    <h3 class="font-semibold text-gray-800 line-clamp-2">{{ $item['title'] }}</h3>
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ $item['subtitle'] }}</p>
                                </div>
                            </div>

                            @if(($item['type'] ?? null) === 'material')
                                <div class="mt-4">
                                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full" style="width: {{ $item['progress_percent'] }}%; background-color: {{ $primaryColor }}"></div>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">{{ $item['progress_percent'] }}% selesai</p>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @else
                <div class="px-5 py-8 text-center text-sm text-gray-400">
                    {{ $section['empty'] }}
                </div>
            @endif
        </section>
    @endforeach
</div>
@endsection
