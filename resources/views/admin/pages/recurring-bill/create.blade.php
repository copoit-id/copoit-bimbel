@extends('admin.layout.admin')

@section('title', isset($recurringBill) ? 'Edit Tagihan Rutin' : 'Buat Tagihan Rutin')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ isset($recurringBill) ? 'Edit Tagihan Rutin' : 'Buat Tagihan Rutin' }}</h1>
        <p class="text-sm text-gray-500">{{ isset($recurringBill) ? 'Perbarui pengaturan dan sumber penerima tagihan berkala ini.' : 'Buat tagihan berkala untuk peserta individual, anggota rombel, atau pemegang akses paket.' }}</p>
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
                <input name="name" value="{{ old('name', $recurringBill?->name ?? '') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="SPP Bulanan">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Nominal</label>
                <input type="number" name="amount" value="{{ old('amount', $recurringBill?->amount ?? '') }}" required min="0" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Contoh: 500000">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Frekuensi</label>
                <select name="frequency" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @foreach(['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'] as $key => $label)
                        <option value="{{ $key }}" @selected(old('frequency', $recurringBill?->frequency ?? 'monthly') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Tanggal Jatuh Tempo Bulanan</label>
                <input type="number" name="due_day" min="1" max="31" value="{{ old('due_day', $recurringBill?->due_day ?? 10) }}" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Mulai</label>
                <input type="date" name="start_date" value="{{ old('start_date', $recurringBill?->start_date?->toDateString() ?? now()->toDateString()) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Selesai (Opsional)</label>
                <input type="date" name="end_date" value="{{ old('end_date', $recurringBill?->end_date?->toDateString() ?? '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
        </div>

        <div class="mt-5">
            <label class="mb-2 block text-sm font-semibold text-gray-700">Catatan</label>
            <textarea name="description" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Catatan tambahan tagihan...">{{ old('description', $recurringBill?->description ?? '') }}</textarea>
        </div>

        <!-- Target tagihan dapat digabungkan dari paket, rombel, dan peserta individual. -->
        <div class="mt-6 border-t border-gray-100 pt-5" x-data="{
            users: @js($users),
            packages: @js($packages->map(fn ($package) => ['id' => (int) $package->package_id, 'name' => $package->name, 'price' => (int) $package->price])->values()),
            studyGroups: @js($studyGroups->map(fn ($studyGroup) => ['id' => (int) $studyGroup->id, 'name' => $studyGroup->name, 'userCount' => (int) $studyGroup->users_count])->values()),
            search: '',
            activeTargetTab: 'package',
            selected: @js(old('user_ids', $selectedUserIds ?? [])),
            selectedPackageIds: @js(old('package_ids', $selectedPackageIds ?? [])),
            selectedStudyGroupIds: @js(old('study_group_ids', $selectedStudyGroupIds ?? [])),
            filteredUsers() {
                return this.users.filter(user => 
                    user.name.toLowerCase().includes(this.search.toLowerCase()) || 
                    user.email.toLowerCase().includes(this.search.toLowerCase())
                );
            },
            toggleAll(checked) {
                const filteredIds = this.filteredUsers().map(user => user.id);
                if (checked) {
                    this.selected = [...new Set([...this.selected, ...filteredIds])];
                } else {
                    this.selected = this.selected.filter(id => !filteredIds.includes(id));
                }
            },
            isAllSelected() {
                const filteredIds = this.filteredUsers().map(u => u.id);
                return filteredIds.length > 0 && filteredIds.every(id => this.selected.includes(id));
            }
        }">
            <!-- Hidden inputs for each selected target source. -->
            <template x-for="id in selectedPackageIds" :key="`package-${id}`">
                <input type="hidden" name="package_ids[]" :value="id">
            </template>
            <template x-for="id in selectedStudyGroupIds" :key="`study-group-${id}`">
                <input type="hidden" name="study_group_ids[]" :value="id">
            </template>
            <template x-for="id in selected">
                <input type="hidden" name="user_ids[]" :value="id">
            </template>

            <div class="mb-4">
                <div>
                    <label class="text-sm font-semibold text-gray-800">Target Tagihan</label>
                    <p class="text-xs text-gray-500">Pilih sumber target. Pilihan dari beberapa tab dapat digabung dan peserta ganda hanya menerima satu invoice per periode.</p>
                </div>
            </div>

            <div class="mb-4 flex gap-1 overflow-x-auto border-b border-gray-200" role="tablist" aria-label="Sumber target tagihan">
                <button type="button" @click="activeTargetTab = 'package'" :class="activeTargetTab === 'package' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'" class="inline-flex shrink-0 items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors" role="tab" :aria-selected="activeTargetTab === 'package'">
                    Paket <span x-show="selectedPackageIds.length" x-text="selectedPackageIds.length" class="rounded-full bg-primary/10 px-1.5 py-0.5 text-xs"></span>
                </button>
                <button type="button" @click="activeTargetTab = 'studyGroup'" :class="activeTargetTab === 'studyGroup' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'" class="inline-flex shrink-0 items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors" role="tab" :aria-selected="activeTargetTab === 'studyGroup'">
                    Rombel <span x-show="selectedStudyGroupIds.length" x-text="selectedStudyGroupIds.length" class="rounded-full bg-primary/10 px-1.5 py-0.5 text-xs"></span>
                </button>
                <button type="button" @click="activeTargetTab = 'user'" :class="activeTargetTab === 'user' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'" class="inline-flex shrink-0 items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors" role="tab" :aria-selected="activeTargetTab === 'user'">
                    Peserta Individual <span x-show="selected.length" x-text="selected.length" class="rounded-full bg-primary/10 px-1.5 py-0.5 text-xs"></span>
                </button>
            </div>

            <div x-show="activeTargetTab === 'package'" class="rounded-lg border border-gray-200 p-4" role="tabpanel">
                <div class="mb-3">
                    <h3 class="text-sm font-semibold text-gray-800">Paket</h3>
                    <p class="text-xs text-gray-500">Keanggotaan dinamis: peserta yang memiliki akses paket pada periode tagihan akan otomatis ditagihkan. Peserta baru ikut tanpa dipilih ulang; invoice yang sudah dibuat tetap menjadi riwayat.</p>
                </div>
                <div class="max-h-96 space-y-2 overflow-y-auto pr-1">
                    <template x-for="package in packages" :key="package.id">
                        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2 hover:bg-gray-50">
                            <span class="flex items-center gap-2">
                                <input type="checkbox" :value="package.id" x-model="selectedPackageIds" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                                <span class="text-sm font-medium text-gray-800" x-text="package.name"></span>
                            </span>
                            <span class="text-xs text-gray-500" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(package.price)"></span>
                        </label>
                    </template>
                    <p x-show="packages.length === 0" class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500">Belum ada paket aktif.</p>
                </div>
            </div>

            <div x-show="activeTargetTab === 'studyGroup'" class="rounded-lg border border-gray-200 p-4" role="tabpanel">
                <div class="mb-3">
                    <h3 class="text-sm font-semibold text-gray-800">Rombel</h3>
                    <p class="text-xs text-gray-500">Semua anggota rombel akan ditagihkan saat invoice dibuat.</p>
                </div>
                <div class="max-h-96 space-y-2 overflow-y-auto pr-1">
                    <template x-for="studyGroup in studyGroups" :key="studyGroup.id">
                        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2 hover:bg-gray-50">
                            <span class="flex items-center gap-2">
                                <input type="checkbox" :value="studyGroup.id" x-model="selectedStudyGroupIds" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                                <span class="text-sm font-medium text-gray-800" x-text="studyGroup.name"></span>
                            </span>
                            <span class="text-xs text-gray-500"><span x-text="studyGroup.userCount"></span> peserta</span>
                        </label>
                    </template>
                    <p x-show="studyGroups.length === 0" class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500">Belum ada rombel aktif.</p>
                </div>
            </div>

            <div x-show="activeTargetTab === 'user'" class="space-y-3" role="tabpanel">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Peserta Individual</h3>
                    <p class="text-xs text-gray-500">Cari dan pilih peserta tertentu untuk ditagihkan secara manual.</p>
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

            <!-- Tabel pemilihan peserta individual -->
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
                    Peserta individual: <span x-text="selected.length" class="font-bold text-primary"></span> dari <span x-text="users.length"></span> peserta.
                </div>
                <div x-show="selected.length > 0">
                    <button type="button" @click="selected = []" class="text-red-500 hover:text-red-700 hover:underline font-semibold">Bersihkan Pilihan</button>
                </div>
            </div>
            </div>
        </div>

        <label class="mt-6 flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $recurringBill?->is_active ?? true)) class="rounded border-gray-300 text-primary focus:ring-primary">
            Aktifkan tagihan rutin ini
        </label>

        <div class="mt-6 flex justify-end gap-2 border-t border-gray-200 pt-5">
            <a href="{{ route('admin.recurring-bills.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</a>
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">{{ isset($recurringBill) ? 'Simpan Perubahan' : 'Simpan Tagihan' }}</button>
        </div>
    </form>
</div>
@endsection
