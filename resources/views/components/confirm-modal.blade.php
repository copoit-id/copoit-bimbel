@props([
    'id' => 'confirmModal',
    'title' => 'Konfirmasi',
    'message' => 'Apakah Anda yakin?',
    'confirmText' => 'Ya, lanjutkan',
    'cancelText' => 'Batal',
    'confirmVariant' => 'danger',
])

@php
$variantClasses = match($confirmVariant) {
    'danger' => 'bg-red hover:bg-red/90 text-white border border-red/20',
    'warning' => 'bg-yellow hover:bg-yellow/90 text-white border border-yellow-200',
    default => 'bg-primary hover:bg-primary/90 text-white border border-primary/20',
};

$iconBg = match($confirmVariant) {
    'danger' => 'bg-red/10',
    'warning' => 'bg-yellow-100',
    default => 'bg-primary/10',
};

$iconColor = match($confirmVariant) {
    'danger' => 'text-red',
    'warning' => 'text-yellow-700',
    default => 'text-primary',
};
@endphp

<div id="{{ $id }}"
     data-modal-confirm
     class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/40 px-4 py-6 transition duration-300"
     style="display: none !important;">
    <div class="w-full max-w-sm rounded-2xl bg-white shadow-2xl">
        <div class="p-6 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full {{ $iconBg }}">
                @if($confirmVariant === 'danger')
                    <i class="ri-error-warning-line text-2xl {{ $iconColor }}"></i>
                @elseif($confirmVariant === 'warning')
                    <i class="ri-alert-line text-2xl {{ $iconColor }}"></i>
                @else
                    <i class="ri-question-line text-2xl {{ $iconColor }}"></i>
                @endif
            </div>
            <h3 class="mb-1.5 text-base font-semibold text-gray-900">{{ $title }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed" id="{{ $id }}_message">{{ $message }}</p>
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <button type="button"
                    data-confirm-cancel="{{ $id }}"
                    class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                {{ $cancelText }}
            </button>
            <button type="button"
                    data-confirm-action="{{ $id }}"
                    class="flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold {{ $variantClasses }} transition-colors">
                {{ $confirmText }}
            </button>
        </div>
    </div>
</div>
