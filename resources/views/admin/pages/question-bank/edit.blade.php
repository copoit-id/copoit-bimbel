@extends('admin.layout.admin')
@section('title', __('Edit Bank Soal'))
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Edit Bank Soal') }}</h1>
            <p class="text-gray-500">{{ __('Perbarui informasi bank soal.') }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-border bg-white p-6">
        <form action="{{ route('admin.question-bank.update', $bank->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            @if($importTarget)
            <input type="hidden" name="import_for" value="{{ $importTarget }}">
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Nama Bank') }}</label>
                <input type="text" name="name" required
                    value="{{ old('name', $bank->name) }}"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20">
                @error('name')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Parent Bank') }}</label>
                <select name="parent_id"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <option value="">{{ __('(Tidak ada - Bank utama)') }}</option>
                    @foreach ($parentOptions as $option)
                    <option value="{{ $option->id }}" @selected((string) old('parent_id', $bank->parent_id) === (string) $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
                @error('parent_id')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Deskripsi') }}</label>
                <textarea name="description" rows="4"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20">{{ old('description', $bank->description) }}</textarea>
                @error('description')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.question-bank.show', ['questionBank' => $bank->id, 'import_for' => $importTarget]) }}"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">{{ __('Batal') }}</a>
                <button type="submit"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">{{ __('Simpan') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
