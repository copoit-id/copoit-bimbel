{{--
    Question Card Component (Soal)
    
    Props:
    - type: string (default: 'Pilihan Ganda')
    - points: int (default: 0)
    - question: string (required)
    - options: array (default: []) - [['text' => '...', 'correct' => true]]
    - discussion: string | null
    - editUrl: string (default: '#')
    - deleteUrl: string (default: '#')
--}}

@props([
    'type' => 'Pilihan Ganda',
    'points' => 0,
    'question' => '',
    'options' => [],
    'discussion' => null,
    'editUrl' => '#',
    'deleteUrl' => '#',
])

<x-ui.card variant="default" padding="lg" class="flex flex-col" data-question-card x-show="!search || (filteredQuestions.includes($el))">
    {{-- Badges --}}
    <div class="flex items-center gap-2 mb-3">
        <x-ui.badge variant="primary" size="sm">
            {{ $type }}
        </x-ui.badge>
        <x-ui.badge variant="success" size="sm">
            {{ $points }} poin
        </x-ui.badge>
    </div>

    {{-- Question --}}
    <div class="font-bold text-lg text-gray-900 mb-3">
        {!! $question !!}
    </div>

    {{-- Options --}}
    <div class="mb-3">
        <div class="font-semibold text-gray-700 mb-2">Opsi Jawaban:</div>
        <ul class="space-y-2">
            @foreach($options as $option)
                <li class="flex items-center gap-2">
                    @if($option['correct'] ?? false)
                        <i class="ri-checkbox-circle-fill text-green"></i>
                    @else
                        <i class="ri-radio-button-line text-gray-400"></i>
                    @endif
                    <span class="text-gray-700">{!! $option['text'] !!}</span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Discussion --}}
    @if($discussion)
        <div class="bg-blue-light border border-blue-200 rounded-lg p-4 mt-2">
            <div class="font-semibold text-blue-800 mb-1">Pembahasan</div>
            <div class="text-blue-900 text-sm">{!! $discussion !!}</div>
        </div>
    @endif

    {{-- Actions --}}
    <x-slot:footer class="mt-4">
        <x-ui.button 
            :href="$editUrl" 
            variant="primary" 
            size="sm" 
            icon="ri-edit-line"
        >
            Edit
        </x-ui.button>
        
        <form action="{{ $deleteUrl }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus soal ini?')" class="inline">
            @csrf
            @method('DELETE')
            <x-ui.button 
                type="submit" 
                variant="danger" 
                size="sm" 
                icon="ri-delete-bin-line"
            >
                Hapus
            </x-ui.button>
        </form>
    </x-slot:footer>
</x-ui.card>
