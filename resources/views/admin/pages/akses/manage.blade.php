@extends('admin.layout.admin')
@section('title', 'Kelola Akses - ' . $item->name ?? $item->title)
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.akses.index', ['tab' => $type]) }}" title="Akses User" />
            <x-breadcrumb-item href="" title="Kelola Akses" />
        </x-slot>
    </x-breadcrumb>
</div>

<!-- Header -->
<div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
    <div class="flex items-center gap-4">
        @php
        $icon = match($type) {
            'package' => 'ri-folder-3-line',
            'video' => 'ri-video-line',
            'document' => 'ri-file-text-line',
            'live' => 'ri-live-line',
            'tryout' => 'ri-file-list-3-line',
            default => 'ri-apps-line',
        };
        $colorClass = match($type) {
            'package' => 'bg-blue-100 text-blue-600',
            'video' => 'bg-red-100 text-red-600',
            'document' => 'bg-green-100 text-green-600',
            'live' => 'bg-purple-100 text-purple-600',
            'tryout' => 'bg-orange-100 text-orange-600',
            default => 'bg-gray-100 text-gray-600',
        };
        @endphp
        <div class="w-14 h-14 {{ $colorClass }} rounded-xl flex items-center justify-center">
            <i class="{{ $icon }} text-2xl"></i>
        </div>
        <div class="flex-1">
            <h1 class="text-xl font-bold text-gray-900">{{ $item->name ?? $item->title }}</h1>
            <p class="text-gray-500">{{ $usersWithAccess->count() }} user memiliki akses</p>
        </div>
        <a href="{{ route('admin.akses.index', ['tab' => $type]) }}" 
           class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
            <i class="ri-arrow-left-line mr-1"></i>Kembali
        </a>
    </div>
</div>

<!-- Split View -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- LEFT: Users with Access -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="ri-user-check-line text-green-500"></i>
                <h3 class="font-semibold text-gray-900">User dengan Akses</h3>
                <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">{{ $usersWithAccess->count() }}</span>
            </div>
        </div>
        
        <div class="max-h-[600px] overflow-y-auto">
            @forelse($usersWithAccess as $access)
            @php $user = $access->user; @endphp
            <div class="p-4 border-b border-gray-100 last:border-b-0 hover:bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=6366f1&color=fff" 
                             class="w-10 h-10 rounded-full">
                        <div>
                            <p class="font-medium text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                Akses: {{ $access->created_at->format('d M Y') }}
                                @if($type === 'package' && $access->end_date)
                                    • Exp: {{ $access->end_date->format('d M Y') }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <button onclick="revokeAccess({{ $user->id }}, '{{ $user->name }}')" 
                            class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                            title="Cabut Akses">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">
                <i class="ri-user-unfollow-line text-4xl text-gray-300 mb-2"></i>
                <p>Belum ada user dengan akses</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- RIGHT: All Users -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center gap-2 mb-3">
                <i class="ri-user-add-line text-primary"></i>
                <h3 class="font-semibold text-gray-900">Tambah Akses User</h3>
                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">{{ $allUsers->total() }}</span>
            </div>
            
            <!-- Search -->
            <form method="GET" action="{{ route('admin.akses.manage') }}" class="flex gap-2">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="item_id" value="{{ $item->package_id ?? $item->material_id ?? $item->tryout_id }}">
                <div class="relative flex-1">
                    <input type="text" name="search" value="{{ $search }}" 
                           placeholder="Cari user..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                    Cari
                </button>
                @if($search)
                <a href="{{ route('admin.akses.manage', ['type' => $type, 'item_id' => $item->package_id ?? $item->material_id ?? $item->tryout_id]) }}" 
                   class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">
                    Reset
                </a>
                @endif
            </form>
        </div>
        
        <div class="max-h-[500px] overflow-y-auto">
            @forelse($allUsers as $user)
            <div class="p-4 border-b border-gray-100 last:border-b-0 hover:bg-gray-50 {{ $user->has_access ? 'opacity-50' : '' }}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=6366f1&color=fff" 
                             class="w-10 h-10 rounded-full">
                        <div>
                            <p class="font-medium text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                        </div>
                    </div>
                    
                    @if($user->has_access)
                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-medium">
                        Sudah Punya Akses
                    </span>
                    @else
                    <div class="flex items-center gap-2">
                        <select id="access_type_{{ $user->id }}" class="text-xs border border-gray-300 rounded-lg px-2 py-1.5">
                            <option value="free">Gratis</option>
                            <option value="paid">Berbayar</option>
                        </select>
                        <button onclick="grantAccess({{ $user->id }}, '{{ $user->name }}')" 
                                class="px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-medium hover:bg-primary/90">
                            <i class="ri-add-line mr-1"></i>Beri Akses
                        </button>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">
                <i class="ri-user-line text-4xl text-gray-300 mb-2"></i>
                <p>Tidak ada user ditemukan</p>
            </div>
            @endforelse
        </div>
        
        <!-- Pagination -->
        @if($allUsers->hasPages())
        <div class="p-4 border-t border-gray-200">
            {{ $allUsers->links() }}
        </div>
        @endif
    </div>
</div>

@endsection

@section('scripts')
<script>
const rawType = '{{ $type }}';
// Normalize type to match what controller expects
let type = rawType.replace(/s$/, ''); // Remove trailing 's'
if (type === 'live') type = 'live_session';

const itemId = '{{ $item->package_id ?? $item->material_id ?? $item->tryout_id }}';
const csrfToken = '{{ csrf_token() }}';

function grantAccess(userId, userName) {
    const accessType = document.getElementById(`access_type_${userId}`).value;
    
    if (!confirm(`Berikan akses ${accessType === 'free' ? 'GRATIS' : 'BERBAYAR'} kepada ${userName}?`)) {
        return;
    }
    
    fetch('{{ route('admin.akses.grant') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            type: type,
            item_id: itemId,
            user_id: userId,
            access_type: accessType
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Akses berhasil diberikan!');
            location.reload();
        } else {
            alert(data.message || 'Gagal memberikan akses');
        }
    })
    .catch(err => {
        alert('Terjadi kesalahan: ' + err.message);
    });
}

function revokeAccess(userId, userName) {
    if (!confirm(`Cabut akses dari ${userName}?`)) {
        return;
    }
    
    fetch('{{ route('admin.akses.revoke') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            type: type,
            item_id: itemId,
            user_id: userId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Akses berhasil dicabut!');
            location.reload();
        } else {
            alert(data.message || 'Gagal mencabut akses');
        }
    })
    .catch(err => {
        alert('Terjadi kesalahan: ' + err.message);
    });
}
</script>
@endsection
