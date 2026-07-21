@extends('admin.layout.admin')

@section('title', 'Jadwal Tutor')

@section('content')
<div class="space-y-6">
    <div>
        <p class="text-sm text-gray-500">Jadwal mengajar</p>
        <h1 class="text-2xl font-bold text-gray-900">{{ $tentor->name }}</h1>
        <p class="text-sm text-gray-500">{{ $weekDates->first()->locale('id')->translatedFormat('d M') }} - {{ $weekDates->last()->locale('id')->translatedFormat('d M Y') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-7">
        @foreach($weekDates as $dayNumber => $date)
            <section class="flex min-h-[250px] flex-col overflow-hidden rounded-lg border border-gray-200 bg-white">
                <header class="border-b border-gray-200 bg-gray-50 px-3 py-2.5 text-gray-800">
                    <p class="font-bold text-sm tracking-wide">{{ $dayLabels[$dayNumber] }}</p>
                    <p class="text-xs text-gray-500">{{ $date->locale('id')->translatedFormat('d M') }}</p>
                </header>

                <div class="flex-1 space-y-2 bg-slate-50/20 p-2">
                    @forelse($weeklySessions->get($dayNumber, collect()) as $session)
                        <article class="rounded-lg border border-gray-200 bg-white p-3">
                            <p class="text-xs font-semibold text-primary">
                                {{ $session->start_at->format('H:i') }}{{ $session->end_at ? ' - ' . $session->end_at->format('H:i') : '' }}
                            </p>
                            <h2 class="mt-1 text-sm font-bold leading-snug text-gray-900">{{ $session->schedule?->title ?? $session->class?->title ?? 'Kelas' }}</h2>
                            <p class="mt-1 flex items-center gap-1 text-[11px] text-gray-500"><i class="ri-group-line"></i>{{ $session->studyGroup?->name ?? 'Tanpa rombel' }}</p>
                            @if($session->location)
                                <p class="mt-1 flex items-center gap-1 text-[11px] text-gray-500"><i class="ri-map-pin-line"></i>{{ $session->location }}</p>
                            @elseif($session->meeting_url)
                                <a href="{{ $session->meeting_url }}" target="_blank" class="mt-1 flex items-center gap-1 text-[11px] font-medium text-primary hover:underline"><i class="ri-video-chat-line"></i>Online meeting</a>
                            @endif
                        </article>
                    @empty
                        <div class="flex h-full min-h-36 flex-col items-center justify-center rounded-lg border border-dashed border-gray-200 bg-slate-50/30 px-2 text-center text-xs text-gray-400">
                            <i class="ri-calendar-event-line mb-1 text-lg opacity-50"></i>
                            Belum ada jadwal
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</div>
@endsection
