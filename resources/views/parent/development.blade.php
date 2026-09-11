@extends('parent.layout')

@section('title', 'Perkembangan Belajar')

@section('content')
<div class="space-y-6">
    <x-layout.page-header
        title="Perkembangan belajar"
        description="Pantau absensi dan feedback tutor {{ $child?->name ?? 'anak' }} di setiap jadwal belajar."
    />

    @if($child)
        <section class="space-y-4">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-primary">Per jadwal</p><h2 class="mt-1 text-xl font-bold text-gray-900">Aktivitas belajar {{ $child->name }}</h2><p class="mt-1 text-sm text-gray-500">Lihat status absensi dan feedback tutor untuk setiap sesi belajar.</p></div>
            @forelse($sessionTimeline as $row)
                @php($session = $row['session'])
                <article class="rounded-2xl border border-gray-200 bg-white p-5"><div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-sm font-bold text-primary">{{ $session->start_at->translatedFormat('d M Y · H:i') }} WIB</p><h3 class="mt-1 font-bold text-gray-900">{{ $session->schedule?->title ?? 'Sesi belajar' }}</h3><p class="mt-1 text-sm text-gray-500">{{ $session->studyGroup?->name ?? 'Sesi personal' }}</p></div><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $row['attendance'] ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-500' }}">{{ $row['attendance'] ? 'Absensi: '.ucfirst($row['attendance']->status) : 'Absensi belum dicatat' }}</span></div><div class="mt-4 flex flex-wrap gap-2">@if($row['feedback']->isNotEmpty())<button type="button" onclick="document.getElementById('feedback-{{ $session->id }}').classList.toggle('hidden')" class="rounded-lg border border-primary px-3 py-2 text-sm font-semibold text-primary hover:bg-primary hover:text-white"><i class="ri-message-3-line mr-1"></i>Feedback {{ $row['feedback']->count() }}</button>@endif</div><div id="feedback-{{ $session->id }}" class="mt-4 hidden space-y-3">@foreach($row['feedback'] as $item)<div class="rounded-xl bg-primary/5 p-4"><p class="font-semibold text-gray-900">{{ $item->title }} <span class="font-normal text-gray-500">· {{ $item->tentor?->name }}</span></p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $item->feedback }}</p></div>@endforeach</div></article>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">Belum ada jadwal anak yang memiliki absensi atau feedback.</div>
            @endforelse
        </section>
    @else
        <x-ui.card variant="flat" class="border border-dashed border-gray-300">
            <div class="px-5 py-14 text-center"><i class="ri-user-search-line text-4xl text-gray-300"></i><p class="mt-3 font-semibold text-gray-800">Belum ada anak dipilih</p><p class="mt-1 text-sm text-gray-500">Pilih anak untuk melihat perkembangannya.</p></div>
        </x-ui.card>
    @endif
</div>
@endsection
