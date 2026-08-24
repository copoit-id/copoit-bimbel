@extends('admin.layout.admin')
@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('admin.update-notifications.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $update->title }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $update->published_at?->format('d M Y H:i') ?? $update->created_at->format('d M Y H:i') }}
            @if($update->author)
                • {{ $update->author->name }}
            @endif
        </p>
    </div>

    @if($update->summary)
        <div class="bg-blue-50 border border-blue-100 text-blue-900 rounded-lg p-4">
            {{ $update->summary }}
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-lg p-6 prose max-w-none">
        {!! nl2br(e($update->body)) !!}
    </div>
</div>
@endsection
