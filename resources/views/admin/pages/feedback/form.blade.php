@extends('admin.layout.admin')
@section('title', $question ? 'Edit Feedback' : 'Tambah Feedback')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.feedback.index') }}" title="Feedback Tryout" />
            <x-breadcrumb-item href="{{ route('admin.feedback.show', $tryout->tryout_id) }}" title="{{ $tryout->name }}" />
            <x-breadcrumb-item href="" title="{{ $question ? 'Edit' : 'Tambah' }}" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc
    title="{{ $question ? 'Edit Pertanyaan Feedback' : 'Tambah Pertanyaan Feedback' }}"
    description="Pertanyaan akan muncul setelah siswa selesai tryout." />

<div class="bg-white p-6 rounded-lg border border-border mt-6">
    <form method="POST"
        action="{{ $question ? route('admin.feedback.update', [$tryout->tryout_id, $question->feedback_question_id]) : route('admin.feedback.store', $tryout->tryout_id) }}">
        @csrf
        @if($question)
            @method('PUT')
        @endif

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Pertanyaan</label>
            <input type="text" name="question_text" value="{{ old('question_text', $question->question_text ?? '') }}"
                class="w-full border border-border rounded-lg px-4 py-2" placeholder="Contoh: Seberapa puas dengan soal?" required>
            @error('question_text')
            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Urutan</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $question->sort_order ?? 0) }}"
                class="w-full border border-border rounded-lg px-4 py-2" min="0">
            @error('sort_order')
            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1"
                    {{ old('is_active', $question->is_active ?? true) ? 'checked' : '' }}>
                <span class="text-sm text-gray-700">Aktif</span>
            </label>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('admin.feedback.show', $tryout->tryout_id) }}"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Batal</a>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                {{ $question ? 'Simpan Perubahan' : 'Tambah Pertanyaan' }}
            </button>
        </div>
    </form>
</div>

@endsection
