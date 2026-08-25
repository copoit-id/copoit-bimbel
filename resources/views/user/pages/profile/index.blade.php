@extends('user.layout.new-user')

@section('title', 'Profil')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$user = auth()->user();
@endphp

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.dashboard.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Profil</h1>
        <p class="text-gray-500 text-sm">Kelola informasi profilmu</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Profile Card -->
    <div class="bg-white rounded-2xl p-6 border border-gray-100">
        <div class="text-center mb-6">
            <div class="w-24 h-24 rounded-full mx-auto mb-4 flex items-center justify-center text-white text-3xl font-bold" style="background-color: {{ $primaryColor }}">
                {{ substr($user->name, 0, 1) }}
            </div>
            <h3 class="font-bold text-gray-800 text-lg">{{ $user->name }}</h3>
            <p class="text-gray-400 text-sm">{{ $user->email }}</p>
        </div>
        
        <div class="space-y-3">
            <a href="{{ route('user.package.my') }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <i class="ri-road-map-line text-lg" style="color: {{ $primaryColor }}"></i>
                    <span class="text-gray-700">Paket Saya</span>
                </div>
                <i class="ri-arrow-right-s-line text-gray-400"></i>
            </a>
            <a href="{{ route('user.package.riwayatPembelian') }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <i class="ri-history-line text-lg" style="color: {{ $primaryColor }}"></i>
                    <span class="text-gray-700">Riwayat</span>
                </div>
                <i class="ri-arrow-right-s-line text-gray-400"></i>
            </a>
            <a href="{{ route('user.package.riwayatPembelian') }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <i class="ri-shopping-bag-line text-lg" style="color: {{ $primaryColor }}"></i>
                    <span class="text-gray-700">Pembelian</span>
                </div>
                <i class="ri-arrow-right-s-line text-gray-400"></i>
            </a>
        </div>
    </div>
    
    <!-- Edit Profile -->
    <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-6">Edit Profil</h3>
        
        <form action="{{ route('user.profile.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:border-transparent"
                           style="--tw-ring-color: {{ $primaryColor }}40"
                           required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" value="{{ $user->email }}" 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-500"
                           disabled>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                    <input type="tel" name="phone" value="{{ old('phone', $user->phone ?? '') }}" 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:border-transparent"
                           style="--tw-ring-color: {{ $primaryColor }}40">
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="education_level" class="block text-sm font-medium text-gray-700 mb-1">Kelas / Level <span class="text-gray-400">(Opsional)</span></label>
                        <input id="education_level" name="education_level" type="text" value="{{ old('education_level', $user->education_level ?? '') }}" placeholder="Contoh: Kelas 12"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:border-transparent"
                            style="--tw-ring-color: {{ $primaryColor }}40">
                    </div>
                    <div>
                        <label for="origin_institution" class="block text-sm font-medium text-gray-700 mb-1">Asal Sekolah / Instansi <span class="text-gray-400">(Opsional)</span></label>
                        <input id="origin_institution" name="origin_institution" type="text" value="{{ old('origin_institution', $user->origin_institution ?? '') }}" placeholder="Contoh: SMA Negeri 1 Jakarta"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:border-transparent"
                            style="--tw-ring-color: {{ $primaryColor }}40">
                    </div>
                </div>

                <div class="space-y-4">
                    <x-form.participant-destination-selector
                        :destination-categories="$destinationCategories"
                        :selected-destination-id="old('participant_destination_category_id', $user->participant_destination_category_id)"
                        :selected-source="old('participant_destination_source', $user->participant_destination_source ?? '')"
                        :selected-external-id="old('participant_destination_external_id', $user->participant_destination_external_id ?? '')"
                        :selected-institution-name="old('participant_destination_institution_name', $user->participant_destination_institution_name ?? '')"
                        :selected-program-name="old('participant_destination_program_name', $user->participant_destination_program_name ?? '')"
                        :required="app(\App\Services\ParticipantDestinationSelectionService::class)->isRequired()" />
                    <x-form.participant-destination-selector
                        choice="2"
                        :destination-categories="$destinationCategories"
                        :selected-destination-id="old('second_participant_destination_category_id', $user->second_participant_destination_category_id)"
                        :selected-source="old('second_participant_destination_source', $user->second_participant_destination_source ?? '')"
                        :selected-external-id="old('second_participant_destination_external_id', $user->second_participant_destination_external_id ?? '')"
                        :selected-institution-name="old('second_participant_destination_institution_name', $user->second_participant_destination_institution_name ?? '')"
                        :selected-program-name="old('second_participant_destination_program_name', $user->second_participant_destination_program_name ?? '')" />
                </div>
            </div>
            
            <div class="mt-6">
                <button type="submit" class="px-6 py-2.5 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
                    <i class="ri-save-line mr-2"></i>Simpan Perubahan
                </button>
            </div>
        </form>
        
        <hr class="my-8 border-gray-100">
        
        <h3 class="font-bold text-gray-800 mb-6">Ubah Password</h3>
        
        <form action="{{ route('user.profile.password.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Saat Ini</label>
                    <input type="password" name="current_password" 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:border-transparent"
                           style="--tw-ring-color: {{ $primaryColor }}40"
                           required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                    <input type="password" name="password" 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:border-transparent"
                           style="--tw-ring-color: {{ $primaryColor }}40"
                           required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:border-transparent"
                           style="--tw-ring-color: {{ $primaryColor }}40"
                           required>
                </div>
            </div>
            
            <div class="mt-6">
                <button type="submit" class="px-6 py-2.5 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
                    <i class="ri-lock-password-line mr-2"></i>Ubah Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

{{-- Selector destination dirender dan dikelola oleh komponen form di atas. --}}
{{--
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const institution = document.getElementById('destination_institution');
        const program = document.getElementById('destination_program');
        const hidden = document.getElementById('participant_destination_category_id');
        const sourceInput = document.getElementById('participant_destination_source');
        const externalIdInput = document.getElementById('participant_destination_external_id');
        const institutionNameInput = document.getElementById('participant_destination_institution_name');
        const programNameInput = document.getElementById('participant_destination_program_name');
        const officialStatus = document.getElementById('official_destination_status');
        const selectedProgramId = @json((string) ($selectedProgramId ?? ''));
        const selectedOfficialProgramName = @json((string) ($selectedOfficialProgramName ?? ''));
        const officialAutoLoadEnabled = @json((bool) config('client.branding.participant_destination_api_enabled', false));
        const officialInstitutionsUrl = @json(route('participant-destinations.official.institutions'));
        const officialProgramsUrl = @json(route('participant-destinations.official.programs'));
        const programsByInstitution = @json($destinationCategories->mapWithKeys(fn($category) => [
            (string) $category->id => $category->activeChildren
                ->map(fn($child) => ['id' => (string) $child->id, 'name' => $child->name])
                ->values()
                ->all(),
        ]));
        let officialInstitutions = {};
        let officialPrograms = {};

        const isOfficialValue = (value) => String(value || '').startsWith('api:snpmb:');

        const clearOfficialSnapshot = () => {
            if (sourceInput) sourceInput.value = '';
            if (externalIdInput) externalIdInput.value = '';
            if (institutionNameInput) institutionNameInput.value = '';
            if (programNameInput) programNameInput.value = '';
        };

        const renderProgramOptions = (institutionId) => {
            if (isOfficialValue(institutionId)) {
                renderOfficialProgramOptions(institutionId);
                return;
            }

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
            const institutionOption = institution.selectedOptions?.[0];

            if (isOfficialValue(institutionId)) {
                const selectedProgramOption = program.selectedOptions?.[0];
                hidden.value = '';
                if (sourceInput) sourceInput.value = 'snpmb';
                if (externalIdInput) externalIdInput.value = institutionOption?.dataset.externalId || institutionId.replace('api:snpmb:', '');
                if (institutionNameInput) institutionNameInput.value = institutionOption?.dataset.name || institutionOption?.textContent?.replace('[Resmi]', '').trim() || '';
                if (programNameInput) programNameInput.value = isOfficialValue(program.value)
                    ? (selectedProgramOption?.dataset.name || selectedProgramOption?.textContent?.replace('[Resmi]', '').trim() || '')
                    : '';
                return;
            }

            hidden.value = (!program.disabled && program.value) ? program.value : (institutionId || '');
            if (hidden.value) {
                if (sourceInput) sourceInput.value = 'db';
            } else {
                clearOfficialSnapshot();
            }
            if (externalIdInput) externalIdInput.value = '';
            if (institutionNameInput) institutionNameInput.value = '';
            if (programNameInput) programNameInput.value = '';
        };

        const addOfficialInstitutionOptions = (items) => {
            items.forEach((item) => {
                const value = `api:snpmb:${item.id_ptn}`;
                if ([...institution.options].some((option) => option.value === value)) return;

                const option = document.createElement('option');
                option.value = value;
                option.textContent = `[Resmi] ${item.nama}`;
                option.dataset.externalId = item.id_ptn || '';
                option.dataset.name = item.nama || '';
                option.dataset.sourceIds = JSON.stringify(item.source_ids || {});
                institution.appendChild(option);
                officialInstitutions[value] = item;
            });
        };

        const renderOfficialProgramOptions = async (institutionValue) => {
            const selected = officialInstitutions[institutionValue]
                || {
                    id_ptn: institutionValue.replace('api:snpmb:', ''),
                    nama: institution.selectedOptions?.[0]?.dataset.name || '',
                    source_ids: {},
                };
            program.innerHTML = '<option value="">Memuat prodi resmi...</option>';
            program.disabled = true;
            syncDestination();

            if (!officialPrograms[institutionValue]) {
                const params = new URLSearchParams({
                    source: 'all',
                    ptn: selected.id_ptn || '',
                });

                if (selected.source_ids?.snbt) params.set('ptn_snbt', selected.source_ids.snbt);
                if (selected.source_ids?.snbp) params.set('ptn_snbp', selected.source_ids.snbp);

                const response = await fetch(`${officialProgramsUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) throw new Error('Gagal memuat prodi resmi.');
                const payload = await response.json();
                officialPrograms[institutionValue] = Array.isArray(payload.data) ? payload.data : [];
            }

            program.innerHTML = '<option value="">Pilih prodi resmi jika ada</option>';
            officialPrograms[institutionValue].forEach((item) => {
                const option = document.createElement('option');
                option.value = `api:snpmb:${item.id_prodi || item.kode_prodi || item.nama}:program`;
                option.textContent = `[Resmi] ${item.nama}`;
                option.dataset.externalId = item.id_prodi || item.kode_prodi || item.nama || selected.id_ptn || '';
                option.dataset.name = item.nama || '';
                if (selectedOfficialProgramName && item.nama === selectedOfficialProgramName) {
                    option.selected = true;
                }
                program.appendChild(option);
            });
            program.disabled = false;
            syncDestination();
        };

        institution?.addEventListener('change', () => {
            Promise.resolve(renderProgramOptions(institution.value))
                .catch((error) => {
                    program.innerHTML = `<option value="">${error.message || 'Gagal memuat prodi resmi.'}</option>`;
                    program.disabled = true;
                })
                .finally(syncDestination);
        });
        program?.addEventListener('change', syncDestination);
        const loadOfficialDestinations = async () => {
            if (officialStatus) officialStatus.textContent = 'Memuat data resmi...';

            try {
                const response = await fetch(`${officialInstitutionsUrl}?source=all`, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) throw new Error('Gagal memuat data resmi.');
                const payload = await response.json();
                const items = Array.isArray(payload.data) ? payload.data : [];
                addOfficialInstitutionOptions(items);
                if (officialStatus) officialStatus.textContent = '';
            } catch (error) {
                if (officialStatus) officialStatus.textContent = error.message || 'Gagal memuat data resmi.';
            }
        };

        if (officialAutoLoadEnabled) {
            loadOfficialDestinations();
        }
        renderProgramOptions(institution?.value || '');
        syncDestination();
    });
</script>
@endpush
--}}
