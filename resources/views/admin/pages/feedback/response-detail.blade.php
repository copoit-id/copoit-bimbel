@extends('admin.layout.admin')
@section('title', 'Detail Feedback')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.feedback.index') }}" title="Feedback Tryout" />
            <x-breadcrumb-item href="{{ route('admin.feedback.responses', $tryout->tryout_id) }}" title="Respon" />
            <x-breadcrumb-item href="" title="Detail" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="Detail Feedback" description="{{ $tryout->name }} • {{ $submission->user->name ?? 'User' }}" />

<div class="bg-white p-6 rounded-lg border border-border mt-6">
    <div class="mb-4 text-sm text-gray-600">
        <span class="font-medium">Waktu:</span>
        {{ optional($submission->submitted_at)->format('d M Y H:i') ?? '-' }}
    </div>

    <div class="space-y-4">
        @foreach($submission->answers as $answer)
        <div class="border border-gray-200 rounded-lg p-4">
            <p class="font-semibold text-gray-900">{!! $answer->question->question_text ?? '-' !!}</p>
            <div class="mt-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                    Skor: {{ $answer->score }} / 5
                </span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.feedback.responses', $tryout->tryout_id) }}"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Kembali</a>
    </div>
</div>

@endsection
