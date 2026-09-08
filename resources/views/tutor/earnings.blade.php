@extends('tutor.layout')

@section('title', 'Penghasilan Tutor')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-gray-500">Laporan honor</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">Penghasilan per Pertemuan</h1>
            <p class="mt-1 text-sm text-gray-500">Status pembayaran ditetapkan oleh admin melalui penggajian tutor.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-lg bg-primary/10 px-4 py-2.5 text-sm font-semibold text-primary"><i class="ri-money-dollar-circle-line text-lg"></i>Honor dari absensi yang disetujui</span>
    </div>

    <section class="grid gap-4 sm:grid-cols-2">
        <article class="rounded-xl border border-gray-200 bg-white p-5">
            <div class="flex items-start justify-between gap-4"><div><p class="text-sm text-gray-500">Sudah dibayar</p><p class="mt-2 text-2xl font-bold text-emerald-700">Rp {{ number_format((int) ($summary->paid_amount ?? 0), 0, ',', '.') }}</p></div><span class="rounded-lg bg-emerald-50 p-2 text-emerald-600"><i class="ri-checkbox-circle-line text-xl"></i></span></div>
        </article>
        <article class="rounded-xl border border-gray-200 bg-white p-5">
            <div class="flex items-start justify-between gap-4"><div><p class="text-sm text-gray-500">Menunggu pembayaran</p><p class="mt-2 text-2xl font-bold text-amber-700">Rp {{ number_format((int) ($summary->pending_amount ?? 0), 0, ',', '.') }}</p></div><span class="rounded-lg bg-amber-50 p-2 text-amber-600"><i class="ri-time-line text-xl"></i></span></div>
        </article>
    </section>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <header class="border-b border-gray-200 bg-gray-50 px-5 py-4"><h2 class="font-bold text-gray-900">Riwayat honor</h2><p class="mt-1 text-sm text-gray-500">Honor setiap pertemuan yang telah masuk ke penggajian Anda.</p></header>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[650px] text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Pertemuan</th><th class="px-5 py-3">Honor</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Dibayar</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $item)
                        <tr><td class="px-5 py-4">{{ $item->session_date?->translatedFormat('d M Y') ?? '—' }}</td><td class="px-5 py-4 font-medium text-gray-900">{{ $item->description }}</td><td class="px-5 py-4 font-semibold text-gray-900">Rp {{ number_format((int) $item->amount, 0, ',', '.') }}</td><td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->payroll?->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $item->payroll?->status === 'paid' ? 'Dibayar' : 'Menunggu' }}</span></td><td class="px-5 py-4">{{ $item->payroll?->paid_at?->translatedFormat('d M Y') ?? '—' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-gray-500"><i class="ri-wallet-3-line mb-2 block text-3xl text-gray-300"></i>Belum ada honor pertemuan yang masuk penggajian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $items->links() }}</div>
        @endif
    </section>
</div>
@endsection
