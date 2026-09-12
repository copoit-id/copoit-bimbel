@extends('tutor.layout')

@section('title', 'Pembayaran Siswa')

@section('content')
<div class="space-y-6">
    <div>
        <p class="text-sm text-gray-500">Penerimaan pembayaran paket</p>
        <h1 class="text-2xl font-bold text-gray-900">Pembayaran siswa</h1>
        <p class="mt-1 text-sm text-gray-500">Pilih pertemuan. Siswa diambil dari rombel, sedangkan nominal dan siklus mengikuti paket.</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-[780px] w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-5 py-3">Pertemuan</th><th class="px-5 py-3">Rombel</th><th class="px-5 py-3">Paket</th><th class="px-5 py-3">Siklus / nominal</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sessions as $session)
                        @php $package = $session->studyGroup?->package; @endphp
                        <tr>
                            <td class="px-5 py-4"><p class="font-semibold text-gray-900">{{ $session->schedule?->title ?? 'Sesi belajar' }}</p><p class="mt-1 text-xs text-gray-500">{{ $session->start_at->locale('id')->translatedFormat('d M Y · H:i') }} WIB</p></td>
                            <td class="px-5 py-4 text-gray-700">{{ $session->studyGroup?->name }}</td>
                            <td class="px-5 py-4 font-medium text-gray-800">{{ $package?->name }}</td>
                            <td class="px-5 py-4"><p class="font-medium text-gray-800">Rp {{ number_format($package?->price ?? 0, 0, ',', '.') }}</p><p class="mt-1 text-xs text-gray-500">{{ ['per_session' => 'Setiap pertemuan', 'daily' => 'Harian', 'monthly' => 'Bulanan'][$package?->tutor_payment_frequency] ?? '—' }}</p></td>
                            <td class="px-5 py-4 text-right"><form method="POST" action="{{ route('tutor.package-payments.prepare', $session) }}">@csrf<x-ui.button type="submit" size="sm" icon="ri-hand-coin-line">Kelola pembayaran</x-ui.button></form></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-14 text-center text-gray-500">Belum ada jadwal rombel dengan pembayaran tutor aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sessions->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $sessions->links() }}</div>@endif
    </div>
</div>
@endsection
