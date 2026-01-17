@extends('user.layout.user')
@section('title', 'Event')
@section('content')
<div class="help">
    <x-page-desc title="Bantuan" description="Pertanyaan yang sering diajukan"></x-page-desc>
    <div class="grid md:grid-cols-2 mt-6">
        @forelse($faqs as $faq)
        @php $modalId = 'faq-modal-' . $loop->index; @endphp
        <div class="bg-white px-4 py-2 border border-gray-900/10 flex justify-between gap-2">
            <div class="flex items-center gap-2">
                <i class="ri-question-line text-2xl"></i>
                <p>{{ $faq->question }}</p>
            </div>
            <button data-modal-target="{{ $modalId }}" data-modal-toggle="{{ $modalId }}"
                class="bg-primary px-2 rounded-lg text-white">
                <i class="ri-eye-line"></i>
            </button>
        </div>
        @empty
        <div class="col-span-2 bg-white px-4 py-6 border border-gray-900/10 text-center text-gray-500">
            Belum ada FAQ yang tersedia.
        </div>
        @endforelse
    </div>
</div>

@foreach($faqs as $faq)
@php $modalId = 'faq-modal-' . $loop->index; @endphp
<div id="{{ $modalId }}" data-modal-backdrop="static" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow-sm ">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">
                    {{ $faq->question }}
                </h3>
                <button type="button"
                    class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center"
                    data-modal-hide="{{ $modalId }}">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <div class="p-4 md:p-5 space-y-4 max-h-72 overflow-y-auto">
                <div class="text-base leading-relaxed text-gray-500">
                    {!! $faq->answer !!}
                </div>
            </div>
            <div class="flex items-center justify-center w-full p-4 md:p-5 border-t border-gray-200 rounded-b">
                <button data-modal-hide="{{ $modalId }}" type="button"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    Kembali</button>
            </div>
        </div>
    </div>
</div>
@endforeach

@endsection
@section('scripts')
<script>
    console.log('Dashboard scripts loaded');
</script>
@endsection
@section('styles')
<style>
    /* Ensure modal backdrop is above navbar */
    [data-modal-backdrop] {
        z-index: 60 !important;
    }

    /* Ensure modal content is above backdrop */
    [data-modal-backdrop]>div {
        z-index: 65 !important;
    }

    /* Ensure navbar stays below modal */
    nav {
        z-index: 40 !important;
    }
</style>
@endsection
