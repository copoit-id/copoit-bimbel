@extends('admin.layout.admin')

@section('title', isset($recurringBill) ? 'Edit Tagihan Rutin' : 'Buat Tagihan Rutin')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ isset($recurringBill) ? 'Edit Tagihan Rutin' : 'Buat Tagihan Rutin' }}</h1>
        <p class="text-sm text-gray-500">{{ isset($recurringBill) ? 'Perbarui pengaturan dan peserta sasaran tagihan berkala ini.' : 'Buat tagihan berkala yang ditujukan langsung ke peserta pilihan Anda.' }}</p>
    </div>

    <form method="POST" action="{{ isset($recurringBill) ? route('admin.recurring-bills.update', $recurringBill) : route('admin.recurring-bills.store') }}" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @isset($recurringBill)
            @method('PUT')
        @endisset

        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Nama Tagihan</label>
                <input name="name" value="{{ old('name', $recurringBill->name ?? '') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="SPP Bulanan">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Nominal</label>
                <input type="number" name="amount" value="{{ old('amount', $recurringBill->amount ?? '') }}" required min="0" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Contoh: 500000">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Frekuensi</label>
                <select name="frequency" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @foreach(['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'] as $key => $label)
                        <option value="{{ $key }}" @selected(old('frequency', $recurringBill->frequency ?? 'monthly') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Tanggal Jatuh Tempo Bulanan</label>
                <input type="number" name="due_day" min="1" max="31" value="{{ old('due_day', $recurringBill->due_day ?? 10) }}" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Mulai</label>
                <input type="date" name="start_date" value="{{ old('start_date', isset($recurringBill) ? $recurringBill->start_date?->toDateString() : now()->toDateString()) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Selesai (Opsional)</label>
                <input type="date" name="end_date" value="{{ old('end_date', $recurringBill->end_date?->toDateString() ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
        </div>

        <div class="mt-5">
            <label class="mb-2 block text-sm font-semibold text-gray-700">Catatan</label>
            <textarea name="description" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Catatan tambahan tagihan...">{{ old('description', $recurringBill->description ?? '') }}</textarea>
        </div>

        <!-- Target Peserta (Checklist Table with Search) -->
        <div class="mt-6 border-t border-gray-100 pt-5" x-data="{
            users: @js($users),
            search: '',
            selected: @js(old('user_ids', $selectedUserIds ?? [])),
            filteredUsers() {
                return this.users.filter(user => 
                    user.name.toLowerCase().includes(this.search.toLowerCase()) || 
                    user.email.toLowerCase().includes(this.search.toLowerCase())
                );
            },
            toggleAll(checked) {
                if (checked) {
                    this.selected = this.filteredUsers().map(u => u.id);
                } else {
                    this.selected = [];
                }
            },
            isAllSelected() {
                const filteredIds = this.filteredUsers().map(u => u.id);
                return filteredIds.length > 0 && filteredIds.every(id => this.selected.includes(id));
            }
        }">
            <!-- Hidden inputs to submit selected user_ids -->
            <template x-for="id in selected">
                <input type="hidden" name="user_ids[]" :value="id">
            </template>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3.5">
                <div>
                    <label class="text-sm font-semibold text-gray-800">Target Peserta</label>
                    <p class="text-xs text-gray-500">Cari dan pilih peserta yang akan dikenakan tagihan rutin ini.</p>
                </div>
                <div class="relative w-full sm:w-72">
                    <input 
                        type="text" 
                        x-model="search" 
                        placeholder="Cari nama atau email..." 
                        class="w-full rounded-lg border border-gray-300 pl-9 pr-4 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                    <i class="ri-search-line absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                </div>
            </div>

            <!-- Table Container -->
            <div class="overflow-hidden border border-gray-200 rounded-lg shadow-sm max-h-96 overflow-y-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 sticky top-0 z-10 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center">
                                <input 
                                    type="checkbox" 
                                    @change="toggleAll($el.checked)" 
                                    :checked="isAllSelected()"
                                    class="rounded border-gray-300 text-primary focus:ring-primary h-4 w-4"
                                >
                            </th>
                            <th class="px-4 py-3 font-semibold">Nama</th>
                            <th class="px-4 py-3 font-semibold">Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="user in filteredUsers()" :key="user.id">
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-4 py-3 text-center">
                                    <input 
                                        type="checkbox" 
                                        :value="user.id" 
                                        x-model="selected"
                                        class="rounded border-gray-300 text-primary focus:ring-primary h-4 w-4"
                                    >
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900" x-text="user.name"></td>
                                <td class="px-4 py-3 text-gray-500" x-text="user.email"></td>
                            </tr>
                        </template>
                        <tr x-show="filteredUsers().length === 0">
                            <td colspan="3" class="px-4 py-8 text-center text-gray-500 bg-slate-50/50">Tidak ada peserta yang cocok.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-2.5 flex items-center justify-between text-xs text-gray-500">
                <div>
                    Terpilih: <span x-text="selected.length" class="font-bold text-primary"></span> dari <span x-text="users.length"></span> peserta.
                </div>
                <div x-show="selected.length > 0">
                    <button type="button" @click="selected = []" class="text-red-500 hover:text-red-700 hover:underline font-semibold">Bersihkan Pilihan</button>
                </div>
            </div>
        </div>

        <label class="mt-6 flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $recurringBill->is_active ?? true)) class="rounded border-gray-300 text-primary focus:ring-primary">
            Aktifkan tagihan rutin ini
        </label>

        <div class="mt-6 flex justify-end gap-2 border-t border-gray-200 pt-5">
            <a href="{{ route('admin.recurring-bills.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</a>
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">{{ isset($recurringBill) ? 'Simpan Perubahan' : 'Simpan Tagihan' }}</button>
        </div>
    </form>
</div>
@endsection
