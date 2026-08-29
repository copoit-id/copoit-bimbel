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
            'class', 'classes' => 'ri-video-on-line',
            'tryout' => 'ri-file-list-3-line',
            'tes_koran' => 'ri-file-edit-line',
            default => 'ri-apps-line',
        };
        $colorClass = match($type) {
            'package' => 'bg-blue-100 text-blue-600',
            'video' => 'bg-red-100 text-red-600',
            'document' => 'bg-green-100 text-green-600',
            'live' => 'bg-purple-100 text-purple-600',
            'class', 'classes' => 'bg-cyan-100 text-cyan-600',
            'tryout' => 'bg-orange-100 text-orange-600',
            'tes_koran' => 'bg-emerald-100 text-emerald-600',
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

            <div class="mb-3 inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1">
                <button type="button" data-access-panel-tab="mandiri"
                        class="access-panel-tab rounded-md bg-white px-3 py-1.5 text-sm font-medium text-primary shadow-sm">
                    Mandiri
                </button>
                @if($canUseStudyGroupAccess)
                <button type="button" data-access-panel-tab="rombel"
                        class="access-panel-tab rounded-md px-3 py-1.5 text-sm font-medium text-gray-500">
                    Rombel
                </button>
                @endif
            </div>
            
            <div id="access_panel_mandiri">
            <!-- Search -->
            <form method="GET" action="{{ route('admin.akses.manage') }}" class="grid gap-2 md:grid-cols-[1fr_1fr_auto]">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="item_id" value="{{ $item->package_id ?? $item->material_id ?? $item->class_id ?? $item->tryout_id ?? $item->id }}">
                <input type="hidden" name="mode" value="mandiri">
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" 
                           placeholder="Cari nama atau email peserta..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <i class="ri-search-line pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                </div>
                <div class="relative">
                    <input type="text" name="school" value="{{ $school }}"
                           placeholder="Cari nama sekolah..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <i class="ri-school-line pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Cari</button>
                    @if($search || $school)
                    <a href="{{ route('admin.akses.manage', ['type' => $type, 'item_id' => $item->package_id ?? $item->material_id ?? $item->tryout_id ?? $item->id]) }}"
                       class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Reset</a>
                    @endif
                </div>
            </form>
            </div>
        </div>

        <div id="access_panel_mandiri_list">
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
                            <i class="{{ $user->access_status === 'expired' ? 'ri-refresh-line' : 'ri-add-line' }} mr-1"></i>{{ $user->access_status === 'expired' ? 'Perpanjang' : 'Beri Akses' }}
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

        @if($canUseStudyGroupAccess)
        <div id="access_panel_rombel" class="hidden p-4">
            <div class="mb-3 flex items-center gap-2">
                <i class="ri-group-line text-primary"></i>
                <h3 class="font-semibold text-gray-900">Tambah Akses dari Rombel</h3>
            </div>
            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label for="study_group_select" class="block text-xs font-medium text-gray-500 mb-1">Pilih Rombel</label>
                    <select id="study_group_select" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <option value="">Pilih rombel</option>
                        @foreach($studyGroups as $studyGroup)
                            <option value="{{ $studyGroup->id }}">{{ $studyGroup->name }} ({{ $studyGroup->users->count() }} user)</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="rombel_access_type" class="block text-xs font-medium text-gray-500 mb-1">Tipe Akses</label>
                    <select id="rombel_access_type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <option value="free">Gratis</option>
                        <option value="paid">Berbayar</option>
                    </select>
                </div>
            </div>

            <div id="study_group_members" class="mt-4 max-h-[260px] overflow-y-auto rounded-lg border border-gray-200">
                <div class="p-5 text-center text-sm text-gray-500">Pilih rombel untuk melihat anggota.</div>
            </div>

            <button type="button" onclick="grantStudyGroupAccess()"
                    class="mt-4 w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary/90">
                <i class="ri-group-line mr-1"></i>Beri Akses ke Anggota Terpilih
            </button>
        </div>
        @endif
    </div>
</div>

@endsection

@section('scripts')
@php
    $studyGroupsPayload = $studyGroups->map(function ($group) {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'users' => $group->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            })->values(),
        ];
    })->values();
@endphp
<script>
const rawType = '{{ $type }}';
// Normalize type to match what controller expects
let type = rawType.replace(/s$/, ''); // Remove trailing 's'
if (type === 'live') type = 'live_session';
if (type === 'classe') type = 'class';

const itemId = '{{ $item->package_id ?? $item->material_id ?? $item->class_id ?? $item->tryout_id ?? $item->id }}';
const csrfToken = '{{ csrf_token() }}';
const canUseStudyGroupAccess = @json($canUseStudyGroupAccess);
const studyGroups = {{ \Illuminate\Support\Js::from($studyGroupsPayload) }};

document.querySelectorAll('[data-access-panel-tab]').forEach((button) => {
    button.addEventListener('click', () => {
        const activeTab = button.dataset.accessPanelTab;
        document.getElementById('access_panel_mandiri').classList.toggle('hidden', activeTab !== 'mandiri');
        document.getElementById('access_panel_mandiri_list').classList.toggle('hidden', activeTab !== 'mandiri');
        document.getElementById('access_panel_rombel')?.classList.toggle('hidden', activeTab !== 'rombel');

        document.querySelectorAll('[data-access-panel-tab]').forEach((tabButton) => {
            const isActive = tabButton.dataset.accessPanelTab === activeTab;
            tabButton.classList.toggle('bg-white', isActive);
            tabButton.classList.toggle('text-primary', isActive);
            tabButton.classList.toggle('shadow-sm', isActive);
            tabButton.classList.toggle('text-gray-500', !isActive);
        });
    });
});

async function grantAccess(userId, userName) {
    const accessType = document.getElementById(`access_type_${userId}`).value;
    
    if (!await window.appConfirm(`Berikan/perpanjang akses ${accessType === 'free' ? 'GRATIS' : 'BERBAYAR'} kepada ${userName}?`, {
        title: 'Berikan akses',
        confirmText: 'Ya, berikan',
        variant: 'success',
    })) {
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
    .then(async (data) => {
        if (data.success) {
            await window.appAlert('Akses berhasil diberikan!', { title: 'Akses diberikan', variant: 'success' });
            location.reload();
        } else {
            await window.appAlert(data.message || 'Gagal memberikan akses', { title: 'Akses gagal', variant: 'danger' });
        }
    })
    .catch(async (err) => {
        await window.appAlert('Terjadi kesalahan: ' + err.message, { title: 'Akses gagal', variant: 'danger' });
    });
}

document.getElementById('study_group_select')?.addEventListener('change', function () {
    const group = studyGroups.find((item) => String(item.id) === String(this.value));
    const wrapper = document.getElementById('study_group_members');

    if (!group) {
        wrapper.innerHTML = '<div class="p-5 text-center text-sm text-gray-500">Pilih rombel untuk melihat anggota.</div>';
        return;
    }

    if (!group.users.length) {
        wrapper.innerHTML = '<div class="p-5 text-center text-sm text-gray-500">Rombel ini belum punya anggota aktif.</div>';
        return;
    }

    wrapper.innerHTML = `
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-2 text-xs font-medium text-gray-500">
            Centang user yang akan diberi akses
        </div>
        ${group.users.map((user) => `
            <label class="flex items-center gap-3 border-b border-gray-100 px-4 py-3 last:border-b-0 hover:bg-gray-50">
                <input type="checkbox" name="rombel_user_ids[]" value="${user.id}" checked class="rounded border-gray-300 text-primary focus:ring-primary">
                <span class="min-w-0">
                    <span class="block truncate text-sm font-medium text-gray-900">${escapeHtml(user.name)}</span>
                    <span class="block truncate text-xs text-gray-500">${escapeHtml(user.email || '-')}</span>
                </span>
            </label>
        `).join('')}
    `;
});

async function grantStudyGroupAccess() {
    if (!canUseStudyGroupAccess) {
        await window.appAlert('Akses via rombel sedang tidak aktif.', { title: 'Tidak tersedia', variant: 'warning' });
        return;
    }

    const groupId = document.getElementById('study_group_select').value;
    const accessType = document.getElementById('rombel_access_type').value;
    const userIds = Array.from(document.querySelectorAll('input[name="rombel_user_ids[]"]:checked')).map((input) => input.value);

    if (!groupId) {
        await window.appAlert('Pilih rombel terlebih dahulu.', { title: 'Rombel belum dipilih', variant: 'warning' });
        return;
    }

    if (!userIds.length) {
        await window.appAlert('Pilih minimal satu anggota rombel.', { title: 'Anggota belum dipilih', variant: 'warning' });
        return;
    }

    if (!await window.appConfirm(`Berikan akses kepada ${userIds.length} anggota terpilih?`, {
        title: 'Berikan akses rombel',
        confirmText: 'Ya, berikan',
        variant: 'success',
    })) {
        return;
    }

    fetch('{{ route('admin.akses.grant-study-group') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            type: type,
            item_id: itemId,
            study_group_id: groupId,
            user_ids: userIds,
            access_type: accessType
        })
    })
    .then(res => res.json())
    .then(async (data) => {
        await window.appAlert(data.message || (data.success ? 'Akses berhasil diberikan!' : 'Gagal memberikan akses'), {
            title: data.success ? 'Akses diberikan' : 'Akses gagal',
            variant: data.success ? 'success' : 'danger',
        });
        if (data.success) location.reload();
    })
    .catch(async (err) => {
        await window.appAlert('Terjadi kesalahan: ' + err.message, { title: 'Akses gagal', variant: 'danger' });
    });
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function revokeAccess(userId, userName) {
    if (!await window.appConfirm(`Cabut akses dari ${userName}?`, {
        title: 'Cabut akses',
        confirmText: 'Ya, cabut',
        variant: 'danger',
    })) {
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
    .then(async (data) => {
        if (data.success) {
            await window.appAlert('Akses berhasil dicabut!', { title: 'Akses dicabut', variant: 'success' });
            location.reload();
        } else {
            await window.appAlert(data.message || 'Gagal mencabut akses', { title: 'Akses gagal', variant: 'danger' });
        }
    })
    .catch(async (err) => {
        await window.appAlert('Terjadi kesalahan: ' + err.message, { title: 'Akses gagal', variant: 'danger' });
    });
}
</script>
@endsection
