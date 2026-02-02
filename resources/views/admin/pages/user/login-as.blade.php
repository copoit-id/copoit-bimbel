@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Login As User</h2>
            <p class="text-gray-500 mt-1">Pilih user untuk login dan melihat pengalaman mereka</p>
        </div>
    </div>

    @if (session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
        <i class="ri-checkbox-circle-line text-lg"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- Search & Filter -->
    <div class="bg-white p-6 rounded-lg border border-border">
        <div class="flex flex-col sm:flex-row gap-4 mb-6">
            <div class="flex-1 relative">
                <input type="text" id="user-search" placeholder="Cari nama, email, atau username..."
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <i class="ri-search-line absolute left-3 top-3 text-gray-400"></i>
            </div>

            <select id="status-filter"
                class="border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <option value="">Semua Status</option>
                <option value="aktif" selected>Aktif</option>
                <option value="nonaktif">Tidak Aktif</option>
            </select>

            <button id="reset-filters"
                class="px-4 py-2.5 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="ri-refresh-line"></i> Reset
            </button>
        </div>

        <div class="text-sm text-gray-500 mb-4">
            Menampilkan <span class="font-medium text-gray-700" id="current-count">{{ $users->count() }}</span> dari 
            <span class="font-medium text-gray-700">{{ $users->total() }}</span> user
        </div>

        <!-- User Table -->
        <div id="user-table-container">
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500" id="user-table">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">User</th>
                            <th scope="col" class="px-6 py-3">Username</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3">Bergabung</th>
                            <th scope="col" class="px-6 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody id="user-tbody">
                        @forelse ($users as $user)
                        <tr class="bg-white border-b border-dashed border-gray-200 user-row"
                            data-name="{{ Str::lower($user->name) }}" 
                            data-email="{{ Str::lower($user->email) }}"
                            data-username="{{ Str::lower($user->username) }}"
                            data-status="{{ $user->status }}">
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
                                    <form action="{{ route('admin.user.login-as', $user->id) }}" method="POST" target="_blank" class="inline-block">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary hover:bg-primary/90 rounded-lg transition-colors">
                                            <i class="ri-user-shared-line"></i>
                                            <span>Login As</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                Tidak ada user.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div id="no-results" class="hidden text-center py-12">
            <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="ri-search-line text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">User tidak ditemukan</h3>
            <p class="text-gray-500">Coba ubah kata kunci pencarian atau filter Anda.</p>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('user-search');
        const statusFilter = document.getElementById('status-filter');
        const resetButton = document.getElementById('reset-filters');
        const userTableContainer = document.getElementById('user-table-container');
        const userTbody = document.getElementById('user-tbody');
        const noResults = document.getElementById('no-results');
        const currentCount = document.getElementById('current-count');

        function filterUsers() {
            const searchTerm = searchInput.value.toLowerCase();
            const statusValue = statusFilter.value;
            const userRows = userTbody.querySelectorAll('.user-row');
            let visibleCount = 0;

            userRows.forEach(row => {
                const name = row.dataset.name || '';
                const email = row.dataset.email || '';
                const username = row.dataset.username || '';
                const status = row.dataset.status || '';

                const matchesSearch = name.includes(searchTerm) || 
                                    email.includes(searchTerm) || 
                                    username.includes(searchTerm);
                const matchesStatus = !statusValue || status === statusValue;

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update count
            currentCount.textContent = visibleCount;

            // Show/hide no results
            if (visibleCount === 0) {
                userTableContainer.style.display = 'none';
                noResults.style.display = 'block';
            } else {
                userTableContainer.style.display = 'block';
                noResults.style.display = 'none';
            }
        }

        searchInput.addEventListener('input', filterUsers);
        statusFilter.addEventListener('change', filterUsers);
        
        resetButton.addEventListener('click', function() {
            searchInput.value = '';
            statusFilter.value = 'aktif';
            filterUsers();
        });

        // Initial filter (show only aktif by default)
        filterUsers();
    });
</script>
@endpush
@endsection
