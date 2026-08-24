<style>
    :root {
        --client-color-primary: {{ $clientBranding['primary_color'] ?? '#1C3259' }};
        --client-color-secondary: {{ $clientBranding['secondary_color'] ?? '#F3F3F3' }};
    }

    .client-brand-logo {
        object-fit: contain;
    }

    @if(($clientBranding['logo_display_mode'] ?? 'square') === 'original')
    .client-brand-logo {
        width: auto !important;
        max-width: 12rem !important;
    }
    @endif
</style>
