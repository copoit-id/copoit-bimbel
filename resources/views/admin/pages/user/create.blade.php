@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">
                {{ $user ? 'Edit User' : 'Tambah User Baru' }}
            </h2>
            <p class="text-gray-500">
                {{ $user ? 'Perbarui data user' : 'Tambahkan user baru' }}
            </p>
        </div>
        <a href="{{ route('admin.user.index', request()->query()) }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
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

    @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <div class="flex gap-2 items-start">
            <i class="ri-close-circle-line text-lg mt-0.5"></i>
            <div>
                <p class="font-semibold mb-1">Terjadi kesalahan pada data:</p>
                <ul class="list-disc list-inside text-sm space-y-0.5">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <!-- Create / Edit Form -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <form action="{{ $user ? route('admin.user.update', array_merge(['user' => $user->id], request()->query())) : route('admin.user.store') }}" method="POST">
            @csrf
            @if ($user)
            @method('PUT')
            @endif

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-form.input name="name" label="Nama" :value="old('name', $user->name ?? '')" required />
                        <x-form.input name="username" label="Username" :value="old('username', $user->username ?? '')"
                            required />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-form.input type="email" name="email" label="Email" :value="old('email', $user->email ?? '')"
                            required />

                        <x-form.input type="password" name="password" label="Password {{ $user
                            ? '(biarkan kosong jika tidak diubah)' : '' }}" :required="!$user" autocomplete="new-password" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-form.select name="role" label="Role" :options="$roleOptions ?? ['admin' => 'Admin', 'user' => 'User']"
                            :value="old('role', $user->role ?? '')" required />
                        <x-form.select name="status" label="Status"
                            :options="['aktif' => 'Aktif', 'nonaktif' => 'Tidak Aktif']"
                            :value="old('status', $user->status ?? 'aktif')" required />
                    </div>

                    <div>
                        @php
                            $selectedDestinationId = (int) old('participant_destination_category_id', $user->participant_destination_category_id ?? null);
                            $selectedDestination = $destinationCategories
                                ->flatMap(fn($category) => collect([$category])->merge($category->activeChildren))
                                ->firstWhere('id', $selectedDestinationId);
                            $selectedInstitutionId = $selectedDestination?->parent_id ?: ($selectedDestination?->id ?? null);
                            $selectedProgramId = $selectedDestination?->parent_id ? $selectedDestination?->id : null;
                            $selectedInstitution = $selectedInstitutionId
                                ? $destinationCategories->firstWhere('id', $selectedInstitutionId)
                                : null;
                            $selectedInstitutionHasPrograms = $selectedInstitutionId
                                ? $selectedInstitution?->activeChildren->isNotEmpty()
                                : false;
                        @endphp
                        <label class="block text-sm font-medium text-gray-700 mb-1">Instansi/Prodi Tujuan</label>
                        <input type="hidden" name="participant_destination_category_id" id="participant_destination_category_id"
                            value="{{ $selectedDestinationId ?: '' }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <select id="destination_institution"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Pilih instansi</option>
                                @foreach($destinationCategories as $category)
                                    <option value="{{ $category->id }}" @selected((int) $selectedInstitutionId === (int) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <select id="destination_program"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed"
                                {{ $selectedInstitutionHasPrograms ? '' : 'disabled' }}>
                                <option value="">{{ $selectedInstitutionId ? 'Tidak ada prodi/sub' : 'Pilih instansi dulu' }}</option>
                                @foreach(($selectedInstitution?->activeChildren ?? collect()) as $child)
                                    <option value="{{ $child->id }}" @selected((int) $selectedProgramId === (int) $child->id)>
                                        {{ $child->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if($destinationCategories->isEmpty())
                        <p class="text-xs text-amber-600 mt-1">Instansi tujuan belum tersedia. Tambahkan di menu Kategori > Tujuan / Instansi.</p>
                        @else
                        <p class="text-xs text-gray-500 mt-1">Pilih instansi dulu, lalu pilih prodi/sub jika tersedia.</p>
                        @endif
                        @error('participant_destination_category_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end px-6 py-5 space-x-2">
                <a href="{{ route('admin.user.index', request()->query()) }}"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                    Batal
                </a>
                <button type="submit"
                    class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const institution = document.getElementById('destination_institution');
        const program = document.getElementById('destination_program');
        const hidden = document.getElementById('participant_destination_category_id');
        const selectedProgramId = @json((string) ($selectedProgramId ?? ''));
        const programsByInstitution = @json($destinationCategories->mapWithKeys(fn($category) => [
            (string) $category->id => $category->activeChildren
                ->map(fn($child) => ['id' => (string) $child->id, 'name' => $child->name])
                ->values()
                ->all(),
        ]));

        const renderProgramOptions = (institutionId) => {
            const programs = programsByInstitution[institutionId] || [];
            const placeholderText = !institutionId
                ? 'Pilih instansi dulu'
                : (programs.length > 0 ? 'Pilih prodi/sub' : 'Tidak ada prodi/sub');

            program.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = placeholderText;
            program.appendChild(placeholder);

            programs.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                if (item.id === selectedProgramId) {
                    option.selected = true;
                }
                program.appendChild(option);
            });

            program.disabled = !institutionId || programs.length === 0;
        };

        const syncDestination = () => {
            if (!institution || !program || !hidden) return;
            const institutionId = institution.value;

            hidden.value = (!program.disabled && program.value) ? program.value : (institutionId || '');
        };

        institution?.addEventListener('change', () => {
            renderProgramOptions(institution.value);
            syncDestination();
        });
        program?.addEventListener('change', syncDestination);
        renderProgramOptions(institution?.value || '');
        syncDestination();
    });
</script>
@endsection
