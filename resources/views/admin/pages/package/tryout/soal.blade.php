@extends('admin.layout.admin')
@section('title', 'Manajemen Soal')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            @if($package->package_id === 'standalone')
            <x-breadcrumb-item href="{{ route('admin.tryout.index') }}" title="Manajemen Tryout" />
            <x-breadcrumb-item href="" title="Soal" />
            @else
            <x-breadcrumb-item href="{{ route('admin.package.index') }}" title="Manajemen Paket" />
            <x-breadcrumb-item href="" title="Soal" />
            @endif
        </x-slot>
    </x-breadcrumb>
    <x-btn title="Tambah Soal"
        route="{{ route('admin.package.tryout.soal.create', ['package_id' => $package->package_id, 'tryout_detail_id' => $tryout->tryoutDetails->first()->tryout_detail_id]) }}"
        icon="ri-add-fill">
    </x-btn>
</div>
<div class="package-bimbel bg-white p-8 rounded-lg border border-border flex justify-center text-center">
    <x-page-desc title="Manajemen Soal - {{ $tryout->name }}">
    </x-page-desc>
</div>
<div class="mt-4 space-y-4">
    @foreach ($questions as $question)
    <div class="bg-white border border-border rounded-xl p-6 flex flex-col">
        <div class="flex items-center gap-2 mb-2">
            <span
                class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-[#0B2B9A]/10 text-[#0B2B9A] border border-[#0B2B9A]/10">
                Pilihan Ganda
            </span>
            @php
                $maxWeight = optional($question->questionOptions)->max(function($opt){
                    return is_null($opt->weight) ? 0 : (float)$opt->weight;
                });
                $displayWeight = ($maxWeight && $maxWeight > 0) ? $maxWeight : (float)($question->default_weight ?? 0);
            @endphp
            <span
                class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                {{ (float) $displayWeight }} poin
            </span>
        </div>

        <div class="font-bold text-lg text-gray-900 mb-2">
            {{ $question->question_text }}
        </div>

        <div class="mb-2">
            <div class="font-semibold text-gray-700 mb-1">Opsi Jawaban:</div>
            <ul class="space-y-2 text-gray-600">
                @foreach ($question->questionOptions as $option)
                <li class="flex items-center gap-2  {{$option->is_correct == 1 ? 'text-green' : ''}}">
                    <i
                        class="{{$option->is_correct == 1 ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line'}}"></i>
                    <span>{{ $option->option_text }}</span>
                </li>
                @endforeach
            </ul>
        </div>

        @if ($question && $question->explanation)
        <div class="bg-blue-50 border border-primary border-dashed rounded-lg p-4 mt-2">
            <div class="font-semibold text-primary mb-1">Pembahasan</div>
            <div class="text-primary">{{ $question->explanation }}</div>
        </div>
        @endif

        <div class="flex gap-3 mt-6">
            <x-btn title="Edit Soal"
                route="{{ route('admin.package.tryout.soal.edit', ['package_id' => $package->package_id, 'tryout_detail_id' => $tryout->tryoutDetails->first()->tryout_detail_id, 'question_id' => $question->question_id]) }}"
                icon="ri-pencil-fill">
            </x-btn>
            <x-btn color='red' title="Hapus Soal" route="" icon="ri-delete-bin-5-fill">
            </x-btn>
        </div>
    </div>
    @endforeach
</div>
@endsection
@section('scripts')
<script>
    console.log('Dashboard scripts loaded');
</script>
@endsection
@section('styles')
