@extends('tutor.layout')

@section('title', 'Tambah Sesi Mengajar')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div><a href="{{ route('tutor.schedule.index') }}" class="text-sm font-semibold text-primary hover:underline">← Kembali ke jadwal</a><h1 class="mt-3 text-2xl font-bold text-gray-900">Tambah Sesi Mengajar</h1><p class="mt-1 text-sm text-gray-500">Tambahkan satu pertemuan pada jadwal yang ditugaskan kepada Anda.</p></div>
    <form method="POST" action="{{ route('tutor.schedule.store') }}" class="space-y-5 rounded-xl border border-gray-200 bg-white p-6">@csrf
        <div><label class="mb-2 block text-sm font-semibold">Jadwal kelas</label><select name="class_schedule_id" required class="w-full rounded-lg border-gray-300"><option value="">Pilih jadwal</option>@foreach($schedules as $schedule)<option value="{{ $schedule->id }}" @selected(old('class_schedule_id') == $schedule->id)>{{ $schedule->title }}</option>@endforeach</select></div>
        <div class="grid gap-4 sm:grid-cols-2"><div><label class="mb-2 block text-sm font-semibold">Tanggal</label><input type="date" name="session_date" min="{{ now()->toDateString() }}" value="{{ old('session_date', now()->toDateString()) }}" required class="w-full rounded-lg border-gray-300"></div><div><label class="mb-2 block text-sm font-semibold">Mulai</label><input type="time" name="start_time" value="{{ old('start_time') }}" required class="w-full rounded-lg border-gray-300"></div><div><label class="mb-2 block text-sm font-semibold">Selesai</label><input type="time" name="end_time" value="{{ old('end_time') }}" class="w-full rounded-lg border-gray-300"></div></div>
        <div><label class="mb-2 block text-sm font-semibold">Link meeting</label><input type="url" name="meeting_url" value="{{ old('meeting_url') }}" class="w-full rounded-lg border-gray-300" placeholder="https://..."></div>
        <div><label class="mb-2 block text-sm font-semibold">Lokasi</label><input name="location" value="{{ old('location') }}" class="w-full rounded-lg border-gray-300"></div>
        <div><label class="mb-2 block text-sm font-semibold">Catatan</label><textarea name="notes" rows="3" class="w-full rounded-lg border-gray-300">{{ old('notes') }}</textarea></div>
        <div class="flex justify-end gap-3"><a href="{{ route('tutor.schedule.index') }}" class="rounded-lg border px-4 py-2 text-sm font-semibold">Batal</a><button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan sesi</button></div>
    </form>
</div>
@endsection
