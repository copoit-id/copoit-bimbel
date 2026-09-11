@extends('parent.layout')

@section('title', 'Perkembangan Belajar')

@section('content')
<div class="space-y-6" x-data="{ tab: 'progress' }">
    <x-layout.page-header
        title="Perkembangan belajar"
        description="Pantau evaluasi tutor, kekuatan, dan target belajar {{ $child?->name ?? 'anak' }}."
    />

    @if($child)
        <section class="space-y-4">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-primary">Per jadwal</p><h2 class="mt-1 text-xl font-bold text-gray-900">Aktivitas belajar {{ $child->name }}</h2><p class="mt-1 text-sm text-gray-500">Absensi, feedback tutor, dan progress yang tercatat pada setiap sesi.</p></div>
            @forelse($sessionTimeline as $row)
                @php($session = $row['session'])
                <article class="rounded-2xl border border-gray-200 bg-white p-5"><div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-sm font-bold text-primary">{{ $session->start_at->translatedFormat('d M Y · H:i') }} WIB</p><h3 class="mt-1 font-bold text-gray-900">{{ $session->schedule?->title ?? 'Sesi belajar' }}</h3><p class="mt-1 text-sm text-gray-500">{{ $session->studyGroup?->name ?? 'Sesi personal' }}</p></div><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $row['attendance'] ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-500' }}">{{ $row['attendance'] ? 'Absensi: '.ucfirst($row['attendance']->status) : 'Absensi belum dicatat' }}</span></div><div class="mt-4 flex flex-wrap gap-2">@if($row['feedback']->isNotEmpty())<button type="button" onclick="document.getElementById('feedback-{{ $session->id }}').classList.toggle('hidden')" class="rounded-lg border border-primary px-3 py-2 text-sm font-semibold text-primary hover:bg-primary hover:text-white"><i class="ri-message-3-line mr-1"></i>Feedback {{ $row['feedback']->count() }}</button>@endif @if($row['progress']->isNotEmpty())<button type="button" onclick="document.getElementById('progress-{{ $session->id }}').classList.toggle('hidden')" class="rounded-lg border border-primary px-3 py-2 text-sm font-semibold text-primary hover:bg-primary hover:text-white"><i class="ri-line-chart-line mr-1"></i>Progress {{ $row['progress']->count() }}</button>@endif</div><div id="feedback-{{ $session->id }}" class="mt-4 hidden space-y-3">@foreach($row['feedback'] as $item)<div class="rounded-xl bg-primary/5 p-4"><p class="font-semibold text-gray-900">{{ $item->title }} <span class="font-normal text-gray-500">· {{ $item->tentor?->name }}</span></p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $item->feedback }}</p></div>@endforeach</div><div id="progress-{{ $session->id }}" class="mt-4 hidden space-y-3">@foreach($row['progress'] as $item)<div class="rounded-xl bg-primary/5 p-4"><p class="font-semibold text-gray-900">Progress belajar <span class="font-normal text-gray-500">· {{ $item->tentor?->name }}</span></p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $item->summary }}</p></div>@endforeach</div></article>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">Belum ada jadwal anak yang memiliki absensi, feedback, atau progress.</div>
            @endforelse
        </section>
        <div class="inline-flex max-w-full gap-1 overflow-x-auto rounded-lg border border-gray-200 bg-white p-1">
            <button type="button" @click="tab = 'progress'" :class="tab === 'progress' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50'" class="whitespace-nowrap rounded-md px-4 py-2 text-sm font-semibold transition-colors">
                <i class="ri-line-chart-line mr-1.5"></i>Laporan progres
            </button>
            <button type="button" @click="tab = 'feedback'" :class="tab === 'feedback' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50'" class="whitespace-nowrap rounded-md px-4 py-2 text-sm font-semibold transition-colors">
                <i class="ri-message-3-line mr-1.5"></i>Feedback tutor
            </button>
        </div>

        <section x-show="tab === 'progress'" class="space-y-4">
            @forelse($progress as $report)
                <x-ui.card variant="flat" class="border border-gray-200">
                    <x-ui.card.header
                        :title="$report->package?->name ?? 'Laporan perkembangan'"
                        :subtitle="($report->tentor?->name ?? 'Tutor').' · '.($report->studyGroup?->name ?? 'Personal')"
                    >
                        <x-slot:action>
                            <div class="text-right">
                                <x-ui.badge variant="primary">Progres {{ $report->progress_percent ?? '—' }}%</x-ui.badge>
                                <p class="mt-1.5 text-xs text-gray-400">{{ $report->period_start?->translatedFormat('d M') }}–{{ $report->period_end?->translatedFormat('d M Y') }}</p>
                            </div>
                        </x-slot:action>
                    </x-ui.card.header>
                    <x-ui.card.body class="space-y-5">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                                <p class="text-xs font-medium text-gray-500">Penguasaan materi</p>
                                <p class="mt-1 text-xl font-bold text-gray-900">{{ $report->mastery_score ?? '—' }}@if($report->mastery_score !== null)<span class="text-xs font-medium text-gray-400">/100</span>@endif</p>
                            </div>
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                                <p class="text-xs font-medium text-gray-500">Disiplin</p>
                                <p class="mt-1 text-xl font-bold text-gray-900">{{ $report->discipline_score ?? '—' }}@if($report->discipline_score !== null)<span class="text-xs font-medium text-gray-400">/100</span>@endif</p>
                            </div>
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                                <p class="text-xs font-medium text-gray-500">Partisipasi</p>
                                <p class="mt-1 text-xl font-bold text-gray-900">{{ $report->participation_score ?? '—' }}@if($report->participation_score !== null)<span class="text-xs font-medium text-gray-400">/100</span>@endif</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-gray-900">Ringkasan tutor</p>
                            <p class="mt-1 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $report->summary ?: 'Tutor belum menambahkan ringkasan.' }}</p>
                        </div>

                        <div class="grid gap-3 lg:grid-cols-3">
                            <div class="rounded-lg border border-emerald-100 bg-emerald-50/60 p-4">
                                <p class="text-sm font-semibold text-emerald-800"><i class="ri-checkbox-circle-line mr-1"></i>Kekuatan</p>
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $report->strengths ?: 'Belum ada catatan.' }}</p>
                            </div>
                            <div class="rounded-lg border border-amber-100 bg-amber-50/60 p-4">
                                <p class="text-sm font-semibold text-amber-800"><i class="ri-focus-3-line mr-1"></i>Perlu ditingkatkan</p>
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $report->improvements ?: 'Belum ada catatan.' }}</p>
                            </div>
                            <div class="rounded-lg border border-primary/15 bg-primary/5 p-4">
                                <p class="text-sm font-semibold text-primary"><i class="ri-flag-line mr-1"></i>Target berikutnya</p>
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $report->next_target ?: 'Belum ada target.' }}</p>
                            </div>
                        </div>
                    </x-ui.card.body>
                </x-ui.card>
            @empty
                <x-ui.card variant="flat" class="border border-dashed border-gray-300">
                    <div class="px-5 py-14 text-center"><i class="ri-line-chart-line text-4xl text-gray-300"></i><p class="mt-3 font-semibold text-gray-800">Belum ada laporan progres</p><p class="mt-1 text-sm text-gray-500">Laporan yang dibuat tutor akan tampil di sini.</p></div>
                </x-ui.card>
            @endforelse

            @if($progress instanceof \Illuminate\Pagination\AbstractPaginator)
                {{ $progress->links() }}
            @endif
        </section>

        <section x-show="tab === 'feedback'" x-cloak class="space-y-4">
            @forelse($feedback as $item)
                <x-ui.card variant="flat" class="border border-gray-200">
                    <x-ui.card.header :title="$item->title" :subtitle="($item->tentor?->name ?? 'Tutor').' · '.($item->studyGroup?->name ?? 'Personal')">
                        <x-slot:action>
                            <span class="text-xs text-gray-400">{{ $item->created_at?->translatedFormat('d M Y') }}</span>
                        </x-slot:action>
                    </x-ui.card.header>
                    <x-ui.card.body>
                        <p class="whitespace-pre-line text-sm leading-7 text-gray-600">{{ $item->feedback }}</p>
                    </x-ui.card.body>
                </x-ui.card>
            @empty
                <x-ui.card variant="flat" class="border border-dashed border-gray-300">
                    <div class="px-5 py-14 text-center"><i class="ri-message-3-line text-4xl text-gray-300"></i><p class="mt-3 font-semibold text-gray-800">Belum ada feedback tutor</p><p class="mt-1 text-sm text-gray-500">Catatan evaluasi setiap pertemuan akan tampil di sini.</p></div>
                </x-ui.card>
            @endforelse

            @if($feedback instanceof \Illuminate\Pagination\AbstractPaginator)
                {{ $feedback->links() }}
            @endif
        </section>
    @else
        <x-ui.card variant="flat" class="border border-dashed border-gray-300">
            <div class="px-5 py-14 text-center"><i class="ri-user-search-line text-4xl text-gray-300"></i><p class="mt-3 font-semibold text-gray-800">Belum ada anak dipilih</p><p class="mt-1 text-sm text-gray-500">Pilih anak untuk melihat perkembangannya.</p></div>
        </x-ui.card>
    @endif
</div>
@endsection
