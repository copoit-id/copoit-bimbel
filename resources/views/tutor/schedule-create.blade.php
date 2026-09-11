@extends('tutor.layout')

@section('title', 'Tambah Sesi Mengajar')

@section('content')
<div class="space-y-6">
    <div class="border-b border-gray-200 pb-5"><a href="{{ route('tutor.schedule.index') }}" class="text-sm font-semibold text-primary hover:underline">← Kembali ke Penjadwalan</a><h1 class="mt-3 text-2xl font-bold text-gray-900">Tambah sesi mengajar</h1><p class="mt-1 text-sm text-gray-500">Tambahkan satu pertemuan pada jadwal yang sudah ditugaskan kepada Anda.</p></div>
    <form method="POST" action="{{ route('tutor.schedule.store') }}" class="w-full rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 lg:p-8">@csrf
        <div class="grid gap-5 lg:grid-cols-2"><div class="lg:col-span-2"><x-ui.input.select name="class_schedule_id" label="Jadwal kelas" placeholder="Pilih jadwal" :required="true"><option value="" disabled @selected(! old('class_schedule_id'))>Pilih jadwal</option>@foreach($schedules as $schedule)<option value="{{ $schedule->id }}" @selected(old('class_schedule_id') == $schedule->id)>{{ $schedule->title }}</option>@endforeach</x-ui.input.select></div><x-ui.input name="session_date" type="date" label="Tanggal sesi" :value="old('session_date', now()->toDateString())" :required="true" :min="now()->toDateString()" /><x-ui.input name="start_time" type="time" label="Waktu mulai" :value="old('start_time')" :required="true" /><x-ui.input name="end_time" type="time" label="Waktu selesai" :value="old('end_time')" helper="Opsional bila durasi mengikuti jadwal kelas." /><x-ui.input name="location" label="Lokasi" :value="old('location')" placeholder="Contoh: Ruang A atau Google Meet" /><div class="lg:col-span-2"><x-ui.input name="meeting_url" type="url" label="Link meeting" :value="old('meeting_url')" placeholder="https://..." helper="Isi untuk sesi online." /></div><div class="lg:col-span-2"><x-ui.input.textarea name="notes" label="Catatan sesi" :value="old('notes')" rows="5" placeholder="Catatan untuk tutor atau peserta (opsional)." /></div></div>
        <div class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end"><x-ui.button :href="route('tutor.schedule.index')" variant="outline">Batal</x-ui.button><x-ui.button type="submit" icon="ri-save-line">Simpan sesi</x-ui.button></div>
    </form>
</div>
@endsection
