@extends('parent.layout')

@section('title', 'Laporan Belajar')

@push('styles')
<style>
@media print {
    header, aside, .no-print { display: none !important; }
    body { background: #fff; }
    .responsive-shell { max-width: none !important; padding: 0 !important; }
    .print-break { break-inside: avoid; }
}
</style>
@endpush

@section('content')
<div class="space-y-5">
    <x-layout.page-header title="Laporan belajar {{ $child->name }}" description="Rekap personal: aktivitas Tryout, presensi, paket, dan feedback tutor." :border="false" class="no-print">
        <x-slot:actions><x-ui.button type="button" icon="ri-printer-line" onclick="window.print()">Cetak / Simpan PDF</x-ui.button></x-slot:actions>
    </x-layout.page-header>

    <section class="print-break rounded-xl border border-slate-200 bg-white p-6">
        <div class="flex items-start justify-between gap-4"><div><p class="text-sm font-medium text-slate-500">Laporan perkembangan siswa</p><h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $child->name }}</h1><p class="mt-1 text-sm text-slate-500">Dicetak pada {{ now()->translatedFormat('d F Y') }}</p></div><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-lg text-indigo-600"><i class="ri-file-chart-line"></i></span></div>
        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4"><div class="rounded-lg border border-slate-100 bg-slate-50 p-3"><p class="text-xs font-medium text-slate-500">Tryout selesai</p><p class="mt-1 text-xl font-bold text-slate-900">{{ $assessmentSummary['completed'] }}</p></div><div class="rounded-lg border border-slate-100 bg-slate-50 p-3"><p class="text-xs font-medium text-slate-500">Rata-rata nilai</p><p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($assessmentSummary['average_score'], 0) }}</p></div><div class="rounded-lg border border-slate-100 bg-slate-50 p-3"><p class="text-xs font-medium text-slate-500">Nilai tertinggi</p><p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($assessmentSummary['highest_score'], 0) }}</p></div><div class="rounded-lg border border-slate-100 bg-slate-50 p-3"><p class="text-xs font-medium text-slate-500">Kehadiran</p><p class="mt-1 text-xl font-bold text-slate-900">{{ $attendanceSummary['rate'] ?? '—' }}{{ $attendanceSummary['rate'] !== null ? '%' : '' }}</p></div></div>
    </section>

    <section class="print-break w-full max-w-none overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-600">Laporan Tryout</p><h2 class="mt-1 text-lg font-bold text-slate-900">Rincian pengerjaan per Tryout</h2><p class="mt-1 text-sm text-slate-500">Setiap Tryout memuat seluruh attempt anak, nilai, dan ketepatan jawaban.</p></div><span class="rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">{{ $tryoutReports->count() }} Tryout</span></div>
        <div class="divide-y divide-slate-100">
            @forelse($tryoutReports as $report)
                <article class="print-break px-5 py-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><i class="ri-file-list-3-line"></i></span><div><h3 class="font-bold text-slate-900">{{ $report['name'] }}</h3><p class="mt-1 text-sm text-slate-500">{{ $report['attempt_count'] }} attempt · Terakhir {{ \Carbon\Carbon::parse($report['last_finished_at'])->translatedFormat('d M Y, H:i') }}</p></div></div><div class="flex items-center gap-3"><div class="text-right"><p class="text-lg font-bold text-indigo-700">{{ number_format($report['latest_score'], 0) }}</p><p class="text-xs text-slate-500">nilai terakhir</p></div><x-ui.button :href="route('parent.assessments.detail', ['tryout' => $report['tryout_id']])" variant="outline" size="sm" icon="ri-file-search-line" class="no-print">Detail</x-ui.button></div></div>
                    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-100"><table class="w-full min-w-[760px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Attempt</th><th class="px-4 py-3">Selesai</th><th class="px-4 py-3 text-center">Benar</th><th class="px-4 py-3 text-center">Salah</th><th class="px-4 py-3 text-center">Kosong</th><th class="px-4 py-3 text-right">Nilai</th><th class="no-print px-4 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($report['attempts'] as $attempt)<tr><td class="px-4 py-3 font-medium text-slate-700">{{ \Illuminate\Support\Str::limit($attempt['attempt_key'], 16, '') }}</td><td class="px-4 py-3 text-slate-600">{{ \Carbon\Carbon::parse($attempt['finished_at'])->translatedFormat('d M Y, H:i') }}</td><td class="px-4 py-3 text-center text-emerald-700">{{ $attempt['correct_answers'] }}</td><td class="px-4 py-3 text-center text-rose-700">{{ $attempt['wrong_answers'] }}</td><td class="px-4 py-3 text-center text-amber-700">{{ $attempt['unanswered'] }}</td><td class="px-4 py-3 text-right font-bold text-indigo-700">{{ number_format($attempt['score'], 0) }}</td><td class="no-print px-4 py-3 text-right"><x-ui.button :href="route('parent.report.attempt', ['tryout' => $report['tryout_id'], 'attemptToken' => $attempt['attempt_key']])" variant="outline" size="sm" icon="ri-file-search-line">Detail</x-ui.button></td></tr>@endforeach</tbody></table></div>
                </article>
            @empty
                <div class="px-5 py-14 text-center text-sm text-slate-500">Belum ada Tryout yang diselesaikan.</div>
            @endforelse
        </div>
    </section>

    <section class="print-break grid gap-5 xl:grid-cols-2"><article class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="font-bold text-slate-900">Paket belajar</h2><div class="mt-4 divide-y divide-slate-100 text-sm">@forelse($packages as $access)<div class="flex justify-between gap-4 py-3 first:pt-0"><span class="font-medium text-slate-800">{{ $access->package?->name ?? 'Paket belajar' }}</span><span class="shrink-0 text-slate-500">{{ $access->end_date?->translatedFormat('d M Y') ?? 'Tanpa batas' }}</span></div>@empty<p class="py-8 text-center text-slate-500">Belum ada paket.</p>@endforelse</div></article><article class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="font-bold text-slate-900">Ringkasan presensi</h2><div class="mt-4 grid grid-cols-2 gap-3">@foreach(['total'=>'Total sesi', 'present'=>'Hadir', 'late'=>'Terlambat', 'absent'=>'Alpa'] as $key => $label)<div class="rounded-lg border border-slate-100 bg-slate-50 p-3"><p class="text-xs text-slate-500">{{ $label }}</p><p class="mt-1 text-xl font-bold text-slate-900">{{ $attendanceSummary[$key] }}</p></div>@endforeach</div></article></section>

    <section class="print-break rounded-xl border border-slate-200 bg-white p-5"><div><h2 class="font-bold text-slate-900">Feedback setiap pertemuan</h2><p class="mt-1 text-sm text-slate-500">Catatan tutor yang dibagikan setelah sesi belajar anak.</p></div><div class="mt-4 divide-y divide-slate-100">@forelse($feedback as $item)<article class="py-4 first:pt-0"><div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between"><div><p class="font-semibold text-slate-800">{{ $item->title }}</p><p class="mt-1 text-xs text-slate-500">{{ $item->tentor?->name ?? 'Tutor' }} · {{ $item->session?->schedule?->title ?? ($item->studyGroup?->name ?? 'Sesi bimbingan') }}</p></div><p class="shrink-0 text-xs text-slate-400">{{ $item->created_at?->translatedFormat('d M Y') }}</p></div><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $item->feedback }}</p></article>@empty<p class="py-10 text-center text-slate-500">Belum ada feedback pertemuan yang dibagikan tutor.</p>@endforelse</div></section>
    <section class="print-break rounded-xl border border-slate-200 bg-white p-5"><h2 class="font-bold text-slate-900">Target dan perkembangan tutor</h2><div class="mt-4 divide-y divide-slate-100">@forelse($progress as $item)<article class="py-4 first:pt-0"><p class="font-semibold text-slate-800">{{ $item->package?->name ?? 'Perkembangan belajar' }} <span class="font-normal text-slate-400">· {{ $item->tentor?->name }}</span></p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $item->summary }}</p>@if($item->next_target)<p class="mt-2 text-sm text-slate-600"><span class="font-semibold text-slate-800">Target berikutnya:</span> {{ $item->next_target }}</p>@endif</article>@empty<p class="py-10 text-center text-slate-500">Belum ada laporan perkembangan dari tutor.</p>@endforelse</div></section>
</div>
@endsection
