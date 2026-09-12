@extends('tutor.layout')
@section('title', 'Feedback Sesi')
@section('content')
<div class="space-y-6" x-data="{ scope: @js(old('scope', 'personal')) }">
    <div><a href="{{ route('tutor.schedule.index', ['range' => 'today']) }}" class="text-sm font-semibold text-primary hover:underline">← Kembali ke Penjadwalan</a><p class="mt-4 text-xs font-semibold uppercase tracking-wide text-primary">Feedback sesi</p><h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $session->schedule?->title ?? 'Sesi belajar' }}</h1><p class="mt-1 text-sm text-gray-500">{{ $session->start_at->translatedFormat('d M Y · H:i') }} WIB</p></div>
    <form method="POST" action="{{ route('tutor.schedule.feedback.store', $session) }}" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">@csrf
        <x-ui.input.select name="scope" label="Jenis feedback" :required="true" x-model="scope"><option value="personal">Personal</option>@if($session->study_group_id)<option value="group">Rombel</option>@endif</x-ui.input.select>
        <div x-show="scope === 'personal'"><x-ui.input.select name="user_id" label="Peserta" placeholder="Pilih peserta" x-bind:required="scope === 'personal'" x-bind:disabled="scope !== 'personal'"><option value="" disabled>Pilih peserta</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->name }}</option>@endforeach</x-ui.input.select></div>
        <x-ui.input name="title" label="Judul feedback" :value="old('title')" :required="true" placeholder="Contoh: Evaluasi sesi hari ini" />
        <x-ui.input.textarea name="feedback" label="Feedback" :value="old('feedback')" :required="true" rows="7" placeholder="Tulis feedback yang jelas dan mudah ditindaklanjuti." />
        <div class="flex justify-end gap-3 border-t border-gray-100 pt-5"><x-ui.button :href="route('tutor.schedule.index', ['range' => 'today'])" variant="outline">Batal</x-ui.button><x-ui.button type="submit" icon="ri-send-plane-line">Simpan feedback</x-ui.button></div>
    </form>
</div>
@endsection
