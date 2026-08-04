@php
    $statusLabels = [
        'pending_approval' => 'Menunggu persetujuan',
        'pending_payment' => 'Menunggu pembayaran',
        'active' => 'Aktif',
        'expired' => 'Kedaluwarsa',
        'cancelled' => 'Dibatalkan',
    ];
@endphp

<form method="GET" class="mt-4">
    <input type="hidden" name="tab" value="pengajuan">
    <select name="status" onchange="this.form.submit()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
        <option value="">Semua status</option>
        @foreach($statusLabels as $value => $label)
            <option value="{{ $value }}" @selected($applicationStatus === $value)>{{ $label }}</option>
        @endforeach
    </select>
</form>

<div class="mt-4 space-y-4">
    @forelse($groupApplications as $studyGroup)
        <section class="rounded-2xl border border-gray-200 bg-white">
            <div class="flex flex-col justify-between gap-4 border-b border-gray-200 p-5 sm:flex-row sm:items-start">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-gray-900">{{ $studyGroup->package->name }}</h2>
                        <span class="rounded-full border border-primary/20 bg-primary/5 px-2.5 py-1 text-xs font-bold text-primary">{{ $statusLabels[$studyGroup->status] ?? $studyGroup->status }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Diajukan {{ $studyGroup->organizer->name }} · {{ $studyGroup->members_count }}/{{ $studyGroup->target_participants }} peserta · {{ $studyGroup->paid_members_count }} lunas
                    </p>
                    <p class="mt-2 text-xs font-semibold tracking-widest text-gray-600">KODE {{ $studyGroup->invite_code }}</p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-sm font-bold text-gray-900">Rp {{ number_format($studyGroup->unit_price_snapshot, 0, ',', '.') }}/siswa</p>
                    @if($studyGroup->status === 'pending_approval')
                        @if($studyGroup->members_count === $studyGroup->target_participants)
                            <form method="POST" action="{{ route('admin.package-booking.cohorts.approve', $studyGroup) }}" class="mt-3">
                                @csrf
                                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Setujui rombel</button>
                            </form>
                        @else
                            <p class="mt-2 text-xs text-amber-700">Menunggu rombel terisi lengkap sebelum dapat disetujui.</p>
                        @endif
                    @elseif($studyGroup->status === 'pending_payment')
                        <p class="mt-2 text-xs text-gray-500">Tenggat {{ $studyGroup->expires_at?->translatedFormat('d M Y H:i') ?? '-' }}</p>
                    @endif
                </div>
            </div>

            <div class="divide-y divide-gray-100">
                @foreach($studyGroup->members as $member)
                    @php
                        $memberStatus = [
                            'awaiting_approval' => 'Menunggu persetujuan',
                            'awaiting_payment' => 'Menunggu pembayaran',
                            'paid' => 'Lunas',
                            'cancelled' => 'Dibatalkan',
                        ][$member->status] ?? $member->status;
                    @endphp
                    <div class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                        <div>
                            <p class="text-sm font-bold text-gray-900">{{ $member->user->name }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $member->user->email }} · {{ $member->role === 'organizer' ? 'Pengaju rombel' : 'Anggota' }}</p>
                            <p class="mt-2 text-xs font-semibold {{ $member->status === 'paid' ? 'text-green-700' : 'text-amber-700' }}">
                                {{ $memberStatus }}
                                @if($member->invoice)
                                    · {{ $member->invoice->invoice_number }} · Sisa Rp {{ number_format($member->invoice->remaining_amount, 0, ',', '.') }}
                                @endif
                            </p>
                        </div>

                        @if($member->invoice && $member->invoice->status !== 'paid')
                            <form method="POST" action="{{ route('admin.package-booking.cohorts.payments.store', $member->invoice) }}" class="flex flex-col gap-2 sm:flex-row">
                                @csrf
                                <input type="number" name="amount" min="1" max="{{ $member->invoice->remaining_amount }}" value="{{ $member->invoice->remaining_amount }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm sm:w-40 focus:border-primary focus:ring-primary">
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
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">Belum ada pengajuan rombel.</div>
    @endforelse

    {{ $groupApplications->links() }}
</div>
