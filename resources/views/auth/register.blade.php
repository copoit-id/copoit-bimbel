<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Register - {{ $clientBranding['name'] }}</title>
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
    <x-website-translation-head />
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet" />

    @if($recaptcha_enabled)
    <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptcha_site_key }}"></script>
    @endif
    <style>
        .auth-page .auth-surface,
        .auth-page .auth-surface * { box-shadow: none !important; }
        .register-progress-item:not(.is-active) .register-progress-number { border-color: #d1d5db; color: #9ca3af; }
        .register-progress-item.is-active .register-progress-number { border-color: var(--primary-color, #10b981); background: var(--primary-color, #10b981); color: #fff; }
    </style>
    <noscript><style>[data-register-step] { display: block !important; margin-top: 1.5rem; } #nextStepWrap, #previousStepWrap { display: none !important; } #submitStepWrap { display: block !important; width: 100%; } #submitBtn { display: flex !important; width: 100%; }</style></noscript>
</head>

<body class="auth-page bg-gray-50">
    @include('components.flash-alert')
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-lg w-full space-y-6">
            <x-auth.header title="Daftar Akun Baru" prompt="Sudah punya akun?" :href="route('login')" link-label="Masuk di sini" />

            <x-auth.error-summary />

            @php
                $accountFields = ['name', 'email', 'date_of_birth', 'phone', 'password'];
                $destinationFields = [
                    'participant_destination_category_id',
                    'participant_destination_source',
                    'participant_destination_external_id',
                    'participant_destination_institution_name',
                    'participant_destination_program_name',
                ];
                $errorFields = $errors->keys();
                $initialRegisterStep = min(3, max(1, (int) old('register_step', 1)));

                if (collect($errorFields)->intersect($accountFields)->isNotEmpty()) {
                    $initialRegisterStep = 1;
                } elseif (collect($errorFields)->intersect($destinationFields)->isNotEmpty()) {
                    $initialRegisterStep = 3;
                }
            @endphp

            <x-ui.card padding="md" class="auth-surface rounded-2xl" aria-label="Progress pendaftaran">
                <div class="grid grid-cols-3 gap-2 sm:gap-3">
                    @foreach([1 => ['Akun', 'Data utama'], 2 => ['Profil', 'Opsional'], 3 => ['Selesai', 'Tujuan & konfirmasi']] as $stepNumber => [$stepTitle, $stepDescription])
                        <div class="register-progress-item flex min-w-0 items-center gap-2 rounded-xl border border-gray-200 bg-white px-2.5 py-2.5 {{ $initialRegisterStep >= $stepNumber ? 'is-active' : '' }}" data-progress-step="{{ $stepNumber }}">
                            <span class="register-progress-number flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-xs font-bold">{{ $stepNumber }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-xs font-semibold text-gray-800">{{ $stepTitle }}</span>
                                <span class="mt-0.5 block truncate text-[11px] text-gray-500">{{ $stepDescription }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <form class="mt-6" action="{{ route('register.store') }}" method="POST" id="registerForm" data-initial-step="{{ $initialRegisterStep }}">
                <x-ui.card padding="lg" class="auth-surface space-y-6 rounded-2xl">
                @csrf
                <input type="hidden" name="register_step" id="register_step" value="{{ $initialRegisterStep }}">
                @if(!empty($affiliateRefCode))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                    Kamu mendaftar melalui kode referral <span class="font-semibold">{{ $affiliateRefCode }}</span>.
                </div>
                @endif
                <section class="space-y-4" data-register-step="1">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Buat akunmu</h3>
                        <p class="mt-1 text-sm text-gray-500">Isi data utama terlebih dahulu.</p>
                    </div>
                    <x-ui.input name="name" label="Nama Lengkap" placeholder="Nama lengkap" :value="old('name')" required autocomplete="name" helper="Wajib diisi." />
                    <x-ui.input name="email" type="email" label="Email" :value="old('email')" required autocomplete="email" />
                    <x-ui.input name="date_of_birth" type="date" label="Tanggal Lahir" :value="old('date_of_birth')" required />
                    <x-ui.input name="phone" type="tel" label="No. HP" placeholder="6281234567890" :value="old('phone')" required autocomplete="tel" helper="Gunakan format 62xxxxxxxxxx." />

                    <div class="space-y-4">
                        <x-ui.input name="password" type="password" label="Password" required autocomplete="new-password" minlength="8" helper="Minimal 8 karakter." />
                        <x-ui.input name="password_confirmation" type="password" label="Konfirmasi Password" required autocomplete="new-password" minlength="8" />
                    </div>
                </section>

                <section class="space-y-4" data-register-step="2" hidden>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Lengkapi profilmu</h3>
                        <p class="mt-1 text-sm text-gray-500">Semua data pada langkah ini opsional dan bisa dilengkapi nanti.</p>
                    </div>
                    <div class="space-y-4">
                        <x-ui.input name="education_level" label="Kelas / Level (Opsional)" placeholder="Contoh: Kelas 12" :value="old('education_level')" />
                        <x-ui.input name="origin_institution" label="Asal Sekolah / Instansi (Opsional)" placeholder="Contoh: SMA Negeri 1 Jakarta" :value="old('origin_institution')" />
                    </div>
                </section>

                <section class="space-y-4" data-register-step="3" hidden>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Tujuan belajarmu</h3>
                        <p class="mt-1 text-sm text-gray-500">Pilih hingga dua instansi atau program studi tujuanmu.</p>
                    </div>
                    <x-form.participant-destination-selector :destination-categories="$destinationCategories" :selected-destination-id="old('participant_destination_category_id')" :selected-source="old('participant_destination_source')" :selected-external-id="old('participant_destination_external_id')" :selected-institution-name="old('participant_destination_institution_name')" :selected-program-name="old('participant_destination_program_name')" :required="app(\App\Services\ParticipantDestinationSelectionService::class)->isRequired()" />
                    <x-form.participant-destination-selector choice="2" :destination-categories="$destinationCategories" :selected-destination-id="old('second_participant_destination_category_id')" :selected-source="old('second_participant_destination_source')" :selected-external-id="old('second_participant_destination_external_id')" :selected-institution-name="old('second_participant_destination_institution_name')" :selected-program-name="old('second_participant_destination_program_name')" />

                    <x-ui.input name="affiliate_ref_code" label="Kode Referral (Opsional)" placeholder="Contoh: REFKODE" :value="old('affiliate_ref_code', $affiliateRefCode ?? '')" autocomplete="off" class="uppercase" helper="Masukkan kode referral dari teman atau promosi jika ada." />
                </section>

                <!-- Hidden field for reCAPTCHA v3 token -->
                @if($recaptcha_enabled)
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                @endif

                <div class="flex items-center gap-3">
                    <div id="previousStepWrap" class="w-1/3" hidden>
                        <x-ui.button type="button" id="previousStepBtn" variant="outline" :full-width="true">Kembali</x-ui.button>
                    </div>
                    <div id="nextStepWrap" class="flex-1">
                        <x-ui.button type="button" id="nextStepBtn" :full-width="true">Lanjutkan</x-ui.button>
                    </div>
                    <div id="submitStepWrap" class="flex-1" hidden>
                        <x-ui.button type="submit" id="submitBtn" icon="ri-user-add-line" :full-width="true">Buat Akun</x-ui.button>
                    </div>
                </div>
                @if($recaptcha_enabled)
                <div class="text-center">
                    <p class="text-xs text-gray-500">
                        Situs ini dilindungi oleh reCAPTCHA dan berlaku
                        <a href="https://policies.google.com/privacy" class="text-primary hover:underline"
                            target="_blank">Kebijakan Privasi</a> dan
                        <a href="https://policies.google.com/terms" class="text-primary hover:underline"
                            target="_blank">Persyaratan Layanan</a> Google.
                    </p>
                </div>
                @endif
                </x-ui.card>
            </form>

            <x-auth.legal-links />
        </div>
    </div>
    @vite('resources/js/app.js')
    <x-website-translator />
</body>

</html>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('registerForm');
        const steps = [...document.querySelectorAll('[data-register-step]')];
        const progressItems = [...document.querySelectorAll('[data-progress-step]')];
        const previousButton = document.getElementById('previousStepBtn');
        const nextButton = document.getElementById('nextStepBtn');
        const submitButton = document.getElementById('submitBtn');
        const previousStepWrap = document.getElementById('previousStepWrap');
        const nextStepWrap = document.getElementById('nextStepWrap');
        const submitStepWrap = document.getElementById('submitStepWrap');
        const stepInput = document.getElementById('register_step');

        if (!form || !steps.length || !previousButton || !nextButton || !submitButton || !previousStepWrap || !nextStepWrap || !submitStepWrap) return;

        let currentStep = Math.min(3, Math.max(1, Number(form.dataset.initialStep || 1)));

        const showStep = (step, focus = true) => {
            currentStep = Math.min(3, Math.max(1, step));
            stepInput.value = currentStep;

            steps.forEach((section) => {
                section.hidden = Number(section.dataset.registerStep) !== currentStep;
            });
            progressItems.forEach((item) => {
                item.classList.toggle('is-active', Number(item.dataset.progressStep) <= currentStep);
            });

            previousStepWrap.hidden = currentStep === 1;
            nextStepWrap.hidden = currentStep === 3;
            submitStepWrap.hidden = currentStep !== 3;
            nextButton.textContent = currentStep === 2 ? 'Lewati / Lanjutkan' : 'Lanjutkan';

            if (focus) {
                steps[currentStep - 1]?.querySelector('h3, input, select')?.focus({ preventScroll: true });
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        };

        const validateCurrentStep = () => {
            const fields = [...steps[currentStep - 1].querySelectorAll('input, select, textarea')]
                .filter((field) => !field.disabled && field.type !== 'hidden');

            return fields.every((field) => {
                if (field.checkValidity()) return true;
                field.reportValidity();
                return false;
            });
        };

        nextButton.addEventListener('click', () => {
            if (validateCurrentStep()) showStep(currentStep + 1);
        });
        previousButton.addEventListener('click', () => showStep(currentStep - 1));
        form.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && currentStep < 3 && event.target.tagName !== 'TEXTAREA') {
                event.preventDefault();
                if (validateCurrentStep()) showStep(currentStep + 1);
            }
        });

        showStep(currentStep, false);
    });

    {{-- Selector destination dirender dan dikelola oleh komponen form di atas.
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
    --}}
</script>

@if($recaptcha_enabled)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('registerForm');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i>Memverifikasi...';

            grecaptcha.ready(function() {
                grecaptcha.execute('{{ $recaptcha_site_key }}', {
                    action: 'register'
                }).then(function(token) {
                    document.getElementById('g-recaptcha-response').value = token;
                    form.submit();
                }).catch(function(error) {
                    console.error('reCAPTCHA error:', error);
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span class="absolute left-0 inset-y-0 flex items-center pl-3"><i class="ri-user-add-line text-primary/60 group-hover:text-primary/80"></i></span>Daftar';
                    alert('Terjadi kesalahan pada verifikasi reCAPTCHA. Silakan coba lagi.');
                });
            });
        });
    });
</script>
@endif
