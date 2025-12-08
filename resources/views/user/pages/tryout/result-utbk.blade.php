@extends('user.layout.tryout')

@section('title', 'Hasil UTBK')

@section('content')
<div class="min-h-screen bg-gray-50 py-10">
    <div class="max-w-5xl mx-auto space-y-6">
        <div class="bg-white border border-border rounded-2xl p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <p class="text-sm text-gray-500">Tryout UTBK</p>
                    <h1 class="text-2xl font-semibold text-gray-900 mt-1">{{ $tryout->name }}</h1>
                    <p class="text-gray-500 text-sm mt-2">Attempt token: {{ $attemptToken }}</p>
                </div>
                <div class="text-center">
                    <p class="text-sm text-gray-500">Nilai Total</p>
                    <p class="text-4xl font-bold text-primary">{{ number_format($totalScore) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Skala 0 - 1000</p>
                </div>
            </div>
        </div>

        <div class="bg-white border border-border rounded-2xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Rincian Subtest</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtest</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Benar</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Salah</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Kosong</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Skor Subtest</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($subtests as $subtest)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-gray-900">{{ $subtest['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ ucfirst($subtest['type']) }}</p>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-900">{{ $subtest['correct'] }}</td>
                            <td class="px-4 py-3 text-center text-sm text-gray-900">{{ $subtest['wrong'] }}</td>
                            <td class="px-4 py-3 text-center text-sm text-gray-900">{{ $subtest['unanswered'] }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold bg-primary/10 text-primary">{{ number_format($subtest['score']) }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white border border-border rounded-2xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Catatan</h2>
            <p class="text-sm text-gray-600 leading-relaxed">
                Nilai UTBK dihitung menggunakan pendekatan IRT sederhana. Semakin banyak peserta lain yang menjawab benar pada sebuah soal,
                semakin kecil bobot soal tersebut pada perhitungan nilai akhir Anda. Skor ditampilkan setelah seluruh peserta menyelesaikan tryout.
            </p>
        </div>

        <div class="flex flex-wrap gap-3 justify-center">
            @if($package)
            <a href="{{ route('user.package.tryout', $package->package_id) }}"
                class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="ri-arrow-left-line mr-2"></i>Kembali
            </a>
            <a href="{{ route('user.package.tryout.riwayat', [$package->package_id, $tryout->tryout_id]) }}"
                class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                <i class="ri-history-line mr-2"></i>Riwayat
            </a>
            <a href="{{ route('user.package.tryout.ranking', [$package->package_id, $tryout->tryout_id]) }}"
                class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                <i class="ri-trophy-line mr-2"></i>Ranking
            </a>
            @else
            <a href="{{ route('user.event.index') }}"
                class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="ri-arrow-left-line mr-2"></i>Kembali ke Event
            </a>
            <a href="{{ route('user.dashboard.index') }}"
                class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                <i class="ri-home-2-line mr-2"></i>Dashboard
            </a>
            @endif
        </div>
    </div>
</div>
@endsection
