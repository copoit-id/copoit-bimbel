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
    <div class="bg-white rounded-lg border border-gray-200">
        <form action="{{ $user ? route('admin.user.update', array_merge(request()->query(), ['user' => $user->id])) : route('admin.user.store') }}" method="POST">
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

                        <x-form.input type="tel" name="phone" label="Nomor WhatsApp"
                            :value="old('phone', $user->phone ?? '')"
                            placeholder="Contoh: 6281234567890"
                            helper="Wajib untuk siswa. Gunakan format 62 tanpa angka 0 atau tanda + di depan."
                            inputmode="numeric"
                            pattern="62[0-9]{8,14}"
                            autocomplete="tel" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-form.input type="date" name="birthday" label="Tanggal Lahir"
                            :value="old('birthday', $user->birthday ?? '')"
                            max="{{ now()->toDateString() }}" />

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

                    @if($parentPortalEnabled ?? false)
                    @php
                        $isParentRole = old('role', $user?->role ?? '') === 'parent';
                        $isStudentRole = old('role', $user?->role ?? '') === 'user';
                    @endphp
                    <section id="parent-child-section" @class(['rounded-xl border border-primary/20 bg-primary/5 p-4', 'hidden' => !$isParentRole])>
                        <div>
                            <h3 class="font-semibold text-gray-900">Pilih anak yang diasuh</h3>
                            <p class="mt-1 text-sm text-gray-500">Wajib pilih minimal satu siswa. Gunakan pencarian agar lebih cepat menemukan anak.</p>
                        </div>
                        <div class="mt-4">
                            <label for="child-search" class="sr-only">Cari anak</label>
                            <div class="relative">
                                <i class="ri-search-line pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input id="child-search" type="search" placeholder="Cari nama atau email anak..."
                                    class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-primary focus:ring-primary">
                            </div>
                        </div>
                        <div id="child-search-results" class="mt-2 space-y-2"></div>
                        <div id="selected-children" class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($childOptions ?? [] as $childOption)
                                <div data-selected-child data-user-id="{{ $childOption->id }}" class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm text-gray-700">
                                    <input type="hidden" name="child_ids[]" value="{{ $childOption->id }}">
                                    <span class="min-w-0 flex-1"><span class="block truncate font-semibold">{{ $childOption->name }}</span><span class="block truncate text-xs text-gray-400">{{ $childOption->email }}</span></span>
                                    <button type="button" data-remove-child class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600" aria-label="Hapus anak"><i class="ri-close-line text-lg"></i></button>
                                </div>
                            @endforeach
                        </div>
                        <p id="selected-children-empty" @class(['mt-4 text-sm text-gray-500', 'hidden' => ($childOptions ?? collect())->isNotEmpty()])>Belum ada anak yang dipilih.</p>
                        @error('child_ids')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </section>

                    <section id="student-parent-section" @class(['rounded-xl border border-primary/20 bg-primary/5 p-4', 'hidden' => !$isStudentRole])>
                        <div>
                            <h3 class="font-semibold text-gray-900">Orang tua / wali siswa <span class="font-normal text-gray-400">(opsional)</span></h3>
                            <p class="mt-1 text-sm text-gray-500">Hubungkan wali bila diperlukan. Satu siswa dapat memiliki lebih dari satu wali.</p>
                        </div>
                        <div class="mt-4 space-y-3">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-primary/20 bg-white px-3 py-3 text-sm font-medium text-gray-700">
                                <input id="link-parent-account" type="checkbox" value="1" @checked(old('link_parent_account', ($parentOptions ?? collect())->isNotEmpty() || old('add_parent_account'))) class="rounded border-gray-300 text-primary focus:ring-primary">
                                Hubungkan atau tambahkan akun orang tua / wali
                            </label>

                            <div id="parent-link-fields" @class(['space-y-3', 'hidden' => !old('link_parent_account', ($parentOptions ?? collect())->isNotEmpty() || old('add_parent_account'))])>
                            <div>
                                <label for="parent-account-search" class="block text-sm font-medium text-gray-700">Pilih akun orang tua yang sudah ada</label>
                                <input id="parent-account-search" type="search" placeholder="Cari nama atau email orang tua..."
                                    class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-primary focus:ring-primary">
                                <div id="parent-search-results" class="mt-2 space-y-2"></div>
                                <div id="selected-parent" class="mt-2">
                                    @foreach($parentOptions ?? [] as $parentOption)
                                        <div data-selected-parent data-user-id="{{ $parentOption->id }}" class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm text-gray-700">
                                            <input type="hidden" name="parent_user_id" value="{{ $parentOption->id }}">
                                            <span class="min-w-0 flex-1"><span class="block truncate font-semibold">{{ $parentOption->name }}</span><span class="block truncate text-xs text-gray-400">{{ $parentOption->email }}</span></span>
                                            <button type="button" data-remove-parent class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600" aria-label="Hapus orang tua"><i class="ri-close-line text-lg"></i></button>
                                        </div>
                                    @endforeach
                                </div>
                                @error('parent_user_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-dashed border-primary/30 bg-white px-3 py-3 text-sm font-medium text-gray-700">
                                <input id="add-parent-account" type="checkbox" name="add_parent_account" value="1" @checked(old('add_parent_account')) class="rounded border-gray-300 text-primary focus:ring-primary">
                                Buatkan akun orang tua baru untuk siswa ini
                            </label>

                            <div id="new-parent-fields" @class(['grid grid-cols-1 gap-4 rounded-lg border border-gray-200 bg-white p-4 md:grid-cols-3', 'hidden' => !old('add_parent_account')])>
                                <x-form.input name="parent_name" label="Nama orang tua" :value="old('parent_name')" />
                                <x-form.input type="email" name="parent_email" label="Email orang tua" :value="old('parent_email')" />
                                <x-form.input type="password" name="parent_password" label="Password akun orang tua" autocomplete="new-password" />
                            </div>
                            </div>
                        </div>
                    </section>
                    @endif

                    <div>
                        @php
                            $selectedDestinationId = (int) old('participant_destination_category_id', $user->participant_destination_category_id ?? null);
                            $selectedDestinationSource = old('participant_destination_source', $user->participant_destination_source ?? ($selectedDestinationId ? 'db' : ''));
                            $selectedOfficialExternalId = old('participant_destination_external_id', $user->participant_destination_external_id ?? '');
                            $selectedOfficialInstitutionName = old('participant_destination_institution_name', $user->participant_destination_institution_name ?? '');
                            $selectedOfficialProgramName = old('participant_destination_program_name', $user->participant_destination_program_name ?? '');
                            $officialApiEnabled = (bool) config('client.branding.participant_destination_api_enabled', false);
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
                        <input type="hidden" id="participant_destination_source" name="participant_destination_source" value="{{ $selectedDestinationSource }}">
                        <input type="hidden" id="participant_destination_external_id" name="participant_destination_external_id" value="{{ $selectedOfficialExternalId }}">
                        <input type="hidden" id="participant_destination_institution_name" name="participant_destination_institution_name" value="{{ $selectedOfficialInstitutionName }}">
                        <input type="hidden" id="participant_destination_program_name" name="participant_destination_program_name" value="{{ $selectedOfficialProgramName }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <select id="destination_institution"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Pilih instansi</option>
                                @foreach($destinationCategories as $category)
                                    <option value="{{ $category->id }}" @selected((int) $selectedInstitutionId === (int) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                                @if($selectedDestinationSource === 'snpmb' && $selectedOfficialInstitutionName !== '')
                                    <option value="api:snpmb:{{ $selectedOfficialExternalId }}" data-external-id="{{ $selectedOfficialExternalId }}" data-name="{{ $selectedOfficialInstitutionName }}" selected>
                                        [Resmi] {{ $selectedOfficialInstitutionName }}
                                    </option>
                                @endif
                            </select>
                            <select id="destination_program"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed"
                                {{ $selectedInstitutionHasPrograms || ($selectedDestinationSource === 'snpmb' && $selectedOfficialInstitutionName !== '') ? '' : 'disabled' }}>
                                <option value="">{{ $selectedInstitutionId ? 'Tidak ada prodi/sub' : 'Pilih instansi dulu' }}</option>
                                @foreach(($selectedInstitution?->activeChildren ?? collect()) as $child)
                                    <option value="{{ $child->id }}" @selected((int) $selectedProgramId === (int) $child->id)>
                                        {{ $child->name }}
                                    </option>
                                @endforeach
                                @if($selectedDestinationSource === 'snpmb' && $selectedOfficialProgramName !== '')
                                    <option value="api:snpmb:{{ $selectedOfficialExternalId }}:program" data-external-id="{{ $selectedOfficialExternalId }}" data-name="{{ $selectedOfficialProgramName }}" selected>
                                        [Resmi] {{ $selectedOfficialProgramName }}
                                    </option>
                                @endif
                            </select>
                        </div>
                        <span id="official_destination_status" class="mt-2 block text-xs text-gray-500"></span>
                        @if($destinationCategories->isEmpty() && !$officialApiEnabled)
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
        const roleSelect = document.querySelector('select[name="role"]');
        const parentChildSection = document.getElementById('parent-child-section');
        const studentParentSection = document.getElementById('student-parent-section');
        const childSearch = document.getElementById('child-search');
        const childSearchResults = document.getElementById('child-search-results');
        const selectedChildren = document.getElementById('selected-children');
        const selectedChildrenEmpty = document.getElementById('selected-children-empty');
        const parentAccountSearch = document.getElementById('parent-account-search');
        const parentSearchResults = document.getElementById('parent-search-results');
        const selectedParent = document.getElementById('selected-parent');
        const linkParentAccount = document.getElementById('link-parent-account');
        const parentLinkFields = document.getElementById('parent-link-fields');
        const addParentAccount = document.getElementById('add-parent-account');
        const newParentFields = document.getElementById('new-parent-fields');
        const relationshipOptionsUrl = @json(route('admin.user.relationship-options'));

        const syncRelationshipSections = () => {
            const role = roleSelect?.value || '';
            parentChildSection?.classList.toggle('hidden', role !== 'parent');
            studentParentSection?.classList.toggle('hidden', role !== 'user');
        };

        const syncNewParentFields = () => {
            const wantsParentLink = Boolean(linkParentAccount?.checked);
            const isCreatingParent = Boolean(addParentAccount?.checked);
            parentLinkFields?.classList.toggle('hidden', !wantsParentLink);
            newParentFields?.classList.toggle('hidden', !wantsParentLink || !isCreatingParent);
            if (addParentAccount) addParentAccount.disabled = !wantsParentLink;
            parentAccountSearch?.toggleAttribute('disabled', !wantsParentLink || isCreatingParent);
            selectedParent?.querySelector('input[name="parent_user_id"]')?.toggleAttribute('disabled', !wantsParentLink || isCreatingParent);

            newParentFields?.querySelectorAll('input').forEach((input) => {
                input.required = wantsParentLink && isCreatingParent;
                input.disabled = !wantsParentLink || !isCreatingParent;
            });
        };

        const renderSearchResults = (container, users, onSelect) => {
            if (!container) return;
            container.replaceChildren();

            if (users.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'text-sm text-gray-500';
                empty.textContent = 'Tidak ada akun yang cocok.';
                container.appendChild(empty);
                return;
            }

            users.forEach((user) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'flex w-full items-center justify-between rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-left text-sm transition hover:border-primary hover:bg-primary/5';

                const identity = document.createElement('span');
                identity.className = 'min-w-0';
                const name = document.createElement('span');
                name.className = 'block truncate font-semibold text-gray-800';
                name.textContent = user.name;
                const email = document.createElement('span');
                email.className = 'block truncate text-xs text-gray-400';
                email.textContent = user.email;
                identity.append(name, email);

                const action = document.createElement('span');
                action.className = 'ml-3 shrink-0 text-primary';
                action.textContent = 'Pilih';
                button.append(identity, action);
                button.addEventListener('click', () => onSelect(user));
                container.appendChild(button);
            });
        };

        const refreshSelectedChildrenState = () => {
            selectedChildrenEmpty?.classList.toggle('hidden', Boolean(selectedChildren?.querySelector('[data-selected-child]')));
        };

        const addChild = (user) => {
            if (!selectedChildren || selectedChildren.querySelector(`[data-user-id="${user.id}"]`)) return;

            const card = document.createElement('div');
            card.dataset.selectedChild = '';
            card.dataset.userId = user.id;
            card.className = 'flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm text-gray-700';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'child_ids[]';
            input.value = user.id;
            const identity = document.createElement('span');
            identity.className = 'min-w-0 flex-1';
            const name = document.createElement('span');
            name.className = 'block truncate font-semibold';
            name.textContent = user.name;
            const email = document.createElement('span');
            email.className = 'block truncate text-xs text-gray-400';
            email.textContent = user.email;
            identity.append(name, email);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600';
            remove.setAttribute('aria-label', 'Hapus anak');
            remove.innerHTML = '<i class="ri-close-line text-lg"></i>';
            remove.addEventListener('click', () => {
                card.remove();
                refreshSelectedChildrenState();
            });
            card.append(input, identity, remove);
            selectedChildren.appendChild(card);
            refreshSelectedChildrenState();
        };

        const setParent = (user) => {
            if (!selectedParent) return;
            selectedParent.replaceChildren();
            const card = document.createElement('div');
            card.dataset.selectedParent = '';
            card.dataset.userId = user.id;
            card.className = 'flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm text-gray-700';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'parent_user_id';
            input.value = user.id;
            const identity = document.createElement('span');
            identity.className = 'min-w-0 flex-1';
            const name = document.createElement('span');
            name.className = 'block truncate font-semibold';
            name.textContent = user.name;
            const email = document.createElement('span');
            email.className = 'block truncate text-xs text-gray-400';
            email.textContent = user.email;
            identity.append(name, email);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600';
            remove.setAttribute('aria-label', 'Hapus orang tua');
            remove.innerHTML = '<i class="ri-close-line text-lg"></i>';
            remove.addEventListener('click', () => card.remove());
            card.append(input, identity, remove);
            selectedParent.appendChild(card);
            syncNewParentFields();
        };

        const attachRemoveHandlers = () => {
            selectedChildren?.querySelectorAll('[data-remove-child]').forEach((button) => {
                button.addEventListener('click', () => {
                    button.closest('[data-selected-child]')?.remove();
                    refreshSelectedChildrenState();
                });
            });
            selectedParent?.querySelectorAll('[data-remove-parent]').forEach((button) => {
                button.addEventListener('click', () => button.closest('[data-selected-parent]')?.remove());
            });
        };

        const setupUserSearch = (input, role, container, onSelect) => {
            let timer;
            let requestNumber = 0;
            input?.addEventListener('input', () => {
                window.clearTimeout(timer);
                const query = input.value.trim();
                if (query.length < 2) {
                    container?.replaceChildren();
                    return;
                }

                timer = window.setTimeout(async () => {
                    const currentRequest = ++requestNumber;
                    const response = await fetch(`${relationshipOptionsUrl}?role=${role}&q=${encodeURIComponent(query)}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!response.ok || currentRequest !== requestNumber) return;
                    const payload = await response.json();
                    renderSearchResults(container, Array.isArray(payload.data) ? payload.data : [], (user) => {
                        onSelect(user);
                        input.value = '';
                        container?.replaceChildren();
                    });
                }, 250);
            });
        };

        attachRemoveHandlers();
        refreshSelectedChildrenState();
        setupUserSearch(childSearch, 'user', childSearchResults, addChild);
        setupUserSearch(parentAccountSearch, 'parent', parentSearchResults, setParent);

        roleSelect?.addEventListener('change', syncRelationshipSections);
        linkParentAccount?.addEventListener('change', syncNewParentFields);
        addParentAccount?.addEventListener('change', syncNewParentFields);
        syncRelationshipSections();
        syncNewParentFields();

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
@endsection
