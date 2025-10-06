@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Manajemen Users</h2>
            <p class="text-gray-500">Kelola pengguna dan akses sistem</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.user.import') }}"
                class="bg-green text-white px-4 py-2 rounded-lg hover:bg-green/90 flex items-center gap-2">
                <i class="ri-upload-line"></i>
                Import CSV
            </a>
            <a href="{{ route('admin.user.create') }}"
                class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 flex items-center gap-2">
                <i class="ri-add-line"></i>
                Tambah User
            </a>
        </div>
    </div>

    <div class="package-bimbel bg-white p-8 rounded-lg border border-border">
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center gap-2">
                <div class="relative">
                    <input type="text" id="user-search" placeholder="Cari user..."
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
                </div>

                {{-- Karena controller index saat ini hanya menampilkan role=user,
                filter role tetap disediakan (untuk konsistensi UI),
                tetapi nilainya mengikuti enum di migration. --}}
                <select id="role-filter"
                    class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">Semua Role</option>
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                </select>

                <select id="status-filter"
                    class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Tidak Aktif</option>
                </select>

                <button id="reset-filters"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <i class="ri-refresh-line"></i> Reset
                </button>
            </div>

            <div id="user-count" class="text-sm text-gray-500">
                Halaman ini: <span class="font-medium text-gray-700">{{ $users->count() }} User</span>
                <span class="mx-1 text-gray-300">•</span>
                Total: <span class="font-medium text-gray-700">{{ $users->total() }} User</span>
            </div>
        </div>

        <!-- User Table -->
        <div id="user-table-container">
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">User</th>
                            <th scope="col" class="px-6 py-3">Username</th>
                            <th scope="col" class="px-6 py-3">Role</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3">Dibuat</th>
                            <th scope="col" class="px-6 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody id="user-tbody">
                        @forelse ($users as $user)
                        <tr class="bg-white border-b border-dashed border-gray-200 user-row"
                            data-name="{{ Str::lower($user->name) }}" data-email="{{ Str::lower($user->email) }}"
                            data-role="{{ $user->role }}" data-status="{{ $user->status }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=444444&color=fff"
                                        class="w-10 h-10 rounded-full" alt="{{ $user->name }}">
                                    <div>
                                        <p class="font-medium">{{ $user->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-700">{{ $user->username }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                $roleClass = match($user->role) {
                                'admin' => 'bg-red-100 text-red-800',
                                'user' => 'bg-green-100 text-green-800',
                                default => 'bg-gray-100 text-gray-800'
                                };
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $roleClass }}">
                                    {{ ucfirst($user->role) }}
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
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.user.show', $user->id) }}"
                                        class="text-primary hover:text-primary/80" title="Lihat">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <a href="{{ route('admin.user.edit', $user->id) }}"
                                        class="text-blue-600 hover:text-blue-800" title="Edit">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                    <form action="{{ route('admin.user.destroy', $user->id) }}" method="POST"
                                        onsubmit="return confirm('Hapus user ini?')" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800" title="Hapus">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
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

        <div id="no-results" class="hidden text-center py-12">
            <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="ri-user-line text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada user ditemukan</h3>
            <p class="text-gray-500">Coba ubah kata kunci pencarian atau filter</p>
        </div>
    </div>

    {{-- Client-side filter untuk data pada halaman saat ini --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('user-search');
            const roleFilter = document.getElementById('role-filter');
            const statusFilter = document.getElementById('status-filter');
            const resetButton = document.getElementById('reset-filters');
            const userCount = document.getElementById('user-count');
            const tbody = document.getElementById('user-tbody');
            const rows = Array.from(tbody.querySelectorAll('.user-row'));
            const noResults = document.getElementById('no-results');
            const tableContainer = document.getElementById('user-table-container');

            function getText(el, attr) {
                return (el.getAttribute(attr) || '').toLowerCase();
            }

            function applyFilters() {
                const q = (searchInput.value || '').toLowerCase().trim();
                const role = roleFilter.value;
                const status = statusFilter.value;

                let visible = 0;
                rows.forEach(row => {
                    const name = getText(row, 'data-name');
                    const email = getText(row, 'data-email');
                    const r = row.getAttribute('data-role') || '';
                    const s = row.getAttribute('data-status') || '';

                    const matchesSearch = !q || name.includes(q) || email.includes(q);
                    const matchesRole = !role || r === role;
                    const matchesStatus = !status || s === status;

                    const show = matchesSearch && matchesRole && matchesStatus;
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                // Toggle empty state
                const anyVisible = visible > 0;
                tableContainer.style.display = anyVisible ? 'block' : 'none';
                noResults.classList.toggle('hidden', anyVisible);

                // Update count (hanya untuk halaman ini)
                userCount.querySelector('span.font-medium.text-gray-7').textContent = '';
                const spans = userCount.querySelectorAll('span.font-medium.text-gray-700');
                if (spans.length >= 2) {
                    spans[0].textContent = `${visible} User`;
                    // spans[1] tetap total seluruh data (server)
                }
            }

            function resetFilters() {
                searchInput.value = '';
                roleFilter.value = '';
                statusFilter.value = '';
                applyFilters();
            }

            searchInput.addEventListener('input', applyFilters);
            roleFilter.addEventListener('change', applyFilters);
            statusFilter.addEventListener('change', applyFilters);
            resetButton.addEventListener('click', resetFilters);

            applyFilters();
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
                                <option value="admin" @selected(old('role')==='admin' )>Admin</option>
                                <option value="user" @selected(old('role')==='user' )>User</option>
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