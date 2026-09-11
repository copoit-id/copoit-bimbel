@extends(($parentReport ?? false) ? 'parent.layout' : 'admin.layout.admin')

@section('title', 'Rekap Tryout Siswa')

@section('content')
    @php($isParentReport = $parentReport ?? false)
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-bold text-gray-900">Rekap Tryout Siswa</h1><p class="mt-1 text-sm text-gray-500">{{ $student->name }} · {{ $student->email }}</p></div>
        <div class="flex gap-2"><a href="{{ $isParentReport ? route('parent.dashboard', ['anak' => $child->id]) : route('admin.school.student-tryouts.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50"><i class="ri-arrow-left-line"></i>Kembali</a>@if($isParentReport)<x-ui.button type="button" icon="ri-printer-line" onclick="window.print()">Cetak</x-ui.button>@endif</div>
    </div>

    @if(count($chartData['subtests']) > 0)
        <section class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach($chartData['subtests'] as $index => $subtest)
                <article class="rounded-xl border border-gray-200 bg-white p-5">
                    <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Pelajaran {{ $index + 1 }}</p><h2 class="mt-1 truncate text-lg font-bold text-gray-900">{{ $subtest['title'] }}</h2></div><span class="rounded-full bg-primary/5 px-2.5 py-1 text-xs font-semibold text-primary">{{ count($subtest['values']) }} data</span></div>
                    <div class="mt-5 h-52"><canvas id="subtest-chart-{{ $index }}"></canvas></div>
                </article>
            @endforeach
        </section>

        <section class="mt-5 w-full rounded-xl border border-gray-200 bg-white p-5 sm:p-6 lg:w-2/3">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Try Out</p>
            <h2 class="mt-1 text-lg font-bold text-gray-900">Performa total tryout</h2>
            <div class="mt-4 h-64"><canvas id="tryout-performance-chart"></canvas></div>
        </section>
    @else
        <section class="mt-6 rounded-xl border border-border bg-white p-5 sm:p-6"><p class="py-10 text-center text-sm text-gray-500">Belum ada tryout yang selesai.</p></section>
    @endif

    <section class="mt-6 rounded-xl border border-border bg-white p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-gray-900">Tryout Terakhir yang Diselesaikan</h2>
        <div class="mt-5 overflow-x-auto"><table class="w-full min-w-[640px] text-left text-sm text-gray-600"><thead class="bg-gray-50 text-xs uppercase"><tr><th class="px-4 py-3">Tryout</th><th class="px-4 py-3">Attempt</th><th class="px-4 py-3">Selesai</th><th class="px-4 py-3 text-center">Skor</th>@if($isParentReport)<th class="px-4 py-3 text-right">Aksi</th>@endif</tr></thead><tbody>
            @forelse($attempts as $attempt)
                <tr class="border-t border-gray-100"><td class="px-4 py-3 font-semibold text-gray-900">{{ $attempt->tryout?->name ?? 'Tryout' }}</td><td class="px-4 py-3">{{ $attempt->attempt_token }}</td><td class="px-4 py-3">{{ $attempt->finished_at?->translatedFormat('d M Y, H:i') ?? '-' }}</td><td class="px-4 py-3 text-center">{{ $attempt->score }}</td>@if($isParentReport)<td class="px-4 py-3 text-right"><x-ui.button :href="route('parent.report.attempt', ['anak' => $child->id, 'tryout' => $attempt->tryout_id, 'attemptToken' => $attempt->attempt_token])" variant="outline" size="sm" icon="ri-file-search-line">Detail</x-ui.button></td>@endif</tr>
            @empty
                <tr><td colspan="{{ $isParentReport ? 5 : 4 }}" class="px-4 py-10 text-center">Belum ada data.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
    @if($isParentReport)
        <section class="mt-6 rounded-xl border border-border bg-white p-5 sm:p-6"><h2 class="text-lg font-semibold text-gray-900">Feedback Tutor</h2><div class="mt-4 divide-y divide-gray-100">@forelse($feedback as $item)<article class="py-4 first:pt-0"><p class="font-semibold text-gray-800">{{ $item->title }}</p><p class="mt-1 text-xs text-gray-500">{{ $item->tentor?->name ?? 'Tutor' }} · {{ $item->created_at?->translatedFormat('d M Y') }}</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $item->feedback }}</p></article>@empty<p class="py-8 text-center text-sm text-gray-500">Belum ada feedback tutor.</p>@endforelse</div></section>
    @endif
@endsection

@if($isParentReport)
    @push('styles')
        <style>
            @media print {
                header, aside, nav, .no-print { display: none !important; }
                body { background: #fff !important; }
                main { padding: 0 !important; }
                .rounded-xl { break-inside: avoid; }
            }
        </style>
    @endpush
@endif

@push('scripts')
    @if(count($chartData['subtests']) > 0)
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const chartData = @json($chartData);
            const chartOptions = (displays) => ({ responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => `Skor: ${displays[context.dataIndex]}` } } }, scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } });
            chartData.subtests.forEach((subtest, index) => {
                new Chart(document.getElementById(`subtest-chart-${index}`), {
                    type: 'line',
                    data: {
                        labels: subtest.labels,
                        datasets: [{ data: subtest.values, borderColor: '#1C3259', backgroundColor: 'rgba(28, 50, 89, .12)', tension: .38, fill: true, pointRadius: 3, pointBackgroundColor: '#1C3259' }],
                    },
                    options: chartOptions(subtest.displays),
                });
            });
            new Chart(document.getElementById('tryout-performance-chart'), { type: 'line', data: { labels: chartData.tryout.labels, datasets: [{ data: chartData.tryout.values, borderColor: '#0f766e', backgroundColor: 'rgba(13, 148, 136, .1)', fill: true, tension: .38, pointRadius: 4, pointBackgroundColor: '#0f766e' }] }, options: chartOptions(chartData.tryout.displays) });
        </script>
    @endif
@endpush
