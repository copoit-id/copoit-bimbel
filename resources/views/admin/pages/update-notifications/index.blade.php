@extends('admin.layout.admin')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Notifikasi Update</h1>
            <p class="text-sm text-gray-500">Daftar update terbaru pada sistem.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($updates as $update)
            <a href="{{ route('admin.update-notifications.show', $update->id) }}"
                class="block bg-white border border-gray-200 rounded-lg p-4 hover:border-primary transition">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $update->title }}</h3>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $update->published_at?->format('d M Y H:i') ?? $update->created_at->format('d M Y H:i') }}
                        </p>
                    </div>
                    <i class="ri-arrow-right-line text-gray-400"></i>
                </div>
                @if($update->summary)
                    <p class="text-sm text-gray-600 mt-3 line-clamp-3">{{ $update->summary }}</p>
                @else
                    <p class="text-sm text-gray-600 mt-3 line-clamp-3">{{ Str::limit(strip_tags($update->body), 140) }}</p>
                @endif
            </a>
        @empty
            <div class="col-span-full bg-white border border-gray-200 rounded-lg p-6 text-center text-gray-500">
                Belum ada update.
            </div>
        @endforelse
    </div>

    <div>
        {{ $updates->links() }}
    </div>
</div>
@endsection
