@props(['fallback'])

<button
    type="button"
    data-fallback="{{ $fallback }}"
    onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.assign(this.dataset.fallback); }"
    {{ $attributes }}
>
    {{ $slot }}
</button>
