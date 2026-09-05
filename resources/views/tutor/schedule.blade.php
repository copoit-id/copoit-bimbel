@extends('tutor.layout')

@section('title', 'Jadwal Tutor')

@section('content')
<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
        <p class="text-sm text-gray-500">Jadwal mengajar</p>
        <h1 class="text-2xl font-bold text-gray-900">{{ $tentor->name }}</h1>
        @if($scheduleRange === 'week')
            <p class="text-sm text-gray-500">{{ $weekDates->first()->locale('id')->translatedFormat('d M') }} - {{ $weekDates->last()->locale('id')->translatedFormat('d M Y') }}</p>
        @elseif($scheduleRange === 'month')
            <p class="text-sm text-gray-500">{{ now()->locale('id')->translatedFormat('F Y') }}</p>
        @else
            <p class="text-sm text-gray-500">Semua jadwal</p>
        @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex rounded-lg border border-gray-200 bg-white p-1 text-xs font-semibold">
                @foreach(['week' => 'Minggu ini', 'month' => 'Bulan ini', 'all' => 'Semua'] as $rangeKey => $rangeLabel)
                    <a href="{{ route('tutor.schedule.index', ['range' => $rangeKey]) }}" class="rounded-md px-3 py-2 {{ $scheduleRange === $rangeKey ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">{{ $rangeLabel }}</a>
                @endforeach
            </div>
            @if($canManageSchedule)
                <a href="{{ route('tutor.schedule.create') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90"><i class="ri-add-line mr-1"></i>Tambah sesi</a>
            @endif
        </div>
    </div>

    @if($scheduleRange === 'week')
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-7">
        @foreach($weekDates as $dayNumber => $date)
            <section class="flex min-h-[250px] flex-col overflow-hidden rounded-lg border border-gray-200 bg-white">
                <header class="border-b border-gray-200 bg-gray-50 px-3 py-2.5 text-gray-800">
                    <p class="text-sm font-bold tracking-wide">{{ $dayLabels[$dayNumber] }}</p>
                    <p class="text-xs text-gray-500">{{ $date->locale('id')->translatedFormat('d M') }}</p>
                </header>
                <div class="flex-1 space-y-2 bg-slate-50/20 p-2">
                    @forelse($weeklySessions->get($dayNumber, collect()) as $session)
                        <article class="rounded-lg border border-gray-200 bg-white p-3">
                            <p class="text-xs font-semibold text-primary">{{ $session->start_at->format('H:i') }}{{ $session->end_at ? ' - '.$session->end_at->format('H:i') : '' }}</p>
                            <h2 class="mt-1 text-sm font-bold leading-snug text-gray-900">{{ $session->schedule?->title ?? $session->class?->title ?? 'Kelas' }}</h2>
                            <p class="mt-1 flex items-center gap-1 text-[11px] text-gray-500"><i class="ri-group-line"></i>{{ $session->studyGroup?->name ?? 'Tanpa rombel' }}</p>
                            @if($session->location)
                                <p class="mt-1 flex items-center gap-1 text-[11px] text-gray-500"><i class="ri-map-pin-line"></i>{{ $session->location }}</p>
                            @elseif($session->meeting_url)
                                <a href="{{ $session->meeting_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 flex items-center gap-1 text-[11px] font-medium text-primary hover:underline"><i class="ri-video-chat-line"></i>Online meeting</a>
                            @endif
                            @if($canManageSchedule && $session->status === 'scheduled' && $session->start_at->isFuture())
                                <form method="POST" action="{{ route('tutor.schedule.cancel', $session) }}" class="mt-3" onsubmit="return confirm('Batalkan sesi ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-[11px] font-semibold text-red-600 hover:underline">Batalkan sesi</button>
                                </form>
                            @elseif($session->status === 'cancelled')
                                <p class="mt-2 text-[11px] font-semibold text-red-600">Dibatalkan</p>
                            @endif
                        </article>
                    @empty
                        <div class="flex h-full min-h-36 flex-col items-center justify-center rounded-lg border border-dashed border-gray-200 bg-slate-50/30 px-2 text-center text-xs text-gray-400"><i class="ri-calendar-event-line mb-1 text-lg opacity-50"></i>Belum ada jadwal</div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
    @else
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($scheduleSessions as $session)
            <article class="rounded-lg border border-gray-200 bg-white p-3">
                <p class="text-xs font-semibold text-primary">{{ $session->start_at->locale('id')->translatedFormat('l, d M Y') }}</p>
                <p class="mt-1 text-xs font-semibold text-gray-500">{{ $session->start_at->format('H:i') }}{{ $session->end_at ? ' - '.$session->end_at->format('H:i') : '' }}</p>
                <h2 class="mt-1 text-sm font-bold leading-snug text-gray-900">{{ $session->schedule?->title ?? $session->class?->title ?? 'Kelas' }}</h2>
                <p class="mt-1 flex items-center gap-1 text-[11px] text-gray-500"><i class="ri-group-line"></i>{{ $session->studyGroup?->name ?? 'Tanpa rombel' }}</p>
                @if($session->location)<p class="mt-1 flex items-center gap-1 text-[11px] text-gray-500"><i class="ri-map-pin-line"></i>{{ $session->location }}</p>@elseif($session->meeting_url)<a href="{{ $session->meeting_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 flex items-center gap-1 text-[11px] font-medium text-primary hover:underline"><i class="ri-video-chat-line"></i>Online meeting</a>@endif
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">Belum ada jadwal pada periode ini.</div>
        @endforelse
    </div>
    @endif
</div>
@endsection
