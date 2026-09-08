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
                    <div class="rounded-xl border border-primary/20 bg-primary/5 p-4">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            @if($roleLocked ?? false)
                                <div>
                                    <span class="mb-1 block text-sm font-medium text-gray-700">Role</span>
                                    <input type="hidden" name="role" value="{{ $formRole }}">
                                    <div class="flex min-h-10 items-center rounded-lg border border-gray-200 bg-gray-100 px-3 text-sm text-gray-700">
                                        Admin reguler
                                    </div>
                                </div>
                            @else
                                <x-form.select name="role" label="Role" :options="$roleOptions"
                                    :value="old('role', $user->role ?? $formRole)" required />
                            @endif
                            <x-form.select name="status" label="Status"
                                :options="['aktif' => 'Aktif', 'nonaktif' => 'Tidak Aktif']"
                                :value="old('status', $user->status ?? 'aktif')" required />
                        </div>
                    </div>

                    <div id="school-admin-study-groups" class="hidden rounded-xl border border-primary/20 bg-primary/5 p-4">
                        <p class="text-sm font-semibold text-gray-800">Rombel yang dipantau</p>
                        <p class="mt-1 text-xs text-gray-500">Admin Sekolah hanya dapat melihat data siswa dari rombel yang dipilih.</p>
                        <div class="relative mt-3" data-school-admin-study-group-picker>
                            <button type="button" data-school-admin-study-group-toggle
                                class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-left text-sm text-gray-700 transition hover:border-primary focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                aria-expanded="false">
                                <span data-school-admin-study-group-summary>Pilih rombel yang dipantau</span>
                                <i class="ri-arrow-down-s-line text-lg text-gray-400"></i>
                            </button>
                            <div data-school-admin-study-group-menu class="absolute z-20 mt-2 hidden w-full rounded-lg border border-gray-200 bg-white p-3 shadow-lg">
                                <div class="relative">
                                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="search" data-school-admin-study-group-search placeholder="Cari rombel..."
                                        class="w-full rounded-lg border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-primary focus:ring-primary">
                                </div>
                                <div class="mt-2 max-h-56 space-y-1 overflow-y-auto" data-school-admin-study-group-options>
                            @foreach($schoolAdminStudyGroups as $studyGroup)
                                    <label data-school-admin-study-group-option data-search-value="{{ strtolower($studyGroup->name) }}" class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <input type="checkbox" name="school_admin_study_group_ids[]" value="{{ $studyGroup->id }}" @checked(in_array($studyGroup->id, $selectedSchoolAdminStudyGroupIds, true))>
                                    {{ $studyGroup->name }}
                                </label>
                            @endforeach
                                    <p data-school-admin-study-group-empty class="hidden px-3 py-2 text-sm text-gray-500">Rombel tidak ditemukan.</p>
                                </div>
                            </div>
                        </div>
                    </div>

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

                    <div data-student-field class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-form.input type="tel" name="phone" label="Nomor WhatsApp"
                            :value="old('phone', $user->phone ?? '')"
                            placeholder="Contoh: 6281234567890"
                            helper="Wajib untuk siswa. Gunakan format 62 tanpa angka 0 atau tanda + di depan."
                            inputmode="numeric"
                            pattern="62[0-9]{8,14}"
                            autocomplete="tel" />
                        <x-form.input type="date" name="birthday" label="Tanggal Lahir"
                            :value="old('birthday', $user->birthday ?? '')"
                            max="{{ now()->toDateString() }}" />
                    </div>

                    <div data-student-field class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-form.input name="education_level" label="Kelas / Level (Opsional)"
                            :value="old('education_level', $user->education_level ?? '')" placeholder="Contoh: Kelas 12" />
                        <x-form.input name="origin_institution" label="Asal Sekolah / Instansi (Opsional)"
                            :value="old('origin_institution', $user->origin_institution ?? '')" placeholder="Contoh: SMA Negeri 1 Jakarta" />
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

                    <div data-student-field>
                        <x-form.participant-destination-selector
                            :destination-categories="$destinationCategories"
                            :selected-destination-id="old('participant_destination_category_id', $user->participant_destination_category_id ?? null)"
                            :selected-source="old('participant_destination_source', $user->participant_destination_source ?? '')"
                            :selected-external-id="old('participant_destination_external_id', $user->participant_destination_external_id ?? '')"
                            :selected-institution-name="old('participant_destination_institution_name', $user->participant_destination_institution_name ?? '')"
                            :selected-program-name="old('participant_destination_program_name', $user->participant_destination_program_name ?? '')"
                            :required="($user?->role ?? old('role', 'user')) === 'user' && app(\App\Services\ParticipantDestinationSelectionService::class)->isRequired()" />
                    </div>

                    <div data-student-field class="mt-6">
                        <x-form.participant-destination-selector
                            choice="2"
                            :destination-categories="$destinationCategories"
                            :selected-destination-id="old('second_participant_destination_category_id', $user->second_participant_destination_category_id ?? null)"
                            :selected-source="old('second_participant_destination_source', $user->second_participant_destination_source ?? '')"
                            :selected-external-id="old('second_participant_destination_external_id', $user->second_participant_destination_external_id ?? '')"
                            :selected-institution-name="old('second_participant_destination_institution_name', $user->second_participant_destination_institution_name ?? '')"
                            :selected-program-name="old('second_participant_destination_program_name', $user->second_participant_destination_program_name ?? '')" />
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
        const roleSelect = document.querySelector('[name="role"]');
        const studentFields = document.querySelectorAll('[data-student-field]');
        const parentChildSection = document.getElementById('parent-child-section');
        const studentParentSection = document.getElementById('student-parent-section');
        const schoolAdminStudyGroups = document.getElementById('school-admin-study-groups');
        const schoolAdminStudyGroupPicker = document.querySelector('[data-school-admin-study-group-picker]');
        const schoolAdminStudyGroupToggle = document.querySelector('[data-school-admin-study-group-toggle]');
        const schoolAdminStudyGroupMenu = document.querySelector('[data-school-admin-study-group-menu]');
        const schoolAdminStudyGroupSearch = document.querySelector('[data-school-admin-study-group-search]');
        const schoolAdminStudyGroupSummary = document.querySelector('[data-school-admin-study-group-summary]');
        const schoolAdminStudyGroupOptions = [...document.querySelectorAll('[data-school-admin-study-group-option]')];
        const schoolAdminStudyGroupEmpty = document.querySelector('[data-school-admin-study-group-empty]');
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
            const isStudent = role === 'user';
            studentFields.forEach((field) => {
                field.classList.toggle('hidden', !isStudent);
                field.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.disabled = !isStudent;
                });
            });
            parentChildSection?.classList.toggle('hidden', role !== 'parent');
            studentParentSection?.classList.toggle('hidden', role !== 'user');
            schoolAdminStudyGroups?.classList.toggle('hidden', role !== 'admin_sekolah');
            schoolAdminStudyGroups?.querySelectorAll('input').forEach((input) => {
                input.disabled = role !== 'admin_sekolah';
            });
            if (role !== 'admin_sekolah') closeSchoolAdminStudyGroupPicker();
        };

        const closeSchoolAdminStudyGroupPicker = () => {
            schoolAdminStudyGroupMenu?.classList.add('hidden');
            schoolAdminStudyGroupToggle?.setAttribute('aria-expanded', 'false');
        };

        const syncSchoolAdminStudyGroupSummary = () => {
            const selected = schoolAdminStudyGroupOptions.filter((option) => option.querySelector('input')?.checked);
            const labels = selected.map((option) => option.textContent.trim());
            schoolAdminStudyGroupSummary.textContent = labels.length === 0
                ? 'Pilih rombel yang dipantau'
                : labels.length <= 2 ? labels.join(', ') : `${labels.length} rombel dipilih`;
        };

        schoolAdminStudyGroupToggle?.addEventListener('click', () => {
            const isOpen = !schoolAdminStudyGroupMenu?.classList.contains('hidden');
            schoolAdminStudyGroupMenu?.classList.toggle('hidden', isOpen);
            schoolAdminStudyGroupToggle.setAttribute('aria-expanded', (!isOpen).toString());
            if (!isOpen) schoolAdminStudyGroupSearch?.focus();
        });

        schoolAdminStudyGroupSearch?.addEventListener('input', () => {
            const keyword = schoolAdminStudyGroupSearch.value.trim().toLowerCase();
            let visibleCount = 0;
            schoolAdminStudyGroupOptions.forEach((option) => {
                const visible = option.dataset.searchValue.includes(keyword);
                option.classList.toggle('hidden', !visible);
                if (visible) visibleCount += 1;
            });
            schoolAdminStudyGroupEmpty?.classList.toggle('hidden', visibleCount > 0);
        });

        schoolAdminStudyGroupOptions.forEach((option) => {
            option.querySelector('input')?.addEventListener('change', syncSchoolAdminStudyGroupSummary);
        });

        document.addEventListener('click', (event) => {
            if (!schoolAdminStudyGroupPicker?.contains(event.target)) closeSchoolAdminStudyGroupPicker();
        });

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
        syncSchoolAdminStudyGroupSummary();
        syncNewParentFields();

    });
</script>
@endsection
