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
@endphp

@if($footerEnabled)
<footer class="mt-16 border-t border-gray-200 bg-white shadow-sm">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4 lg:gap-12">
            <!-- Column 1: Identity & Socials -->
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <img src="{{ $clientBranding['logo_url'] ?? asset('img/logo/logo-copoit.png') }}"
                        alt="{{ $clientBranding['name'] ?? config('app.name') }}"
                        class="h-10 w-10 rounded-xl object-contain shadow-sm">
                    <span class="text-lg font-bold text-gray-900 tracking-tight">{{ $clientBranding['name'] ?? config('app.name') }}</span>
                </div>
                @if($footerDescription)
                <p class="text-sm leading-relaxed text-gray-500">{{ $footerDescription }}</p>
                @endif
                
                <!-- Social Media Links -->
                @if($footerFacebook || $footerInstagram || $footerTwitter || $footerYoutube)
                <div class="flex flex-wrap gap-2.5 pt-2">
                    @if($footerFacebook)
                    <a href="{{ $footerFacebook }}" target="_blank" rel="noopener" 
                       class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-400 transition-all duration-300 hover:border-primary hover:bg-primary hover:text-white shadow-sm"
                       aria-label="Facebook">
                        <i class="ri-facebook-fill text-lg"></i>
                    </a>
                    @endif
                    @if($footerInstagram)
                    <a href="{{ $footerInstagram }}" target="_blank" rel="noopener" 
                       class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-400 transition-all duration-300 hover:border-primary hover:bg-primary hover:text-white shadow-sm"
                       aria-label="Instagram">
                        <i class="ri-instagram-line text-lg"></i>
                    </a>
                    @endif
                    @if($footerTwitter)
                    <a href="{{ $footerTwitter }}" target="_blank" rel="noopener" 
                       class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-400 transition-all duration-300 hover:border-primary hover:bg-primary hover:text-white shadow-sm"
                       aria-label="Twitter">
                        <i class="ri-twitter-x-fill text-lg"></i>
                    </a>
                    @endif
                    @if($footerYoutube)
                    <a href="{{ $footerYoutube }}" target="_blank" rel="noopener" 
                       class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-400 transition-all duration-300 hover:border-primary hover:bg-primary hover:text-white shadow-sm"
                       aria-label="YouTube">
                        <i class="ri-youtube-fill text-lg"></i>
                    </a>
                    @endif
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

            <!-- Column 3: Contact Info -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Kontak</h3>
                @if($footerPhone || $footerWhatsapp || $footerEmail)
                <ul class="space-y-3.5 text-sm">
                    @if($footerPhone)
                    <li class="flex items-start gap-3 text-gray-500">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center text-primary mt-0.5">
                            <i class="ri-phone-line text-base"></i>
                        </span>
                        <a href="tel:{{ $footerPhone }}" class="hover:text-primary transition-colors">{{ $footerPhone }}</a>
                    </li>
                    @endif
                    @if($footerWhatsapp)
                    <li class="flex items-start gap-3 text-gray-500">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center text-green-500 mt-0.5">
                            <i class="ri-whatsapp-line text-base"></i>
                        </span>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $footerWhatsapp) }}" target="_blank" rel="noopener" class="hover:text-green-600 transition-colors">
                            WhatsApp (+{{ preg_replace('/[^0-9]/', '', $footerWhatsapp) }})
                        </a>
                    </li>
                    @endif
                    @if($footerEmail)
                    <li class="flex items-start gap-3 text-gray-500">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center text-primary mt-0.5">
                            <i class="ri-mail-line text-base"></i>
                        </span>
                        <a href="mailto:{{ $footerEmail }}" class="hover:text-primary transition-colors break-all">{{ $footerEmail }}</a>
                    </li>
                    @endif
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
