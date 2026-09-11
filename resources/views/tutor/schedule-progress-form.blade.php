@extends('tutor.layout')

@section('title', 'Catatan Progress Sesi')

@section('content')
@php($isEditing = isset($report))
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><a href="{{ route('tutor.schedule.index', ['range' => 'today']) }}" class="text-sm font-semibold text-primary hover:underline">← Kembali ke Penjadwalan</a><p class="mt-4 text-xs font-semibold uppercase tracking-wide text-primary">Catatan internal per sesi</p><h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $session->schedule?->title ?? $session->class?->title ?? 'Sesi belajar' }}</h1><p class="mt-1 text-sm text-gray-500">{{ $session->start_at->locale('id')->translatedFormat('l, d F Y · H:i') }} WIB · {{ $session->studyGroup?->name ?? 'Sesi personal' }}</p></div></div>
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ $isEditing ? route('tutor.schedule.progress.update', [$session, $report]) : route('tutor.schedule.progress.store', $session) }}" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">@csrf @if($isEditing) @method('PUT') @endif
        <div class="flex items-center justify-between border-b border-gray-100 pb-4"><div><h2 class="font-bold text-gray-900">{{ $isEditing ? 'Edit progress sesi' : 'Buat progress sesi' }}</h2><p class="mt-1 text-sm text-gray-500">Rangkum materi yang dibahas, capaian sesi, dan fokus belajar berikutnya. Progress ini berlaku untuk seluruh jadwal, bukan per peserta.</p></div></div>
        <x-ui.input.textarea name="summary" label="Progress pembelajaran" :value="old('summary', $report->summary ?? '')" :required="true" rows="8" resize="vertical" helper="Contoh: materi yang dibahas, latihan yang dikerjakan, dan rencana sesi selanjutnya." data-summernote data-height="280" data-toolbar='[["style",["style"]],["font",["bold","italic","underline","clear"]],["para",["ul","ol","paragraph"]],["insert",["link"]],["view",["codeview"]]]' class="summernote-field" />
        <div class="flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-5">@if($isEditing)<x-ui.button type="submit" variant="danger" form="delete-session-progress">Hapus</x-ui.button>@endif<x-ui.button type="submit" icon="ri-save-line">{{ $isEditing ? 'Simpan perubahan' : 'Simpan progress' }}</x-ui.button></div>
    </form>
    @if($isEditing)<form id="delete-session-progress" method="POST" action="{{ route('tutor.schedule.progress.destroy', [$session, $report]) }}" onsubmit="return confirm('Hapus progress sesi ini?');">@csrf @method('DELETE')</form>@endif
</div>
@endsection

@include('admin.partials.summernote')
