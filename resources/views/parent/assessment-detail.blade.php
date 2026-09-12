@extends('parent.layout')

@section('title', 'Detail Tryout')

@section('content')
    <x-layout.page-header :title="$tryout->name" description="Rincian setiap pengerjaan dan perkembangan nilai {{ $child->name }}."
        :breadcrumb="[['label' => 'Riwayat Ujian', 'url' => route('parent.assessments')], ['label' => $tryout->name, 'url' => null]]">
        <x-slot:actions><x-ui.button :href="route('parent.assessments')" variant="outline" icon="ri-arrow-left-line">Kembali</x-ui.button></x-slot:actions>
    </x-layout.page-header>

    <section class="grid gap-4 sm:grid-cols-3">
        <x-dashboard.stat-card label="Percobaan Selesai" :value="$attemptSummary['completed']" icon="ri-repeat-2-line" />
        <x-dashboard.stat-card label="Rata-rata Nilai" :value="number_format($attemptSummary['average_score'], 1)" icon="ri-line-chart-line" color="blue" />
        <x-dashboard.stat-card label="Nilai Tertinggi" :value="number_format($attemptSummary['highest_score'], 1)" icon="ri-medal-line" color="orange" />
    </section>

    <div class="mt-6"><x-dashboard.chart-card title="Perkembangan Nilai" :subtitle="$attemptTrendChart['label']" :value="$attemptTrendChart['last_score'] !== null ? number_format($attemptTrendChart['last_score'], 1) : '—'" :trend="$attemptTrendChart['change'] === null ? 'neutral' : ($attemptTrendChart['change'] >= 0 ? 'up' : 'down')" :trend-value="$attemptTrendChart['change_label']" :period-selector="false" chart-id="attempt-trend-chart" chart-type="line" /></div>

    <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.55fr)]">
        <x-ui.card variant="flat" class="rounded-xl border border-gray-200" padding="none">
            <div class="border-b border-gray-100 px-5 py-4"><h2 class="text-lg font-semibold text-gray-900">Seluruh Percobaan</h2><p class="mt-1 text-sm text-gray-500">Nilai, akurasi, waktu selesai, dan detail jawaban.</p></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[720px] text-left text-sm text-gray-600"><thead class="bg-gray-50 text-xs uppercase text-gray-600"><tr><th class="px-5 py-3">Selesai</th><th class="px-5 py-3 text-center">Benar</th><th class="px-5 py-3 text-center">Salah</th><th class="px-5 py-3 text-center">Kosong</th><th class="px-5 py-3 text-center">Skor</th><th class="px-5 py-3 text-center">Aksi</th></tr></thead><tbody>@forelse($attempts as $attempt)<tr class="border-t border-gray-100 hover:bg-gray-50/70"><td class="px-5 py-4 font-medium text-gray-800">{{ \Carbon\Carbon::parse($attempt->finished_at)->translatedFormat('d M Y, H:i') }}</td><td class="px-5 py-4 text-center text-green-700">{{ $attempt->correct_answers }}</td><td class="px-5 py-4 text-center text-red-600">{{ $attempt->wrong_answers }}</td><td class="px-5 py-4 text-center">{{ $attempt->unanswered }}</td><td class="px-5 py-4 text-center font-bold text-primary">{{ number_format($attempt->score, 1) }}</td><td class="px-5 py-4 text-center"><x-ui.button :href="route('parent.report.attempt', ['tryout' => $attempt->tryout_id, 'attemptToken' => $attempt->attempt_key])" variant="outline" size="sm" icon="ri-file-search-line">Detail Jawaban</x-ui.button></td></tr>@empty<tr><td colspan="6" class="px-5 py-14 text-center text-gray-500">Belum ada percobaan selesai.</td></tr>@endforelse</tbody></table></div>
            @if($attempts->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $attempts->links() }}</div>@endif
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card variant="flat" class="rounded-xl border border-gray-200"><x-ui.card.header title="Feedback Tutor" subtitle="Catatan yang dibagikan untuk orang tua" /><div class="divide-y divide-gray-100">@forelse($feedback as $item)<article class="py-4 first:pt-0"><p class="font-semibold text-gray-900">{{ $item->title }}</p><p class="mt-1 text-xs text-gray-500">{{ $item->tentor?->name ?? 'Tutor' }} · {{ $item->created_at?->translatedFormat('d M Y') }}</p><p class="mt-2 line-clamp-3 text-sm leading-6 text-gray-600">{{ $item->feedback }}</p></article>@empty<p class="py-8 text-center text-sm text-gray-500">Belum ada feedback tutor.</p>@endforelse</div></x-ui.card>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>new Chart(document.getElementById('attempt-trend-chart'), { type: 'line', data: { labels: @json($attemptTrendChart['labels']), datasets: [{ data: @json($attemptTrendChart['values']), borderColor: '#1C3259', backgroundColor: 'rgba(28, 50, 89, .10)', fill: true, tension: .38, pointRadius: 4, pointBackgroundColor: '#1C3259' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } } });</script>
@endpush
