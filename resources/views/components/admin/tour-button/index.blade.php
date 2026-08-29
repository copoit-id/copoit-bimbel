@props([
    'tourKey',
    'label' => 'Pelajari halaman ini',
])

@php
    $tourPermission = match ($tourKey) {
        'admin.tryout.create' => ['tryout', 'create'],
        default => null,
    };
    $canUseTour = ! $tourPermission
        || auth()->user()?->isSuperAdmin()
        || auth()->user()?->hasPermission($tourPermission[0], $tourPermission[1]);
@endphp

@if (config('client.branding.admin_tours_enabled', false) && $canUseTour)
    <button type="button" data-admin-tour-start="{{ $tourKey }}"
        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-primary/30 text-primary transition hover:bg-primary hover:text-white sm:h-auto sm:w-auto sm:gap-2 sm:rounded-lg sm:px-3 sm:py-2"
        title="{{ $label }}" aria-label="{{ $label }}">
        <i class="ri-question-line text-lg"></i>
        <span class="hidden text-sm font-semibold sm:inline">{{ $label }}</span>
    </button>
@endif
