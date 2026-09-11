@props([
    'id',
    'name',
    'value' => 'fullscore',
    'variant' => 'standard',
    'label' => 'Mode Penilaian',
])

@php
    $options = $variant === 'essay'
        ? [
            'full' => 'Benar/Salah Fullscore',
            'range' => 'Range (Proporsional)',
        ]
        : [
            'fullscore' => 'Benar/Salah Fullscore',
            'partial' => 'Partial',
        ];
@endphp

<div>
    <div class="mb-1 flex items-center gap-1">
        <label for="{{ $id }}" class="block text-xs font-medium text-gray-600">{{ $label }}</label>
        <div class="group relative">
            <i class="ri-information-line cursor-help text-sm text-gray-400"></i>
            <div class="pointer-events-none absolute left-1/2 top-full z-20 mt-1 hidden w-72 -translate-x-1/2 rounded-md border border-gray-200 bg-white p-2 text-[11px] leading-relaxed text-gray-600 shadow-sm group-hover:block">
                Fullscore: nilai memakai skor benar atau salah. Partial/Range: nilai proporsional ketika jawaban hanya sebagian benar.
            </div>
        </div>
    </div>
    <select id="{{ $id }}" name="{{ $name }}"
        class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($value === $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</div>
