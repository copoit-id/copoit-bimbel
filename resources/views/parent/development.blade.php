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
                <article class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6" x-data="{ modal: null, feedbackTab: 'personal' }">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-sm font-bold text-primary">{{ $session->start_at->translatedFormat('d M Y · H:i') }} WIB</p><h3 class="mt-1 text-lg font-bold text-gray-900">{{ $session->schedule?->title ?? 'Sesi belajar' }}</h3><p class="mt-1 text-sm text-gray-500">{{ $session->studyGroup?->name ?? 'Sesi personal' }}</p></div><span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $row['attendance'] ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-500' }}">{{ $row['attendance'] ? 'Absensi: '.ucfirst($row['attendance']->status) : 'Absensi belum dicatat' }}</span></div>

                    <div class="mt-5 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                        <x-ui.button type="button" variant="outline" icon="ri-message-3-line" @click="feedbackTab = 'personal'; modal = 'feedback'" :disabled="! $hasFeedback">{{ $hasFeedback ? 'Feedback' : 'Feedback belum ada' }}</x-ui.button>
                        <x-ui.button type="button" variant="outline" icon="ri-line-chart-line" @click="modal = 'progress'" :disabled="! $row['progress']">{{ $row['progress'] ? 'Progress' : 'Progress belum ada' }}</x-ui.button>
                    </div>

                    <template x-teleport="body">
                        <div x-show="modal" x-cloak x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/40 p-4" role="dialog" aria-modal="true" @keydown.escape.window="modal = null">
                            <div class="absolute inset-0" @click="modal = null"></div>
                            <div x-show="modal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-3 sm:scale-95" class="relative z-10 flex max-h-[calc(100vh-2rem)] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" @click.stop>
                                <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4 sm:px-6"><div><p class="text-xs font-semibold uppercase tracking-wide text-primary" x-text="modal === 'feedback' ? 'Feedback tutor' : 'Progress pembelajaran'"></p><h3 class="mt-1 text-lg font-bold text-gray-900">{{ $session->schedule?->title ?? 'Sesi belajar' }}</h3><p class="mt-1 text-sm text-gray-500">{{ $session->start_at->translatedFormat('d M Y · H:i') }} WIB</p></div><button type="button" @click="modal = null" class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Tutup"><i class="ri-close-line text-xl"></i></button></div>
                                <div class="overflow-y-auto px-5 py-5 sm:px-6">
                                    <div x-show="modal === 'feedback'">
                                        <div class="inline-flex rounded-lg bg-gray-100 p-1"><button type="button" @click="feedbackTab = 'personal'" :class="feedbackTab === 'personal' ? 'bg-white text-primary shadow-sm' : 'text-gray-500 hover:text-gray-700'" class="rounded-md px-3 py-2 text-sm font-semibold">Personal ({{ $row['personalFeedback']->count() }})</button><button type="button" @click="feedbackTab = 'group'" :class="feedbackTab === 'group' ? 'bg-white text-primary shadow-sm' : 'text-gray-500 hover:text-gray-700'" class="rounded-md px-3 py-2 text-sm font-semibold">Rombel ({{ $row['groupFeedback']->count() }})</button></div>
                                        <div x-show="feedbackTab === 'personal'" class="mt-4 space-y-3">@forelse($row['personalFeedback'] as $item)<article class="rounded-xl border border-gray-100 bg-gray-50 p-4"><p class="font-semibold text-gray-900">{{ $item->title }}</p><p class="mt-1 text-xs text-gray-500">{{ $item->tentor?->name ?? 'Tutor' }}</p><p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $item->feedback }}</p></article>@empty<p class="py-8 text-center text-sm text-gray-500">Belum ada feedback personal pada sesi ini.</p>@endforelse</div>
                                        <div x-show="feedbackTab === 'group'" x-cloak class="mt-4 space-y-3">@forelse($row['groupFeedback'] as $item)<article class="rounded-xl border border-gray-100 bg-gray-50 p-4"><p class="font-semibold text-gray-900">{{ $item->title }}</p><p class="mt-1 text-xs text-gray-500">{{ $item->tentor?->name ?? 'Tutor' }} · Rombel</p><p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $item->feedback }}</p></article>@empty<p class="py-8 text-center text-sm text-gray-500">Belum ada feedback rombel pada sesi ini.</p>@endforelse</div>
                                    </div>
                                    <div x-show="modal === 'progress'" x-cloak><div class="rounded-xl border border-primary/10 bg-primary/5 p-4"><p class="text-sm font-semibold text-gray-900">Ringkasan progress sesi</p><p class="mt-1 text-xs text-gray-500">{{ $row['progress']?->tentor?->name ?? 'Tutor' }}</p><div class="prose prose-sm mt-4 max-w-none break-words text-gray-700">{!! $row['progressHtml'] !!}</div></div></div>
                                </div>
                            </div>
                        </div>
                    </template>
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
