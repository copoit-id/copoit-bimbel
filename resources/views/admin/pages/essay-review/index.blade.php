@extends('admin.layout.admin')
@section('title', 'Koreksi Essay')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.dashboard') }}" title="Dashboard" />
            <x-breadcrumb-item href="" title="Koreksi Essay" />
        </x-slot>
    </x-breadcrumb>
</div>

<x-page-desc title="Koreksi Essay" description="Pantau status koreksi essay otomatis (AI) dan koreksi manual." />

<!-- Tab Navigation -->
<div class="mb-6 border-b border-gray-200">
    <nav class="flex space-x-8" aria-label="Tabs">
        <a href="{{ route('admin.essay-review.index', ['tab' => 'manual']) }}"
            class="inline-flex items-center px-1 py-4 text-sm font-medium border-b-2 transition-colors {{ $tab === 'manual' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <i class="ri-edit-line mr-2"></i>
            Koreksi Manual
            @if(isset($pendingTryouts) && count($pendingTryouts) > 0)
                <span class="ml-2 px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">
                    {{ collect($pendingTryouts)->sum('pending_count') }}
                </span>
            @endif
        </a>
        
        {{-- Essay AI Tab dengan Quota Check --}}
        @php
            $essayAI = $planQuota['essay_ai'] ?? \App\Services\PlanQuotaService::canUseEssayAI();
        @endphp
        
        @if($essayAI['enabled'])
            <a href="{{ route('admin.essay-review.index', ['tab' => 'automatic']) }}"
                class="inline-flex items-center px-1 py-4 text-sm font-medium border-b-2 transition-colors {{ $tab === 'automatic' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <i class="ri-robot-line mr-2"></i>
                Koreksi Otomatis (AI)
                @if(isset($stats) && $stats['total_processing'] > 0)
                    <span class="ml-2 px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">
                        {{ $stats['total_processing'] }}
                    </span>
                @endif
            </a>
        @else
            {{-- Tab AI Disabled dengan Tooltip --}}
            <div class="relative group inline-flex">
                <button type="button" disabled
                    class="inline-flex items-center px-1 py-4 text-sm font-medium border-b-2 border-transparent text-gray-400 cursor-not-allowed">
                    <i class="ri-lock-line mr-2"></i>
                    Koreksi Otomatis (AI)
                </button>
                {{-- Tooltip --}}
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block w-72 z-50">
                    <div class="bg-gray-800 text-white text-xs rounded-lg py-2 px-3 shadow-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="ri-information-line text-orange-400"></i>
                            <p class="font-medium">Fitur Tidak Tersedia</p>
                        </div>
                        <p class="text-gray-300">Essay AI tidak tersedia di plan Anda. Upgrade ke Plan Pro atau Enterprise untuk mengaktifkan fitur ini.</p>
                        {{-- Arrow --}}
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-800"></div>
                    </div>
                </div>
            </div>
        @endif
    </nav>
</div>

{{-- Alert Essay AI Status --}}
@if($tab === 'automatic')
    @if(!$essayAI['enabled'])
        <div class="rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700 mb-6">
            <div class="flex items-center gap-2">
                <i class="ri-lock-line text-lg"></i>
                <div>
                    <p class="font-medium">Essay AI Tidak Tersedia</p>
                    <p>Fitur koreksi essay otomatis tidak tersedia di plan Anda. Silakan hubungi administrator untuk upgrade plan.</p>
                </div>
            </div>
        </div>
    @elseif(!$essayAI['allowed'])
        <div class="rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700 mb-6">
            <div class="flex items-center gap-2">
                <i class="ri-information-line text-lg"></i>
                <div>
                    <p class="font-medium">Kuota Essay AI Habis</p>
                    <p>{{ $essayAI['reason'] }}</p>
                </div>
            </div>
        </div>
    @elseif($essayAI['limit'] > 0 && $essayAI['current'] >= $essayAI['limit'] - 100)
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-700 mb-6">
            <div class="flex items-center gap-2">
                <i class="ri-alert-line text-lg"></i>
                <div>
                    <p class="font-medium">Kuota Essay AI Hampir Habis</p>
                    <p>Anda telah menggunakan {{ $essayAI['current'] }} dari {{ $essayAI['limit'] }} essay. Segera upgrade plan untuk menambah kuota.</p>
                </div>
            </div>
        </div>
    @endif
@endif

@if($tab === 'manual')
    {{-- TAB MANUAL --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($pendingTryouts as $row)
            @php
                $tryout = $row['tryout'];
                $scoringLabel = $tryout->requiresIrtScoring()
                    ? 'IRT'
                    : ($tryout->is_toefl ? 'TOEFL ITP' : null);
            @endphp
            <div class="tryout-card bg-white px-5 py-5 rounded-lg border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium">
                        {{ strtoupper($tryout->type_tryout) }} {{ $scoringLabel ? '- ' . $scoringLabel : '' }}
                    </span>
                    <span class="px-2 py-1 bg-amber-50 text-amber-700 rounded-full text-xs border border-amber-100">
                        {{ $row['pending_count'] }} belum dikoreksi
                    </span>
                </div>

                <p class="text-lg font-bold text-black text-center mb-4">{{ $tryout->name }}</p>

                <div class="flex flex-col gap-1 mb-4">
                    <span class="flex items-center justify-between">
                        <p class="font-medium">Total Soal:</p>
                        <p class="font-light">{{ $tryout->total_questions ?? 0 }} Soal</p>
                    </span>
                    <span class="flex items-center justify-between">
                        <p class="font-medium">Durasi:</p>
                        <p class="font-light">{{ $tryout->total_duration ?? 0 }} Menit</p>
                    </span>
                    <span class="flex items-center justify-between">
                        <p class="font-medium">Subtest:</p>
                        <p class="font-light">{{ $tryout->tryoutDetails->count() }} Bagian</p>
                    </span>
                </div>

                @if($row['last_answered_at'])
                <p class="text-xs text-gray-500 mb-4">Terakhir dijawab: {{ \Carbon\Carbon::parse($row['last_answered_at'])->translatedFormat('d M Y H:i') }}</p>
                @endif

                <a href="{{ route('admin.essay-review.tryout', $tryout->tryout_id) }}"
                    class="flex w-full justify-center bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/90">
                    <i class="ri-pencil-line mr-2"></i>
                    Koreksi Manual
                </a>
            </div>
        @empty
            <div class="text-center text-gray-500 py-8 col-span-full">
                <i class="ri-check-double-line text-4xl mb-2 block"></i>
                <p>Tidak ada jawaban essay yang perlu dikoreksi manual.</p>
                <p class="text-sm text-gray-400 mt-1">Semua essay sudah terkoreksi atau diproses AI!</p>
            </div>
        @endforelse
    </div>
@else
    {{-- TAB OTOMATIS (AI) --}}
    
    {{-- Filter Tabs --}}
    @php
        $currentFilter = request('filter', 'all');
        $filters = [
            'all' => ['label' => 'Semua', 'count' => $jobs->count()],
            'pending' => ['label' => 'Menunggu', 'count' => $jobs->where('status', 'pending')->count()],
            'queued' => ['label' => 'Antrian', 'count' => $jobs->where('status', 'queued')->count()],
            'processing' => ['label' => 'Diproses', 'count' => $jobs->where('status', 'processing')->count()],
            'completed' => ['label' => 'Selesai', 'count' => $jobs->where('status', 'completed')->count()],
            'failed' => ['label' => 'Gagal', 'count' => $jobs->where('status', 'failed')->count()],
        ];
    @endphp
    
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach($filters as $key => $filter)
            <a href="{{ route('admin.essay-review.index', ['tab' => 'automatic', 'filter' => $key]) }}"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $currentFilter === $key ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                {{ $filter['label'] }}
                <span class="ml-1 text-xs {{ $currentFilter === $key ? 'text-gray-300' : 'text-gray-400' }}">{{ $filter['count'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        @foreach([
            ['label' => 'Menunggu', 'count' => $stats['total_pending_ai'] ?? 0, 'icon' => 'ri-time-line'],
            ['label' => 'Diproses', 'count' => $stats['total_processing'] ?? 0, 'icon' => 'ri-loader-4-line'],
            ['label' => 'Selesai', 'count' => $stats['total_completed'] ?? 0, 'icon' => 'ri-check-line'],
            ['label' => 'Gagal', 'count' => $stats['total_failed'] ?? 0, 'icon' => 'ri-close-line'],
            ['label' => 'Total', 'count' => $jobs->count(), 'icon' => 'ri-stack-line'],
        ] as $stat)
        <div class="bg-white p-3 rounded-lg border border-gray-200">
            <div class="flex items-center gap-2">
                <i class="{{ $stat['icon'] }} text-gray-400"></i>
                <div>
                    <p class="text-xs text-gray-500">{{ $stat['label'] }}</p>
                    <p class="text-lg font-bold text-gray-800">{{ $stat['count'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Job List (Table-like) --}}
    @php
        $filteredJobs = $currentFilter === 'all' 
            ? $jobs 
            : $jobs->filter(fn($j) => $j->status === $currentFilter);
    @endphp
    
    @if($filteredJobs->count() > 0)
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden" id="jobs-container">
            {{-- Header --}}
            <div class="grid grid-cols-12 gap-4 px-4 py-3 bg-white border-b border-gray-200 text-xs font-bold text-gray-900 uppercase tracking-wider">
                <div class="col-span-1">Status</div>
                <div class="col-span-2">Tryout</div>
                <div class="col-span-2">Peserta</div>
                <div class="col-span-2">Token Ujian</div>
                <div class="col-span-1 text-center">Benar</div>
                <div class="col-span-1 text-center">Salah</div>
                <div class="col-span-1 text-center">Similarity</div>
                <div class="col-span-1">Progress</div>
                <div class="col-span-1 text-right">Aksi</div>
            </div>
            
            {{-- Progress Animation CSS --}}
            <style>
                @keyframes progress-indeterminate {
                    0% { transform: translateX(-100%); }
                    50% { transform: translateX(0%); }
                    100% { transform: translateX(100%); }
                }
                @keyframes progress-shimmer {
                    0% { background-position: -200% 0; }
                    100% { background-position: 200% 0; }
                }
                .progress-bar-animated {
                    background: linear-gradient(90deg, 
                        #6366f1 0%, #8b5cf6 20%, #6366f1 40%, 
                        #8b5cf6 60%, #6366f1 80%, #8b5cf6 100%);
                    background-size: 200% 100%;
                    animation: progress-shimmer 1.5s linear infinite;
                    position: relative;
                }
                .progress-bar-animated::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: linear-gradient(90deg, 
                        transparent 0%, rgba(255,255,255,0.4) 50%, transparent 100%);
                    animation: progress-indeterminate 1s ease-in-out infinite;
                }
                .progress-bar-container {
                    position: relative;
                    overflow: hidden;
                }
            </style>
            
            {{-- Body --}}
            <div class="divide-y divide-gray-100" id="jobs-list">
                @foreach($filteredJobs as $job)
                    @php
                        $statusConfig = [
                            'pending' => ['color' => 'text-amber-600 bg-amber-50', 'label' => 'Menunggu', 'icon' => 'ri-time-line'],
                            'queued' => ['color' => 'text-blue-600 bg-blue-50', 'label' => 'Antrian', 'icon' => 'ri-hourglass-line'],
                            'processing' => ['color' => 'text-indigo-600 bg-indigo-50', 'label' => 'Diproses', 'icon' => 'ri-loader-4-line'],
                            'completed' => ['color' => 'text-green-600 bg-green-50', 'label' => 'Selesai', 'icon' => 'ri-check-line'],
                            'failed' => ['color' => 'text-red-600 bg-red-50', 'label' => 'Gagal', 'icon' => 'ri-close-line'],
                        ][$job->status] ?? ['color' => 'text-gray-600 bg-gray-50', 'label' => 'Unknown', 'icon' => 'ri-question-line'];
                        
                        $attemptToken = $job->userAnswer?->attempt_token ?? substr($job->user_answer_id ?? 'N/A', 0, 8);
                        $processedCount = $job->processed_essays ?? 0;
                        $totalCount = $job->total_essays ?? 0;
                        $progressPercent = $totalCount > 0 ? round(($processedCount / $totalCount) * 100) : 0;
                    @endphp
                    <div class="job-row group" data-job-id="{{ $job->id }}">
                        {{-- Main Row --}}
                        <div class="grid grid-cols-12 gap-4 px-4 py-3 items-center hover:bg-gray-50 transition-colors cursor-pointer" onclick="toggleDetail({{ $job->id }})">
                            {{-- Accordion Arrow --}}
                            <div class="col-span-1 flex items-center gap-2">
                                <i id="arrow-{{ $job->id }}" class="ri-arrow-right-s-line text-gray-400 text-lg transition-transform"></i>
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium {{ $statusConfig['color'] }}">
                                    <i class="{{ $statusConfig['icon'] }} {{ $job->status === 'processing' ? 'animate-spin' : '' }}"></i>
                                    {{ $statusConfig['label'] }}
                                </span>
                            </div>
                            
                            {{-- Tryout --}}
                            <div class="col-span-2">
                                <p class="text-sm font-medium text-gray-900 truncate" title="{{ $job->tryout?->name ?? 'Tryout tidak ditemukan' }}">
                                    {{ $job->tryout?->name ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-400">{{ $job->created_at->format('H:i') }}</p>
                            </div>
                            
                            {{-- User --}}
                            <div class="col-span-2">
                                <p class="text-sm text-gray-700 truncate" title="{{ $job->user?->name ?? 'User #' . $job->user_id }}">
                                    {{ $job->user?->name ?? 'User #' . $job->user_id }}
                                </p>
                            </div>
                            
                            {{-- Token Full --}}
                            <div class="col-span-2">
                                <code class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded font-mono block truncate" title="{{ $attemptToken }}">
                                    {{ $attemptToken }}
                                </code>
                            </div>
                            
                            {{-- Benar --}}
                            <div class="col-span-1 text-center">
                                @if($job->status === 'completed')
                                    <span class="job-correct text-sm font-medium text-green-600">{{ $job->correct_count ?? 0 }}</span>
                                @else
                                    <span class="text-xs text-gray-300">-</span>
                                @endif
                            </div>
                            
                            {{-- Salah --}}
                            <div class="col-span-1 text-center">
                                @if($job->status === 'completed')
                                    <span class="job-incorrect text-sm font-medium text-red-600">{{ $job->incorrect_count ?? 0 }}</span>
                                @else
                                    <span class="text-xs text-gray-300">-</span>
                                @endif
                            </div>
                            
                            {{-- Similarity --}}
                            <div class="col-span-1 text-center">
                                @if($job->status === 'completed')
                                    <span class="job-similarity text-sm font-medium text-gray-700">{{ number_format($job->total_similarity_score ?? 0, 0) }}%</span>
                                @else
                                    <span class="text-xs text-gray-300">-</span>
                                @endif
                            </div>
                            
                            {{-- Progress (1 kolom tapi panjang + animasi) --}}
                            <div class="col-span-1">
                                @if(in_array($job->status, ['pending', 'queued', 'processing', 'completed']))
                                    @php
                                        $isCompleted = $job->status === 'completed';
                                        $isProcessing = $job->status === 'processing';
                                        $bgColor = $isCompleted ? 'bg-green-100' : 'bg-gray-100';
                                        $textColor = $isCompleted ? 'text-green-600' : 'text-gray-500';
                                        $processed = $isCompleted ? $totalCount : ($job->processed_essays ?? 0);
                                        $progressWidth = $isCompleted ? 100 : $progressPercent;
                                    @endphp
                                    <div class="flex items-center gap-2 min-w-[120px]">
                                        <div class="flex-1 {{ $bgColor }} rounded-full h-2.5 progress-bar-container">
                                            @if($isProcessing)
                                                <div class="job-progress-bar progress-bar-animated h-2.5 rounded-full transition-all duration-700" 
                                                    style="width: {{ $progressWidth }}%"></div>
                                            @else
                                                <div class="job-progress-bar {{ $isCompleted ? 'bg-green-500' : 'bg-gray-300' }} h-2.5 rounded-full transition-all duration-700" 
                                                    style="width: {{ $progressWidth }}%"></div>
                                            @endif
                                        </div>
                                        <span class="job-progress-text text-xs {{ $textColor }} whitespace-nowrap">{{ $processed }}/{{ $totalCount }}</span>
                                    </div>
                                @elseif($job->status === 'failed')
                                    <span class="text-xs text-red-500 truncate block">{{ Str::limit($job->error_message, 12) }}</span>
                                @else
                                    <span class="text-xs text-gray-300">-</span>
                                @endif
                            </div>
                            
                            {{-- Actions (tampil terus) --}}
                            <div class="col-span-1 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if($job->status === 'failed')
                                        <button onclick="event.stopPropagation(); retryJob({{ $job->id }})" 
                                            class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                            title="Retry">
                                            <i class="ri-refresh-line"></i>
                                        </button>
                                    @endif
                                    @if(in_array($job->status, ['completed', 'failed']))
                                        <button onclick="event.stopPropagation(); deleteJob({{ $job->id }})" 
                                            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                                            title="Hapus">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-300">-</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        {{-- Detail Row (Hidden by default) --}}
                        <div id="detail-{{ $job->id }}" class="hidden bg-gray-50 border-t border-gray-100">
                            <div class="p-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Detail Essay ({{ $totalCount }} soal)</h4>
                                @php
                                    $details = \App\Models\UserAnswerDetail::whereHas('userAnswer', fn($q) => $q->where('user_answer_id', $job->user_answer_id))
                                        ->whereHas('question', fn($q) => $q->where('question_type', 'essay'))
                                        ->with(['question', 'userAnswer'])
                                        ->get();
                                @endphp
                                
                                @if($details->count() > 0)
                                    <div class="space-y-2 max-h-64 overflow-y-auto">
                                        @foreach($details as $idx => $detail)
                                            @php
                                                $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
                                                $isPending = $answerMeta['pending_review'] ?? false;
                                                $similarity = $answerMeta['ai_similarity'] ?? null;
                                                $score = $answerMeta['score_obtained'] ?? null;
                                            @endphp
                                            <div class="flex items-center gap-3 p-2 bg-white rounded border border-gray-200 text-sm">
                                                <span class="text-xs text-gray-400 w-6">{{ $idx + 1 }}</span>
                                                <div class="flex-1 truncate" title="{{ $detail->question?->question_text ?? 'Soal #' . $detail->question_id }}">
                                                    {{ Str::limit(strip_tags($detail->question?->question_text ?? 'Soal #' . $detail->question_id), 50) }}
                                                </div>
                                                <div class="w-32 text-xs text-gray-500 truncate" title="{{ $detail->answer_text }}">
                                                    Jawaban: {{ Str::limit($detail->answer_text, 20) }}
                                                </div>
                                                <div class="w-24 text-right">
                                                    @if($isPending)
                                                        <span class="text-xs text-amber-600">Menunggu</span>
                                                    @elseif($similarity !== null)
                                                        <span class="text-xs font-medium {{ $detail->is_correct ? 'text-green-600' : 'text-red-600' }}">
                                                            {{ $detail->is_correct ? 'Benar' : 'Salah' }} ({{ round($similarity * 100) }}%)
                                                        </span>
                                                        <span class="text-xs text-gray-400">| {{ $score ?? 0 }} poin</span>
                                                    @else
                                                        <span class="text-xs text-gray-400">-</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-400">Tidak ada detail essay</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="text-center py-12 bg-white rounded-lg border border-gray-200">
            <i class="ri-inbox-line text-4xl text-gray-300 mb-2"></i>
            <p class="text-gray-500">Tidak ada job dengan status "{{ $filters[$currentFilter]['label'] }}"</p>
        </div>
    @endif
@endif

@endsection

@section('scripts')
@if($tab === 'automatic' && $jobs->count() > 0)
<script>
    // Toggle detail row
    function toggleDetail(jobId) {
        const detail = document.getElementById(`detail-${jobId}`);
        const arrow = document.getElementById(`arrow-${jobId}`);
        if (detail) {
            detail.classList.toggle('hidden');
            if (arrow) {
                arrow.classList.toggle('rotate-90');
            }
        }
    }
    
    // Polling untuk update status job setiap 3 detik
    const jobIds = {!! json_encode($jobs->pluck('id')) !!};
    let pollingInterval;
    
    function updateJobStatus() {
        if (jobIds.length === 0) return;
        
        fetch('{{ route("admin.essay-review.jobs.status") }}?job_ids=' + jobIds.join(','))
            .then(response => response.json())
            .then(data => {
                let hasActiveJobs = false;
                
                data.forEach(job => {
                    const row = document.querySelector(`.job-row[data-job-id="${job.id}"]`);
                    if (!row) return;
                    
                    // Update state for local animation
                    if (!jobProgressState[job.id]) {
                        jobProgressState[job.id] = { localPercent: 0, serverPercent: 0, status: job.status };
                    }
                    jobProgressState[job.id].status = job.status;
                    jobProgressState[job.id].serverPercent = job.progress_percentage || 0;
                    
                    // Check if still active
                    if (job.status === 'processing' || job.status === 'queued' || job.status === 'pending') {
                        hasActiveJobs = true;
                    }
                    
                    // Update status badge (keep arrow, update status)
                    const statusConfig = {
                        'pending': { color: 'text-amber-600 bg-amber-50', label: 'Menunggu', icon: 'ri-time-line' },
                        'queued': { color: 'text-blue-600 bg-blue-50', label: 'Antrian', icon: 'ri-hourglass-line' },
                        'processing': { color: 'text-indigo-600 bg-indigo-50', label: 'Diproses', icon: 'ri-loader-4-line' },
                        'completed': { color: 'text-green-600 bg-green-50', label: 'Selesai', icon: 'ri-check-line' },
                        'failed': { color: 'text-red-600 bg-red-50', label: 'Gagal', icon: 'ri-close-line' }
                    }[job.status] || { color: 'text-gray-600 bg-gray-50', label: 'Unknown', icon: 'ri-question-line' };
                    
                    const statusCell = row.querySelector('.col-span-1');
                    if (statusCell) {
                        const arrow = statusCell.querySelector('i[id^="arrow-"]');
                        statusCell.innerHTML = `
                            ${arrow ? arrow.outerHTML : '<i class="ri-arrow-right-s-line text-gray-400 text-lg transition-transform"></i>'}
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium ${statusConfig.color}">
                                <i class="${statusConfig.icon} ${job.status === 'processing' ? 'animate-spin' : ''}"></i>
                                ${statusConfig.label}
                            </span>
                        `;
                    }
                    
                    // Update progress (col-span-1) dengan animasi smooth
                    const allColSpan1 = row.querySelectorAll('.col-span-1');
                    // Progress adalah col-span-1 ke-8 (setelah similarity)
                    const progressCell = allColSpan1[7]; 
                    if (progressCell && ['pending', 'queued', 'processing', 'completed'].includes(job.status)) {
                        const isCompleted = job.status === 'completed';
                        const isProcessing = job.status === 'processing';
                        const processed = isCompleted ? (job.total_essays || 0) : (job.processed_essays || 0);
                        const total = job.total_essays || 0;
                        const percent = isCompleted ? 100 : (total > 0 ? Math.round((processed / total) * 100) : 0);
                        
                        if (isProcessing) hasActiveJobs = true;
                        
                        const bgColor = isCompleted ? 'bg-green-100' : 'bg-gray-100';
                        const textColor = isCompleted ? 'text-green-600' : 'text-gray-500';
                        const barClass = isProcessing ? 'progress-bar-animated' : (isCompleted ? 'bg-green-500' : 'bg-gray-300');
                        
                        progressCell.innerHTML = `
                            <div class="flex items-center gap-2 min-w-[120px]">
                                <div class="flex-1 ${bgColor} rounded-full h-2.5 progress-bar-container">
                                    <div class="job-progress-bar ${barClass} h-2.5 rounded-full transition-all duration-700" 
                                        style="width: ${percent}%"></div>
                                </div>
                                <span class="text-xs ${textColor} whitespace-nowrap">${processed}/${total}</span>
                            </div>
                        `;
                    }
                    
                    // Update hasil (benar, salah, similarity) - columns 5,6,7 (setelah token)
                    const centerCells = row.querySelectorAll('.col-span-1.text-center');
                    if (centerCells.length >= 3 && job.status === 'completed') {
                        centerCells[0].innerHTML = `<span class="job-correct text-sm font-medium text-green-600">${job.correct_count || 0}</span>`;
                        centerCells[1].innerHTML = `<span class="job-incorrect text-sm font-medium text-red-600">${job.incorrect_count || 0}</span>`;
                        centerCells[2].innerHTML = `<span class="job-similarity text-sm font-medium text-gray-700">${Math.round(job.total_similarity_score || 0)}%</span>`;
                    }
                });
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Delete job
    function deleteJob(jobId) {
        if (!confirm('Yakin ingin menghapus job ini?')) return;
        
        fetch(`{{ url('admin/essay-review/jobs') }}/${jobId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`.job-row[data-job-id="${jobId}"]`);
                if (row) {
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 300);
                }
            }
        });
    }
    
    // Retry job
    function retryJob(jobId) {
        fetch(`{{ url('admin/essay-review/jobs') }}/${jobId}/retry`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) location.reload();
        });
    }
    
    // Local smooth progress animation
    const jobProgressState = {};
    
    function animateProgressSmoothly() {
        jobIds.forEach(id => {
            if (!jobProgressState[id]) {
                jobProgressState[id] = { localPercent: 0, serverPercent: 0, status: 'pending' };
            }
            
            const state = jobProgressState[id];
            const row = document.querySelector(`.job-row[data-job-id="${id}"]`);
            if (!row) return;
            
            // Jika masih processing, naikkan pelan-pelan
            if (state.status === 'processing' || state.status === 'queued' || state.status === 'pending') {
                if (state.localPercent < state.serverPercent) {
                    // Naik pelan menuju server percent
                    state.localPercent += 2; // Naik 2% per frame
                    if (state.localPercent > state.serverPercent) state.localPercent = state.serverPercent;
                } else if (state.localPercent < 95 && state.serverPercent === 0) {
                    // Jika server belum kasih percent, naik pelan sampai 95%
                    state.localPercent += 0.5;
                    if (state.localPercent > 95) state.localPercent = 95;
                }
                
                // Update bar
                const bar = row.querySelector('.job-progress-bar');
                const text = row.querySelector('.job-progress-text');
                if (bar) {
                    bar.style.width = state.localPercent + '%';
                    bar.style.transition = 'width 0.3s ease-out';
                }
            } else if (state.status === 'completed') {
                // Langsung 100%
                state.localPercent = 100;
                const bar = row.querySelector('.job-progress-bar');
                if (bar) {
                    bar.style.width = '100%';
                    bar.classList.remove('progress-bar-animated');
                    bar.classList.add('bg-green-500');
                }
            }
        });
        
        requestAnimationFrame(animateProgressSmoothly);
    }
    
    // Start animation loop
    requestAnimationFrame(animateProgressSmoothly);
    updateJobStatus();
    pollingInterval = setInterval(updateJobStatus, 3000);
</script>
@endif
@endsection
