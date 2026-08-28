@props([
    'questionsUrl',
    'explanationsUrl',
    'label' => 'Unduh PDF',
])

<details class="relative shrink-0">
    <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-lg bg-primary px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-primary/90 [&::-webkit-details-marker]:hidden">
        <i class="ri-download-2-line"></i>
        {{ $label }}
        <i class="ri-arrow-down-s-line text-base"></i>
    </summary>
    <div class="absolute right-0 z-30 mt-2 w-60 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-lg">
        <a href="{{ $questionsUrl }}" class="flex items-start gap-3 px-4 py-3 text-sm text-gray-700 transition-colors hover:bg-gray-50">
            <i class="ri-file-text-line mt-0.5 text-lg text-primary"></i>
            <span><span class="block font-semibold">Soal saja</span><span class="mt-0.5 block text-xs text-gray-500">Untuk latihan atau dicetak.</span></span>
        </a>
        <a href="{{ $explanationsUrl }}" class="flex items-start gap-3 border-t border-gray-100 px-4 py-3 text-sm text-gray-700 transition-colors hover:bg-gray-50">
            <i class="ri-file-list-3-line mt-0.5 text-lg text-primary"></i>
            <span><span class="block font-semibold">Soal + pembahasan</span><span class="mt-0.5 block text-xs text-gray-500">Termasuk jawaban dan pembahasan.</span></span>
        </a>
    </div>
</details>
