@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Template Sertifikat</h1>
            <p class="mt-1 text-sm text-gray-500">Atur desain dan posisi isi sertifikat untuk client ini.</p>
        </div>
        <a href="{{ route('admin.certificate.template.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 font-semibold text-white hover:bg-primary/90">
            <i class="ri-add-line"></i> Buat Template
        </a>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse($templates as $template)
            <article class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <img src="{{ route('admin.certificate.template.background', $template) }}" alt="{{ $template->name }}" class="h-44 w-full object-cover object-top">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="font-semibold text-gray-900">{{ $template->name }}</h2>
                        <span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-green-100 text-green-700' => $template->is_active, 'bg-gray-100 text-gray-600' => ! $template->is_active])>{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">{{ collect($template->layout ?? [])->where('enabled', true)->count() }} elemen aktif</p>
                    <div class="mt-5 flex gap-2">
                        <a href="{{ route('admin.certificate.template.preview', $template) }}" target="_blank" rel="noopener" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" title="Preview dengan data dummy"><i class="ri-eye-line"></i></a>
                        <a href="{{ route('admin.certificate.template.edit', $template) }}" class="flex-1 rounded-lg border border-primary px-3 py-2 text-center text-sm font-semibold text-primary hover:bg-primary hover:text-white">Atur Layout</a>
                        <form method="POST" action="{{ route('admin.certificate.template.destroy', $template) }}" onsubmit="return confirm('Hapus template ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-red-600 hover:bg-red-50" title="Hapus template"><i class="ri-delete-bin-line"></i></button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
                <i class="ri-award-line text-4xl text-gray-300"></i>
                <h2 class="mt-3 text-lg font-semibold text-gray-900">Belum ada template sertifikat</h2>
                <p class="mt-1 text-sm text-gray-500">Upload background lalu atur elemen sertifikat dengan drag-and-drop.</p>
            </div>
        @endforelse
    </div>

    @if($templates->hasPages())
        {{ $templates->links() }}
    @endif
</div>
@endsection
