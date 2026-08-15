@extends('admin.layout.admin')
@section('title', 'Peringkat Nilai Tryout')

@section('content')
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <x-breadcrumb>
            <x-slot name="items">
                <x-breadcrumb-item href="{{ route('admin.laporan.index') }}" title="Laporan Tryout" />
                <x-breadcrumb-item href="{{ route('admin.laporan.show', $tryout->tryout_id) }}" title="{{ $tryout->name }}" />
                <x-breadcrumb-item href="" title="Peringkat Nilai" />
            </x-slot>
        </x-breadcrumb>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.laporan.show', $tryout->tryout_id) }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                <i class="ri-arrow-left-line"></i> Ringkasan Pengerjaan
            </a>
            <a href="{{ $publicLiveScoreUrl }}" target="_blank"
                class="inline-flex items-center gap-2 rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary transition hover:bg-primary hover:text-white">
                <i class="ri-external-link-line"></i> Public Link
            </a>
            <button type="button" id="copy-live-score-link" data-link="{{ $publicLiveScoreUrl }}"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90">
                <i class="ri-link-m"></i> Copy Link
            </button>
        </div>
    </div>

    <x-page-desc title="Peringkat Nilai - {{ $tryout->name }}" />

    <section class="mt-6 overflow-hidden rounded-xl border border-border bg-white">
        <div class="border-b border-gray-100 px-5 py-5 sm:px-6">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Live Score</p>
            <h2 class="mt-1 text-xl font-semibold text-gray-900">Peringkat Peserta per Subtest</h2>
            <p class="mt-1 text-sm text-gray-500">Urutan berdasarkan akumulasi skor seluruh subtest yang telah tersubmit.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm text-gray-600">
                <thead class="border-b border-gray-100 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="w-20 px-5 py-4 text-center">Peringkat</th>
                        <th class="px-5 py-4">Peserta</th>
                        @foreach ($liveScore['subtests'] ?? [] as $subtest)
                            <th class="px-5 py-4 text-center">{{ $subtest['label'] }}</th>
                        @endforeach
                        <th class="px-5 py-4 text-center">Total Skor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($liveScore['rows'] ?? [] as $row)
                        @php
                            $rankClass = match ((int) $row['rank']) {
                                1 => 'bg-gray-900 text-white',
                                2 => 'bg-gray-600 text-white',
                                3 => 'bg-gray-400 text-white',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <tr class="border-b border-gray-100 transition hover:bg-gray-50/70">
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full px-2 text-xs font-bold {{ $rankClass }}">{{ $row['rank'] }}</span>
                            </td>
                            <td class="px-5 py-4 font-semibold text-gray-900">{{ $row['name'] }}</td>
                            @foreach ($liveScore['subtests'] ?? [] as $subtest)
                                @php $scoreValue = $row['scores'][$subtest['tryout_detail_id']] ?? null; @endphp
                                <td class="px-5 py-4 text-center font-medium {{ is_null($scoreValue) ? 'text-gray-400' : 'text-gray-800' }}">
                                    {{ is_null($scoreValue) ? '-' : rtrim(rtrim(number_format((float) $scoreValue, 2, '.', ''), '0'), '.') }}
                                </td>
                            @endforeach
                            <td class="px-5 py-4 text-center text-base font-bold text-primary">
                                {{ rtrim(rtrim(number_format((float) ($row['total'] ?? 0), 2, '.', ''), '0'), '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + count($liveScore['subtests'] ?? []) }}" class="px-6 py-14 text-center text-gray-500">
                                Belum ada data live score yang tersubmit.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        document.getElementById('copy-live-score-link')?.addEventListener('click', async (event) => {
            const button = event.currentTarget;
            try {
                await navigator.clipboard.writeText(button.dataset.link || '');
                button.innerHTML = '<i class="ri-check-line"></i> Link Tersalin';
                setTimeout(() => { button.innerHTML = '<i class="ri-link-m"></i> Copy Link'; }, 1500);
            } catch (_) {
                button.textContent = 'Gagal menyalin';
            }
        });
    </script>
@endsection
