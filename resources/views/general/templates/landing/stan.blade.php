<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
@php
    $landingContent = $content ?? [];
    $landingDefaults = \App\Http\Controllers\GeneralPageController::defaultLandingContent();
    $landingValue = fn (string $key, mixed $default = null) => data_get($landingContent, $key, $default);
    $landingItems = fn (string $key) => data_get($landingContent, $key, data_get($landingDefaults, $key, [])) ?: [];
    $landingAsset = function (?string $path, string $fallback): string {
        $target = $path ?: $fallback;

        if (\Illuminate\Support\Str::startsWith($target, ['http://', 'https://', '//', '/'])) {
            return $target;
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($target)) {
            return \Illuminate\Support\Facades\Storage::url($target);
        }

        return asset($target);
    };
    $documentTitle = data_get($seo, 'title') ?: $landingValue('meta.title', $clientBranding['name']);
    $documentDescription = data_get($seo, 'description')
        ?: $landingValue('hero.description', 'Persiapan terarah untuk seleksi PKN STAN.');
    $dashboardHref = auth()->check() ? route('user.dashboard.index') : route('login');
    $dashboardLabel = auth()->check() ? 'Dashboard' : 'Masuk';
    $heroLogos = $landingItems('hero.logo_stack');
    $achievements = $landingItems('achievements.items');
    $testimonials = $landingItems('testimonials.items');
    $partners = $landingItems('partners.items');
    $faqs = $landingItems('faq.items');
    $termsHref = $landingValue('footer.terms_href');
    $termsHref = $termsHref && $termsHref !== '#' ? $termsHref : route('public.terms.id');
    $privacyHref = $landingValue('footer.privacy_href');
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $documentDescription }}">
    <meta name="theme-color" content="{{ $clientBranding['primary_color'] ?? '#10233f' }}">
    <meta property="og:title" content="{{ $documentTitle }}">
    <meta property="og:description" content="{{ $documentDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $landingAsset(data_get($seo, 'image'), 'img/stan-landing-hero.webp') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <title>{{ $documentTitle }} - {{ $clientBranding['name'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('components.branding-styles')
    @include('components.favicon-link')

    <style>
        :root {
            --stan-navy: color-mix(in srgb, var(--client-color-primary, #17345c) 82%, #071526);
            --stan-primary: var(--client-color-primary, #17345c);
            --stan-gold: #d5a83f;
            --stan-cream: #f8f5ee;
            --stan-ink: #101828;
            --stan-muted: #667085;
        }

        body {
            margin: 0;
            overflow-x: hidden;
            background: #fff;
            color: var(--stan-ink);
            font-family: "Poppins", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        [data-stan-menu][hidden],
        [data-stan-modal][hidden] {
            display: none !important;
        }

        .stan-shell {
            width: min(1180px, calc(100% - 2rem));
            margin-inline: auto;
        }

        .stan-header {
            position: fixed;
            inset: 0 0 auto;
            z-index: 50;
            border-bottom: 1px solid transparent;
            transition: background-color .25s ease, border-color .25s ease, box-shadow .25s ease;
        }

        .stan-header.is-scrolled,
        .stan-header.is-open {
            border-color: rgba(16, 24, 40, .08);
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 10px 32px rgba(16, 24, 40, .06);
            backdrop-filter: blur(16px);
        }

        .stan-scroll-progress {
            position: absolute;
            right: 0;
            bottom: -1px;
            left: 0;
            height: 2px;
            overflow: hidden;
            pointer-events: none;
        }

        .stan-scroll-progress span {
            display: block;
            width: 0;
            height: 100%;
            background: linear-gradient(90deg, var(--stan-primary), var(--stan-gold));
            box-shadow: 0 0 12px rgba(213, 168, 63, .5);
            transition: width .1s linear;
        }

        .stan-hero {
            position: relative;
            min-height: 760px;
            overflow: hidden;
            padding: 9.5rem 0 6rem;
            background:
                radial-gradient(circle at 8% 15%, color-mix(in srgb, var(--stan-primary) 14%, transparent), transparent 30%),
                radial-gradient(circle at 92% 8%, rgba(213, 168, 63, .15), transparent 25%),
                linear-gradient(135deg, #fff 0%, #faf9f6 50%, #f2f5f9 100%);
        }

        .stan-hero-orb {
            position: absolute;
            border: 1px solid rgba(213, 168, 63, .2);
            border-radius: 999px;
            pointer-events: none;
        }

        .stan-hero-orb-one {
            top: 7rem;
            right: -7rem;
            width: 28rem;
            height: 28rem;
            box-shadow:
                0 0 0 4rem rgba(213, 168, 63, .025),
                0 0 0 8rem rgba(16, 35, 63, .018);
        }

        .stan-hero-orb-two {
            bottom: 4rem;
            left: -5rem;
            width: 12rem;
            height: 12rem;
            border-style: dashed;
            animation: stan-spin 32s linear infinite;
        }

        .stan-hero::before {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(16, 35, 63, .045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(16, 35, 63, .045) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: linear-gradient(to bottom, #000, transparent 88%);
            content: "";
            pointer-events: none;
        }

        .stan-hero-grid {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr);
            align-items: center;
            gap: clamp(2.5rem, 6vw, 5rem);
        }

        .stan-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            border: 1px solid color-mix(in srgb, var(--stan-primary) 20%, transparent);
            border-radius: 999px;
            background: color-mix(in srgb, var(--stan-primary) 7%, white);
            padding: .5rem .8rem;
            color: var(--stan-primary);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .stan-eyebrow-dot {
            width: .5rem;
            height: .5rem;
            border-radius: 999px;
            background: var(--stan-gold);
            box-shadow: 0 0 0 5px rgba(213, 168, 63, .15);
        }

        .stan-hero-title {
            max-width: 670px;
            margin: 1.4rem 0 1.25rem;
            color: var(--stan-navy);
            font-size: clamp(2.7rem, 4.6vw, 4.2rem);
            font-weight: 800;
            letter-spacing: -.055em;
            line-height: 1.06;
            overflow-wrap: break-word;
        }

        .stan-highlight {
            position: relative;
            color: var(--stan-primary);
        }

        .stan-highlight::after {
            position: absolute;
            right: 0;
            bottom: .02em;
            left: 0;
            height: .16em;
            border-radius: 999px;
            background: var(--stan-gold);
            content: "";
            opacity: .7;
            transform: rotate(-1.5deg);
            z-index: -1;
        }

        .stan-hero-copy {
            max-width: 610px;
            color: var(--stan-muted);
            font-size: clamp(.98rem, 1.7vw, 1.12rem);
            font-weight: 500;
            line-height: 1.85;
        }

        .stan-btn {
            display: inline-flex;
            min-height: 3.25rem;
            align-items: center;
            justify-content: center;
            gap: .65rem;
            border: 1px solid transparent;
            border-radius: .9rem;
            padding: .8rem 1.25rem;
            font-size: .9rem;
            font-weight: 700;
            text-decoration: none;
            transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease;
        }

        .stan-btn:hover {
            transform: translateY(-2px);
        }

        .stan-btn-primary {
            background: var(--stan-primary);
            box-shadow: 0 12px 28px color-mix(in srgb, var(--stan-primary) 24%, transparent);
            color: #fff;
        }

        .stan-btn-primary:hover {
            background: var(--stan-navy);
        }

        .stan-btn-secondary {
            border-color: #d8dee8;
            background: rgba(255, 255, 255, .82);
            color: var(--stan-navy);
        }

        .stan-visual {
            position: relative;
            min-width: 0;
            isolation: isolate;
        }

        .stan-visual::before {
            position: absolute;
            inset: -1.2rem 1.8rem 1.8rem -1.2rem;
            border: 1px solid rgba(213, 168, 63, .32);
            border-radius: 2.4rem;
            content: "";
            transform: rotate(-2.5deg);
            z-index: -1;
        }

        .stan-visual-frame {
            position: relative;
            overflow: hidden;
            aspect-ratio: 1.14;
            border: 8px solid rgba(255, 255, 255, .85);
            border-radius: 2rem;
            background: #e8edf3;
            box-shadow: 0 28px 80px rgba(16, 35, 63, .17);
        }

        .stan-visual-frame img,
        .stan-visual-frame video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .stan-score-card {
            position: absolute;
            right: -1.25rem;
            bottom: 2rem;
            width: min(245px, 48%);
            border: 1px solid rgba(255, 255, 255, .65);
            border-radius: 1.15rem;
            background: rgba(255, 255, 255, .92);
            padding: 1rem;
            box-shadow: 0 20px 45px rgba(16, 35, 63, .18);
            backdrop-filter: blur(14px);
            animation: stan-float 5.5s ease-in-out infinite;
        }

        .stan-live-card {
            position: absolute;
            top: 2rem;
            left: -2.2rem;
            display: flex;
            align-items: center;
            gap: .7rem;
            border: 1px solid rgba(255, 255, 255, .72);
            border-radius: 1rem;
            background: rgba(255, 255, 255, .93);
            padding: .75rem .9rem;
            box-shadow: 0 18px 40px rgba(16, 35, 63, .15);
            backdrop-filter: blur(14px);
            animation: stan-float 6.5s ease-in-out infinite reverse;
            z-index: 2;
        }

        .stan-live-pulse {
            position: relative;
            display: grid;
            width: 2rem;
            height: 2rem;
            place-items: center;
            border-radius: .7rem;
            background: #fff3f1;
            color: #dc4b3e;
        }

        .stan-live-pulse::after {
            position: absolute;
            inset: -.25rem;
            border: 1px solid rgba(220, 75, 62, .25);
            border-radius: .85rem;
            content: "";
            animation: stan-pulse 2s ease-out infinite;
        }

        .stan-score-bar {
            height: .42rem;
            overflow: hidden;
            border-radius: 999px;
            background: #e9edf2;
        }

        .stan-score-bar > span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--stan-primary), var(--stan-gold));
        }

        .stan-section {
            padding: clamp(4.75rem, 9vw, 7.5rem) 0;
        }

        .stan-section-soft {
            background: #f7f8fa;
        }

        .stan-section-dark {
            overflow: hidden;
            background:
                radial-gradient(circle at 75% 20%, color-mix(in srgb, var(--stan-primary) 60%, transparent), transparent 28%),
                var(--stan-navy);
            color: #fff;
        }

        .stan-marquee {
            overflow: hidden;
            border-block: 1px solid rgba(16, 35, 63, .08);
            background: var(--stan-navy);
            color: #fff;
        }

        .stan-marquee-track {
            display: flex;
            width: max-content;
            animation: stan-marquee 30s linear infinite;
        }

        .stan-marquee-group {
            display: flex;
            flex: none;
            align-items: center;
        }

        .stan-marquee-item {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: 1rem 1.6rem;
            color: rgba(255, 255, 255, .76);
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .stan-marquee-item i {
            color: var(--stan-gold);
            font-size: 1rem;
        }

        .stan-kicker {
            color: var(--stan-primary);
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        .stan-heading {
            margin: .75rem 0 0;
            color: var(--stan-navy);
            font-size: clamp(2rem, 4vw, 3.45rem);
            font-weight: 800;
            letter-spacing: -.045em;
            line-height: 1.13;
        }

        .stan-card {
            border: 1px solid #e4e7ec;
            border-radius: 1.4rem;
            background: #fff;
            box-shadow: 0 10px 35px rgba(16, 24, 40, .04);
        }

        .stan-bento {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 1.25rem;
        }

        .stan-feature-card {
            position: relative;
            min-height: 17.5rem;
            overflow: hidden;
            padding: 1.65rem;
            transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
        }

        .stan-feature-card:nth-child(1),
        .stan-feature-card:nth-child(4) {
            grid-column: span 7;
        }

        .stan-feature-card:nth-child(2),
        .stan-feature-card:nth-child(3) {
            grid-column: span 5;
        }

        .stan-feature-card::after {
            position: absolute;
            right: -2rem;
            bottom: -3rem;
            width: 10rem;
            height: 10rem;
            border: 1px solid color-mix(in srgb, var(--stan-primary) 12%, transparent);
            border-radius: 999px;
            box-shadow: 0 0 0 2rem color-mix(in srgb, var(--stan-primary) 3%, transparent);
            content: "";
            transition: transform .35s ease;
        }

        .stan-feature-card:nth-child(1) {
            background:
                radial-gradient(circle at 88% 15%, rgba(213, 168, 63, .13), transparent 28%),
                #fff;
        }

        .stan-feature-card:nth-child(4) {
            background:
                radial-gradient(circle at 90% 20%, color-mix(in srgb, var(--stan-primary) 10%, transparent), transparent 30%),
                #fff;
        }

        .stan-feature-card:hover {
            border-color: color-mix(in srgb, var(--stan-primary) 35%, #e4e7ec);
            box-shadow: 0 18px 42px rgba(16, 35, 63, .09);
            transform: translateY(-5px);
        }

        .stan-feature-card:hover::after {
            transform: translate(-.7rem, -.7rem) scale(1.08);
        }

        .stan-feature-number {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            color: #e9edf2;
            font-size: 2.6rem;
            font-weight: 900;
            letter-spacing: -.08em;
            line-height: 1;
        }

        .stan-icon-box {
            display: grid;
            width: 3rem;
            height: 3rem;
            place-items: center;
            border-radius: .9rem;
            background: color-mix(in srgb, var(--stan-primary) 9%, white);
            color: var(--stan-primary);
            font-size: 1.35rem;
        }

        .stan-package {
            position: relative;
            display: flex;
            min-height: 100%;
            flex-direction: column;
            overflow: hidden;
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .stan-package:hover {
            box-shadow: 0 24px 58px rgba(16, 35, 63, .12);
            transform: translateY(-6px);
        }

        .stan-package-media {
            height: 12rem;
            overflow: hidden;
            background:
                linear-gradient(135deg, color-mix(in srgb, var(--stan-primary) 92%, black), var(--stan-primary));
        }

        .stan-package-media img,
        .stan-package-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .55s ease;
        }

        .stan-package:hover .stan-package-media img,
        .stan-package:hover .stan-package-media video {
            transform: scale(1.055);
        }

        .stan-package-media-empty {
            display: grid;
            height: 100%;
            place-items: center;
            color: rgba(255, 255, 255, .88);
            font-size: 3rem;
        }

        .stan-quote::before {
            display: block;
            margin-bottom: .7rem;
            color: var(--stan-gold);
            content: "“";
            font-family: Georgia, serif;
            font-size: 4rem;
            line-height: .7;
        }

        .stan-roadmap {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at 5% 15%, rgba(213, 168, 63, .18), transparent 25%),
                radial-gradient(circle at 95% 90%, color-mix(in srgb, var(--stan-primary) 62%, transparent), transparent 30%),
                var(--stan-navy);
            color: #fff;
        }

        .stan-roadmap::before {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, .12) 1px, transparent 1px);
            background-size: 22px 22px;
            content: "";
            mask-image: linear-gradient(90deg, #000, transparent 55%);
            pointer-events: none;
        }

        .stan-roadmap-grid {
            position: relative;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .stan-roadmap-line {
            position: absolute;
            top: 2.35rem;
            right: 12.5%;
            left: 12.5%;
            height: 1px;
            background: linear-gradient(90deg, var(--stan-gold), rgba(255, 255, 255, .14));
        }

        .stan-roadmap-step {
            position: relative;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 1.3rem;
            background: rgba(255, 255, 255, .055);
            padding: 1.15rem;
            backdrop-filter: blur(8px);
            transition: background-color .25s ease, transform .25s ease, border-color .25s ease;
        }

        .stan-roadmap-step:hover {
            border-color: rgba(213, 168, 63, .4);
            background: rgba(255, 255, 255, .09);
            transform: translateY(-5px);
        }

        .stan-roadmap-index {
            position: relative;
            display: grid;
            width: 2.4rem;
            height: 2.4rem;
            place-items: center;
            border: 4px solid var(--stan-navy);
            border-radius: 999px;
            background: var(--stan-gold);
            color: var(--stan-navy);
            font-size: .7rem;
            font-weight: 900;
            box-shadow: 0 0 0 1px rgba(213, 168, 63, .45);
            z-index: 1;
        }

        .stan-cta {
            position: relative;
            isolation: isolate;
            background:
                radial-gradient(circle at 8% 15%, rgba(255, 255, 255, .17), transparent 22%),
                radial-gradient(circle at 90% 80%, rgba(213, 168, 63, .32), transparent 28%),
                linear-gradient(135deg, var(--stan-primary), var(--stan-navy));
        }

        .stan-cta::before,
        .stan-cta::after {
            position: absolute;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 999px;
            content: "";
            pointer-events: none;
            z-index: -1;
        }

        .stan-cta::before {
            top: -7rem;
            left: -5rem;
            width: 18rem;
            height: 18rem;
        }

        .stan-cta::after {
            right: -3rem;
            bottom: -8rem;
            width: 20rem;
            height: 20rem;
        }

        .stan-faq-button[aria-expanded="true"] .stan-faq-icon {
            background: var(--stan-primary);
            color: #fff;
            transform: rotate(45deg);
        }

        .stan-faq-answer {
            overflow: hidden;
        }

        .stan-ai-section {
            position: relative;
            isolation: isolate;
            background:
                radial-gradient(circle at 10% 16%, color-mix(in srgb, var(--stan-primary) 58%, transparent), transparent 28%),
                radial-gradient(circle at 88% 82%, rgba(213, 168, 63, .24), transparent 27%),
                linear-gradient(135deg, #091426 0%, var(--stan-navy) 54%, #121b31 100%);
        }

        .stan-ai-section::before {
            position: absolute;
            inset: 0;
            z-index: -1;
            background-image:
                linear-gradient(rgba(255, 255, 255, .035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .035) 1px, transparent 1px);
            background-size: 2.5rem 2.5rem;
            mask-image: linear-gradient(to bottom, transparent, black 14%, black 86%, transparent);
            content: "";
        }

        .stan-ai-orb {
            position: absolute;
            z-index: -1;
            border: 1px solid rgba(255, 255, 255, .11);
            border-radius: 999px;
            pointer-events: none;
        }

        .stan-ai-orb-one {
            top: -10rem;
            right: -8rem;
            width: 31rem;
            height: 31rem;
            box-shadow: inset 0 0 90px rgba(213, 168, 63, .08);
            animation: stan-ai-orbit 18s linear infinite;
        }

        .stan-ai-orb-two {
            bottom: -14rem;
            left: -10rem;
            width: 29rem;
            height: 29rem;
            border-color: color-mix(in srgb, var(--stan-primary) 45%, transparent);
            animation: stan-ai-orbit 24s linear reverse infinite;
        }

        .stan-ai-feature {
            position: relative;
            overflow: hidden;
        }

        .stan-ai-feature::after {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 3px;
            background: linear-gradient(to bottom, #fcd34d, color-mix(in srgb, var(--stan-primary) 80%, #fff));
            content: "";
        }

        .stan-ai-workspace {
            position: relative;
        }

        .stan-ai-workspace::before {
            position: absolute;
            inset: -1.25rem;
            z-index: -1;
            border-radius: 2.6rem;
            background: radial-gradient(circle, color-mix(in srgb, var(--stan-primary) 45%, transparent), transparent 67%);
            filter: blur(20px);
            content: "";
        }

        .stan-ai-scan {
            position: relative;
            overflow: hidden;
        }

        .stan-ai-scan::after {
            position: absolute;
            top: 0;
            bottom: 0;
            left: -35%;
            width: 28%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .58), transparent);
            transform: skewX(-24deg);
            animation: stan-ai-scan 5s ease-in-out infinite;
            content: "";
        }

        .stan-reveal {
            opacity: 0;
            transform: translateY(22px);
            transition: opacity .6s ease, transform .6s ease;
        }

        .stan-reveal.is-visible {
            opacity: 1;
            transform: none;
        }

        @keyframes stan-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        @keyframes stan-pulse {
            0% { opacity: .8; transform: scale(.8); }
            100% { opacity: 0; transform: scale(1.25); }
        }

        @keyframes stan-spin {
            to { transform: rotate(360deg); }
        }

        @keyframes stan-marquee {
            to { transform: translateX(-50%); }
        }

        @keyframes stan-ai-orbit {
            to { transform: rotate(360deg); }
        }

        @keyframes stan-ai-scan {
            0%, 18% { left: -35%; }
            55%, 100% { left: 112%; }
        }

        @media (max-width: 960px) {
            .stan-hero {
                min-height: auto;
                padding-top: 8.5rem;
            }

            .stan-hero-grid {
                grid-template-columns: 1fr;
            }

            .stan-hero-copy-wrap {
                text-align: center;
            }

            .stan-hero-title,
            .stan-hero-copy {
                margin-inline: auto;
            }

            .stan-score-card {
                right: .75rem;
            }

            .stan-live-card {
                left: .75rem;
            }

            .stan-feature-card:nth-child(n) {
                grid-column: span 6;
            }

            .stan-roadmap-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .stan-roadmap-line {
                display: none;
            }
        }

        @media (max-width: 640px) {
            .stan-shell {
                width: min(1180px, calc(100% - 1.25rem));
            }

            .stan-hero {
                padding-bottom: 4.5rem;
            }

            .stan-hero-title {
                width: 100%;
                font-size: clamp(2.15rem, 10vw, 2.8rem);
                letter-spacing: -.045em;
            }

            .stan-visual-frame {
                aspect-ratio: .94;
                border-width: 5px;
                border-radius: 1.4rem;
            }

            .stan-score-card {
                bottom: .75rem;
                width: 52%;
                padding: .75rem;
            }

            .stan-live-card {
                top: .75rem;
                left: .75rem;
                padding: .6rem .7rem;
            }

            .stan-live-card p:last-child {
                display: none;
            }

            .stan-bento,
            .stan-roadmap-grid {
                grid-template-columns: 1fr;
            }

            .stan-feature-card:nth-child(n) {
                grid-column: 1;
                min-height: 15rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            .stan-reveal {
                opacity: 1;
                transform: none;
                transition: none;
            }

            *, *::before, *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>

<body>
    <a href="#main-content" class="fixed left-3 top-3 z-[100] -translate-y-24 rounded-lg bg-white px-4 py-3 text-sm font-bold text-slate-900 shadow-xl focus:translate-y-0">
        Lewati ke konten
    </a>

    <header class="stan-header" data-stan-header>
        <div class="stan-shell flex h-[76px] items-center justify-between gap-4">
            <a href="{{ route('landing') }}" class="flex min-w-0 items-center gap-3" aria-label="{{ $clientBranding['name'] }} - Beranda">
                <img src="{{ $clientBranding['logo_url'] }}" alt="" class="h-10 w-10 rounded-xl bg-white object-contain p-1 shadow-sm">
                <div class="min-w-0">
                    <p class="truncate text-[15px] font-extrabold leading-tight text-slate-900">{{ $clientBranding['name'] }}</p>
                    <p class="mt-0.5 hidden text-[10px] font-bold uppercase tracking-[.16em] text-slate-500 sm:block">PKN STAN Preparation</p>
                </div>
            </a>

            <nav class="hidden items-center gap-7 text-[13px] font-semibold text-slate-600 lg:flex" aria-label="Navigasi utama">
                <a href="#keunggulan" class="transition-colors hover:text-primary">Keunggulan</a>
                <a href="#roadmap" class="transition-colors hover:text-primary">Roadmap</a>
                <a href="#ai-learning" class="transition-colors hover:text-primary">AI Learning</a>
                <a href="#program" class="transition-colors hover:text-primary">Program</a>
                <a href="#testimoni" class="transition-colors hover:text-primary">Testimoni</a>
                <a href="#faq" class="transition-colors hover:text-primary">FAQ</a>
            </nav>

            <div class="hidden items-center gap-2 lg:flex">
                <a href="{{ $dashboardHref }}" class="stan-btn min-h-[2.7rem] border-slate-200 bg-white px-4 text-slate-700">
                    {{ $dashboardLabel }}
                </a>
                @guest
                    <a href="{{ route('register') }}" class="stan-btn stan-btn-primary min-h-[2.7rem] px-4">
                        Daftar Gratis
                    </a>
                @endguest
            </div>

            <button type="button" class="grid h-11 w-11 place-items-center rounded-xl border border-slate-200 bg-white text-xl text-slate-800 lg:hidden"
                aria-label="Buka menu" aria-expanded="false" data-stan-menu-toggle>
                <i class="ri-menu-3-line" data-stan-menu-icon></i>
            </button>
        </div>

        <div class="border-t border-slate-100 bg-white px-4 pb-5 pt-3 shadow-xl lg:hidden" data-stan-menu hidden>
            <nav class="mx-auto flex max-w-6xl flex-col gap-1 text-sm font-semibold text-slate-700" aria-label="Navigasi seluler">
                <a href="#keunggulan" class="rounded-xl px-3 py-3 hover:bg-slate-50">Keunggulan</a>
                <a href="#roadmap" class="rounded-xl px-3 py-3 hover:bg-slate-50">Roadmap</a>
                <a href="#ai-learning" class="rounded-xl px-3 py-3 hover:bg-slate-50">AI Learning</a>
                <a href="#program" class="rounded-xl px-3 py-3 hover:bg-slate-50">Program</a>
                <a href="#testimoni" class="rounded-xl px-3 py-3 hover:bg-slate-50">Testimoni</a>
                <a href="#faq" class="rounded-xl px-3 py-3 hover:bg-slate-50">FAQ</a>
                <div class="mt-2 grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
                    <a href="{{ $dashboardHref }}" class="stan-btn stan-btn-secondary min-h-[2.8rem]">{{ $dashboardLabel }}</a>
                    @guest
                        <a href="{{ route('register') }}" class="stan-btn stan-btn-primary min-h-[2.8rem]">Daftar Gratis</a>
                    @else
                        <a href="#program" class="stan-btn stan-btn-primary min-h-[2.8rem]">Lihat Program</a>
                    @endguest
                </div>
            </nav>
        </div>
        <div class="stan-scroll-progress" aria-hidden="true"><span data-stan-scroll-progress></span></div>
    </header>

    <main id="main-content">
        <section class="stan-hero">
            <span class="stan-hero-orb stan-hero-orb-one" aria-hidden="true"></span>
            <span class="stan-hero-orb stan-hero-orb-two" aria-hidden="true"></span>
            <div class="stan-shell stan-hero-grid">
                <div class="stan-hero-copy-wrap stan-reveal">
                    <span class="stan-eyebrow">
                        <span class="stan-eyebrow-dot"></span>
                        {{ $landingValue('hero.badge', 'Persiapan PKN STAN 2026') }}
                    </span>
                    <h1 class="stan-hero-title">{!! $landingValue('hero.title_html', data_get($landingDefaults, 'hero.title_html')) !!}</h1>
                    <p class="stan-hero-copy">{{ $landingValue('hero.description', data_get($landingDefaults, 'hero.description')) }}</p>

                    <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row lg:justify-start">
                        <a href="{{ $landingValue('hero.primary_cta.href', route('register')) }}" class="stan-btn stan-btn-primary">
                            {{ $landingValue('hero.primary_cta.label', 'Mulai Persiapan') }}
                            <i class="ri-arrow-right-line text-lg"></i>
                        </a>
                        <a href="{{ $landingValue('hero.secondary_cta.href', '#program') }}" class="stan-btn stan-btn-secondary"
                            @if(\Illuminate\Support\Str::startsWith($landingValue('hero.secondary_cta.href', ''), ['http://', 'https://'])) target="_blank" rel="noopener noreferrer" @endif>
                            <i class="ri-whatsapp-line text-lg text-emerald-600"></i>
                            {{ $landingValue('hero.secondary_cta.label', 'Konsultasi Gratis') }}
                        </a>
                    </div>

                    <div class="mt-9 flex flex-col items-center gap-4 border-t border-slate-200/80 pt-6 sm:flex-row lg:items-start">
                        @if(count($heroLogos) > 0)
                            <div class="flex -space-x-2" aria-hidden="true">
                                @foreach(array_slice($heroLogos, 0, 4) as $logo)
                                    <img src="{{ $landingAsset(data_get($logo, 'src'), 'img/student_rian.png') }}" alt=""
                                        class="h-9 w-9 rounded-full border-2 border-white bg-white object-cover shadow-sm">
                                @endforeach
                            </div>
                        @endif
                        <p class="max-w-md text-xs font-medium leading-relaxed text-slate-500">
                            {!! $landingValue('hero.social_proof_html', data_get($landingDefaults, 'hero.social_proof_html')) !!}
                        </p>
                    </div>
                </div>

                <div class="stan-visual stan-reveal">
                    <div class="stan-visual-frame">
                        <img src="{{ $landingAsset($landingValue('hero.image'), 'img/stan-landing-hero.webp') }}"
                            alt="{{ $landingValue('hero.image_alt', 'Siswa mempersiapkan seleksi PKN STAN') }}"
                            width="1717" height="916" fetchpriority="high">
                    </div>
                    <div class="stan-live-card" aria-label="Fitur kelas interaktif">
                        <span class="stan-live-pulse"><i class="ri-live-line"></i></span>
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-[.1em] text-slate-900">Kelas interaktif</p>
                            <p class="mt-0.5 text-[9px] font-semibold text-slate-400">Belajar & tanya langsung</p>
                        </div>
                    </div>
                    <div class="stan-score-card" aria-label="Contoh ringkasan progres belajar">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <div>
                                <p class="text-[9px] font-extrabold uppercase tracking-[.13em] text-slate-400">Progress latihan</p>
                                <p class="mt-1 text-sm font-extrabold text-slate-900">Makin konsisten</p>
                            </div>
                            <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-50 text-emerald-600">
                                <i class="ri-line-chart-line"></i>
                            </span>
                        </div>
                        <div class="space-y-2.5">
                            @foreach([['TIU', 84], ['TWK', 72], ['TKP', 90]] as [$label, $score])
                                <div>
                                    <div class="mb-1 flex justify-between text-[9px] font-bold text-slate-500">
                                        <span>{{ $label }}</span>
                                        <span>{{ $score }}%</span>
                                    </div>
                                    <div class="stan-score-bar"><span style="width: {{ $score }}%"></span></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="stan-marquee" aria-label="Fokus persiapan seleksi">
            <div class="stan-marquee-track">
                @foreach([1, 2] as $marqueeGroup)
                    <div class="stan-marquee-group" @if($marqueeGroup === 2) aria-hidden="true" @endif>
                        @foreach([
                            ['ri-brain-line', 'Penalaran numerik'],
                            ['ri-book-2-line', 'Wawasan kebangsaan'],
                            ['ri-user-heart-line', 'Karakteristik pribadi'],
                            ['ri-timer-flash-line', 'Simulasi CAT'],
                            ['ri-bar-chart-grouped-line', 'Analisis nilai'],
                            ['ri-discuss-line', 'Diskusi mentor'],
                        ] as $marqueeItem)
                            <span class="stan-marquee-item"><i class="{{ $marqueeItem[0] }}"></i>{{ $marqueeItem[1] }}</span>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        @if(count($achievements) > 0)
            <section class="border-y border-slate-100 bg-white py-8" aria-label="Fokus persiapan">
                <div class="stan-shell grid gap-3 sm:grid-cols-3">
                    @foreach(array_slice($achievements, 0, 3) as $achievement)
                        <div class="flex items-center gap-4 rounded-2xl px-4 py-3">
                            <span class="grid h-11 min-w-11 place-items-center rounded-xl bg-slate-100 text-xs font-black text-primary">
                                {{ data_get($achievement, 'value') }}
                            </span>
                            <div>
                                <p class="text-sm font-bold text-slate-900">{{ data_get($achievement, 'label') }}</p>
                                <p class="mt-0.5 line-clamp-1 text-xs text-slate-500">{{ data_get($achievement, 'description') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section id="keunggulan" class="stan-section bg-white">
            <div class="stan-shell">
                <div class="mx-auto max-w-3xl text-center stan-reveal">
                    <span class="stan-kicker">Sistem Belajar</span>
                    <h2 class="stan-heading">Bukan sekadar banyak soal.<br>Belajar harus punya arah.</h2>
                    <p class="mx-auto mt-5 max-w-2xl text-sm font-medium leading-7 text-slate-500 sm:text-base">
                        Setiap aktivitas dirancang agar kamu tahu apa yang sudah kuat, apa yang harus diperbaiki, dan langkah berikutnya.
                    </p>
                </div>

                <div class="stan-bento mt-12">
                    @foreach([
                        ['ri-computer-line', 'Tryout rasa ujian', 'Simulasi CAT dengan timer membantu kamu membangun fokus dan tempo pengerjaan.'],
                        ['ri-pie-chart-2-line', 'Analisis progres', 'Baca hasil per materi dan gunakan datanya untuk menentukan prioritas belajar.'],
                        ['ri-book-open-line', 'Pembahasan runtut', 'Pahami cara berpikir di balik jawaban, bukan sekadar menghafal opsi benar.'],
                        ['ri-team-line', 'Support system', 'Tetap konsisten bersama mentor dan komunitas peserta seperjuangan.'],
                    ] as $featureIndex => $feature)
                        <article class="stan-card stan-feature-card stan-reveal">
                            <span class="stan-feature-number">0{{ $featureIndex + 1 }}</span>
                            <span class="stan-icon-box"><i class="{{ $feature[0] }}"></i></span>
                            <div class="relative z-[1] mt-12 max-w-md">
                                <h3 class="text-lg font-extrabold text-slate-900">{{ $feature[1] }}</h3>
                                <p class="mt-3 text-sm font-medium leading-7 text-slate-500">{{ $feature[2] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-16 overflow-hidden rounded-[2rem] bg-[var(--stan-navy)] p-5 text-white shadow-2xl sm:p-8 lg:p-10 stan-reveal">
                    <div class="grid items-center gap-10 lg:grid-cols-[.8fr_1.2fr]">
                        <div>
                            <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[.14em] text-amber-300">Dashboard Belajar</span>
                            <h3 class="mt-5 text-2xl font-extrabold leading-tight sm:text-3xl">Semua progresmu dalam satu tempat.</h3>
                            <p class="mt-4 text-sm font-medium leading-7 text-slate-300">Akses paket, jadwal, materi, tryout, riwayat nilai, dan pembahasan tanpa alur yang membingungkan.</p>
                            <ul class="mt-6 space-y-3 text-sm font-semibold text-slate-200">
                                <li class="flex gap-3"><i class="ri-checkbox-circle-fill text-amber-400"></i> Riwayat latihan tersimpan rapi</li>
                                <li class="flex gap-3"><i class="ri-checkbox-circle-fill text-amber-400"></i> Bisa diakses dari HP maupun laptop</li>
                                <li class="flex gap-3"><i class="ri-checkbox-circle-fill text-amber-400"></i> Materi mengikuti fasilitas paket</li>
                            </ul>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white p-4 text-slate-900 shadow-2xl sm:p-5">
                            <div class="mb-5 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ringkasan belajar</p>
                                    <p class="mt-1 text-base font-extrabold">Halo, Pejuang STAN!</p>
                                </div>
                                <span class="grid h-10 w-10 place-items-center rounded-xl bg-slate-100 text-primary"><i class="ri-user-smile-line"></i></span>
                            </div>
                            <div class="grid grid-cols-3 gap-2.5">
                                @foreach([['ri-file-list-3-line', 'Tryout', '6 aktif'], ['ri-time-line', 'Belajar', '12 jam'], ['ri-award-line', 'Target', 'Terukur']] as $stat)
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <i class="{{ $stat[0] }} text-lg text-primary"></i>
                                        <p class="mt-3 text-[9px] font-bold uppercase tracking-wide text-slate-400">{{ $stat[1] }}</p>
                                        <p class="mt-1 text-xs font-extrabold text-slate-800">{{ $stat[2] }}</p>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3 rounded-xl border border-slate-100 p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-extrabold">Target pekan ini</p>
                                        <p class="mt-1 text-[10px] text-slate-400">4 dari 5 latihan selesai</p>
                                    </div>
                                    <span class="text-sm font-black text-primary">80%</span>
                                </div>
                                <div class="stan-score-bar mt-3 h-2"><span style="width: 80%"></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="roadmap" class="stan-roadmap stan-section">
            <div class="stan-shell relative">
                <div class="grid items-end gap-8 lg:grid-cols-[1fr_.75fr] stan-reveal">
                    <div class="max-w-3xl">
                        <span class="inline-flex rounded-full bg-white/10 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-[.14em] text-amber-300">Roadmap Persiapan</span>
                        <h2 class="mt-5 text-3xl font-extrabold leading-tight sm:text-4xl lg:text-5xl">Dari “mulai dari mana?”<br>jadi siap menghadapi ujian.</h2>
                    </div>
                    <p class="text-sm font-medium leading-7 text-slate-300">Alur belajar dibuat sederhana supaya setiap pekan punya fokus, target, dan evaluasi yang jelas.</p>
                </div>

                <div class="stan-roadmap-grid mt-12">
                    <span class="stan-roadmap-line" aria-hidden="true"></span>
                    @foreach([
                        ['ri-focus-2-line', 'Ukur kemampuan', 'Mulai dari tryout diagnostik untuk membaca posisi awalmu.'],
                        ['ri-route-line', 'Susun prioritas', 'Fokuskan latihan pada materi yang paling perlu dikuatkan.'],
                        ['ri-repeat-2-line', 'Latihan konsisten', 'Jalankan drilling, kelas, dan pembahasan secara bertahap.'],
                        ['ri-flag-2-line', 'Simulasi & evaluasi', 'Uji strategi, tempo, lalu perbaiki sebelum hari seleksi.'],
                    ] as $stepIndex => $step)
                        <article class="stan-roadmap-step stan-reveal">
                            <span class="stan-roadmap-index">0{{ $stepIndex + 1 }}</span>
                            <i class="{{ $step[0] }} mt-8 block text-2xl text-amber-300"></i>
                            <h3 class="mt-4 text-base font-extrabold">{{ $step[1] }}</h3>
                            <p class="mt-3 text-xs font-medium leading-6 text-slate-400">{{ $step[2] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="ai-learning" class="stan-ai-section overflow-hidden py-20 text-white sm:py-28">
            <span class="stan-ai-orb stan-ai-orb-one" aria-hidden="true"></span>
            <span class="stan-ai-orb stan-ai-orb-two" aria-hidden="true"></span>
            <div class="stan-shell relative">
                <div class="mx-auto max-w-3xl text-center stan-reveal">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3.5 py-2 text-[10px] font-extrabold uppercase tracking-[.16em] text-amber-200">
                        <i class="ri-sparkling-2-line text-sm"></i>
                        AI Learning Tools
                    </span>
                    <h2 class="mt-5 text-3xl font-black leading-tight tracking-tight text-white sm:text-4xl">
                        Belajar lebih cerdas, bukan sekadar lebih lama.
                    </h2>
                    <p class="mx-auto mt-4 max-w-2xl text-sm font-medium leading-7 text-slate-300 sm:text-base">
                        Ubah materi dan soal seleksi STAN menjadi catatan, flashcard, serta latihan yang membantu kamu memahami pola soal dengan lebih terarah.
                    </p>
                    <div class="mt-6 flex flex-wrap justify-center gap-2 text-[10px] font-extrabold uppercase tracking-[.12em] text-slate-300">
                        <span class="rounded-full border border-white/10 bg-white/[.06] px-3 py-2"><i class="ri-check-line mr-1 text-amber-300"></i> Catatan cerdas</span>
                        <span class="rounded-full border border-white/10 bg-white/[.06] px-3 py-2"><i class="ri-check-line mr-1 text-amber-300"></i> Flashcard aktif</span>
                        <span class="rounded-full border border-white/10 bg-white/[.06] px-3 py-2"><i class="ri-check-line mr-1 text-amber-300"></i> Soal serupa</span>
                    </div>
                </div>

                <div class="mt-12 grid items-center gap-10 lg:grid-cols-[.9fr_1.1fr] lg:gap-14">
                    <div class="space-y-4 stan-reveal">
                        @foreach([
                            ['ri-sticky-note-line', 'Ringkas konsep penting', 'Jadikan materi panjang sebagai catatan belajar yang runtut dan mudah diulang.'],
                            ['ri-stack-line', 'Latih ingatan aktif', 'Buat flashcard dari rumus, istilah, atau pola soal yang perlu kamu kuasai.'],
                            ['ri-file-add-line', 'Perbanyak latihan terarah', 'Dapatkan soal serupa untuk menguji pemahaman sebelum tryout berikutnya.'],
                        ] as $aiFeature)
                            <article class="stan-ai-feature flex gap-4 rounded-3xl border border-white/10 bg-white/[.07] p-5 pl-6 backdrop-blur-sm transition hover:-translate-y-0.5 hover:border-amber-300/30 hover:bg-white/[.1]">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-amber-400/15 text-xl text-amber-300">
                                    <i class="{{ $aiFeature[0] }}"></i>
                                </span>
                                <div>
                                    <h3 class="font-extrabold text-white">{{ $aiFeature[1] }}</h3>
                                    <p class="mt-1 text-xs font-medium leading-6 text-slate-300">{{ $aiFeature[2] }}</p>
                                </div>
                            </article>
                        @endforeach

                        <div class="flex flex-col gap-3 pt-3 sm:flex-row">
                            <a href="{{ route('user.ai-learning.index', ['tool' => 'note']) }}" class="stan-btn min-h-[3.25rem] bg-amber-400 px-6 text-[var(--stan-navy)] shadow-xl shadow-black/20 hover:bg-amber-300">
                                Coba AI Learning <i class="ri-arrow-right-line"></i>
                            </a>
                            <a href="{{ route('ai-learning-tools') }}" class="stan-btn min-h-[3.25rem] border-white/20 bg-white/10 px-6 text-white hover:bg-white/20">
                                Lihat cara kerjanya <i class="ri-external-link-line"></i>
                            </a>
                        </div>
                    </div>

                    <div class="stan-ai-workspace relative mx-auto w-full max-w-xl stan-reveal">
                        <div class="overflow-hidden rounded-[2rem] border border-white/15 bg-white p-3 shadow-[0_28px_80px_rgba(0,0,0,.35)] sm:p-5">
                            <div class="rounded-[1.4rem] bg-slate-50 p-4 text-slate-900 sm:p-5">
                                <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-[var(--stan-primary)] text-xl text-white shadow-lg shadow-slate-300"><i class="ri-sparkling-2-line"></i></span>
                                        <div>
                                            <p class="text-sm font-extrabold">AI Study Station</p>
                                            <p class="text-[10px] font-medium text-slate-400">Ruang belajar personalmu</p>
                                        </div>
                                    </div>
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-extrabold text-emerald-700">Siap belajar</span>
                                </div>
                                <div class="mt-5 grid grid-cols-[auto_1fr] gap-3">
                                    <div class="flex flex-col items-center">
                                        <span class="grid h-9 w-9 place-items-center rounded-full bg-[var(--stan-primary)] text-sm font-black text-white">1</span>
                                        <span class="my-1 h-12 w-px bg-amber-200"></span>
                                        <span class="grid h-9 w-9 place-items-center rounded-full bg-slate-200 text-sm font-black text-slate-500">2</span>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="stan-ai-scan">
                                            <p class="text-[10px] font-bold uppercase tracking-wide text-[var(--stan-primary)]">Masukkan materi atau soal</p>
                                            <div class="mt-2 rounded-xl border border-slate-200 bg-white p-3 text-xs font-semibold leading-5 text-slate-600">"Jelaskan strategi menyelesaikan soal deret angka secara cepat dan sistematis."</div>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Pilih hasil yang kamu butuhkan</p>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <span class="rounded-lg bg-blue-50 px-2.5 py-1.5 text-[10px] font-bold text-blue-700"><i class="ri-sticky-note-line mr-1"></i>Catatan</span>
                                                <span class="rounded-lg bg-violet-50 px-2.5 py-1.5 text-[10px] font-bold text-violet-700"><i class="ri-stack-line mr-1"></i>Flashcard</span>
                                                <span class="rounded-lg bg-amber-50 px-2.5 py-1.5 text-[10px] font-bold text-amber-700"><i class="ri-file-add-line mr-1"></i>Soal</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5 flex items-center justify-between rounded-2xl bg-[var(--stan-primary)] p-4 text-white">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-amber-100">Hasilmu siap</p>
                                        <p class="mt-1 text-sm font-extrabold">Belajar jadi lebih terstruktur</p>
                                    </div>
                                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/10 text-2xl text-amber-200"><i class="ri-arrow-right-up-line"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="program" class="stan-section stan-section-soft">
            <div class="stan-shell">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between stan-reveal">
                    <div class="max-w-3xl">
                        <span class="stan-kicker">{{ $landingValue('program.eyebrow', 'Program Belajar') }}</span>
                        <h2 class="stan-heading">{{ $landingValue('program.title', 'Pilih ritme belajar yang paling cocok') }}</h2>
                    </div>
                    <p class="max-w-md text-sm font-medium leading-7 text-slate-500">{{ $landingValue('program.description', data_get($landingDefaults, 'program.description')) }}</p>
                </div>

                <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @forelse($landingPackages as $package)
                        @php
                            $programFeatures = is_array($package->features)
                                ? $package->features
                                : json_decode($package->features ?? '[]', true);
                            $programFeatures = is_array($programFeatures) ? array_values(array_filter($programFeatures)) : [];
                            $programThumbnail = $package->image ? \Illuminate\Support\Facades\Storage::url($package->image) : null;
                            $isVideoThumbnail = $package->image
                                && in_array(strtolower(pathinfo($package->image, PATHINFO_EXTENSION)), ['mp4', 'webm', 'mov', 'm4v'], true);
                            $priceLabel = match ($package->type_price) {
                                'paid' => 'Rp '.number_format($package->price, 0, ',', '.'),
                                'free_conditional' => 'Gratis*',
                                default => 'Gratis',
                            };
                            $ctaLabel = match ($package->type_price) {
                                'paid' => 'Lihat detail paket',
                                'free_conditional' => 'Lihat persyaratan',
                                default => 'Ambil paket',
                            };
                        @endphp
                        <article class="stan-card stan-package stan-reveal">
                            <div class="stan-package-media">
                                @if($programThumbnail && $isVideoThumbnail)
                                    <video src="{{ $programThumbnail }}" muted playsinline preload="metadata" aria-label="Preview {{ $package->name }}"></video>
                                @elseif($programThumbnail)
                                    <img src="{{ $programThumbnail }}" alt="Thumbnail {{ $package->name }}" loading="lazy">
                                @else
                                    <div class="stan-package-media-empty">
                                        <i class="ri-graduation-cap-line"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex flex-1 flex-col p-6">
                                <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-[9px] font-extrabold uppercase tracking-[.12em] text-slate-500">Program PKN STAN</span>
                                <h3 class="mt-4 text-xl font-extrabold leading-snug text-slate-900">{{ $package->name }}</h3>
                                <p class="mt-2 text-xs font-medium leading-6 text-slate-500">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($package->description ?: 'Program belajar terarah untuk membantu persiapan seleksimu.'), 135) }}
                                </p>
                                <p class="mt-5 text-2xl font-black text-[var(--stan-navy)]">{{ $priceLabel }}</p>

                                @if($programFeatures !== [])
                                    <ul class="mt-5 space-y-3 border-t border-slate-100 pt-5">
                                        @foreach(array_slice($programFeatures, 0, 4) as $feature)
                                            <li class="flex gap-2.5 text-xs font-semibold leading-5 text-slate-600">
                                                <i class="ri-checkbox-circle-fill mt-0.5 shrink-0 text-primary"></i>
                                                <span>{{ is_array($feature) ? data_get($feature, 'label', '') : $feature }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if($package->type_price === 'free_conditional')
                                    <button type="button" class="stan-btn stan-btn-primary mt-7 w-full" data-stan-modal-open="stan-package-{{ $package->package_id }}">
                                        {{ $ctaLabel }} <i class="ri-arrow-right-line"></i>
                                    </button>
                                @else
                                    <a href="{{ route('user.package.detail', $package->package_id) }}" class="stan-btn stan-btn-primary mt-7 w-full">
                                        {{ $ctaLabel }} <i class="ri-arrow-right-line"></i>
                                    </a>
                                @endif
                            </div>
                        </article>

                        @if($package->type_price === 'free_conditional')
                            <div id="stan-package-{{ $package->package_id }}" class="fixed inset-0 z-[80] grid place-items-center bg-slate-950/55 p-4 backdrop-blur-sm"
                                data-stan-modal hidden role="dialog" aria-modal="true" aria-labelledby="stan-package-title-{{ $package->package_id }}">
                                <button type="button" class="absolute inset-0 cursor-default" aria-label="Tutup modal" data-stan-modal-close="stan-package-{{ $package->package_id }}"></button>
                                <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl sm:p-8">
                                    <button type="button" class="absolute right-4 top-4 grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-600"
                                        aria-label="Tutup" data-stan-modal-close="stan-package-{{ $package->package_id }}">
                                        <i class="ri-close-line text-xl"></i>
                                    </button>
                                    <span class="stan-kicker">Paket Gratis Bersyarat</span>
                                    <h3 id="stan-package-title-{{ $package->package_id }}" class="mt-3 pr-10 text-2xl font-extrabold text-slate-900">{{ $package->name }}</h3>
                                    <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                                        {{ $package->conditional_requirement ?: 'Silakan hubungi admin untuk mengetahui persyaratan paket ini.' }}
                                    </div>
                                    <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
                                        @csrf
                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-slate-700">Upload bukti persyaratan</label>
                                            <input type="file" name="requirement_proofs[]" required multiple accept=".jpg,.jpeg,.png,.pdf,.mp4,.webm"
                                                class="w-full rounded-xl border border-slate-200 p-3 text-xs file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-bold">
                                            <p class="mt-2 text-[11px] leading-5 text-slate-400">JPG, PNG, PDF, MP4, atau WEBM. Maksimal 2 MB per file.</p>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-slate-700">Catatan <span class="font-medium text-slate-400">(opsional)</span></label>
                                            <textarea name="requirement_user_notes" rows="3" maxlength="1000" class="w-full resize-none rounded-xl border border-slate-200 p-3 text-sm focus:border-primary focus:outline-none" placeholder="Tambahkan penjelasan untuk admin"></textarea>
                                        </div>
                                        <button type="submit" class="stan-btn stan-btn-primary w-full">Kirim pengajuan</button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="stan-card col-span-full px-6 py-12 text-center stan-reveal">
                            <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-2xl text-primary"><i class="ri-book-shelf-line"></i></span>
                            <h3 class="mt-4 text-lg font-extrabold text-slate-900">Program sedang disiapkan</h3>
                            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Admin dapat memilih hingga tiga paket aktif untuk ditampilkan di section ini.</p>
                            <a href="{{ route('user.package.index', ['layout' => 'landing']) }}" class="stan-btn stan-btn-secondary mt-6">Lihat semua paket</a>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="stan-section stan-section-dark">
            <div class="stan-shell grid items-center gap-10 lg:grid-cols-[1fr_auto] stan-reveal">
                <div class="max-w-3xl">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-[.12em] text-amber-300">
                        <i class="ri-group-line"></i>
                        {{ $landingValue('community.badge', 'Teman Seperjuangan') }}
                    </span>
                    <h2 class="mt-5 text-3xl font-extrabold leading-tight sm:text-4xl">{{ $landingValue('community.title', data_get($landingDefaults, 'community.title')) }}</h2>
                    <p class="mt-4 max-w-2xl text-sm font-medium leading-7 text-slate-300">{{ $landingValue('community.description', data_get($landingDefaults, 'community.description')) }}</p>
                </div>
                <a href="{{ $landingValue('community.cta.href', '#') }}" target="_blank" rel="noopener noreferrer"
                    class="stan-btn bg-white px-6 text-[var(--stan-navy)] shadow-xl">
                    <i class="ri-whatsapp-line text-xl text-emerald-600"></i>
                    {{ $landingValue('community.cta.label', 'Gabung Komunitas') }}
                </a>
            </div>
        </section>

        <section id="testimoni" class="stan-section bg-white">
            <div class="stan-shell">
                <div class="mx-auto max-w-3xl text-center stan-reveal">
                    <span class="stan-kicker">{{ $landingValue('testimonials.eyebrow', 'Cerita Peserta') }}</span>
                    <h2 class="stan-heading">{{ $landingValue('testimonials.title', data_get($landingDefaults, 'testimonials.title')) }}</h2>
                    <p class="mx-auto mt-5 max-w-2xl text-sm font-medium leading-7 text-slate-500">{{ $landingValue('testimonials.description', data_get($landingDefaults, 'testimonials.description')) }}</p>
                </div>

                @if(count($testimonials) > 0)
                    <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($testimonials as $testimonial)
                            <article class="stan-card stan-quote flex min-h-[300px] flex-col p-7 stan-reveal">
                                <p class="flex-1 text-sm font-medium leading-7 text-slate-600">{{ data_get($testimonial, 'quote') }}</p>
                                <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                                    <img src="{{ $landingAsset(data_get($testimonial, 'image'), 'img/student_rian.png') }}"
                                        alt="Foto {{ data_get($testimonial, 'name', 'peserta') }}" class="h-11 w-11 rounded-full object-cover">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-extrabold text-slate-900">{{ data_get($testimonial, 'name') }}</p>
                                        <p class="mt-1 truncate text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ data_get($testimonial, 'result') }}</p>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif

                @if(count($partners) > 0)
                    <div class="mt-16 border-t border-slate-100 pt-10 text-center stan-reveal">
                        <p class="text-[10px] font-extrabold uppercase tracking-[.15em] text-slate-400">{{ $landingValue('partners.eyebrow', 'Dipercaya Komunitas Belajar') }}</p>
                        <div class="mt-7 flex flex-wrap items-center justify-center gap-4 sm:gap-7">
                            @foreach($partners as $partner)
                                <div class="flex items-center gap-3 rounded-xl border border-slate-100 px-4 py-3">
                                    <img src="{{ $landingAsset(data_get($partner, 'logo'), 'img/logo_kampus.png') }}"
                                        alt="{{ data_get($partner, 'alt', data_get($partner, 'name', 'Logo mitra')) }}" class="h-8 w-8 object-contain">
                                    <div class="text-left">
                                        <p class="text-xs font-extrabold text-slate-700">{{ data_get($partner, 'name') }}</p>
                                        <p class="mt-0.5 text-[9px] font-semibold text-slate-400">{{ data_get($partner, 'location') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section id="faq" class="stan-section stan-section-soft">
            <div class="stan-shell grid gap-12 lg:grid-cols-[.72fr_1.28fr]">
                <div class="stan-reveal">
                    <span class="stan-kicker">{{ $landingValue('faq.eyebrow', 'Pertanyaan Umum') }}</span>
                    <h2 class="stan-heading">{{ $landingValue('faq.title', data_get($landingDefaults, 'faq.title')) }}</h2>
                    <p class="mt-5 text-sm font-medium leading-7 text-slate-500">Belum menemukan jawabanmu? Tim kami siap membantu memilih langkah belajar yang tepat.</p>
                    <a href="{{ $landingValue('hero.secondary_cta.href', '#') }}" class="stan-btn stan-btn-secondary mt-7"
                        @if(\Illuminate\Support\Str::startsWith($landingValue('hero.secondary_cta.href', ''), ['http://', 'https://'])) target="_blank" rel="noopener noreferrer" @endif>
                        <i class="ri-chat-3-line"></i> Tanya admin
                    </a>
                </div>

                <div class="space-y-3 stan-reveal">
                    @foreach($faqs as $faqIndex => $faq)
                        <article class="stan-card overflow-hidden">
                            <h3>
                                <button type="button" class="stan-faq-button flex w-full items-center justify-between gap-5 px-5 py-5 text-left sm:px-6"
                                    aria-expanded="{{ $faqIndex === 0 ? 'true' : 'false' }}" aria-controls="stan-faq-answer-{{ $faqIndex }}" data-stan-faq>
                                    <span class="text-sm font-extrabold leading-6 text-slate-800">{{ data_get($faq, 'question') }}</span>
                                    <span class="stan-faq-icon grid h-8 min-w-8 place-items-center rounded-full bg-slate-100 text-slate-600 transition">
                                        <i class="ri-add-line"></i>
                                    </span>
                                </button>
                            </h3>
                            <div id="stan-faq-answer-{{ $faqIndex }}" class="stan-faq-answer px-5 pb-5 text-xs font-medium leading-6 text-slate-500 sm:px-6"
                                @if($faqIndex !== 0) hidden @endif>
                                {{ data_get($faq, 'answer') }}
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-white py-8 sm:py-12">
            <div class="stan-cta stan-shell overflow-hidden rounded-[2rem] px-6 py-12 text-center text-white shadow-2xl sm:px-12 sm:py-16 stan-reveal">
                <span class="inline-flex rounded-full bg-white/10 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-[.14em] text-amber-300">Mulai Hari Ini</span>
                <h2 class="mx-auto mt-5 max-w-3xl text-3xl font-extrabold leading-tight sm:text-4xl">Target besar dimulai dari latihan pertama yang konsisten.</h2>
                <p class="mx-auto mt-4 max-w-xl text-sm font-medium leading-7 text-white/75">Buat akun, pilih program, dan mulai ukur progres persiapan PKN STAN-mu.</p>
                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ $landingValue('hero.primary_cta.href', route('register')) }}" class="stan-btn bg-white text-[var(--stan-primary)] shadow-xl">
                        {{ $landingValue('hero.primary_cta.label', 'Mulai Persiapan') }} <i class="ri-arrow-right-line"></i>
                    </a>
                    <a href="#program" class="stan-btn border-white/20 bg-white/10 text-white">Lihat program</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="mt-8 bg-[var(--stan-navy)] pt-14 text-white">
        <div class="stan-shell grid gap-10 border-b border-white/10 pb-12 md:grid-cols-12">
            <div class="md:col-span-5">
                <div class="flex items-center gap-3">
                    <img src="{{ $clientBranding['logo_url'] }}" alt="" class="h-11 w-11 rounded-xl bg-white object-contain p-1">
                    <div>
                        <p class="font-extrabold">{{ $clientBranding['name'] }}</p>
                        <p class="mt-1 text-[10px] font-bold uppercase tracking-[.12em] text-slate-400">{{ $landingValue('footer.tagline', data_get($landingDefaults, 'footer.tagline')) }}</p>
                    </div>
                </div>
                <p class="mt-5 max-w-md text-xs font-medium leading-6 text-slate-400">{{ $landingValue('footer.description', data_get($landingDefaults, 'footer.description')) }}</p>
            </div>
            <div class="md:col-span-3 md:col-start-7">
                <p class="text-xs font-extrabold uppercase tracking-[.13em] text-slate-300">{{ $landingValue('footer.navigation_title', 'Navigasi') }}</p>
                <ul class="mt-5 space-y-3 text-xs font-semibold text-slate-400">
                    <li><a href="#keunggulan" class="hover:text-white">Keunggulan</a></li>
                    <li><a href="#roadmap" class="hover:text-white">Roadmap</a></li>
                    <li><a href="#program" class="hover:text-white">Program</a></li>
                    <li><a href="#testimoni" class="hover:text-white">Testimoni</a></li>
                    <li><a href="#faq" class="hover:text-white">FAQ</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-white">{{ $landingValue('footer.nav_login_label', 'Masuk Akun') }}</a></li>
                </ul>
            </div>
            <div class="md:col-span-3">
                <p class="text-xs font-extrabold uppercase tracking-[.13em] text-slate-300">{{ $landingValue('footer.contact_title', 'Hubungi Kami') }}</p>
                <ul class="mt-5 space-y-3 text-xs font-semibold text-slate-400">
                    <li>
                        <a href="{{ $landingValue('footer.whatsapp_href', $landingValue('hero.secondary_cta.href', '#')) }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 hover:text-white">
                            <i class="ri-whatsapp-line text-base"></i> {{ $landingValue('footer.whatsapp_label', 'WhatsApp Admin') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $landingValue('footer.instagram_href', '#') }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 hover:text-white">
                            <i class="ri-instagram-line text-base"></i> {{ $landingValue('footer.instagram_label', 'Instagram') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $landingValue('footer.email_href', '#') }}" class="flex items-center gap-2 hover:text-white">
                            <i class="ri-mail-line text-base"></i> {{ $landingValue('footer.email_label', 'Email') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="stan-shell flex flex-col gap-4 py-6 text-[10px] font-semibold text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ $clientBranding['name'] }}. {{ $landingValue('footer.copyright_suffix', 'Hak cipta dilindungi.') }}</p>
            <div class="flex flex-wrap gap-4">
                <a href="{{ $termsHref }}" class="hover:text-white">{{ $landingValue('footer.terms_label', 'Syarat & Ketentuan') }}</a>
                @if($privacyHref && $privacyHref !== '#')
                    <a href="{{ $privacyHref }}" class="hover:text-white">{{ $landingValue('footer.privacy_label', 'Kebijakan Privasi') }}</a>
                @endif
                <a href="{{ route('public.payment-policy.id') }}" class="hover:text-white">Kebijakan Pembayaran</a>
                <a href="{{ route('public.refund-policy.id') }}" class="hover:text-white">Kebijakan Refund</a>
            </div>
        </div>
    </footer>

    @include('user.components.floating-whatsapp')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const header = document.querySelector('[data-stan-header]');
            const menu = document.querySelector('[data-stan-menu]');
            const menuToggle = document.querySelector('[data-stan-menu-toggle]');
            const menuIcon = document.querySelector('[data-stan-menu-icon]');
            const scrollProgress = document.querySelector('[data-stan-scroll-progress]');

            const syncHeader = () => {
                header?.classList.toggle('is-scrolled', window.scrollY > 12);

                if (scrollProgress) {
                    const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
                    const progress = scrollableHeight > 0
                        ? Math.min(100, Math.max(0, (window.scrollY / scrollableHeight) * 100))
                        : 0;
                    scrollProgress.style.width = `${progress}%`;
                }
            };
            const closeMenu = () => {
                if (!menu || !menuToggle) return;
                menu.hidden = true;
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.setAttribute('aria-label', 'Buka menu');
                menuIcon?.classList.replace('ri-close-line', 'ri-menu-3-line');
                header?.classList.remove('is-open');
            };

            window.addEventListener('scroll', syncHeader, { passive: true });
            syncHeader();

            menuToggle?.addEventListener('click', () => {
                const willOpen = menu.hidden;
                menu.hidden = !willOpen;
                menuToggle.setAttribute('aria-expanded', String(willOpen));
                menuToggle.setAttribute('aria-label', willOpen ? 'Tutup menu' : 'Buka menu');
                menuIcon?.classList.toggle('ri-menu-3-line', !willOpen);
                menuIcon?.classList.toggle('ri-close-line', willOpen);
                header?.classList.toggle('is-open', willOpen);
            });

            menu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));

            document.querySelectorAll('[data-stan-faq]').forEach((button) => {
                button.addEventListener('click', () => {
                    const answer = document.getElementById(button.getAttribute('aria-controls'));
                    const isOpen = button.getAttribute('aria-expanded') === 'true';
                    button.setAttribute('aria-expanded', String(!isOpen));
                    if (answer) answer.hidden = isOpen;
                });
            });

            const closeModal = (id) => {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.hidden = true;
                document.documentElement.classList.remove('overflow-hidden');
            };

            document.querySelectorAll('[data-stan-modal-open]').forEach((button) => {
                button.addEventListener('click', () => {
                    const modal = document.getElementById(button.dataset.stanModalOpen);
                    if (!modal) return;
                    modal.hidden = false;
                    document.documentElement.classList.add('overflow-hidden');
                    modal.querySelector('[data-stan-modal-close]')?.focus();
                });
            });

            document.querySelectorAll('[data-stan-modal-close]').forEach((button) => {
                button.addEventListener('click', () => closeModal(button.dataset.stanModalClose));
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                closeMenu();
                document.querySelectorAll('[data-stan-modal]:not([hidden])').forEach((modal) => closeModal(modal.id));
            });

            const revealItems = document.querySelectorAll('.stan-reveal');
            document.querySelectorAll('.stan-bento .stan-reveal, .stan-roadmap-grid .stan-reveal').forEach((item, index) => {
                item.style.transitionDelay = `${(index % 4) * 70}ms`;
            });

            if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    });
                }, { threshold: 0.12 });
                revealItems.forEach((item) => observer.observe(item));
            } else {
                revealItems.forEach((item) => item.classList.add('is-visible'));
            }
        });
    </script>
</body>
</html>
