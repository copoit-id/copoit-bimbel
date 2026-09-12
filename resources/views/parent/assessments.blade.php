@extends('parent.layout')

@section('title', 'Riwayat Ujian')

@section('content')
    <x-layout.page-header title="Riwayat Ujian" description="Rekap tryout, tren nilai, dan seluruh pengerjaan {{ $child?->name ?? 'anak' }}.">
        <x-slot:actions>@if($child)<x-ui.button :href="route('parent.report')" variant="outline" icon="ri-file-chart-line">Laporan lengkap</x-ui.button>@endif</x-slot:actions>
    </x-layout.page-header>

    @if($child)
        <section class="grid gap-4 sm:grid-cols-3">
            <x-dashboard.stat-card label="Pengerjaan Selesai" :value="$assessmentSummary['completed']" icon="ri-check-double-line" />
            <x-dashboard.stat-card label="Rata-rata Nilai" :value="number_format($assessmentSummary['average_score'], 1)" icon="ri-line-chart-line" color="blue" />
            <x-dashboard.stat-card label="Nilai Tertinggi" :value="number_format($assessmentSummary['highest_score'], 1)" icon="ri-medal-line" color="orange" />
        </section>

        <div class="mt-6"><x-dashboard.chart-card title="Tren Nilai Tryout" :subtitle="$assessmentTrendChart['label']" :value="$assessmentTrendChart['last_score'] !== null ? number_format($assessmentTrendChart['last_score'], 1) : '—'" :trend="$assessmentTrendChart['change'] === null ? 'neutral' : ($assessmentTrendChart['change'] >= 0 ? 'up' : 'down')" :trend-value="$assessmentTrendChart['change_label']" :period-selector="false" chart-id="assessment-trend-chart" chart-type="line" /></div>

        <x-ui.card variant="flat" class="mt-6 rounded-xl border border-gray-200" padding="none">
            <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-lg font-semibold text-gray-900">Daftar Tryout</h2><p class="mt-1 text-sm text-gray-500">Buka tryout untuk melihat setiap percobaan dan detail jawabannya.</p></div><x-ui.badge variant="light" pill>{{ $assessmentItems->total() }} tryout</x-ui.badge></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm text-gray-600"><thead class="bg-gray-50 text-xs uppercase text-gray-600"><tr><th class="px-5 py-3">Tryout</th><th class="px-5 py-3 text-center">Percobaan</th><th class="px-5 py-3 text-center">Rata-rata</th><th class="px-5 py-3 text-center">Tertinggi</th><th class="px-5 py-3">Terakhir Dikerjakan</th><th class="px-5 py-3 text-center">Aksi</th></tr></thead><tbody>@forelse($assessmentItems as $item)<tr class="border-t border-gray-100 hover:bg-gray-50/70"><td class="px-5 py-4 font-semibold text-gray-900">{{ $item->tryout_name }}</td><td class="px-5 py-4 text-center">{{ $item->attempt_count }}</td><td class="px-5 py-4 text-center font-semibold">{{ number_format($item->average_score, 1) }}</td><td class="px-5 py-4 text-center font-bold text-primary">{{ number_format($item->highest_score, 1) }}</td><td class="px-5 py-4">{{ \Carbon\Carbon::parse($item->last_finished_at)->translatedFormat('d M Y, H:i') }}</td><td class="px-5 py-4 text-center"><x-ui.button :href="route('parent.assessments.detail', ['tryout' => $item->tryout_id])" variant="outline" size="sm" icon="ri-bar-chart-2-line">Detail</x-ui.button></td></tr>@empty<tr><td colspan="6" class="px-5 py-14 text-center text-gray-500"><i class="ri-file-list-3-line mb-2 block text-3xl text-gray-300"></i>Belum ada tryout yang diselesaikan.</td></tr>@endforelse</tbody></table></div>
            @if($assessmentItems->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $assessmentItems->links() }}</div>@endif
        </x-ui.card>
    @else
        <x-ui.card variant="flat" class="rounded-xl border border-dashed border-gray-300 px-5 py-14 text-center"><i class="ri-user-search-line text-4xl text-gray-300"></i><p class="mt-3 font-semibold text-gray-700">Belum ada anak yang dapat dipantau</p></x-ui.card>
    @endif
@endsection

@push('scripts')
    @if($child)
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>new Chart(document.getElementById('assessment-trend-chart'), { type: 'line', data: { labels: @json($assessmentTrendChart['labels']), datasets: [{ data: @json($assessmentTrendChart['values']), borderColor: '#1C3259', backgroundColor: 'rgba(28, 50, 89, .10)', fill: true, tension: .38, pointRadius: 4, pointBackgroundColor: '#1C3259' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } } });</script>
    @endif
@endpush
