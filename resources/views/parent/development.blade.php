@extends('parent.layout')

@section('title', 'Perkembangan Belajar')

@section('content')
<div class="space-y-6">
    <x-layout.page-header title="Perkembangan belajar" description="Pantau absensi, feedback, dan progress pembelajaran {{ $child?->name ?? 'anak' }} di setiap jadwal belajar." />

    @if($child)
        <section class="space-y-4">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-primary">Per jadwal</p><h2 class="mt-1 text-xl font-bold text-gray-900">Aktivitas belajar {{ $child->name }}</h2><p class="mt-1 text-sm text-gray-500">Pilih feedback atau progress untuk melihat ringkasan dari sesi yang terkait.</p></div>

            @forelse($sessionTimeline as $row)
                @php($session = $row['session'])
                @php($hasFeedback = $row['personalFeedback']->isNotEmpty() || $row['groupFeedback']->isNotEmpty())
                <article class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-sm font-bold text-primary">{{ $session->start_at->translatedFormat('d M Y · H:i') }} WIB</p><h3 class="mt-1 text-lg font-bold text-gray-900">{{ $session->schedule?->title ?? 'Sesi belajar' }}</h3><p class="mt-1 text-sm text-gray-500">{{ $session->studyGroup?->name ?? 'Sesi personal' }}</p></div><span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $row['attendance'] ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-500' }}">{{ $row['attendance'] ? 'Absensi: '.ucfirst($row['attendance']->status) : 'Absensi belum dicatat' }}</span></div>

                    <div class="mt-5 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                        @if($hasFeedback)
                            <x-ui.modal name="feedback-{{ $session->id }}" title="Feedback tutor" size="lg">
                                <x-slot:trigger><x-ui.button type="button" variant="outline" icon="ri-message-3-line">Feedback</x-ui.button></x-slot:trigger>
                                <div x-data="{ tab: 'personal' }"><div class="inline-flex rounded-lg bg-gray-100 p-1"><button type="button" @click="tab = 'personal'" :class="tab === 'personal' ? 'bg-white text-primary shadow-sm' : 'text-gray-500'" class="rounded-md px-3 py-2 text-sm font-semibold">Personal ({{ $row['personalFeedback']->count() }})</button><button type="button" @click="tab = 'group'" :class="tab === 'group' ? 'bg-white text-primary shadow-sm' : 'text-gray-500'" class="rounded-md px-3 py-2 text-sm font-semibold">Rombel ({{ $row['groupFeedback']->count() }})</button></div><div x-show="tab === 'personal'" class="mt-4 space-y-3">@forelse($row['personalFeedback'] as $item)<article class="rounded-xl border border-gray-100 bg-gray-50 p-4"><p class="font-semibold text-gray-900">{{ $item->title }}</p><p class="mt-1 text-xs text-gray-500">{{ $item->tentor?->name ?? 'Tutor' }}</p><p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $item->feedback }}</p></article>@empty<p class="py-8 text-center text-sm text-gray-500">Belum ada feedback personal pada sesi ini.</p>@endforelse</div><div x-show="tab === 'group'" x-cloak class="mt-4 space-y-3">@forelse($row['groupFeedback'] as $item)<article class="rounded-xl border border-gray-100 bg-gray-50 p-4"><p class="font-semibold text-gray-900">{{ $item->title }}</p><p class="mt-1 text-xs text-gray-500">{{ $item->tentor?->name ?? 'Tutor' }} · Rombel</p><p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $item->feedback }}</p></article>@empty<p class="py-8 text-center text-sm text-gray-500">Belum ada feedback rombel pada sesi ini.</p>@endforelse</div></div>
                            </x-ui.modal>
                        @else
                            <x-ui.button type="button" variant="outline" icon="ri-message-3-line" :disabled="true">Feedback belum ada</x-ui.button>
                        @endif

                        @if($row['progress'])
                            <x-ui.modal name="progress-{{ $session->id }}" title="Progress pembelajaran" size="lg">
                                <x-slot:trigger><x-ui.button type="button" variant="outline" icon="ri-line-chart-line">Progress</x-ui.button></x-slot:trigger>
                                <div class="rounded-xl bg-primary/5 p-4"><p class="text-sm font-semibold text-gray-900">{{ $session->schedule?->title ?? 'Sesi belajar' }}</p><p class="mt-1 text-xs text-gray-500">{{ $row['progress']->tentor?->name ?? 'Tutor' }} · {{ $session->start_at->translatedFormat('d M Y') }}</p><div class="prose prose-sm mt-4 max-w-none text-gray-700">{!! $row['progressHtml'] !!}</div></div>
                            </x-ui.modal>
                        @else
                            <x-ui.button type="button" variant="outline" icon="ri-line-chart-line" :disabled="true">Progress belum ada</x-ui.button>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">Belum ada jadwal anak yang memiliki absensi atau feedback.</div>
            @endforelse
        </section>
    @else
        <x-ui.card variant="flat" class="border border-dashed border-gray-300"><div class="px-5 py-14 text-center"><i class="ri-user-search-line text-4xl text-gray-300"></i><p class="mt-3 font-semibold text-gray-800">Belum ada anak dipilih</p><p class="mt-1 text-sm text-gray-500">Pilih anak untuk melihat perkembangannya.</p></div></x-ui.card>
    @endif
</div>
@endsection
