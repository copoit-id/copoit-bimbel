@props([
    'choice' => 1,
    'destinationCategories' => collect(),
    'selectedDestinationId' => null,
    'selectedSource' => '',
    'selectedExternalId' => '',
    'selectedInstitutionName' => '',
    'selectedProgramName' => '',
    'required' => false,
])

@php
    $fieldPrefix = (int) $choice === 2 ? 'second_participant_destination' : 'participant_destination';
    $selectorId = $fieldPrefix . '-selector';
    $selectedDestination = $destinationCategories
        ->flatMap(fn ($category) => collect([$category])->merge($category->activeChildren))
        ->firstWhere('id', (int) $selectedDestinationId);
    $selectedInstitutionId = $selectedDestination?->parent_id ?: ($selectedDestination?->id ?? null);
    $selectedProgramId = $selectedDestination?->parent_id ? $selectedDestination?->id : null;
    $selectedInstitution = $selectedInstitutionId
        ? $destinationCategories->firstWhere('id', $selectedInstitutionId)
        : null;
    $selectedInstitutionHasPrograms = $selectedInstitution?->activeChildren->isNotEmpty() ?? false;
    $officialApiEnabled = (bool) config('client.branding.participant_destination_api_enabled', false);
    $programsByInstitution = $destinationCategories->mapWithKeys(fn ($category) => [
        (string) $category->id => $category->activeChildren
            ->map(fn ($child) => ['id' => (string) $child->id, 'name' => $child->name])
            ->values()
            ->all(),
    ]);
@endphp

<fieldset id="{{ $selectorId }}" data-destination-selector
    data-programs='@json($programsByInstitution)'
    data-selected-program="{{ $selectedProgramId ?? '' }}"
    data-selected-official-program="{{ $selectedProgramName }}"
    data-official-api-enabled="{{ $officialApiEnabled ? '1' : '0' }}"
    class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
    <legend class="px-1 text-sm font-semibold text-gray-800">
        Tujuan {{ $choice }}
        @if ($required)
            <x-form.required-indicator />
        @endif
    </legend>
    <input type="hidden" name="{{ $fieldPrefix }}_category_id" data-destination-category-id value="{{ $selectedDestinationId ?: '' }}">
    <input type="hidden" name="{{ $fieldPrefix }}_source" data-destination-source value="{{ $selectedSource }}">
    <input type="hidden" name="{{ $fieldPrefix }}_external_id" data-destination-external-id value="{{ $selectedExternalId }}">
    <input type="hidden" name="{{ $fieldPrefix }}_institution_name" data-destination-institution-name value="{{ $selectedInstitutionName }}">
    <input type="hidden" name="{{ $fieldPrefix }}_program_name" data-destination-program-name value="{{ $selectedProgramName }}">

    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div>
            <label for="{{ $fieldPrefix }}_institution" class="mb-1 block text-sm font-medium text-gray-700">
                Instansi Tujuan
                @if ($required)
                    <x-form.required-indicator />
                @endif
            </label>
            <select id="{{ $fieldPrefix }}_institution" data-destination-institution
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                {{ $required ? 'required' : '' }}>
                <option value="">Pilih instansi</option>
                @foreach($destinationCategories as $category)
                    <option value="{{ $category->id }}" @selected((int) $selectedInstitutionId === (int) $category->id)>{{ $category->name }}</option>
                @endforeach
                @if($selectedSource === 'snpmb' && $selectedInstitutionName !== '')
                    <option value="api:snpmb:{{ $selectedExternalId }}" data-external-id="{{ $selectedExternalId }}" data-name="{{ $selectedInstitutionName }}" selected>[Resmi] {{ $selectedInstitutionName }}</option>
                @endif
            </select>
        </div>
        <div>
            <label for="{{ $fieldPrefix }}_program" class="mb-1 block text-sm font-medium text-gray-700">Program Studi / Sub Tujuan</label>
            <select id="{{ $fieldPrefix }}_program" data-destination-program
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
                {{ $selectedInstitutionHasPrograms || ($selectedSource === 'snpmb' && $selectedInstitutionName !== '') ? '' : 'disabled' }}>
                <option value="">{{ $selectedInstitutionId ? 'Tidak ada prodi/sub' : 'Pilih instansi dulu' }}</option>
                @foreach(($selectedInstitution?->activeChildren ?? collect()) as $child)
                    <option value="{{ $child->id }}" @selected((int) $selectedProgramId === (int) $child->id)>{{ $child->name }}</option>
                @endforeach
                @if($selectedSource === 'snpmb' && $selectedProgramName !== '')
                    <option value="api:snpmb:{{ $selectedExternalId }}:program" data-external-id="{{ $selectedExternalId }}" data-name="{{ $selectedProgramName }}" selected>[Resmi] {{ $selectedProgramName }}</option>
                @endif
            </select>
        </div>
    </div>
    <p data-destination-status class="mt-2 text-xs text-gray-500"></p>
</fieldset>

@once
<script>
document.addEventListener('DOMContentLoaded', () => {
    const officialInstitutionsUrl = @json(route('participant-destinations.official.institutions'));
    const officialProgramsUrl = @json(route('participant-destinations.official.programs'));
    const institutionCache = new Map();
    const programCache = new Map();
    const isOfficialValue = (value) => String(value || '').startsWith('api:snpmb:');

    document.querySelectorAll('[data-destination-selector]').forEach((selector) => {
        const institution = selector.querySelector('[data-destination-institution]');
        const program = selector.querySelector('[data-destination-program]');
        const categoryId = selector.querySelector('[data-destination-category-id]');
        const source = selector.querySelector('[data-destination-source]');
        const externalId = selector.querySelector('[data-destination-external-id]');
        const institutionName = selector.querySelector('[data-destination-institution-name]');
        const programName = selector.querySelector('[data-destination-program-name]');
        const status = selector.querySelector('[data-destination-status]');
        const programsByInstitution = JSON.parse(selector.dataset.programs || '{}');
        const selectedProgramId = selector.dataset.selectedProgram || '';
        const selectedOfficialProgramName = selector.dataset.selectedOfficialProgram || '';
        const officialApiEnabled = selector.dataset.officialApiEnabled === '1';
        const officialInstitutions = new Map();

        const clearOfficialSnapshot = () => {
            source.value = '';
            externalId.value = '';
            institutionName.value = '';
            programName.value = '';
        };

        const syncDestination = () => {
            const institutionValue = institution.value;
            const institutionOption = institution.selectedOptions?.[0];

            if (isOfficialValue(institutionValue)) {
                const selectedProgramOption = program.selectedOptions?.[0];
                categoryId.value = '';
                source.value = 'snpmb';
                externalId.value = institutionOption?.dataset.externalId || institutionValue.replace('api:snpmb:', '');
                institutionName.value = institutionOption?.dataset.name || institutionOption?.textContent?.replace('[Resmi]', '').trim() || '';
                programName.value = isOfficialValue(program.value)
                    ? (selectedProgramOption?.dataset.name || selectedProgramOption?.textContent?.replace('[Resmi]', '').trim() || '')
                    : '';
                return;
            }

            categoryId.value = (!program.disabled && program.value) ? program.value : (institutionValue || '');
            if (categoryId.value) {
                source.value = 'db';
                externalId.value = '';
                institutionName.value = '';
                programName.value = '';
            } else {
                clearOfficialSnapshot();
            }
        };

        const renderManualPrograms = (institutionId) => {
            const programs = programsByInstitution[institutionId] || [];
            program.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = !institutionId
                ? 'Pilih instansi dulu'
                : (programs.length ? 'Pilih prodi/sub' : 'Tidak ada prodi/sub');
            program.appendChild(placeholder);

            programs.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                option.selected = item.id === selectedProgramId;
                program.appendChild(option);
            });

            program.disabled = !institutionId || programs.length === 0;
            syncDestination();
        };

        const renderOfficialPrograms = async (institutionValue) => {
            const institutionOption = institution.selectedOptions?.[0];
            const selected = officialInstitutions.get(institutionValue) || {
                id_ptn: institutionValue.replace('api:snpmb:', ''),
                nama: institutionOption?.dataset.name || '',
                source_ids: JSON.parse(institutionOption?.dataset.sourceIds || '{}'),
            };
            const cacheKey = `${selected.id_ptn}|${JSON.stringify(selected.source_ids || {})}`;
            program.innerHTML = '<option value="">Memuat prodi resmi...</option>';
            program.disabled = true;
            syncDestination();

            if (!programCache.has(cacheKey)) {
                const params = new URLSearchParams({ source: 'all', ptn: selected.id_ptn || '' });
                if (selected.source_ids?.snbt) params.set('ptn_snbt', selected.source_ids.snbt);
                if (selected.source_ids?.snbp) params.set('ptn_snbp', selected.source_ids.snbp);
                const response = await fetch(`${officialProgramsUrl}?${params.toString()}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Gagal memuat prodi resmi.');
                const payload = await response.json();
                programCache.set(cacheKey, Array.isArray(payload.data) ? payload.data : []);
            }

            program.innerHTML = '<option value="">Pilih prodi resmi jika ada</option>';
            programCache.get(cacheKey).forEach((item) => {
                const option = document.createElement('option');
                option.value = `api:snpmb:${item.id_prodi || item.kode_prodi || item.nama}:program`;
                option.textContent = `[Resmi] ${item.nama}`;
                option.dataset.externalId = item.id_prodi || item.kode_prodi || item.nama || selected.id_ptn || '';
                option.dataset.name = item.nama || '';
                option.selected = item.nama === selectedOfficialProgramName;
                program.appendChild(option);
            });
            program.disabled = false;
            syncDestination();
        };

        const renderPrograms = (institutionId) => isOfficialValue(institutionId)
            ? renderOfficialPrograms(institutionId)
            : renderManualPrograms(institutionId);

        const addOfficialInstitutions = (items) => {
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
                officialInstitutions.set(value, item);
            });
        };

        const loadOfficialInstitutions = async () => {
            status.textContent = 'Memuat data instansi resmi...';
            try {
                if (!institutionCache.has('all')) {
                    const response = await fetch(`${officialInstitutionsUrl}?source=all`, { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('Gagal memuat data instansi resmi.');
                    const payload = await response.json();
                    institutionCache.set('all', Array.isArray(payload.data) ? payload.data : []);
                }
                addOfficialInstitutions(institutionCache.get('all'));
                status.textContent = '';
            } catch (error) {
                status.textContent = error.message || 'Gagal memuat data instansi resmi.';
            }
        };

        institution.addEventListener('change', () => {
            Promise.resolve(renderPrograms(institution.value)).catch((error) => {
                program.innerHTML = `<option value="">${error.message || 'Gagal memuat prodi resmi.'}</option>`;
                program.disabled = true;
                syncDestination();
            });
        });
        program.addEventListener('change', syncDestination);

        if (officialApiEnabled) loadOfficialInstitutions();
        Promise.resolve(renderPrograms(institution.value)).catch(() => syncDestination());
    });
});
</script>
@endonce
