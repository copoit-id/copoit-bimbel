@extends('user.layout.tryout')
@section('title', 'Lobby')
@section('content')
<div class="lobby flex justify-center items-center w-full min:h-screen text-black">
    <div class="w-3xl mx-auto p-8 mt-18 bg-white shadow rounded-lg flex justify-center items-center flex-col gap-2">
        <div class="rounded-lg text-center">
            <h1 class="text-2xl font-bold mb-4">{{ $tryout->name }}</h1>
            <p class="text-gray-600 mb-6">{{ $tryout->description }}</p>

            @if(isset($tryoutDetails) && $tryoutDetails->count() > 1)
            <!-- SKD Full Information -->
            {{-- <div class="bg-blue-50 border border-primary/10 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-primary mb-4">
                    <i class="ri-information-line mr-2"></i>Informasi SKD Full
                </h3>
                <div class="space-y-4">
                    @foreach($tryoutDetails as $index => $detail)
                    <div class="flex justify-between items-center p-3 bg-white rounded-lg">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm font-semibold">
                                {{ $index + 1 }}
                            </div>
                            <div class="text-left">
                                <div class="font-semibold">{{ strtoupper($detail->type_subtest) }}</div>
                                <div class="text-sm text-gray-600">
                                    @if($detail->type_subtest === 'twk')
                                    Tes Wawasan Kebangsaan
                                    @elseif($detail->type_subtest === 'tiu')
                                    Tes Intelegensi Umum
                                    @elseif($detail->type_subtest === 'tkp')
                                    Tes Karakteristik Pribadi
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            @php
                            $questionCount = \App\Models\Question::where('tryout_detail_id',
                            $detail->tryout_detail_id)->count();
                            @endphp
                            <div class="font-semibold">{{ $questionCount }} Soal</div>
                            <div class="text-sm text-gray-600">{{ $detail->duration }} Menit</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div> --}}

            <!-- Total Information for SKD Full -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="flex items-center gap-3 bg-white p-4 rounded-lg border border-border mt-6">
                    <i
                        class="ri-book-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
                    <div>
                        <p class="text-[24px] font-bold">{{ $totalQuestions }}</p>
                        <p class="text-[12px] mt-[-6px] font-light">Total Soal</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white p-4 rounded-lg border border-border mt-6">
                    <i
                        class="ri-book-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
                    <div>
                        <p class="text-[24px] font-bold">{{ $totalDuration }}</p>
                        <p class="text-[12px] mt-[-6px] font-light">Total Waktu (menit)</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white p-4 rounded-lg border border-border mt-6">
                    <i
                        class="ri-book-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
                    <div>
                        <p class="text-[24px] font-bold">{{ $tryoutDetails->count() }}</p>
                        <p class="text-[12px] mt-[-6px] font-light">Jumlah Jumlah</p>
                    </div>
                </div>
            </div>
            @else
            <!-- Single Subtest Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-primary">{{ $totalQuestions ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Jumlah Soal</div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-primary">{{ $totalDuration ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Durasi (Menit)</div>
                </div>
            </div>
            @endif

            <div class="flex flex-col gap-2 mt-4">
                <span class="flex items-center gap-2 justify-start">
                    <i class="ri-checkbox-circle-fill text-lg"></i>
                    <p>Tidak ada aktifitas lain di akun kamu selama mengerjakan tryout.</p>
                </span>
                <span class="flex items-center gap-2 justify-start">
                    <i class="ri-checkbox-circle-fill text-lg"></i>
                    <p>Pastikan koneksi internet stabil.</p>
                </span>
                <span class="flex items-center gap-2 justify-start">
                    <i class="ri-checkbox-circle-fill text-lg"></i>
                    <p>Jawab semua soal dengan teliti.</p>
                </span>
            </div>

            <a href="{{ route('user.tryout.index', ['id_package' => $package ? $package->package_id : 'free', 'id_tryout' => $tryout->tryout_id, 'number' => 1]) }}"
                class="mt-4 px-8 py-1.5 bg-primary flex justify-center text-white rounded-xl">
                Mulai Tryout
            </a>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
    console.log('Tryout lobby loaded');
</script>
@endsection
@section('styles')
<style>
    /* Add any additional styles if needed */
</style>
@endsection
