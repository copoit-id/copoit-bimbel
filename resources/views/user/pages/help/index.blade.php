@extends('user.layout.new-user')

@section('title', 'Bantuan')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.dashboard.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Bantuan</h1>
        <p class="text-gray-500 text-sm">Pertanyaan yang sering diajukan</p>
    </div>
</div>

<!-- FAQ Grid -->
<div class="grid gap-4">
    @forelse($faqs as $faq)
    <div x-data="{ open: false }" class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <button @click="open = !open" class="w-full p-5 flex items-center justify-between text-left hover:bg-gray-50 transition-colors">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
                    <i class="ri-question-line text-xl"></i>
                </div>
                <span class="font-medium text-gray-800">{{ $faq->question }}</span>
            </div>
            <i class="ri-arrow-down-s-line text-xl text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
        </button>
        <div x-show="open" x-collapse class="border-t border-gray-100">
            <div class="p-5 text-gray-600 leading-relaxed">
                {!! $faq->answer !!}
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-16">
        <div class="w-24 h-24 rounded-full mx-auto mb-4 flex items-center justify-center" style="background-color: {{ $primaryColor }}10">
            <i class="ri-question-mark text-4xl" style="color: {{ $primaryColor }}"></i>
        </div>
        <h3 class="font-semibold text-gray-700 mb-1">Belum ada FAQ</h3>
        <p class="text-gray-400 text-sm">Pertanyaan umum akan ditampilkan di sini.</p>
    </div>
    @endforelse
</div>

<!-- Contact Card -->
<div class="mt-8 bg-white rounded-2xl p-6 border border-gray-100">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
            <i class="ri-customer-service-2-line text-xl"></i>
        </div>
        <div class="flex-1">
            <h3 class="font-semibold text-gray-800">Masih butuh bantuan?</h3>
            <p class="text-sm text-gray-500">Tim support kami siap membantu kamu.</p>
        </div>
        <a href="mailto:{{ $clientBranding['contact_email'] ?? 'support@example.com' }}" class="px-4 py-2 rounded-xl text-white font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
            Hubungi Kami
        </a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    console.log('Help page loaded');
</script>
@endsection
