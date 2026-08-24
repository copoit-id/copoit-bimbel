@props([
    'title',
    'prompt',
    'href',
    'linkLabel',
])

<div class="text-center">
    <div class="flex justify-center">
        <img src="{{ $clientBranding['logo_url'] }}" alt="{{ $clientBranding['name'] }} Logo" class="client-brand-logo h-28 w-28 object-contain">
    </div>
    <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-gray-900">{{ $title }}</h1>
    <p class="mt-2 text-sm text-gray-600">
        {{ $prompt }}
        <a href="{{ $href }}" class="font-semibold text-primary hover:text-primary/80">{{ $linkLabel }}</a>
    </p>
</div>
