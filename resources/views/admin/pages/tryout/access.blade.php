@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold">Kelola Akses Tryout</h2>
            <p class="text-gray-500">{{ $tryout->name }}</p>
        </div>
        <a href="{{ route('admin.tryout.index') }}"
            class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-sm text-gray-600">
                Tryout ini <span class="font-semibold">{{ $tryout->is_premium ? 'Premium' : 'Non Premium' }}</span>.
                {{ $tryout->is_premium ? 'Hanya user yang di-assign bisa mengerjakan.' : 'Semua user bisa mengerjakan (sesuai progres latihan).' }}
            </div>
            <div class="text-sm text-gray-500">Total akses: <span class="font-semibold">{{ count($accessUserIds) }}</span></div>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <input id="user-search" type="text" placeholder="Cari user..."
                class="w-full md:w-72 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-3">User</th>
                        <th class="text-left px-4 py-3">Email</th>
                        <th class="text-center px-4 py-3">Akses</th>
                    </tr>
                </thead>
                <tbody id="user-access-rows">
                    @foreach($users as $user)
                    @php
                        $hasAccess = in_array($user->id, $accessUserIds, true);
                    @endphp
                    <tr class="border-t" data-name="{{ strtolower($user->name) }}" data-email="{{ strtolower($user->email) }}">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-center">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only access-toggle" data-user-id="{{ $user->id }}" {{ $hasAccess ? 'checked' : '' }}>
                                <span class="w-10 h-6 bg-gray-200 rounded-full relative transition">
                                    <span class="dot absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition"></span>
                                </span>
                            </label>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const csrfToken = '{{ csrf_token() }}';

    document.querySelectorAll('.access-toggle').forEach((toggle) => {
        toggle.addEventListener('change', async (event) => {
            const userId = event.target.dataset.userId;
            try {
                const response = await fetch('{{ route('admin.tryout.access.toggle', $tryout->tryout_id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ user_id: userId }),
                });

                if (!response.ok) {
                    throw new Error('Gagal menyimpan perubahan.');
                }
            } catch (error) {
                alert(error.message);
                event.target.checked = !event.target.checked;
            }
        });
    });

    const searchInput = document.getElementById('user-search');
    const rows = document.querySelectorAll('#user-access-rows tr');

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase();
            rows.forEach((row) => {
                const name = row.dataset.name || '';
                const email = row.dataset.email || '';
                row.classList.toggle('hidden', !(name.includes(query) || email.includes(query)));
            });
        });
    }
</script>
@endsection

@section('styles')
<style>
    .access-toggle:checked + span {
        background-color: var(--client-color-primary, #1C3259);
    }

    .access-toggle:checked + span .dot {
        transform: translateX(16px);
    }
</style>
@endsection
