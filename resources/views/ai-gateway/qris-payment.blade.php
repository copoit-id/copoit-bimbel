@php
    $details = is_array($transaction->details) ? $transaction->details : [];
    $expiresAt = filled($details['expires_at'] ?? null) ? \Carbon\Carbon::parse($details['expires_at']) : null;
    $isExpired = $transaction->status === 'expired' || ($expiresAt && $expiresAt->isPast());
    $qrisImage = filled($details['qris_content'] ?? null)
        ? base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(300)->margin(1)->generate($details['qris_content']))
        : null;
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran QRIS AI Gateway</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-3xl px-4 py-10 sm:py-16">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50">
            <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-6 text-white sm:px-8">
                <p class="text-sm font-medium text-indigo-100">AI Gateway</p>
                <h1 class="mt-1 text-2xl font-bold">Pembayaran QRIS</h1>
                <p class="mt-1 text-sm text-indigo-100">Scan kode QR untuk menyelesaikan pembelian paket AI.</p>
            </div>

            <div class="grid gap-8 p-6 sm:p-8 md:grid-cols-[320px_1fr]">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    @if($qrisImage && ! $isExpired)
                        <img src="data:image/png;base64,{{ $qrisImage }}" alt="Kode QRIS pembayaran" class="mx-auto h-[280px] w-[280px] max-w-full rounded-xl bg-white p-2">
                    @else
                        <div class="flex h-[280px] items-center justify-center rounded-xl bg-rose-50 p-6 text-center text-sm text-rose-700">
                            {{ $isExpired ? 'QRIS ini sudah kedaluwarsa.' : 'Kode QRIS tidak tersedia.' }}
                        </div>
                    @endif
                    <button id="checkStatus" type="button" @disabled($isExpired || ! $qrisImage) class="mt-4 w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                        Cek status pembayaran
                    </button>
                    <p id="statusMessage" class="mt-3 text-center text-sm text-slate-500"></p>
                </div>

                <div>
                    <h2 class="text-xl font-bold">{{ $transaction->plan?->name ?? 'Paket AI Gateway' }}</h2>
                    <dl class="mt-5 divide-y divide-slate-100 text-sm">
                        <div class="flex items-center justify-between gap-4 py-3"><dt class="text-slate-500">Nominal</dt><dd class="font-bold">Rp {{ number_format((float) $transaction->amount, 0, ',', '.') }}</dd></div>
                        <div class="flex items-center justify-between gap-4 py-3"><dt class="text-slate-500">Status</dt><dd id="paymentStatus" class="font-semibold capitalize {{ $transaction->status === 'paid' ? 'text-emerald-600' : ($isExpired ? 'text-rose-600' : 'text-amber-600') }}">{{ $transaction->status }}</dd></div>
                        <div class="flex items-center justify-between gap-4 py-3"><dt class="text-slate-500">Invoice QRIS</dt><dd class="font-mono text-xs">{{ $details['qris_invoiceid'] ?? '-' }}</dd></div>
                        <div class="flex items-center justify-between gap-4 py-3"><dt class="text-slate-500">Berlaku sampai</dt><dd class="font-medium">{{ $expiresAt?->format('d M Y H:i') ?? '-' }} WIB</dd></div>
                    </dl>
                    <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Setelah membayar, tekan tombol cek status. Halaman ini hanya memeriksa saat diminta agar layanan QRIS tetap stabil.</div>
                </div>
            </div>
        </section>
    </main>
    <script>
        document.getElementById('checkStatus')?.addEventListener('click', async (event) => {
            const button = event.currentTarget;
            const message = document.getElementById('statusMessage');
            button.disabled = true;
            button.textContent = 'Mengecek...';
            try {
                const response = await fetch(@json(route('ai-gateway-payments.qris.status', $transaction->external_id)), {headers: {Accept: 'application/json'}});
                const data = await response.json();
                if (!response.ok) throw new Error();
                document.getElementById('paymentStatus').textContent = data.status;
                if (data.status === 'paid') {
                    message.textContent = 'Pembayaran berhasil dikonfirmasi. Anda dapat kembali ke aplikasi.';
                    return;
                }
                if (data.status === 'expired' || data.status === 'failed') {
                    message.textContent = 'Pembayaran tidak dapat dilanjutkan. Silakan buat pembayaran baru dari aplikasi.';
                    return;
                }
                message.textContent = 'Pembayaran belum ditemukan. Silakan tunggu sebentar lalu coba lagi.';
            } catch (_) {
                message.textContent = 'Status belum bisa diperiksa. Silakan coba lagi.';
            } finally {
                if (!['paid', 'expired', 'failed'].includes(document.getElementById('paymentStatus').textContent.trim())) {
                    button.disabled = false;
                    button.textContent = 'Cek status pembayaran';
                }
            }
        });
    </script>
</body>
</html>
