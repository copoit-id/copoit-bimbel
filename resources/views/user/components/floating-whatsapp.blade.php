@php
    $whatsappNumber = preg_replace('/\D+/', '', (string) ($clientBranding['contact_whatsapp_number'] ?? ''));
    $whatsappText = trim((string) ($clientBranding['contact_whatsapp_button_text'] ?? 'Chat Admin')) ?: 'Chat Admin';
@endphp

@if($whatsappNumber !== '')
    <a href="https://wa.me/{{ $whatsappNumber }}"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="{{ $whatsappText }}"
        id="floating-whatsapp"
        class="fixed bottom-24 right-5 z-50 inline-flex items-center gap-2 rounded-full bg-[#25D366] px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-green-900/20 transition hover:bg-[#1ebe5d] focus:outline-none focus:ring-4 focus:ring-green-300 md:bottom-6 md:right-6">
        <i class="ri-whatsapp-line text-xl leading-none"></i>
        <span class="max-w-[150px] truncate">{{ $whatsappText }}</span>
    </a>
@endif
