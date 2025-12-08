@extends('user.layout.tryout')

@section('title', 'Menunggu Hasil UTBK')

@section('content')
<div class="min-h-screen flex items-center justify-center py-16 px-4">
    <div class="max-w-2xl w-full bg-white rounded-2xl border border-border p-10 text-center">
        <div class="flex flex-col items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                <i class="ri-timer-line text-2xl text-primary"></i>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Nilai UTBK Sedang Diproses</h1>
            <p class="text-sm uppercase tracking-wide text-gray-500">{{ $tryout->name }}</p>
            <p class="text-gray-600 leading-relaxed">
                Terima kasih telah menyelesaikan tryout UTBK. Sistem sedang menunggu seluruh peserta menyelesaikan ujian
                untuk menghitung skor IRT. Nilai Anda akan otomatis muncul setelah periode tryout berakhir.
            </p>
            <div class="bg-primary/5 border border-primary/20 rounded-xl p-4 w-full">
                <p class="text-sm text-gray-600">Perkiraan rilis nilai</p>
                <p class="text-lg font-semibold text-primary mt-1">
                    {{ $releaseTime ? $releaseTime->translatedFormat('d F Y H:i') : 'Segera setelah jadwal tryout berakhir' }}
                </p>
            </div>
            <a href="{{ route('user.dashboard.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90">
                <i class="ri-arrow-left-line"></i>Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
