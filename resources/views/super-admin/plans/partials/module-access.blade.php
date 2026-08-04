<section class="mb-4 rounded-lg border border-gray-200 bg-white p-5">
    <div class="flex flex-col gap-4 border-b border-gray-100 pb-5 md:flex-row md:items-end md:justify-between">
        <div>
            <h3 class="font-semibold text-gray-900">Grouping Modul</h3>
            <p class="mt-1 text-sm text-gray-500">Pilih preset, lalu sesuaikan fitur satu per satu bila diperlukan.</p>
        </div>
        <div class="w-full md:w-80">
            <label for="module_preset" class="mb-2 block text-sm font-medium text-gray-700">Preset Modul</label>
            <select id="module_preset" name="module_preset"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                @foreach($modulePresets as $presetKey => $preset)
                    <option value="{{ $presetKey }}" @selected($selectedModulePreset === $presetKey)>
                        {{ $preset['label'] }}
                    </option>
                @endforeach
            </select>
            <p id="module_preset_description" class="mt-1.5 text-xs text-gray-500"></p>
        </div>
    </div>

    <div class="mt-5 grid gap-4 xl:grid-cols-2">
        @foreach($moduleGroups as $groupKey => $group)
            <div class="rounded-xl border border-gray-200 p-4">
                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-900">{{ $group['label'] }}</h4>
                    <p class="mt-1 text-xs leading-relaxed text-gray-500">{{ $group['description'] }}</p>
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach($group['features'] as $feature)
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-100 px-3 py-2.5 hover:border-primary/30 hover:bg-primary/5">
                            <input type="hidden" name="module_features[{{ $feature }}]" value="0">
                            <input type="checkbox" name="module_features[{{ $feature }}]" value="1"
                                data-module-feature="{{ $feature }}"
                                @checked(!empty($selectedModuleFeatures[$feature]))
                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="text-sm text-gray-700">{{ $moduleFeatureLabels[$feature] ?? \Illuminate\Support\Str::headline($feature) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @error('module_preset')
        <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error('module_features')
        <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
    @enderror
</section>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const presetSelect = document.getElementById('module_preset');
        const description = document.getElementById('module_preset_description');
        const featureInputs = Array.from(document.querySelectorAll('[data-module-feature]'));
        const presets = @json($modulePresets);
        const presetFeatures = @json($modulePresetFeatures);

        function updateDescription() {
            description.textContent = presets[presetSelect.value]?.description || '';
        }

        function applyPreset() {
            const selectedPreset = presetSelect.value;
            updateDescription();

            if (selectedPreset === 'custom') {
                return;
            }

            const enabledFeatures = presetFeatures[selectedPreset] || {};
            featureInputs.forEach((input) => {
                input.checked = Boolean(enabledFeatures[input.dataset.moduleFeature]);
            });
        }

        presetSelect?.addEventListener('change', applyPreset);
        featureInputs.forEach((input) => {
            input.addEventListener('change', function () {
                if (presetSelect) {
                    presetSelect.value = 'custom';
                    updateDescription();
                }
            });
        });

        updateDescription();
    });
</script>
@endpush
