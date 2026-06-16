@extends('user.layout.new-user')

@section('title', $material->title)

@section('content')
@php
    $primaryColor = $clientBranding['primary_color'] ?? '#10b981';
    $typeRoute = match ($material->type) {
        'document' => route('user.material.documents'),
        'live_session' => route('user.material.live-sessions'),
        default => route('user.material.videos'),
    };
    $embedUrl = $material->embed_url;
@endphp

<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ $typeRoute }}" class="p-2 rounded-xl bg-white border border-gray-100 text-gray-500 hover:text-gray-800">
            <i class="ri-arrow-left-line text-xl"></i>
        </a>
        <div class="min-w-0">
            <p class="text-sm text-gray-500">{{ $material->type_label }}</p>
            <h1 class="text-2xl font-bold text-gray-800 truncate">{{ $material->title }}</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-6">
        <main class="space-y-5">
            <section class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="aspect-video bg-gray-950">
                    @if($material->type === 'video')
                        <iframe
                            src="{{ $embedUrl }}"
                            class="w-full h-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                        </iframe>
                    @elseif($material->type === 'document')
                        <iframe src="{{ $embedUrl }}" class="w-full h-full bg-white" frameborder="0"></iframe>
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-purple-800 to-indigo-900 text-white">
                            <div class="text-center px-6">
                                <i class="ri-live-line text-6xl mb-4"></i>
                                <h2 class="text-2xl font-bold mb-2">Live Session</h2>
                                <a href="{{ $material->content_url }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center px-5 py-3 bg-white text-purple-900 rounded-xl font-semibold hover:bg-gray-100">
                                    <i class="ri-video-chat-line mr-2"></i>Masuk Sesi
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="p-5 space-y-4">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-3 text-xs text-gray-500">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-100">
                                    <i class="{{ $material->type_icon }} mr-1"></i>{{ $material->type_label }}
                                </span>
                                @if($material->duration_minutes)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-100">
                                    <i class="ri-time-line mr-1"></i>{{ $material->formatted_duration }}
                                </span>
                                @endif
                                @foreach($material->categories as $category)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-100">
                                    <i class="ri-folder-line mr-1"></i>{{ $category->name }}
                                </span>
                                @endforeach
                            </div>
                            <h2 class="text-xl font-bold text-gray-800">{{ $material->title }}</h2>
                        </div>

                        <button type="button" id="completeButton"
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-sm font-semibold {{ $userAccess?->is_completed ? 'bg-green-100 text-green-700' : 'text-white hover:opacity-90' }}"
                            style="{{ $userAccess?->is_completed ? '' : 'background-color: ' . $primaryColor }}"
                            {{ $userAccess?->is_completed ? 'disabled' : '' }}>
                            <i class="{{ $userAccess?->is_completed ? 'ri-check-double-line' : 'ri-check-line' }} mr-1"></i>
                            <span>{{ $userAccess?->is_completed ? 'Selesai' : 'Tandai Selesai' }}</span>
                        </button>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-gray-500">Progress</span>
                            <span id="progressText" class="font-semibold" style="color: {{ $primaryColor }}">{{ (int) ($userAccess->progress_percentage ?? 0) }}%</span>
                        </div>
                        <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            <div id="progressBar" class="h-full rounded-full transition-all" style="width: {{ (int) ($userAccess->progress_percentage ?? 0) }}%; background-color: {{ $primaryColor }}"></div>
                        </div>
                    </div>

                    @if($material->description)
                    <div class="prose max-w-none text-gray-600">
                        {!! nl2br(e($material->description)) !!}
                    </div>
                    @endif

                    @if($material->type !== 'video')
                    <a href="{{ $material->content_url }}" target="_blank" rel="noopener"
                       class="inline-flex items-center px-4 py-2.5 rounded-xl bg-gray-100 text-gray-700 text-sm font-semibold hover:bg-gray-200">
                        <i class="ri-external-link-line mr-1"></i>Buka di Tab Baru
                    </a>
                    @endif
                </div>
            </section>
        </main>

        <aside class="space-y-5">
            <section class="bg-white rounded-2xl border border-gray-100 p-5">
                <h2 class="font-bold text-gray-800 mb-4">Status Belajar</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Status</span>
                        <span id="statusText" class="font-semibold {{ $userAccess?->is_completed ? 'text-green-600' : 'text-yellow-600' }}">
                            {{ $userAccess?->is_completed ? 'Selesai' : 'Sedang dipelajari' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Mulai</span>
                        <span class="font-medium text-gray-700">{{ $userAccess?->started_at?->format('d M Y H:i') ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Selesai</span>
                        <span id="completedAtText" class="font-medium text-gray-700">{{ $userAccess?->completed_at?->format('d M Y H:i') ?? '-' }}</span>
                    </div>
                </div>
            </section>

            @if($relatedMaterials->count() > 0)
            <section class="bg-white rounded-2xl border border-gray-100 p-5">
                <h2 class="font-bold text-gray-800 mb-4">Materi Terkait</h2>
                <div class="space-y-3">
                    @foreach($relatedMaterials as $related)
                    <a href="{{ route('user.material.show', $related->material_id) }}" class="flex gap-3 group">
                        <div class="w-20 aspect-video bg-gray-100 rounded-xl overflow-hidden shrink-0 flex items-center justify-center">
                            @if($related->thumbnail_url)
                                <img src="{{ $related->thumbnail_url }}" alt="{{ $related->title }}" class="w-full h-full object-cover">
                            @else
                                <i class="{{ $related->type_icon }} text-gray-400 text-xl"></i>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-gray-800 line-clamp-2 group-hover:text-primary">{{ $related->title }}</h3>
                            <p class="text-xs text-gray-400 mt-1">{{ $related->type_label }}</p>
                        </div>
                    </a>
                    @endforeach
                </div>
            </section>
            @endif
        </aside>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const completeButton = document.getElementById('completeButton');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const statusText = document.getElementById('statusText');
    const completedAtText = document.getElementById('completedAtText');

    completeButton?.addEventListener('click', async () => {
        if (completeButton.disabled) return;

        completeButton.disabled = true;
        completeButton.classList.add('opacity-70');

        try {
            const response = await fetch('{{ route('user.material.complete', $material->material_id) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Gagal menandai selesai.');
            }

            progressBar.style.width = '100%';
            progressText.textContent = '100%';
            statusText.textContent = 'Selesai';
            statusText.classList.remove('text-yellow-600');
            statusText.classList.add('text-green-600');
            completedAtText.textContent = new Date().toLocaleString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
            completeButton.innerHTML = '<i class="ri-check-double-line mr-1"></i><span>Selesai</span>';
            completeButton.removeAttribute('style');
            completeButton.className = 'inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-sm font-semibold bg-green-100 text-green-700';
        } catch (error) {
            completeButton.disabled = false;
            completeButton.classList.remove('opacity-70');
            alert(error.message);
        }
    });
});
</script>
@endsection
