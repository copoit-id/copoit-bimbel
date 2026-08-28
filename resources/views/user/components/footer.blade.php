@php
    $footerEnabled = (bool) ($clientBranding['footer_enabled'] ?? true);
    $footerLinks = collect($clientBranding['footer_links'] ?? [])
        ->filter(fn ($link) => filled($link['label'] ?? null) && filled($link['url'] ?? null))
        ->values();
    $footerDescription = $clientBranding['footer_description']
        ?? 'Platform belajar digital untuk mengelola materi, paket, tryout, dan progres belajar dalam satu tempat.';
    $footerCopyright = $clientBranding['footer_copyright']
        ?? 'Copyright ' . now()->year . ' ' . ($clientBranding['name'] ?? config('app.name')) . '. All rights reserved.';

    $footerAddress = $clientBranding['footer_address'] ?? null;
    $footerPhone = $clientBranding['footer_phone'] ?? null;
    $footerEmail = $clientBranding['footer_email'] ?? null;
    $footerWhatsapp = $clientBranding['footer_whatsapp'] ?? null;

    $footerFacebook = $clientBranding['footer_facebook'] ?? null;
    $footerInstagram = $clientBranding['footer_instagram'] ?? null;
    $footerTwitter = $clientBranding['footer_twitter'] ?? null;
    $footerYoutube = $clientBranding['footer_youtube'] ?? null;

    $footerContacts = is_array($clientBranding['footer_contacts'] ?? null)
        ? collect($clientBranding['footer_contacts'])->filter(fn ($contact) => filled($contact['value'] ?? null))->values()
        : collect([
            ['type' => 'phone', 'label' => 'Telepon', 'value' => $footerPhone],
            ['type' => 'whatsapp', 'label' => 'WhatsApp', 'value' => $footerWhatsapp],
            ['type' => 'email', 'label' => 'Email', 'value' => $footerEmail],
        ])->filter(fn ($contact) => filled($contact['value']))->values();
    $footerSocials = is_array($clientBranding['footer_socials'] ?? null)
        ? collect($clientBranding['footer_socials'])->filter(fn ($social) => filled($social['url'] ?? null))->values()
        : collect([
            ['platform' => 'facebook', 'label' => 'Facebook', 'url' => $footerFacebook],
            ['platform' => 'instagram', 'label' => 'Instagram', 'url' => $footerInstagram],
            ['platform' => 'twitter', 'label' => 'X/Twitter', 'url' => $footerTwitter],
            ['platform' => 'youtube', 'label' => 'YouTube', 'url' => $footerYoutube],
        ])->filter(fn ($social) => filled($social['url']))->values();
    $socialPlatforms = [
        'facebook' => ['label' => 'Facebook', 'icon' => 'ri-facebook-fill'],
        'instagram' => ['label' => 'Instagram', 'icon' => 'ri-instagram-line'],
        'twitter' => ['label' => 'X/Twitter', 'icon' => 'ri-twitter-x-fill'],
        'youtube' => ['label' => 'YouTube', 'icon' => 'ri-youtube-fill'],
        'tiktok' => ['label' => 'TikTok', 'icon' => 'ri-tiktok-fill'],
        'linkedin' => ['label' => 'LinkedIn', 'icon' => 'ri-linkedin-fill'],
        'website' => ['label' => 'Website', 'icon' => 'ri-global-line'],
        'custom' => ['label' => 'Tautan', 'icon' => 'ri-links-line'],
    ];
@endphp

@if($footerEnabled)
<footer class="mt-16 border-t border-gray-200 bg-white shadow-sm">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4 lg:gap-12">
            <!-- Column 1: Identity & Social Media -->
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <img src="{{ $clientBranding['logo_url'] ?? asset('img/logo/logo-copoit.png') }}"
                        alt="{{ $clientBranding['name'] ?? config('app.name') }}"
                        class="client-brand-logo h-10 w-10 rounded-xl object-contain shadow-sm">
                    <span class="text-lg font-bold text-gray-900 tracking-tight">{{ $clientBranding['name'] ?? config('app.name') }}</span>
                </div>
                @if($footerDescription)
                <p class="text-sm leading-relaxed text-gray-500">{{ $footerDescription }}</p>
                @endif

                @if($footerSocials->isNotEmpty())
                <div class="flex flex-wrap gap-2.5 pt-2">
                    @foreach($footerSocials as $social)
                    @php
                        $platform = $socialPlatforms[$social['platform'] ?? 'custom'] ?? $socialPlatforms['custom'];
                        $label = filled($social['label'] ?? null) ? $social['label'] : $platform['label'];
                    @endphp
                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener"
                       class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-400 shadow-sm transition-all duration-300 hover:border-primary hover:bg-primary hover:text-white"
                       aria-label="{{ $label }}">
                        <i class="{{ $platform['icon'] }} text-lg"></i>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Column 2: Quick Links -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Tautan</h3>
                @if($footerLinks->isNotEmpty())
                <ul class="space-y-2.5 text-sm">
                    @foreach($footerLinks as $link)
                    <li>
                        <a href="{{ $link['url'] }}" 
                           class="text-gray-500 transition-colors duration-200 hover:text-primary flex items-center gap-1.5"
                           @if(\Illuminate\Support\Str::startsWith($link['url'], ['http://', 'https://'])) target="_blank" rel="noopener" @endif>
                            <i class="ri-arrow-right-s-line text-gray-300"></i>
                            {{ $link['label'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-sm text-gray-400 italic">Tidak ada tautan.</p>
                @endif
            </div>

            <!-- Column 3: Contact -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Kontak</h3>
                @if($footerContacts->isNotEmpty())
                <ul class="space-y-3.5 text-sm">
                    @foreach($footerContacts as $contact)
                    @php
                        $contactType = $contact['type'] ?? 'text';
                        $contactValue = $contact['value'] ?? '';
                        $contactLabel = $contact['label'] ?? '';
                        $contactIcon = match ($contactType) {
                            'phone' => 'ri-phone-line',
                            'whatsapp' => 'ri-whatsapp-line',
                            'email' => 'ri-mail-line',
                            default => 'ri-information-line',
                        };
                    @endphp
                    <li class="flex items-start gap-3 text-gray-500">
                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center {{ $contactType === 'whatsapp' ? 'text-green-500' : 'text-primary' }}">
                            <i class="{{ $contactIcon }} text-base"></i>
                        </span>
                        @if($contactType === 'phone')
                        <a href="tel:{{ $contactValue }}" class="break-all transition-colors hover:text-primary">{{ $contactLabel ? $contactLabel.': ' : '' }}{{ $contactValue }}</a>
                        @elseif($contactType === 'whatsapp')
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contactValue) }}" target="_blank" rel="noopener" class="break-all transition-colors hover:text-green-600">{{ $contactLabel ?: 'WhatsApp' }}: +{{ preg_replace('/[^0-9]/', '', $contactValue) }}</a>
                        @elseif($contactType === 'email')
                        <a href="mailto:{{ $contactValue }}" class="break-all transition-colors hover:text-primary">{{ $contactLabel ? $contactLabel.': ' : '' }}{{ $contactValue }}</a>
                        @else
                        <span class="break-words">{{ $contactLabel ? $contactLabel.': ' : '' }}{{ $contactValue }}</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-sm text-gray-400 italic">Kontak belum diatur.</p>
                @endif

            </div>

            <!-- Column 4: Address -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Alamat Kami</h3>
                @if($footerAddress)
                <div class="flex items-start gap-3 text-sm text-gray-500 leading-relaxed">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center text-primary mt-0.5">
                        <i class="ri-map-pin-line text-base"></i>
                    </span>
                    <span>{!! nl2br(e($footerAddress)) !!}</span>
                </div>
                @else
                <p class="text-sm text-gray-400 italic">Alamat belum diatur.</p>
                @endif
            </div>
        </div>

        <!-- Bottom Copyright -->
        <div class="mt-12 border-t border-gray-100 pt-6 text-center text-xs text-gray-400">
            <p>{{ $footerCopyright }}</p>
        </div>
    </div>
</footer>
@endif
