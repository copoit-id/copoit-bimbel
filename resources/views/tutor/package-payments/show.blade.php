@extends('tutor.layout')

@section('title', 'Penerimaan Pembayaran')

@section('content')
    <div class="space-y-6">
        <header class="border-b border-gray-200 pb-5">
            <a href="{{ route('tutor.package-payments.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-primary transition hover:text-primary/80">
                <span aria-hidden="true">←</span>
                Kembali ke pembayaran siswa
            </a>

            <div class="mt-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary">{{ $session->start_at->locale('id')->translatedFormat('l, d F Y') }}</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Penerimaan pembayaran</h1>
                    <p class="mt-2 text-sm leading-6 text-gray-500">{{ $session->schedule?->title ?? 'Sesi belajar' }} · {{ $session->studyGroup->name }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                    <p class="font-semibold text-gray-900">{{ $package->name }}</p>
                    <p class="mt-1">Rp {{ number_format($package->price, 0, ',', '.') }} · {{ \App\Services\TutorPackagePaymentService::billingFrequencyLabel($package->tutor_payment_frequency, true) }}</p>
                </div>
            </div>
        </header>

        <section class="grid gap-3 sm:grid-cols-3" aria-label="Ringkasan tagihan periode ini">
            <x-ui.card variant="flat" class="rounded-2xl border border-gray-200 p-4 shadow-none">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tagihan peserta</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($paymentSummary['invoice_count']) }}</p>
                <p class="mt-1 text-sm text-gray-500">Rp {{ number_format($paymentSummary['amount'], 0, ',', '.') }}</p>
            </x-ui.card>
            <x-ui.card variant="flat" class="rounded-2xl border border-gray-200 p-4 shadow-none">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Sudah diterima</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">Rp {{ number_format($paymentSummary['paid'], 0, ',', '.') }}</p>
                <p class="mt-1 text-sm text-gray-500">Tercatat pada periode ini</p>
            </x-ui.card>
            <x-ui.card variant="flat" class="rounded-2xl border border-gray-200 p-4 shadow-none">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Masih perlu diterima</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">Rp {{ number_format($paymentSummary['remaining'], 0, ',', '.') }}</p>
                <p class="mt-1 text-sm text-gray-500">Dari seluruh tagihan peserta</p>
            </x-ui.card>
        </section>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="font-bold text-gray-900">Penerimaan periode ini</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-500">Pilih peserta, konfirmasi nominal dan metode pembayaran, lalu sistem menentukan status tagihan secara otomatis.</p>
                </div>
                <span class="w-fit rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">{{ $currentInvoices->total() }} peserta</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[960px] w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">Peserta</th>
                            <th scope="col" class="px-5 py-3">Tagihan</th>
                            <th scope="col" class="px-5 py-3">Diterima</th>
                            <th scope="col" class="px-5 py-3">Sisa</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="sticky right-0 border-l border-gray-100 bg-gray-50 px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($currentInvoices as $invoice)
                            @php
                                $isPaid = $invoice->status === 'paid' || $invoice->remaining_amount <= 0;
                                $statusLabel = $isPaid ? 'Lunas' : ($invoice->status === 'partial' ? 'Belum lunas' : 'Belum bayar');
                                $statusVariant = $isPaid ? 'success' : ($invoice->status === 'partial' ? 'warning' : 'secondary');
                            @endphp
                            <tr class="transition hover:bg-gray-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-gray-900">{{ $invoice->user->name }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $invoice->user->email }}</p>
                                </td>
                                <td class="px-5 py-4 font-semibold tabular-nums text-gray-900">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 tabular-nums text-gray-700">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 font-semibold tabular-nums text-gray-900">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</td>
                                <td class="px-5 py-4"><x-ui.badge :variant="$statusVariant" size="sm" pill>{{ $statusLabel }}</x-ui.badge></td>
                                <td class="sticky right-0 border-l border-gray-100 bg-white px-5 py-4 text-right">
                                    @if ($isPaid)
                                        <span class="text-sm font-medium text-gray-500">Selesai</span>
                                    @else
                                        <x-ui.button
                                            type="button"
                                            size="sm"
                                            data-payment-action="{{ route('tutor.package-payments.record', $invoice) }}"
                                            data-payment-student="{{ $invoice->user->name }}"
                                            data-payment-remaining="{{ $invoice->remaining_amount }}"
                                            onclick="openPaymentModal(this)"
                                        >
                                            Catat penerimaan
                                        </x-ui.button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-14 text-center">
                                    <p class="font-semibold text-gray-900">Belum ada tagihan periode ini</p>
                                    <p class="mt-1 text-sm text-gray-500">Siapkan tagihan dari halaman pembayaran siswa terlebih dahulu.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($currentInvoices->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">{{ $currentInvoices->links() }}</div>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-5 py-5">
                <h2 class="font-bold text-gray-900">Riwayat dan tunggakan rombel</h2>
                <p class="mt-1 text-sm text-gray-500">Mencakup periode sebelumnya yang masih memiliki sisa tagihan.</p>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse ($historyInvoices as $invoice)
                    <article class="grid gap-4 px-5 py-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <p class="font-semibold text-gray-900">{{ $invoice->user->name }}</p>
                                <span class="text-sm text-gray-400">·</span>
                                <p class="text-sm text-gray-500">
                                    {{ $invoice->period_start?->locale('id')->translatedFormat('d M Y') }}
                                    @if ($invoice->period_end && ! $invoice->period_start?->isSameDay($invoice->period_end))
                                        – {{ $invoice->period_end->locale('id')->translatedFormat('d M Y') }}
                                    @endif
                                </p>
                            </div>
                            <p class="mt-2 text-sm text-gray-600">Diterima Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }} · Sisa <span class="font-semibold {{ $invoice->remaining_amount > 0 ? 'text-amber-700' : 'text-emerald-700' }}">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</span></p>
                        </div>
                        <ul class="space-y-1 text-sm text-gray-500 lg:text-right">
                            @forelse ($invoice->payments as $payment)
                                <li>Rp {{ number_format($payment->amount, 0, ',', '.') }} · {{ $payment->paid_at->locale('id')->translatedFormat('d M Y, H:i') }} · {{ $payment->paidBy?->name ?? '—' }}</li>
                            @empty
                                <li>Belum ada penerimaan.</li>
                            @endforelse
                        </ul>
                    </article>
                @empty
                    <div class="px-5 py-14 text-center text-sm text-gray-500">Belum ada riwayat pembayaran.</div>
                @endforelse
            </div>

            @if ($historyInvoices->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">{{ $historyInvoices->links() }}</div>
            @endif
        </section>
    </div>

    <div id="payment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/45 p-4" role="dialog" aria-modal="true" aria-labelledby="payment-modal-title" onclick="if (event.target === this) closePaymentModal()">
        <div class="w-full max-w-md rounded-2xl border border-gray-100 bg-white" role="document">
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-primary">Konfirmasi penerimaan</p>
                    <h2 id="payment-modal-title" class="mt-1 text-lg font-bold text-gray-900">Catat pembayaran</h2>
                    <p id="payment-modal-student" class="mt-1 text-sm text-gray-500"></p>
                </div>
                <button type="button" class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" onclick="closePaymentModal()" aria-label="Tutup modal">✕</button>
            </div>

            <form id="payment-form" method="POST" class="space-y-5 px-5 py-5">
                @csrf
                <input type="hidden" name="class_session_id" value="{{ $session->id }}">
                <x-ui.input name="amount" type="number" min="1" required label="Nominal diterima" placeholder="Contoh: 50.000" />

                <div>
                    <label for="payment-method" class="mb-2 block text-sm font-semibold text-gray-700">Metode pembayaran</label>
                    <select id="payment-method" name="payment_method" class="block w-full rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary" required>
                        <option value="cash">Tunai</option>
                        <option value="transfer">Transfer bank</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>

                <div class="w-full">
                    <x-ui.badge id="payment-status" variant="success" size="sm" pill class="border border-emerald-200 bg-emerald-50 text-emerald-700">
                        <span id="payment-status-label">Lunas</span>
                        <span aria-hidden="true">·</span>
                        <span id="payment-status-detail">Nominal sesuai tagihan</span>
                    </x-ui.badge>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:justify-end">
                    <x-ui.button type="button" variant="secondary" onclick="closePaymentModal()">Batal</x-ui.button>
                    <x-ui.button type="submit">Simpan penerimaan</x-ui.button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function paymentAmountValue(amount) {
            return Number(String(amount.value || '').replace(/\D/g, ''));
        }

        function updatePaymentStatus() {
            const amount = document.getElementById('amount');
            const remaining = Number(amount.max || 0);
            const received = paymentAmountValue(amount);
            const status = document.getElementById('payment-status');
            const label = document.getElementById('payment-status-label');
            const detail = document.getElementById('payment-status-detail');

            status.className = 'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold';

            if (received === remaining && remaining > 0) {
                status.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
                label.textContent = 'Lunas';
                detail.textContent = 'Nominal sesuai tagihan';
            } else if (received > remaining) {
                status.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-700');
                label.textContent = 'Nominal melebihi tagihan';
                detail.textContent = 'Sesuaikan nominal penerimaan';
            } else {
                status.classList.add('border-amber-200', 'bg-amber-50', 'text-amber-700');
                label.textContent = 'Belum lunas';
                detail.textContent = received > 0 ? 'Masih ada sisa tagihan' : 'Masukkan nominal penerimaan';
            }
        }

        function openPaymentModal(button) {
            const modal = document.getElementById('payment-modal');
            const form = document.getElementById('payment-form');
            const amount = document.getElementById('amount');
            const remaining = Number(button.dataset.paymentRemaining || 0);

            form.action = button.dataset.paymentAction;
            amount.max = String(remaining);
            amount.value = String(remaining);
            document.getElementById('payment-modal-student').textContent = button.dataset.paymentStudent;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            updatePaymentStatus();

            window.setTimeout(() => amount.focus(), 0);
        }

        function closePaymentModal() {
            const modal = document.getElementById('payment-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closePaymentModal();
            }
        });

        document.getElementById('amount').addEventListener('input', updatePaymentStatus);
    </script>
@endpush
