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
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
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
                        <th class="px-4 py-3 text-center">Aksi</th>
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
                            <td class="px-4 py-3 text-center">
                                <button type="button" data-open-edit-modal="edit-admin-{{ $admin->id }}"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-full border border-primary text-primary hover:bg-primary hover:text-white transition">
                                    <i class="ri-edit-line text-base"></i>
                                    Edit
                                </button>
                                <button type="button" data-open-edit-modal="extend-admin-{{ $admin->id }}"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-full border border-emerald-600 text-emerald-700 hover:bg-emerald-600 hover:text-white transition">
                                    <i class="ri-time-line text-base"></i>
                                    Perpanjang
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">Belum ada admin demo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @foreach($admins as $admin)
            <div id="edit-admin-{{ $admin->id }}" class="fixed inset-0 z-50 hidden items-center justify-center px-4">
                <div class="absolute inset-0 bg-black/50" data-close-edit-modal></div>
                <div class="relative bg-white rounded-2xl w-full max-w-lg p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-500">Edit data Admin Demo</p>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $admin->name }}</h3>
                        </div>
                        <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl leading-none" data-close-edit-modal>&times;</button>
                    </div>
                    <form method="POST" action="{{ route('super-admin.admins.update', $admin) }}" class="space-y-4">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nama</label>
                                        <input type="text" name="name" value="{{ old('name', $admin->name) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                        <input type="email" name="email" value="{{ old('email', $admin->email) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                                        <input type="text" name="username" value="{{ old('username', $admin->username) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2">
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Password Baru (opsional)</label>
                                            <input type="password" name="password" class="w-full border border-gray-200 rounded-lg px-4 py-2">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Konfirmasi Password</label>
                                            <input type="password" name="password_confirmation" class="w-full border border-gray-200 rounded-lg px-4 py-2">
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
                <div class="relative bg-white rounded-2xl w-full max-w-lg p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-500">Perpanjang akses Admin Demo</p>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $admin->name }}</h3>
                        </div>
                        <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl leading-none" data-close-edit-modal>&times;</button>
                    </div>
                    <p class="mb-4 text-sm text-gray-500">Akses saat ini: {{ $admin->admin_expires_at?->copy()->setTimezone('Asia/Jakarta')->format('d M Y H:i') ?? '-' }}.</p>
                    <form method="POST" action="{{ route('super-admin.admins.extend', $admin) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')
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

        document.querySelectorAll('[data-open-edit-modal]').forEach(button => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-open-edit-modal');
                const modal = document.getElementById(targetId);
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
        });

        document.querySelectorAll('[data-close-edit-modal]').forEach(button => {
            button.addEventListener('click', () => {
                const modal = button.closest('[id^=\"edit-admin-\"], [id^=\"extend-admin-\"]');
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
    });
</script>
@endpush
