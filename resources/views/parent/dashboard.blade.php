@extends('parent.layout')

@section('title', 'Ringkasan Anak')

@section('content')
    @if(! $child)
        <x-ui.card variant="flat" class="rounded-xl border border-dashed border-gray-300 px-6 py-16 text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl bg-primary/10 text-2xl text-primary"><i class="ri-user-search-line"></i></span>
            <h1 class="mt-4 text-xl font-bold text-gray-900">Belum ada anak yang terhubung</h1>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">Minta Admin menautkan akun Orang Tua ini ke akun siswa agar seluruh aktivitas belajar dapat dipantau.</p>
        </x-ui.card>
    @else
        <x-layout.page-header title="Ringkasan {{ $child->name }}" description="Pantau performa akademik, kehadiran, jadwal, dan tindak lanjut tutor dalam satu dashboard.">
            <x-slot:actions><x-ui.button :href="route('parent.report')" variant="outline" icon="ri-file-chart-line">Lihat laporan</x-ui.button></x-slot:actions>
        </x-layout.page-header>

        <x-ui.card variant="flat" class="mb-6 rounded-xl border border-gray-200">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-start gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary text-xl font-bold text-white">{{ strtoupper(mb_substr($child->name, 0, 1)) }}</span>
                    <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h2 class="truncate text-lg font-bold text-gray-900">{{ $child->name }}</h2><x-ui.badge :variant="$child->status === 'aktif' ? 'success' : 'secondary'" pill :icon="$childAccountStatus['icon']">{{ $childAccountStatus['label'] }}</x-ui.badge></div><p class="mt-1 text-sm text-gray-500">{{ $activePackages->count() }} paket aktif · {{ $assessmentSummary['completed'] }} ujian selesai</p><p class="mt-3 text-sm text-gray-600">{{ $childAccountStatus['description'] }}</p></div>
                </div>
                <div class="w-full max-w-sm"><div class="flex items-center justify-between text-sm"><span class="font-medium text-gray-600">Konsistensi kehadiran</span><span class="font-bold text-primary">{{ $attendanceSummary['rate'] ?? '—' }}{{ $attendanceSummary['rate'] !== null ? '%' : '' }}</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-primary" style="width: {{ $attendanceSummary['rate'] ?? 0 }}%"></div></div><p class="mt-2 text-xs text-gray-500">{{ $attendanceSummary['present'] }} dari {{ $attendanceSummary['total'] }} sesi tercatat hadir.</p></div>
            </div>
        </x-ui.card>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-dashboard.stat-card label="Tingkat Kehadiran" :value="($attendanceSummary['rate'] ?? '—').($attendanceSummary['rate'] !== null ? '%' : '')" icon="ri-calendar-check-line" color="green" />
            <x-dashboard.stat-card label="Rata-rata Nilai" :value="$assessmentSummary['completed'] ? number_format($assessmentSummary['average_score'], 1) : '—'" icon="ri-line-chart-line" color="blue" />
            <x-dashboard.stat-card label="Nilai Tertinggi" :value="$assessmentSummary['completed'] ? number_format($assessmentSummary['highest_score'], 1) : '—'" icon="ri-medal-line" color="orange" />
            <x-dashboard.stat-card label="Paket Aktif" :value="$activePackages->count()" icon="ri-book-open-line" color="primary" />
        </section>

        @if($alerts->isNotEmpty())
            <div class="mt-6 rounded-xl border border-yellow-200 bg-yellow-50 px-5 py-4"><div class="flex items-start gap-3"><i class="ri-error-warning-line mt-0.5 text-xl text-yellow-700"></i><div><h2 class="font-semibold text-yellow-900">Perlu perhatian</h2><div class="mt-1 space-y-1">@foreach($alerts as $alert)<p class="text-sm text-yellow-800">{{ $alert['text'] }}</p>@endforeach</div></div></div></div>
        @endif

        <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.6fr)]">
            <x-dashboard.chart-card title="Tren Nilai Tryout" subtitle="Perubahan hasil pada pengerjaan terbaru" :value="$scoreTrendChart['last_score'] !== null ? number_format($scoreTrendChart['last_score'], 1) : '—'" :trend="$scoreTrendChart['change'] === null ? 'neutral' : ($scoreTrendChart['change'] >= 0 ? 'up' : 'down')" :trend-value="$scoreTrendChart['change_label']" :period-selector="false" chart-id="parent-score-chart" chart-type="line" />
            <x-ui.card variant="flat" class="rounded-xl border border-gray-200">
                <x-ui.card.header title="Jadwal Terdekat" subtitle="Sesi yang sudah disetujui tutor" :border="true" />
                <x-ui.card.body class="divide-y divide-gray-100">
                    @forelse($upcomingBookings as $booking)<div class="py-3 first:pt-0"><p class="font-semibold text-gray-900">{{ $booking->package?->name ?? 'Sesi bimbingan' }}</p><p class="mt-1 text-sm text-gray-500">{{ $booking->scheduled_start_at?->translatedFormat('D, d M · H:i') }} · {{ $booking->tentor?->name ?? 'Tutor' }}</p></div>@empty<div class="py-10 text-center text-sm text-gray-500"><i class="ri-calendar-event-line mb-2 block text-3xl text-gray-300"></i>Belum ada jadwal mendatang.</div>@endforelse
                </x-ui.card.body>
            </x-ui.card>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-2">
            <x-ui.card variant="flat" class="rounded-xl border border-gray-200"><x-ui.card.header title="Hasil Ujian Terbaru" subtitle="Ringkasan performa terkini"><x-slot:action><x-ui.button :href="route('parent.assessments')" variant="ghost" size="sm">Lihat semua</x-ui.button></x-slot:action></x-ui.card.header><div class="overflow-x-auto"><table class="w-full min-w-[520px] text-left text-sm text-gray-600"><thead class="bg-gray-50 text-xs uppercase text-gray-600"><tr><th class="px-4 py-3">Tryout</th><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3 text-right">Skor</th></tr></thead><tbody>@forelse($recentAnswers as $answer)<tr class="border-t border-gray-100 hover:bg-gray-50/70"><td class="px-4 py-3 font-semibold text-gray-900">{{ $answer->tryout_name }}</td><td class="px-4 py-3">{{ \Carbon\Carbon::parse($answer->finished_at)->translatedFormat('d M Y') }}</td><td class="px-4 py-3 text-right font-bold text-primary">{{ number_format((float) $answer->score, 1) }}</td></tr>@empty<tr><td colspan="3" class="px-4 py-10 text-center text-gray-500">Belum ada ujian selesai.</td></tr>@endforelse</tbody></table></div></x-ui.card>
            <x-ui.card variant="flat" class="rounded-xl border border-gray-200"><x-ui.card.header title="Feedback Tutor Terbaru" subtitle="Catatan tindak lanjut untuk orang tua"><x-slot:action><x-ui.button :href="route('parent.development')" variant="ghost" size="sm">Lihat semua</x-ui.button></x-slot:action></x-ui.card.header><div class="divide-y divide-gray-100">@forelse($recentFeedback as $feedback)<article class="py-4 first:pt-0"><div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-gray-900">{{ $feedback->title }}</p><p class="mt-1 text-xs text-gray-500">{{ $feedback->tentor?->name ?? 'Tutor' }} · {{ $feedback->created_at?->translatedFormat('d M Y') }}</p></div><i class="ri-message-3-line text-primary"></i></div><p class="mt-2 line-clamp-2 text-sm leading-6 text-gray-600">{{ $feedback->feedback }}</p></article>@empty<div class="py-10 text-center text-sm text-gray-500">Belum ada feedback tutor.</div>@endforelse</div></x-ui.card>
        </section>
    @endif
@endsection

@push('scripts')
    @if($child)
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            new Chart(document.getElementById('parent-score-chart'), {
                type: 'line',
                data: { labels: @json($scoreTrendChart['labels']), datasets: [{ data: @json($scoreTrendChart['values']), borderColor: '#1C3259', backgroundColor: 'rgba(28, 50, 89, .10)', fill: true, tension: .38, pointRadius: 4, pointBackgroundColor: '#1C3259' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } }
            });
        </script>
    @endif
@endpush
