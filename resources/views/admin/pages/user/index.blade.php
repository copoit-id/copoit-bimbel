@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Manajemen User</h2>
            <p class="text-gray-500">Kelola pengguna dan akses sistem</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.user.export-excel') }}"
                class="bg-white text-green border border-green px-4 py-2 rounded-lg hover:bg-green/5 flex items-center gap-2">
                <i class="ri-file-excel-2-line"></i>
                Export Excel
            </a>
            <a href="{{ route('admin.user.import') }}"
                class="bg-green text-white px-4 py-2 rounded-lg hover:bg-green/90 flex items-center gap-2">
                <i class="ri-upload-line"></i>
                Import CSV
            </a>
            {{-- Button dengan Cek Plan Quota --}}
            <x-plan-quota-button 
                feature="user"
                href="{{ route('admin.user.create') }}"
                icon="ri-add-line"
                label="Tambah User"
                variant="primary"
                size="md"
                tooltipPosition="bottom" />
        </div>
    </div>

    @if (session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
        <i class="ri-checkbox-circle-line text-lg"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if (session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center gap-2">
        <i class="ri-error-warning-line text-lg"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    {{-- Alert Quota Status --}}
    @php
        $userQuota = $planQuota['user'] ?? \App\Services\PlanQuotaService::canRegisterUser();
    @endphp
    
    @if(!$userQuota['allowed'])
        <div class="rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700">
            <div class="flex items-center gap-2">
                <i class="ri-information-line text-lg"></i>
                <div>
                    <p class="font-medium">Kuota User Terpenuhi</p>
                    <p>{{ $userQuota['reason'] }} Silakan hubungi administrator untuk upgrade plan.</p>
                </div>
            </div>
        </div>
    @elseif($userQuota['limit'] > 0 && $userQuota['current'] >= $userQuota['limit'] - 5)
        {{-- Warning jika hampir penuh (5 user tersisa) --}}
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-700">
            <div class="flex items-center gap-2">
                <i class="ri-alert-line text-lg"></i>
                <div>
                    <p class="font-medium">Kuota User Hampir Penuh</p>
                    <p>Anda telah menggunakan {{ $userQuota['current'] }} dari {{ $userQuota['limit'] }} user. Segera upgrade plan untuk menambah lebih banyak user.</p>
                </div>
            </div>
        </div>
    @endif

    @include('admin.pages.user.partials.management-tabs', [
        'activeManagementTab' => $activeRole,
        'roleOptions' => $roleOptions,
    ])

    <form id="bulk-delete-form" action="{{ route('admin.user.bulk-destroy') }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <div class="package-bimbel bg-white p-8 rounded-lg border border-border">
        <div class="mb-4 flex flex-col justify-between gap-3 xl:flex-row xl:items-center">
            <form method="GET" action="{{ route('admin.user.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="role" value="{{ $activeRole }}">
                <div class="relative">
                    <input type="search" name="search" value="{{ $search }}" placeholder="Cari nama, email, username, atau nomor HP..."
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
                </div>

                <select name="status"
                    class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">Semua Status</option>
                    <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                    <option value="nonaktif" @selected($status === 'nonaktif')>Tidak Aktif</option>
                </select>

                <button type="submit"
                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                    <i class="ri-search-line"></i> Cari
                </button>
                <a href="{{ route('admin.user.index', ['role' => $activeRole]) }}"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <i class="ri-refresh-line"></i> Reset
                </a>
            </form>

            <div class="flex items-center gap-3">
                <div id="user-count" class="text-sm text-gray-500">
                    Menampilkan: <span class="font-medium text-gray-700">{{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}</span>
                    <span class="mx-1 text-gray-300">•</span>
                    Total {{ $roleOptions[$activeRole] ?? 'User' }}: <span class="font-medium text-gray-700 total-count">{{ $users->total() }} User</span>
                </div>
                <button type="submit" form="bulk-delete-form" id="bulk-delete-button" disabled
                    class="flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2 text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">
                    <i class="ri-delete-bin-5-line"></i>
                    Hapus Terpilih
                    <span class="inline-flex items-center justify-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-600">
                        <span id="bulk-selected-count">0</span>
                    </span>
                </button>
            </div>
        </div>

        <!-- User Table -->
        <div>
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 w-12">
                                <input type="checkbox" id="select-all-users"
                                    class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary/50">
                            </th>
                            <th scope="col" class="px-6 py-3">User</th>
                            <th scope="col" class="px-6 py-3">Username</th>
                            <th scope="col" class="px-6 py-3">Tujuan Belajar</th>
                            <th scope="col" class="px-6 py-3">Program & Kelas</th>
                            <th scope="col" class="px-6 py-3">Kehadiran</th>
                            <th scope="col" class="px-6 py-3">Role</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3">Dibuat</th>
                            <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="user-tbody">
                        @forelse ($users as $user)
                        <tr class="bg-white border-b border-dashed border-gray-200 user-row"
                            data-name="{{ Str::lower($user->name) }}" data-email="{{ Str::lower($user->email) }}"
                            data-username="{{ Str::lower($user->username) }}" data-phone="{{ Str::lower($user->phone) }}"
                            data-role="{{ $user->role }}" data-status="{{ $user->status }}">
                            <td class="px-6 py-4">
                                <input type="checkbox" name="ids[]" value="{{ $user->id }}" form="bulk-delete-form"
                                    class="user-checkbox h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary/50">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=444444&color=fff"
                                        class="w-10 h-10 rounded-full" alt="{{ $user->name }}">
                                    <div>
                                        <p class="font-medium">{{ $user->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $user->email }}</p>
                                        @if($user->phone)
                                            <p class="mt-0.5 text-xs text-gray-400"><i class="ri-phone-line"></i> {{ $user->phone }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-700">{{ $user->username }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-700">
                                    {{ $user->participant_destination_display_name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $activePackages = $user->userPackageAccess->filter(fn ($access) => $access->is_active);
                                    $packageNames = $activePackages->pluck('package.name')->filter()->values();
                                    $studyGroupNames = $user->studyGroups->pluck('name')->filter()->values();
                                @endphp
                                <div class="space-y-1.5">
                                    <p class="max-w-48 truncate text-sm font-medium text-gray-700" title="{{ $packageNames->implode(', ') }}">
                                        <i class="ri-book-open-line text-primary"></i>
                                        {{ $packageNames->isNotEmpty() ? $packageNames->implode(', ') : 'Belum ada paket aktif' }}
                                    </p>
                                    <p class="max-w-48 truncate text-xs text-gray-500" title="{{ $studyGroupNames->implode(', ') }}">
                                        <i class="ri-team-line"></i>
                                        {{ $studyGroupNames->isNotEmpty() ? $studyGroupNames->implode(', ') : 'Belum masuk rombel' }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($user->attendance_record_count > 0)
                                    @php $attendanceRate = round(($user->attendance_present_count / $user->attendance_record_count) * 100); @endphp
                                    <div class="min-w-24">
                                        <div class="mb-1 flex items-center justify-between gap-2 text-xs"><span class="font-medium text-gray-700">{{ $attendanceRate }}%</span><span class="text-gray-400">{{ $user->attendance_present_count }}/{{ $user->attendance_record_count }}</span></div>
                                        <div class="h-1.5 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full {{ $attendanceRate >= 80 ? 'bg-emerald-500' : ($attendanceRate >= 60 ? 'bg-amber-400' : 'bg-red-500') }}" style="width: {{ $attendanceRate }}%"></div></div>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">Belum ada data</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                $roleClass = match($user->role) {
                                'admin' => 'bg-red-100 text-red-800',
                                'admin_demo' => 'bg-amber-100 text-amber-800',
                                'user' => 'bg-green-100 text-green-800',
                                'konsultan' => 'bg-blue-100 text-blue-800',
                                default => 'bg-gray-100 text-gray-800'
                                };
                                $roleLabel = $roleOptions[$user->role] ?? \Illuminate\Support\Str::headline($user->role);
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $roleClass }}">
                                    {{ $roleLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                $statusClass = match($user->status) {
                                'aktif' => 'bg-green-100 text-green-800',
                                'nonaktif' => 'bg-gray-100 text-gray-800',
                                default => 'bg-gray-100 text-gray-800'
                                };
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusClass }}">
                                    {{ $user->status === 'aktif' ? 'Aktif' : 'Tidak Aktif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                {{ optional($user->created_at)->format('Y-m-d') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('admin.user.show', $user->id) }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-primary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                        title="Lihat detail user" aria-label="Lihat detail user">
                                        <i class="ri-eye-line text-base"></i>
                                        <span class="sr-only">Lihat detail user</span>
                                    </a>
                                    <a href="{{ route('admin.user.report', $user->id) }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-primary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                        title="Lihat laporan belajar" aria-label="Lihat laporan belajar">
                                        <i class="ri-bar-chart-line text-base"></i>
                                        <span class="sr-only">Lihat laporan belajar</span>
                                    </a>
                                    @if($user->role === 'user')
                                    <form action="{{ route('admin.user.login-as', $user->id) }}" method="POST" 
                                        class="inline-flex"
                                        target="_blank">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-primary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                            title="Login sebagai user di tab baru" aria-label="Login sebagai user di tab baru">
                                            <i class="ri-user-shared-line text-base"></i>
                                            <span class="sr-only">Login sebagai user di tab baru</span>
                                        </button>
                                    </form>
                                    @endif
                                    <a href="{{ route('admin.user.edit', array_merge(request()->query(), ['user' => $user->id])) }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-primary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                        title="Edit user" aria-label="Edit user">
                                        <i class="ri-edit-line text-base"></i>
                                        <span class="sr-only">Edit user</span>
                                    </a>
                                    <form action="{{ route('admin.user.destroy', $user->id) }}" method="POST"
                                        onsubmit="return confirm('Hapus user {{ addslashes($user->name) }}?')" class="inline-flex">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-100"
                                            title="Hapus user" aria-label="Hapus user">
                                            <i class="ri-delete-bin-line text-base"></i>
                                            <span class="sr-only">Hapus user</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-6 py-10 text-center text-gray-500">
                                Tidak ada user.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $users->withQueryString()->links() }}
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('select-all-users');
            const bulkButton = document.getElementById('bulk-delete-button');
            const bulkCount = document.getElementById('bulk-selected-count');
            const bulkForm = document.getElementById('bulk-delete-form');

            function getRowCheckboxes() {
                return Array.from(document.querySelectorAll('.user-checkbox'));
            }

            function updateBulkState() {
                const checkboxes = getRowCheckboxes();
                const checked = checkboxes.filter(cb => cb.checked);

                if (bulkButton) {
                    bulkButton.disabled = checked.length === 0;
                }

                if (bulkCount) {
                    bulkCount.textContent = checked.length;
                }

                if (selectAll) {
                    if (checked.length === 0) {
                        selectAll.checked = false;
                        selectAll.indeterminate = false;
                    } else if (checked.length === checkboxes.length) {
                        selectAll.checked = true;
                        selectAll.indeterminate = false;
                    } else {
                        selectAll.checked = false;
                        selectAll.indeterminate = true;
                    }
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    getRowCheckboxes().forEach(cb => {
                        if (!cb.disabled) {
                            cb.checked = selectAll.checked;
                        }
                    });
                    updateBulkState();
                });
            }

            getRowCheckboxes().forEach(cb => {
                cb.addEventListener('change', updateBulkState);
            });


            if (bulkForm) {
                bulkForm.addEventListener('submit', function (event) {
                    const selected = getRowCheckboxes().filter(cb => cb.checked);
                    if (selected.length === 0) {
                        event.preventDefault();
                        return;
                    }

                    if (!confirm(`Hapus ${selected.length} user terpilih?`)) {
                        event.preventDefault();
                    }
                });
            }

            updateBulkState();
        });
    </script>
</div>

<!-- Add User Modal -->
<div id="add-user-modal" tabindex="-1" aria-hidden="true"
    class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Tambah User Baru
                </h3>
                <button type="button"
                    class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center"
                    data-modal-hide="add-user-modal">
                    <i class="ri-close-line text-lg"></i>
                </button>
            </div>

            {{-- Form disesuaikan dengan validasi di controller --}}
            <form action="{{ route('admin.user.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Nama Lengkap</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5"
                                required>
                            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                            <input type="text" name="username" value="{{ old('username') }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5"
                                required>
                            @error('username') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5"
                                required>
                            @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Password</label>
                            <input type="password" name="password"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5"
                                required>
                            @error('password') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Role</label>
                            <select name="role"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5"
                                required>
                                <option value="" @selected(old('role')==='' )>Pilih role</option>
                                @foreach($roleOptions as $roleSlug => $roleName)
                                    <option value="{{ $roleSlug }}" @selected(old('role') === $roleSlug)>{{ $roleName }}</option>
                                @endforeach
                            </select>
                            @error('role') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Status</label>
                            <select name="status"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5"
                                required>
                                <option value="" @selected(old('status')==='' )>Pilih status</option>
                                <option value="aktif" @selected(old('status')==='aktif' )>Aktif</option>
                                <option value="nonaktif" @selected(old('status')==='nonaktif' )>Tidak Aktif</option>
                            </select>
                            @error('status') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Optional: foto profil (tidak divalidasi di controller) --}}
                        <div class="col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Foto Profil</label>
                            <div class="flex items-center justify-center w-full">
                                <label for="dropzone-file"
                                    class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <i class="ri-upload-cloud-2-line text-4xl text-gray-500 mb-2"></i>
                                        <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Klik untuk
                                                upload</span> atau drag and drop</p>
                                        <p class="text-xs text-gray-500">PNG atau JPG (MAX. 2MB)</p>
                                    </div>
                                    <input id="dropzone-file" type="file" name="avatar" accept="image/png,image/jpeg"
                                        class="hidden" />
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="button" data-modal-hide="add-user-modal"
                        class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                        Batal
                    </button>
                    <button type="submit"
                        class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
