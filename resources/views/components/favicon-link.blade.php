@if (!empty($clientBranding['favicon_url'] ?? null))
    @php
        $faviconUrl = $clientBranding['favicon_url'];
        $faviconVersion = md5($faviconUrl);
        $faviconHref = $faviconUrl.(str_contains($faviconUrl, '?') ? '&' : '?').'v='.$faviconVersion;
    @endphp
    <link rel="icon" href="{{ $faviconHref }}" sizes="any">
    <link rel="shortcut icon" href="{{ $faviconHref }}">
    <link rel="apple-touch-icon" href="{{ $faviconHref }}">
@endif
