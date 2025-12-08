@if (!empty($clientBranding['favicon_url'] ?? null))
    <link rel="icon" type="image/png" href="{{ $clientBranding['favicon_url'] }}">
    <link rel="shortcut icon" type="image/png" href="{{ $clientBranding['favicon_url'] }}">
@endif
