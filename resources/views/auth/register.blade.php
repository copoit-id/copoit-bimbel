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
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet" />

    @if($recaptcha_enabled)
    <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptcha_site_key }}"></script>
    @endif
</head>

<body class="bg-gray-50">
    @include('components.flash-alert')
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="flex justify-center">
                    <img src="{{ $clientBranding['logo_url'] }}" alt="{{ $clientBranding['name'] }} Logo"
                        class="h-32 object-contain">
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Daftar Akun Baru
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Sudah punya akun?
                    <a href="{{ route('login') }}" class="font-medium text-primary hover:text-primary/80">
                        Masuk di sini
                    </a>
                </p>
            </div>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form class="mt-8 space-y-6" action="{{ route('register.store') }}" method="POST" id="registerForm">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input id="name" name="name" type="text" autocomplete="name" required placeholder="Nama Lengkap"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary"
                            value="{{ old('name') }}">
                        <p class="text-xs text-gray-400 mt-1">Wajib diisi.</p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Email
                        </label>
                        <input id="email" name="email" type="email" autocomplete="email" required
                            value="{{ old('email') }}"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg  placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700">
                            Tanggal Lahir
                        </label>
                        <input id="date_of_birth" name="date_of_birth" type="date" required
                            value="{{ old('date_of_birth') }}"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg  placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">
                            No. HP <span class="text-red-500">*</span>
                        </label>
                        <input id="phone" name="phone" type="tel" autocomplete="tel" required
                            value="{{ old('phone') }}" placeholder="62xxxxxxxxxx"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary">
                        <p class="text-xs text-gray-500 mt-1">Format: 62xxxxxxxxxx (contoh: 6281234567890)</p>
                    </div>

                    <div>
                        @php
                            $selectedDestinationId = (int) old('participant_destination_category_id');
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
                        <label for="participant_destination_category_id" class="block text-sm font-medium text-gray-700">
                            Instansi/Prodi Tujuan
                        </label>
                        <input type="hidden" id="participant_destination_category_id" name="participant_destination_category_id"
                            value="{{ $selectedDestinationId ?: '' }}">
                        <div class="grid grid-cols-1 gap-3">
                            <select id="destination_institution"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">Pilih instansi</option>
                                @foreach($destinationCategories as $category)
                                    <option value="{{ $category->id }}" @selected((int) $selectedInstitutionId === (int) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <select id="destination_program"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-primary focus:border-primary disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed"
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
                            <p class="text-xs text-amber-600 mt-1">Instansi tujuan belum tersedia. Hubungi admin.</p>
                        @else
                            <p class="text-xs text-gray-500 mt-1">Pilih instansi dulu, lalu pilih prodi/sub jika tersedia.</p>
                        @endif
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg  placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                            Konfirmasi Password
                        </label>
                        <input id="password_confirmation" name="password_confirmation" type="password"
                            autocomplete="new-password" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg  placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <!-- Hidden field for reCAPTCHA v3 token -->
                @if($recaptcha_enabled)
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                @endif

                <div>
                    <button type="submit" id="submitBtn"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="ri-user-add-line text-primary/60 group-hover:text-primary/80"></i>
                        </span>
                        Daftar
                    </button>
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
            </form>
        </div>
    </div>
</body>

</html>

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
