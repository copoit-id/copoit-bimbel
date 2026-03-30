@extends('user.layout.user')

@section('title', $material->title)

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex items-center text-sm text-gray-500">
            <a href="{{ route('user.material.index') }}" class="hover:text-primary">Materi</a>
            <i class="ri-arrow-right-s-line mx-2"></i>
            <a href="{{ route('user.material.' . ($material->type === 'document' ? 'documents' : ($material->type === 'live_session' ? 'live-sessions' : 'videos'))) }}" class="hover:text-primary">{{ $material->type_label }}</a>
            <i class="ri-arrow-right-s-line mx-2"></i>
            <span class="text-gray-900 truncate max-w-xs">{{ $material->title }}</span>
        </nav>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <!-- Content Player -->
                <div class="aspect-video bg-gray-900 relative">
                    @if($material->type === 'video')
                    <!-- Video Embed -->
                    <iframe 
                        src="{{ $material->content_url }}" 
                        class="w-full h-full"
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                    @elseif($material->type === 'document')
                    <!-- Document Preview -->
                    <div class="w-full h-full flex items-center justify-center bg-gray-100">
                        <div class="text-center">
                            <i class="ri-file-text-line text-6xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600 mb-4">Dokumen PDF</p>
                            <a href="{{ $material->content_url }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                                <i class="ri-external-link-line mr-2"></i>Buka Dokumen
                            </a>
                        </div>
                    </div>
                    @else
                    <!-- Live Session -->
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-purple-900 to-indigo-900">
                        <div class="text-center text-white">
                            <i class="ri-live-line text-6xl mb-4"></i>
                            <p class="text-xl font-semibold mb-4">Live Session</p>
                            <a href="{{ $material->content_url }}" target="_blank" class="inline-flex items-center px-6 py-3 bg-white text-purple-900 rounded-lg hover:bg-gray-100 font-medium">
                                <i class="ri-video-chat-line mr-2"></i>Join Live Session
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
                
                <!-- Progress Bar (for video) -->
                @if($material->type === 'video' && $userAccess && !$userAccess->is_completed)
                <div class="px-4 py-3 bg-gray-50 border-b">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-600">Progress</span>
                        <span class="text-sm font-medium text-primary" id="progressText">{{ $userAccess->progress_percentage }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="progressBar" class="bg-primary h-2 rounded-full transition-all" style="width: {{ $userAccess->progress_percentage }}%"></div>
                    </div>
                </div>
                @endif
                
                <!-- Content Info -->
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $material->title }}</h1>
                            <div class="flex items-center gap-4 text-sm text-gray-500">
                                <span class="inline-flex items-center">
                                    <i class="{{ $material->type_icon }} mr-1"></i>
                                    {{ $material->type_label }}
                                </span>
                                @if($material->duration_minutes)
                                <span class="inline-flex items-center">
                                    <i class="ri-time-line mr-1"></i>
                                    {{ $material->formatted_duration }}
                                </span>
                                @endif
                                @if($material->categories->count() > 0)
                                <span class="inline-flex items-center">
                                    <i class="ri-folder-line mr-1"></i>
                                    {{ $material->categories->pluck('name')->implode(', ') }}
                                </span>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Completion Button -->
                        @if(!$userAccess || !$userAccess->is_completed)
                        <button onclick="markAsCompleted()" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 flex items-center">
                            <i class="ri-check-line mr-1"></i>
                            Tandai Selesai
                        </button>
                        @else
                        <span class="px-4 py-2 bg-green-100 text-green-700 rounded-lg flex items-center">
                            <i class="ri-check-double-line mr-1"></i>
                            Selesai
                        </span>
                        @endif
                    </div>
                    
                    @if($material->description)
                    <div class="prose max-w-none">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Deskripsi</h3>
                        <p class="text-gray-600">{{ $material->description }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Related Materials -->
            @if($relatedMaterials->count() > 0)
            <div class="bg-white rounded-lg shadow p-4 mb-4">
                <h3 class="font-semibold text-gray-900 mb-4">Materi Terkait</h3>
                <div class="space-y-3">
                    @foreach($relatedMaterials as $related)
                    <a href="{{ route('user.material.show', $related->material_id) }}" class="flex gap-3 group">
                        <div class="w-20 h-14 bg-gray-100 rounded flex-shrink-0 flex items-center justify-center">
                            @if($related->thumbnail_url)
                            <img src="{{ $related->thumbnail_url }}" alt="" class="w-full h-full object-cover rounded">
                            @else
                            <i class="{{ $related->type_icon }} text-gray-400"></i>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-medium text-gray-900 line-clamp-2 group-hover:text-primary">{{ $related->title }}</h4>
                            <span class="text-xs text-gray-500">{{ $related->type_label }}</span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Back Button -->
            <a href="{{ route('user.material.index') }}" class="block w-full py-3 px-4 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-center">
                <i class="ri-arrow-left-line mr-2"></i>Kembali ke Materi
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const materialId = {{ $material->material_id }};
    
    // Mark as started when page loads
    document.addEventListener('DOMContentLoaded', function() {
        fetch(`{{ route('user.material.start', $material->material_id) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        }).catch(err => console.error('Error marking as started:', err));
    });
    
    // Mark as completed
    function markAsCompleted() {
        fetch(`{{ route('user.material.complete', $material->material_id) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        })
        .catch(err => console.error('Error marking as completed:', err));
    }
    
    @if($material->type === 'video')
    // Update progress periodically for videos
    let progressInterval;
    
    function updateProgress(currentTime, duration) {
        if (!duration) return;
        
        fetch(`{{ route('user.material.progress', $material->material_id) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                progress_seconds: Math.floor(currentTime),
                total_duration: Math.floor(duration)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('progressText').textContent = data.progress_percentage + '%';
                document.getElementById('progressBar').style.width = data.progress_percentage + '%';
                
                if (data.status === 'completed') {
                    clearInterval(progressInterval);
                }
            }
        })
        .catch(err => console.error('Error updating progress:', err));
    }
    @endif
</script>
@endsection
