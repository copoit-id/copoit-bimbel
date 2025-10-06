@extends('user.layout.tryout')
@section('title', 'Lobby')
@section('content')
<div
    class="lobby flex flex-col md:flex-row gap-4 md:justify-center items-center md:items-start mt-10 w-full h-screen text-dark">
    <div class="w-full md:w-4xl flex justify-center items-start flex-col gap-2 text-dark">
        <div class="bg-white shadow rounded-lg px-6 py-8 flex-col gap-2">
            <p class="font-bold">Soal 1 dari 10</p>
            <p class="font-light">Semua karyawan di perusahaan A mendapat pelatihan. Budi adalah karyawan di perusahaan
                A.
                Kesimpulan yang
                benar adalah...</p>
            <div class="flex flex-col gap-2 mt-4 w-full">
                <div class="flex w-full items-center gap-1 font-light border border-gray-900/10 px-4 py-2 rounded-lg">
                    <input type="radio" name="answer" id="answer1" class="mr-2">
                    <p>Budi tidak mengikuti pelatihan</p>
                </div>
                <div class="flex w-full items-center gap-1 font-light border border-gray-900/10 px-4 py-2 rounded-lg">
                    <input type="radio" name="answer" id="answer1" class="mr-2">
                    <p>Budi tidak mengikuti pelatihan</p>
                </div>
                <div class="flex w-full items-center gap-1 font-light border border-gray-900/10 px-4 py-2 rounded-lg">
                    <input type="radio" name="answer" id="answer1" class="mr-2">
                    <p>Budi tidak mengikuti pelatihan</p>
                </div>
                <div class="flex w-full items-center gap-1 font-light border border-gray-900/10 px-4 py-2 rounded-lg">
                    <input type="radio" name="answer" id="answer1" class="mr-2">
                    <p>Budi tidak mengikuti pelatihan</p>
                </div>
            </div>
        </div>
        <div class="flex justify-between items-center w-full">
            <a href="{{ route('user.tryout.lobby', ['id_package' => 1, 'id_tryout' => 1]) }}"
                class="flex justify-center border border-primary text-primary px-8 py-2 rounded-lg mt-4 text-sm">Sebelumnya</a>
            <a href="{{ route('user.tryout.lobby', ['id_package' => 1, 'id_tryout' => 1]) }}"
                class="flex justify-center border border-red text-red px-8 py-2 rounded-lg mt-4 text-sm"><i
                    class="ri-flag-fill me-1"></i>Tandai</a>
            <a href="{{ route('user.tryout.result', ['id_package' => 1, 'id_tryout' => 1]) }}"
                class="flex justify-center bg-primary text-white px-8 py-2 rounded-lg mt-4 text-sm">Selanjutnya</a>
        </div>
    </div>
    <div class="p-6 bg-white shadow rounded-lg flex justify-center items-start flex-col gap-2">
        <p class="font-bold">Navigasi Soal</p>
        <div class="grid grid-cols-5 gap-4 mt-2">
            <button class="w-10 h-10 flex justify-center items-center rounded-lg bg-primary text-white">1</button>
            <button
                class="w-10 h-10 flex justify-center items-center rounded-lg border border-primary text-primary">2</button>
            <button
                class="w-10 h-10 flex justify-center items-center rounded-lg border border-primary text-primary">3</button>
            <button
                class="w-10 h-10 flex justify-center items-center rounded-lg border border-primary text-primary">4</button>
            <button
                class="w-10 h-10 flex justify-center items-center rounded-lg border border-primary text-primary">5</button>
            <button
                class="w-10 h-10 flex justify-center items-center rounded-lg border border-primary text-primary">6</button>
            <button
                class="w-10 h-10 flex justify-center items-center rounded-lg border border-primary text-primary">7</button>
            <button
                class="w-10 h-10 flex justify-center items-center rounded-lg border border-primary text-primary">8</button>
            <button
                class="w-10 h-10 flex justify-center items-center rounded-lg border border-primary text-primary">9</button>
            <button
                class="w-10 h-10 flex justify-center items-center rounded-lg border border-primary text-primary">10</button>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
    console.log('Dashboard scripts loaded');
</script>
@endsection
@section('styles')