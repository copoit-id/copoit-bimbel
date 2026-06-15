@props([
    'class' => '',
    'compact' => false,
])

<p {{ $attributes->merge(['class' => trim(($compact ? 'text-[11px]' : 'text-xs') . ' text-gray-500 leading-relaxed ' . $class)]) }}>
    Dengan melanjutkan transaksi, Anda menyetujui
    <a href="{{ route('public.terms') }}" target="_blank" class="font-medium text-primary hover:underline">Syarat dan Ketentuan</a>,
    <a href="{{ route('public.payment-policy') }}" target="_blank" class="font-medium text-primary hover:underline">Kebijakan Pembayaran</a>,
    dan
    <a href="{{ route('public.refund-policy') }}" target="_blank" class="font-medium text-primary hover:underline">Refund Policy</a>.
</p>
