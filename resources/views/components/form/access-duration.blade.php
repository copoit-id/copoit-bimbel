@props([
    'label' => 'Durasi akses setelah dibeli',
    'labelId' => null,
    'value' => 1,
    'unit' => 'forever',
    'valueName' => 'access_duration_value',
    'unitName' => 'access_duration_unit',
    'id' => null,
])

<div @if($id) id="{{ $id }}" @endif class="w-full">
    <label for="{{ $valueName }}" @if($labelId) id="{{ $labelId }}" @endif class="mb-2 block text-sm font-medium text-gray-900">
        {{ $label }}
    </label>

    <div class="grid grid-cols-[minmax(0,1fr)_9rem] gap-2">
        <x-ui.input
            :name="$valueName"
            type="number"
            :value="$value"
            min="1"
            max="1200"
            placeholder="Jumlah"
            aria-label="Jumlah durasi akses"
        />

        <x-ui.input.select
            :name="$unitName"
            :value="$unit"
            aria-label="Satuan durasi akses"
        >
            <option value="forever" @selected($unit === 'forever')>Selamanya</option>
            <option value="day" @selected($unit === 'day')>Hari</option>
            <option value="week" @selected($unit === 'week')>Minggu</option>
            <option value="month" @selected($unit === 'month')>Bulan</option>
            <option value="year" @selected($unit === 'year')>Tahun</option>
        </x-ui.input.select>
    </div>
</div>
