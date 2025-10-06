@props([
'type' => 'Pilihan Ganda',
'points' => 0,
'question',
'options' => [],
'discussion' => null,
'editUrl' => '#',
'deleteUrl' => '#'
])

<div data-question-card class="bg-white border border-gray-200 rounded-xl p-6 shadow flex flex-col"
    x-show="!search || (filteredQuestions.includes($el))">

    <div class="flex items-center gap-2 mb-2">
        <span
            class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-[#0B2B9A]/10 text-[#0B2B9A] border border-[#0B2B9A]/10">
            {{ $type }}
        </span>
        <span
            class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
            {{ $points }} poin
        </span>
    </div>

    <div class="font-bold text-lg text-gray-900 mb-2">
        {!! $question !!}
    </div>

    <div class="mb-2">
        <div class="font-semibold text-gray-700 mb-1">Opsi Jawaban:</div>
        <ul class="space-y-2">
            @foreach($options as $option)
            <li class="flex items-center gap-2">
                <span class="material-icons text-base text-gray-400">
                    {{ $option['correct'] ? 'check_circle' : 'radio_button_unchecked' }}
                </span>
                <span>{!! $option['text'] !!}</span>
            </li>
            @endforeach
        </ul>
    </div>

    @if($discussion)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-2">
        <div class="font-semibold text-blue-800 mb-1">Pembahasan</div>
        <div class="text-blue-900 text-sm">{!! $discussion !!}</div>
    </div>
    @endif

    <div class="flex gap-3 mt-6">
        <a href="{{ $editUrl }}"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#0B2B9A] text-white font-semibold hover:bg-[#0B2B9A]/90 transition">
            <span class="material-icons text-base">edit</span> Edit
        </a>
        <form action="{{ $deleteUrl }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus soal ini?')">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700 transition">
                <span class="material-icons text-base">delete</span> Hapus
            </button>
        </form>
    </div>
</div>