@extends('admin.layout.admin')
@section('title', 'Koreksi Essay')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.dashboard') }}" title="Dashboard" />
            <x-breadcrumb-item href="{{ route('admin.essay-review.index') }}" title="Koreksi Essay" />
            <x-breadcrumb-item href="{{ route('admin.essay-review.tryout', $tryout->tryout_id) }}" title="Pilih Peserta" />
            <x-breadcrumb-item href="" title="Jawaban Essay" />
        </x-slot>
    </x-breadcrumb>
</div>

<x-page-desc title="Koreksi Essay - {{ $tryout->name }}" description="Jawaban essay manual dari {{ $user->name }}." />

@if (session('success'))
<div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
    {{ session('success') }}
</div>
@endif
@if (session('error'))
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
    {{ session('error') }}
</div>
@endif

<div class="space-y-4">
    @forelse($reviews as $review)
        @php
            $question = $review->question;
            $subtestName = optional($review->userAnswer->tryoutDetail)->type_subtest ?? '-';
        @endphp
        <div class="bg-white border border-border rounded-xl p-6">
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                    Belum Dikoreksi
                </span>
                <span class="text-sm text-gray-600">Subtest: <span class="font-medium text-gray-800">{{ strtoupper($subtestName) }}</span></span>
                @if($review->answered_at)
                    <span class="text-sm text-gray-500">Dijawab: {{ $review->answered_at->translatedFormat('d M Y H:i') }}</span>
                @endif
            </div>

            <div class="space-y-3">
                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-1">Soal:</p>
                    <div class="prose max-w-none text-gray-800">{!! $question->question_text ?? '-' !!}</div>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-1">Jawaban Peserta:</p>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-gray-800">
                        {!! nl2br(e($review->answer_text ?? '')) ?: '-' !!}
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <form action="{{ route('admin.essay-review.review', $review->user_answer_detail_id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="result" value="correct">
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700">
                        <i class="ri-check-line"></i> Benar
                    </button>
                </form>
                <form action="{{ route('admin.essay-review.review', $review->user_answer_detail_id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="result" value="incorrect">
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700">
                        <i class="ri-close-line"></i> Salah
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="bg-white border border-border rounded-xl p-8 text-center text-gray-500">
            Tidak ada jawaban essay yang perlu dikoreksi untuk peserta ini.
        </div>
    @endforelse
</div>

@endsection
