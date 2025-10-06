@extends('user.layout.user')
@section('title', 'Tryout Paket')
@section('content')
<div class="dashboard">
    <x-page-desc title="Tryout - {{ $package->name }}" description="Daftar tryout yang tersedia dalam paket ini">
    </x-page-desc>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-6 text-gray-600">
        @forelse($tryouts as $tryout)
        @php
        $tryoutDetail = $tryout->tryoutDetails->first();
        $questionCount = 0;
        if ($tryoutDetail) {
        $questionCount = \App\Models\Question::where('tryout_detail_id', $tryoutDetail->tryout_detail_id)->count();
        }
        $userAttempts = $tryout->userAnswers->count();
        $lastAttempt = $tryout->userAnswers->sortByDesc('created_at')->first();
        @endphp

        <div class="bg-white px-5 py-5 shadow rounded-lg flex flex-col justify-between">
            <div class="flex flex-col gap-1 mb-4">
                <p class="text-lg font-bold text-black text-center mb-4">{{ $tryout->name }}</p>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Jumlah Soal:</p>
                    <p class="font-light">{{ $questionCount }} Soal</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Durasi:</p>
                    <p class="font-light">{{ $tryoutDetail ? $tryoutDetail->duration : 0 }} Menit</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Tipe:</p>
                    <p class="font-light">{{ ucfirst($tryout->type_tryout) }}</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Dikerjakan:</p>
                    <p class="font-light">{{ $userAttempts }} Kali</p>
                </span>
                @if($lastAttempt)
                <span class="flex items-center justify-between">
                    <p class="font-medium">Skor Terakhir:</p>
                    <p class="font-light {{ $lastAttempt->percentage >= 70 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($lastAttempt->percentage ?? 0, 1) }}%
                    </p>
                </span>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2 font-light">
                @if($questionCount > 0)
                <a href="{{ route('user.tryout.lobby', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]) }}"
                    class="flex w-full justify-center bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-primary/90 transition-colors">
                    Kerjakan
                </a>
                @else
                <button
                    class="flex w-full justify-center bg-gray-400 text-white px-4 py-2 rounded-lg text-sm cursor-not-allowed"
                    disabled>
                    Belum Ada Soal
                </button>
                @endif

                @if($userAttempts > 0)
                <a href="{{ route('user.package.tryout.riwayat', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]) }}"
                    class="flex w-full justify-center border border-primary text-primary px-4 py-2 rounded-lg text-sm hover:bg-primary hover:text-white transition-colors">
                    Riwayat
                </a>
                @endif

                <a href="{{ route('user.package.tryout.ranking', ['id_package' =>$package->package_id, 'id_tryout' => $tryout->tryout_id]) }}"
                    class="flex justify-center border border-primary text-primary px-3 py-2 rounded-lg text-sm hover:bg-primary hover:text-white transition-colors">
                    <i class="ri-bar-chart-2-fill"></i>
                </a>
            </div>

            @if($lastAttempt && $lastAttempt->is_completed)
            <div class="mt-3">
                <a href="{{ route('user.package.tryout.pembahasan', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]) }}"
                    class="flex w-full justify-center bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition-colors">
                    Lihat Pembahasan
                </a>
            </div>
            @endif
        </div>
        @empty
        <div class="col-span-full text-center py-8">
            <i class="ri-file-list-line text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500">Belum ada tryout tersedia dalam paket ini</p>
        </div>
        @endforelse
    </div>
</div>
@endsection

@section('scripts')

@endsection
@section('styles')