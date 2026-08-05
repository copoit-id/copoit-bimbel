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
        <form method="GET" action="{{ route('admin.user.login-as-page') }}" class="mb-6 flex flex-col gap-4 sm:flex-row">
            <div class="flex-1 relative">
                <input type="search" name="search" value="{{ $search }}" placeholder="Cari nama, email, atau username..."
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <i class="ri-search-line absolute left-3 top-3 text-gray-400"></i>
            </div>

            <select name="status"
                class="border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <option value="">Semua Status</option>
                <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Tidak Aktif</option>
            </select>

            <button type="submit" class="px-4 py-2.5 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                <i class="ri-search-line"></i> Cari
            </button>
            <a href="{{ route('admin.user.login-as-page') }}"
                class="px-4 py-2.5 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="ri-refresh-line"></i> Reset
            </a>
        </form>

        <div class="text-sm text-gray-500 mb-4">
            Menampilkan <span class="font-medium text-gray-700">{{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}</span> dari
            <span class="font-medium text-gray-700">{{ $users->total() }}</span> user
        </div>

        <!-- User Table -->
        <div>
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

        <!-- Pagination -->
        <div class="mt-4">
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
</div>

@endsection
