@extends('super-admin.layouts.app')
@section('title', 'Super Admin - Admin Demo')
@section('content')

<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold">Admin Demo</h2>
            <p class="text-gray-500">Kelola akun admin dan batas akses waktunya.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" data-copy-demo-link="{{ route('demo-requests.create') }}" class="inline-flex items-center gap-2 rounded-lg border border-primary/30 bg-white px-4 py-2.5 text-sm font-semibold text-primary hover:bg-primary/5">
                <i class="ri-links-line text-lg"></i>
                Salin Link Pengajuan
            </button>
            @if ($tab === 'admins')
                <a href="{{ route('super-admin.admins.export-excel') }}" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">
                    <i class="ri-file-excel-2-line text-lg"></i>
                    Export Excel
                </a>
                <button type="button" data-open-modal="create-admin-modal" class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-white hover:bg-primary/90">
                    <i class="ri-user-add-line text-lg"></i>
                    Tambah Admin
                </button>
            @endif
        </div>
    </div>

    <div class="flex flex-wrap gap-2 border-b border-gray-200">
        <a href="{{ route('super-admin.admins.index', ['tab' => 'admins']) }}"
            class="inline-flex items-center gap-2 border-b-2 px-3 py-2 text-sm font-semibold {{ $tab === 'admins' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
            Akun Demo
            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('super-admin.admins.index', ['tab' => 'requests']) }}"
            class="inline-flex items-center gap-2 border-b-2 px-3 py-2 text-sm font-semibold {{ $tab === 'requests' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
            Pengajuan
            <span class="rounded-full px-2 py-0.5 text-xs {{ $pendingRequestCount > 0 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600' }}">{{ $pendingRequestCount }}</span>
        </a>
    </div>

    @if ($tab === 'requests')
        <div class="overflow-hidden rounded-xl border border-border bg-white">
            <div class="border-b border-gray-100 px-6 py-5">
                <h3 class="text-lg font-semibold text-gray-900">Pengajuan Demo</h3>
                <p class="mt-1 text-sm text-gray-500">Setujui pengajuan setelah menentukan masa akses akun demo.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-gray-600">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Nama</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">WhatsApp</th>
                            <th class="px-4 py-3 text-left">Asal Bimbel</th>
                            <th class="px-4 py-3 text-left">Keterangan</th>
                            <th class="px-4 py-3 text-center">Diajukan</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($demoRequests as $demoRequest)
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $demoRequest->name }}</td>
                                <td class="px-4 py-3">{{ $demoRequest->email }}</td>
                                <td class="px-4 py-3">
                                    <a href="https://wa.me/{{ $demoRequest->phone }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 font-medium text-emerald-700 hover:underline">
                                        <i class="ri-whatsapp-line"></i>{{ $demoRequest->phone }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">{{ $demoRequest->origin_institution }}</td>
                                <td class="max-w-xs px-4 py-3">{{ \Illuminate\Support\Str::limit($demoRequest->request_note ?: '-', 100) }}</td>
                                <td class="px-4 py-3 text-center">{{ $demoRequest->created_at?->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <button type="button" data-open-modal="approve-request-{{ $demoRequest->id }}"
                                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                            <i class="ri-check-line text-base"></i>
                                            ACC
                                        </button>
                                        <form method="POST" action="{{ route('super-admin.admins.requests.reject', $demoRequest) }}" data-reject-demo-request>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Tolak dan hapus pengajuan"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 text-red-600 hover:bg-red-50"
                                                aria-label="Tolak dan hapus pengajuan {{ $demoRequest->name }}">
                                                <i class="ri-close-line text-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-gray-500">Belum ada pengajuan demo yang menunggu persetujuan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4">
                {{ $demoRequests->links() }}
            </div>
        </div>

        @foreach ($demoRequests as $demoRequest)
            <div id="approve-request-{{ $demoRequest->id }}" class="fixed inset-0 z-50 hidden items-center justify-center px-4">
                <div class="absolute inset-0 bg-black/50" data-close-edit-modal></div>
                <div class="relative flex h-[calc(100vh-2rem)] max-h-[560px] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white p-6 shadow-xl">
                    <div class="mb-4 flex shrink-0 items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Setujui Pengajuan Demo</p>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $demoRequest->name }}</h3>
                        </div>
                        <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-600" data-close-edit-modal>&times;</button>
                    </div>
                    @if (old('form_context') === 'approve-request-'.$demoRequest->id && $errors->any())
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <p class="mb-4 text-sm leading-6 text-gray-500">Akun dibuat dengan password awal <strong class="text-gray-700">password123</strong>. Tentukan masa akses sebelum menyetujui.</p>
                    <form method="POST" action="{{ route('super-admin.admins.requests.approve', $demoRequest) }}" class="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                        @csrf
                        <input type="hidden" name="form_context" value="approve-request-{{ $demoRequest->id }}">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-700">Cara menentukan akses</label>
                            <select name="expiry_type" class="w-full rounded-lg border border-gray-200 px-4 py-2" data-expiry-select>
                                <option value="duration" @selected(old('expiry_type', 'duration') === 'duration')>Durasi (hari/jam)</option>
                                <option value="date" @selected(old('expiry_type') === 'date')>Sampai tanggal</option>
                            </select>
                        </div>
                        <div data-expiry-duration>
                            <label class="mb-2 block text-sm font-semibold text-gray-700">Durasi akses</label>
                            <div class="flex gap-2">
                                <input type="number" name="duration_days" min="0" max="365" value="{{ old('duration_days', 7) }}" class="w-full rounded-lg border border-gray-200 px-4 py-2" placeholder="Hari">
                                <input type="number" name="duration_hours" min="0" max="720" value="{{ old('duration_hours', 0) }}" class="w-full rounded-lg border border-gray-200 px-4 py-2" placeholder="Jam">
                            </div>
                        </div>
                        <div class="hidden" data-expiry-date>
                            <label class="mb-2 block text-sm font-semibold text-gray-700">Berlaku sampai</label>
                            <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" class="w-full rounded-lg border border-gray-200 px-4 py-2">
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" data-close-edit-modal class="rounded-lg border border-gray-200 px-4 py-2 text-gray-600 hover:bg-gray-50">Batal</button>
                            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 font-semibold text-white hover:bg-emerald-700">ACC & Buat Akun</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @else
    <div id="create-admin-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto px-4 py-6">
        <div class="absolute inset-0 bg-black/50" data-close-modal></div>
        <div class="relative my-auto flex h-[calc(100vh-2rem)] max-h-[720px] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex shrink-0 items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Admin Demo</p>
                    <h3 class="text-lg font-semibold text-gray-900">Tambah Admin</h3>
                </div>
                <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-600" data-close-modal>&times;</button>
            </div>
            @if (old('form_context') === 'create-admin' && $errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('super-admin.admins.store') }}" class="grid min-h-0 flex-1 grid-cols-1 gap-4 overflow-y-auto pr-1 md:grid-cols-2">
            @csrf
            <input type="hidden" name="form_context" value="create-admin">
            @foreach ($returnQuery as $key => $value)
                <input type="hidden" name="return_{{ $key }}" value="{{ $value }}">
            @endforeach
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nomor WhatsApp Peminta</label>
                <input type="tel" name="phone" value="{{ old('phone') }}" inputmode="tel" autocomplete="tel"
                    placeholder="Contoh: 081234567890" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
                <p class="mt-1 text-xs text-gray-500">Dipakai untuk menghubungi peminta akses demo.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Username (opsional)</label>
                <input type="text" name="username" value="{{ old('username') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Asal Bimbel <span class="text-red-600">*</span></label>
                <input type="text" name="origin_institution" value="{{ old('origin_institution') }}" placeholder="Contoh: Bimbel Cakrawala" class="w-full rounded-lg border border-gray-200 px-4 py-2" required>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Status Deal Demo <span class="text-red-600">*</span></label>
                <select name="demo_deal_status" class="w-full rounded-lg border border-gray-200 px-4 py-2" required>
                    @foreach ($dealStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('demo_deal_status', 'baru') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                <input type="password" name="password" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
            </div>
            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-gray-700">Catatan</label>
                <textarea name="demo_note" rows="6" data-summernote data-height="220"
                    data-toolbar='[["font",["bold","underline","clear"]],["para",["ul","ol","paragraph"]],["insert",["link"]],["view",["codeview"]]]'
                    class="summernote-field w-full rounded-lg border border-gray-200 px-4 py-2"
                    placeholder="Tulis catatan internal tentang akun demo ini...">{{ old('demo_note') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tipe Batas Waktu</label>
                <select name="expiry_type" id="expiry_type" class="w-full border border-gray-200 rounded-lg px-4 py-2">
                    <option value="date" @selected(old('expiry_type', 'date') === 'date')>Sampai Tanggal</option>
                    <option value="duration" @selected(old('expiry_type') === 'duration')>Durasi (hari/jam)</option>
                </select>
            </div>
            <div id="expiry_date_field">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Berlaku Sampai</label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2">
            </div>
            <div id="expiry_duration_field" class="hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Durasi</label>
                <div class="flex gap-2">
                    <input type="number" name="duration_days" min="0" max="365" value="{{ old('duration_days', 0) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2" placeholder="Hari">
                    <input type="number" name="duration_hours" min="0" max="720" value="{{ old('duration_hours', 0) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2" placeholder="Jam">
                </div>
                <p class="text-xs text-gray-500 mt-2">Isi salah satu atau keduanya.</p>
            </div>
            <div class="flex justify-end gap-2 md:col-span-2">
                <button type="button" data-close-modal class="px-5 py-2 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Buat Admin</button>
            </div>
            </form>
        </div>
    </div>

    <div class="bg-white border border-border rounded-xl p-6">
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Daftar Admin</h3>
                <p class="text-sm text-gray-500">Cari, filter status, dan urutkan data admin demo.</p>
            </div>
            <form method="GET" action="{{ route('super-admin.admins.index') }}" class="flex flex-col gap-2 sm:flex-row">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, WA"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm sm:w-56">
                <select name="sort" class="rounded-lg border border-gray-200 px-3 py-2 text-sm" onchange="this.form.submit()">
                    @foreach ($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Terapkan</button>
            </form>
        </div>

        <div class="mb-5 flex flex-wrap gap-2 border-b border-gray-200">
            @foreach (['all' => 'Semua', 'active' => 'Aktif', 'expired' => 'Expired'] as $tabStatus => $label)
                <a href="{{ route('super-admin.admins.index', array_filter(['status' => $tabStatus, 'sort' => $sort, 'search' => request('search')])) }}"
                    class="inline-flex items-center gap-2 border-b-2 px-3 py-2 text-sm font-semibold transition {{ $status === $tabStatus ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    {{ $label }}
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $status === $tabStatus ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-600' }}">{{ $counts[$tabStatus] }}</span>
                </a>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-600">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                    <tr>
                        <th class="min-w-[280px] px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">WhatsApp</th>
                        <th class="px-4 py-3 text-left">Asal Bimbel</th>
                        <th class="px-4 py-3 text-center">Catatan</th>
                        <th class="px-4 py-3 text-center">Status Deal</th>
                        <th class="px-4 py-3 text-center">Ditambahkan</th>
                        <th class="px-4 py-3 text-center">Expired</th>
                        <th class="px-4 py-3 text-center">Status Akses</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($admins as $admin)
                        @php
                            $expired = $admin->admin_expires_at && now()->gte($admin->admin_expires_at);
                        @endphp
                        <tr class="border-t border-gray-100">
                            <td class="min-w-[280px] px-4 py-3 font-medium text-gray-900">{{ $admin->name }}</td>
                            <td class="px-4 py-3">{{ $admin->email }}</td>
                            <td class="px-4 py-3">
                                @if($admin->phone)
                                    <a href="https://wa.me/{{ $admin->phone }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 font-medium text-emerald-700 hover:text-emerald-800 hover:underline">
                                        <i class="ri-whatsapp-line text-base"></i>{{ $admin->phone }}
                                    </a>
                                @else
                                    <span class="text-xs font-medium text-red-600">Belum diisi</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $admin->origin_institution ?: '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $notePreview = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $admin->demo_note), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
                                @endphp
                                @if ($notePreview !== '')
                                    <div class="group relative inline-flex">
                                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-sky-200 text-sky-700 hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-sky-300"
                                            title="Lihat catatan" aria-label="Lihat catatan {{ $admin->name }}">
                                            <i class="ri-sticky-note-line text-base"></i>
                                        </button>
                                        <span role="tooltip" class="pointer-events-none invisible absolute bottom-full right-0 z-30 mb-2 w-72 rounded-lg bg-gray-900 px-3 py-2 text-left text-xs font-normal leading-5 text-white opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
                                            {{ $notePreview }}
                                        </span>
                                    </div>
                                @else
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-400" title="Belum ada catatan" aria-label="Belum ada catatan">
                                        <i class="ri-sticky-note-line text-base"></i>
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $dealStatus = $admin->demo_deal_status ?: 'baru';
                                    $dealStatusClasses = [
                                        'baru' => 'bg-gray-100 text-gray-700',
                                        'potensial' => 'bg-blue-100 text-blue-700',
                                        'menunggu_keputusan' => 'bg-amber-100 text-amber-700',
                                        'deal' => 'bg-emerald-100 text-emerald-700',
                                        'tidak_jadi' => 'bg-red-100 text-red-700',
                                    ];
                                @endphp
                                <span class="inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold {{ $dealStatusClasses[$dealStatus] ?? $dealStatusClasses['baru'] }}">
                                    {{ $dealStatusOptions[$dealStatus] ?? $dealStatusOptions['baru'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">{{ $admin->created_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                {{ $admin->admin_expires_at ? $admin->admin_expires_at->format('d M Y H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $expired ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $expired ? 'Expired' : 'Aktif' }}
                                </span>
                            </td>
                            <td class="min-w-[140px] px-4 py-3 align-middle text-right">
                                <div class="flex flex-nowrap items-center justify-end gap-2 whitespace-nowrap">
                                    <button type="button" data-open-modal="edit-admin-{{ $admin->id }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-primary text-primary transition hover:bg-primary hover:text-white"
                                        title="Edit {{ $admin->name }}" aria-label="Edit {{ $admin->name }}">
                                        <i class="ri-edit-line text-base"></i>
                                    </button>
                                    <button type="button" data-open-modal="extend-admin-{{ $admin->id }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-emerald-600 text-emerald-700 transition hover:bg-emerald-600 hover:text-white"
                                        title="Perpanjang akses {{ $admin->name }}" aria-label="Perpanjang akses {{ $admin->name }}">
                                        <i class="ri-time-line text-base"></i>
                                    </button>
                                    <form method="POST" action="{{ route('super-admin.admins.reset-password', $admin) }}" class="shrink-0"
                                        onsubmit="return confirm('Reset password {{ $admin->name }} ke password default (password123)?');">
                                        @csrf
                                        @foreach ($returnQuery as $key => $value)
                                            <input type="hidden" name="return_{{ $key }}" value="{{ $value }}">
                                        @endforeach
                                        <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-amber-500 text-amber-700 transition hover:bg-amber-500 hover:text-white"
                                            title="Reset password {{ $admin->name }}" aria-label="Reset password {{ $admin->name }}">
                                            <i class="ri-lock-password-line text-base"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-6 text-center text-gray-500">Tidak ada admin demo pada filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $admins->links() }}
        </div>

        @foreach($admins as $admin)
            <div id="edit-admin-{{ $admin->id }}" class="fixed inset-0 z-50 hidden items-center justify-center px-4">
                <div class="absolute inset-0 bg-black/50" data-close-edit-modal></div>
                <div class="relative flex h-[calc(100vh-2rem)] max-h-[720px] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white p-6 shadow-xl">
                    <div class="mb-4 flex shrink-0 items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Edit data Admin Demo</p>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $admin->name }}</h3>
                        </div>
                        <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl leading-none" data-close-edit-modal>&times;</button>
                    </div>
                    @if (old('form_context') === 'edit-admin-'.$admin->id && $errors->any())
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('super-admin.admins.update', $admin) }}" class="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="form_context" value="edit-admin-{{ $admin->id }}">
                                    @foreach ($returnQuery as $key => $value)
                                        <input type="hidden" name="return_{{ $key }}" value="{{ $value }}">
                                    @endforeach
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nama</label>
                                        <input type="text" name="name" value="{{ old('name', $admin->name) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                        <input type="email" name="email" value="{{ old('email', $admin->email) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nomor WhatsApp Peminta</label>
                                        <input type="tel" name="phone" value="{{ old('phone', $admin->phone) }}" inputmode="tel" autocomplete="tel"
                                            placeholder="Contoh: 081234567890" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                                        <input type="text" name="username" value="{{ old('username', $admin->username) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-gray-700">Asal Bimbel <span class="text-red-600">*</span></label>
                                        <input type="text" name="origin_institution" value="{{ old('origin_institution', $admin->origin_institution) }}" placeholder="Contoh: Bimbel Cakrawala" class="w-full rounded-lg border border-gray-200 px-4 py-2" required>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-gray-700">Status Deal Demo <span class="text-red-600">*</span></label>
                                        <select name="demo_deal_status" class="w-full rounded-lg border border-gray-200 px-4 py-2" required>
                                            @foreach ($dealStatusOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(old('demo_deal_status', $admin->demo_deal_status ?: 'baru') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-gray-700">Catatan</label>
                                        <textarea name="demo_note" rows="6" data-summernote data-height="220"
                                            data-toolbar='[["font",["bold","underline","clear"]],["para",["ul","ol","paragraph"]],["insert",["link"]],["view",["codeview"]]]'
                                            class="summernote-field w-full rounded-lg border border-gray-200 px-4 py-2"
                                            placeholder="Tulis catatan internal tentang akun demo ini...">{{ old('demo_note', $admin->demo_note) }}</textarea>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-gray-700">Password Baru (opsional)</label>
                                            <input type="password" name="password" class="w-full rounded-lg border border-gray-200 px-4 py-2">
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-gray-700">Konfirmasi Password</label>
                                            <input type="password" name="password_confirmation" class="w-full rounded-lg border border-gray-200 px-4 py-2">
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-2 pt-2">
                                        <button type="button" data-close-edit-modal
                                            class="px-4 py-2 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50">Batal</button>
                                        <button type="submit"
                                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Simpan</button>
                                    </div>
                    </form>
                </div>
            </div>

            <div id="extend-admin-{{ $admin->id }}" class="fixed inset-0 z-50 hidden items-center justify-center px-4">
                <div class="absolute inset-0 bg-black/50" data-close-edit-modal></div>
                <div class="relative flex h-[calc(100vh-2rem)] max-h-[560px] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white p-6 shadow-xl">
                    <div class="mb-4 flex shrink-0 items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Perpanjang akses Admin Demo</p>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $admin->name }}</h3>
                        </div>
                        <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl leading-none" data-close-edit-modal>&times;</button>
                    </div>
                    @if (old('form_context') === 'extend-admin-'.$admin->id && $errors->any())
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <p class="mb-4 text-sm text-gray-500">Akses saat ini: {{ $admin->admin_expires_at?->copy()->setTimezone('Asia/Jakarta')->format('d M Y H:i') ?? '-' }}.</p>
                    <form method="POST" action="{{ route('super-admin.admins.extend', $admin) }}" class="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="form_context" value="extend-admin-{{ $admin->id }}">
                        @foreach ($returnQuery as $key => $value)
                            <input type="hidden" name="return_{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Cara memperpanjang</label>
                            <select name="expiry_type" class="w-full border border-gray-200 rounded-lg px-4 py-2" data-expiry-select>
                                <option value="duration">Tambah durasi</option>
                                <option value="date">Atur sampai tanggal</option>
                            </select>
                        </div>
                        <div data-expiry-duration>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tambahan durasi</label>
                            <div class="flex gap-2">
                                <input type="number" name="duration_days" min="0" max="365" value="0" class="w-full border border-gray-200 rounded-lg px-4 py-2" placeholder="Hari">
                                <input type="number" name="duration_hours" min="0" max="720" value="0" class="w-full border border-gray-200 rounded-lg px-4 py-2" placeholder="Jam">
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Durasi ditambahkan dari tanggal berakhir saat ini. Jika sudah expired, dihitung dari sekarang.</p>
                        </div>
                        <div class="hidden" data-expiry-date>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Berlaku sampai</label>
                            <input type="datetime-local" name="expires_at" class="w-full border border-gray-200 rounded-lg px-4 py-2">
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" data-close-edit-modal class="px-4 py-2 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">Perpanjang Akses</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('expiry_type');
        const dateField = document.getElementById('expiry_date_field');
        const durationField = document.getElementById('expiry_duration_field');

        if (select && dateField && durationField) {
            const toggleFields = () => {
                if (select.value === 'duration') {
                    dateField.classList.add('hidden');
                    durationField.classList.remove('hidden');
                } else {
                    durationField.classList.add('hidden');
                    dateField.classList.remove('hidden');
                }
            };

            select.addEventListener('change', toggleFields);
            toggleFields();
        }

        const openModal = (modalId) => {
            const modal = document.getElementById(modalId);
            if (!modal) return;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            window.initSummernoteFields?.();
        };

        document.querySelectorAll('[data-open-modal]').forEach(button => {
            button.addEventListener('click', () => {
                openModal(button.getAttribute('data-open-modal'));
            });
        });

        document.querySelectorAll('[data-close-modal], [data-close-edit-modal]').forEach(button => {
            button.addEventListener('click', () => {
                const modal = button.closest('[id="create-admin-modal"], [id^="edit-admin-"], [id^="extend-admin-"], [id^="approve-request-"]');
                if (!modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        });

        document.querySelectorAll('[data-expiry-select]').forEach(selectEl => {
            const wrapper = selectEl.closest('form');
            if (!wrapper) return;
            const dateWrap = wrapper.querySelector('[data-expiry-date]');
            const durationWrap = wrapper.querySelector('[data-expiry-duration]');

            const toggle = () => {
                if (selectEl.value === 'duration') {
                    dateWrap?.classList.add('hidden');
                    durationWrap?.classList.remove('hidden');
                } else {
                    durationWrap?.classList.add('hidden');
                    dateWrap?.classList.remove('hidden');
                }
            };

            selectEl.addEventListener('change', toggle);
            toggle();
        });

        const formWithError = @json(old('form_context'));
        if (formWithError) {
            openModal(formWithError);
        }

        document.querySelectorAll('[data-copy-demo-link]').forEach(button => {
            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(button.dataset.copyDemoLink);
                    window.alert('Link pengajuan demo berhasil disalin.');
                } catch (error) {
                    window.prompt('Salin link pengajuan demo berikut:', button.dataset.copyDemoLink);
                }
            });
        });

        document.querySelectorAll('[data-reject-demo-request]').forEach(form => {
            form.addEventListener('submit', (event) => {
                if (!window.confirm('Tolak pengajuan ini? Data pengajuan akan dihapus permanen.')) {
                    event.preventDefault();
                }
            });
        });
    });
</script>
@endpush
