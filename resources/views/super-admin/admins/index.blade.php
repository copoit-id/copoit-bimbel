@extends('super-admin.layouts.app')
@section('title', 'Super Admin - Admin Demo')
@section('content')

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">Admin Demo</h2>
            <p class="text-gray-500">Buat akun admin dengan batas akses waktu.</p>
        </div>
    </div>

    <div class="bg-white border border-border rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Tambah Admin</h3>
        <form method="POST" action="{{ route('super-admin.admins.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Username (opsional)</label>
                <input type="text" name="username" value="{{ old('username') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                <input type="password" name="password" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
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
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Buat Admin</button>
            </div>
        </form>
    </div>

    <div class="bg-white border border-border rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Daftar Admin</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-600">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-center">Expired</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($admins as $admin)
                        @php
                            $expired = $admin->admin_expires_at && now()->gte($admin->admin_expires_at);
                        @endphp
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $admin->name }}</td>
                            <td class="px-4 py-3">{{ $admin->email }}</td>
                            <td class="px-4 py-3 text-center">
                                {{ $admin->admin_expires_at ? $admin->admin_expires_at->format('d M Y H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $expired ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $expired ? 'Expired' : 'Aktif' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada admin demo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('expiry_type');
        const dateField = document.getElementById('expiry_date_field');
        const durationField = document.getElementById('expiry_duration_field');

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
    });
</script>
@endpush
