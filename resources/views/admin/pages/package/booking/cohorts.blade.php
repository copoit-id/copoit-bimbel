@extends('admin.layout.admin')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <a href="{{ route('admin.package.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-gray-500 hover:text-primary">
                <i class="ri-arrow-left-line"></i>
                Kembali ke paket
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Kelompok Booking</h1>
            <p class="mt-1 text-sm text-gray-500">Pantau anggota, invoice terpisah, pembayaran, dan rombel yang terbentuk.</p>
        </div>
        <form method="GET">
            <select name="status" onchange="this.form.submit()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                <option value="">Semua status</option>
                <option value="forming" @selected($status === 'forming')>Menunggu anggota</option>
                <option value="ready" @selected($status === 'ready')>Siap dijadwalkan</option>
                <option value="expired" @selected($status === 'expired')>Kedaluwarsa</option>
                <option value="cancelled" @selected($status === 'cancelled')>Dibatalkan</option>
            </select>
        </form>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    @forelse($cohorts as $cohort)
        <section class="rounded-2xl border border-gray-200 bg-white">
            <div class="flex flex-col justify-between gap-3 border-b border-gray-200 p-5 sm:flex-row sm:items-start">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-gray-900">{{ $cohort->package->name }}</h2>
                        <span class="rounded-full border border-primary/20 bg-primary/5 px-2.5 py-1 text-xs font-bold text-primary">{{ strtoupper($cohort->invite_code) }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Dibuat {{ $cohort->organizer->name }} · {{ $cohort->paid_participants_count }}/{{ $cohort->target_participants }} anggota lunas
                    </p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-sm font-bold text-gray-900">Rp {{ number_format($cohort->unit_price_snapshot, 0, ',', '.') }}/orang</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $cohort->status === 'ready' ? 'Rombel: '.($cohort->studyGroup?->name ?? '-') : 'Tenggat '.$cohort->expires_at?->translatedFormat('d M Y H:i') }}</p>
                </div>
            </div>

            <div class="divide-y divide-gray-100">
                @foreach($cohort->participants as $participant)
                    <div class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                        <div>
                            <p class="text-sm font-bold text-gray-900">{{ $participant->user->name }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $participant->user->email }} · {{ $participant->role === 'organizer' ? 'Pembuat kelompok' : 'Anggota' }}</p>
                            <p class="mt-2 text-xs font-semibold {{ $participant->status === 'paid' ? 'text-green-700' : 'text-amber-700' }}">
                                {{ $participant->status === 'paid' ? 'Lunas' : 'Menunggu pembayaran' }}
                                @if($participant->invoice)
                                    · {{ $participant->invoice->invoice_number }}
                                    · Sisa Rp {{ number_format($participant->invoice->remaining_amount, 0, ',', '.') }}
                                @endif
                            </p>
                        </div>

                        @if($participant->invoice && $participant->invoice->status !== 'paid')
                            <form method="POST" action="{{ route('admin.package-booking.cohorts.payments.store', $participant->invoice) }}" class="flex flex-col gap-2 sm:flex-row">
                                @csrf
                                <input type="number" name="amount" min="1" max="{{ $participant->invoice->remaining_amount }}" value="{{ $participant->invoice->remaining_amount }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm sm:w-40 focus:border-primary focus:ring-primary">
                                <select name="payment_method" required class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                    <option value="transfer">Transfer</option>
                                    <option value="cash">Tunai</option>
                                    <option value="qris">QRIS</option>
                                </select>
                                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Catat bayar</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">Belum ada kelompok booking.</div>
    @endforelse

    {{ $cohorts->links() }}
</div>
@endsection
