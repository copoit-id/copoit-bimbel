@extends('admin.layout.admin')

@section('title', 'Penggajian Tutor')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Penggajian Tutor</h1>
            <p class="text-sm text-gray-500">Rekap dihitung dari absensi Tutor berstatus hadir atau terlambat.</p>
        </div>
        <button type="button" data-modal-target="create-tutor-payroll-modal" data-modal-toggle="create-tutor-payroll-modal"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
            <i class="ri-add-line"></i>
            Tambah Penggajian
        </button>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('admin.tutor-payrolls.index') }}" class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4">
        <div>
            <label class="mb-1 block text-xs font-semibold text-gray-600">Mulai periode</label>
            <input type="date" name="period_start" value="{{ $periodStart->toDateString() }}" class="rounded-lg border border-gray-300 px-3 py-2">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold text-gray-600">Akhir periode</label>
            <input type="date" name="period_end" value="{{ $periodEnd->toDateString() }}" class="rounded-lg border border-gray-300 px-3 py-2">
        </div>
        <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Tampilkan</button>
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr>
                    <th class="px-4 py-3">Tutor</th>
                    <th class="px-4 py-3">Sesi Hadir</th>
                    <th class="px-4 py-3">Bruto</th>
                    <th class="px-4 py-3">Penyesuaian</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Kelola</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrolls as $payroll)
                    <tr class="border-t border-gray-100 align-top">
                        <td class="px-4 py-3"><p class="font-semibold text-gray-900">{{ $payroll->tentor->name }}</p><p class="text-xs text-gray-500">Rp {{ number_format($payroll->rate_per_attendance, 0, ',', '.') }} / kehadiran</p></td>
                        <td class="px-4 py-3">{{ $payroll->items->count() }} sesi</td>
                        <td class="px-4 py-3">Rp {{ number_format($payroll->gross_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($payroll->adjustment_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900">Rp {{ number_format($payroll->net_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $payroll->status === 'paid' ? 'bg-green-100 text-green-700' : ($payroll->status === 'approved' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700') }}">{{ ucfirst($payroll->status) }}</span></td>
                        <td class="px-4 py-3">
                            <button type="button" data-modal-target="edit-tutor-payroll-modal-{{ $payroll->id }}" data-modal-toggle="edit-tutor-payroll-modal-{{ $payroll->id }}" class="font-semibold text-primary hover:underline">Kelola</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">Belum ada rekap. Klik Generate Rekap untuk periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $payrolls->links() }}
</div>

<div id="create-tutor-payroll-modal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full overflow-y-auto overflow-x-hidden p-4 md:inset-0">
    <div class="relative max-h-full w-full max-w-2xl">
        <div class="relative rounded-lg bg-white shadow">
            <div class="flex items-center justify-between border-b p-5">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Tambah Penggajian Tutor</h3>
                    <p class="mt-1 text-sm text-gray-500">Nominal disimpan khusus untuk rekap periode ini.</p>
                </div>
                <button type="button" data-modal-hide="create-tutor-payroll-modal" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-900"><i class="ri-close-line text-xl"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.tutor-payrolls.generate') }}">
                @csrf
                <div class="grid gap-5 p-6 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700">Periode mulai</label>
                        <input type="date" name="period_start" value="{{ old('period_start', $periodStart->toDateString()) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700">Periode berakhir</label>
                        <input type="date" name="period_end" value="{{ old('period_end', $periodEnd->toDateString()) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-gray-700">Tutor</label>
                        <select name="tentor_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5">
                            <option value="">Pilih Tutor</option>
                            @foreach($tentors as $tentor)
                                <option value="{{ $tentor->id }}" @selected((string) old('tentor_id') === (string) $tentor->id)>{{ $tentor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-gray-700">Honor per kehadiran</label>
                        <input type="number" name="rate_per_attendance" value="{{ old('rate_per_attendance') }}" min="0" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5" placeholder="Contoh: 150000">
                        <p class="mt-2 text-xs text-gray-500">Total bruto = jumlah hadir atau terlambat × honor per kehadiran.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t px-6 py-4">
                    <button type="button" data-modal-hide="create-tutor-payroll-modal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
                    <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan Penggajian</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($payrolls as $payroll)
<div id="edit-tutor-payroll-modal-{{ $payroll->id }}" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full overflow-y-auto overflow-x-hidden p-4 md:inset-0">
    <div class="relative max-h-full w-full max-w-xl">
        <div class="relative rounded-lg bg-white shadow">
            <div class="flex items-center justify-between border-b p-5">
                <div><h3 class="text-lg font-semibold text-gray-900">Kelola Penggajian</h3><p class="mt-1 text-sm text-gray-500">{{ $payroll->tentor->name }} · {{ $payroll->items->count() }} kehadiran</p></div>
                <button type="button" data-modal-hide="edit-tutor-payroll-modal-{{ $payroll->id }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-900"><i class="ri-close-line text-xl"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.tutor-payrolls.update', $payroll) }}">
                @csrf
                @method('PUT')
                <div class="space-y-5 p-6">
                    <div class="grid grid-cols-2 gap-4 rounded-lg bg-gray-50 p-4 text-sm"><div><p class="text-gray-500">Bruto</p><p class="font-semibold text-gray-900">Rp {{ number_format($payroll->gross_amount, 0, ',', '.') }}</p></div><div><p class="text-gray-500">Nominal hadir</p><p class="font-semibold text-gray-900">Rp {{ number_format($payroll->rate_per_attendance, 0, ',', '.') }}</p></div></div>
                    <div><label class="mb-2 block text-sm font-semibold text-gray-700">Bonus / potongan</label><input type="number" name="adjustment_amount" value="{{ $payroll->adjustment_amount }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5"><p class="mt-1 text-xs text-gray-500">Gunakan angka negatif untuk potongan.</p></div>
                    <div><label class="mb-2 block text-sm font-semibold text-gray-700">Status pembayaran</label><select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2.5">@foreach(['draft' => 'Draft', 'approved' => 'Disetujui', 'paid' => 'Lunas'] as $key => $label)<option value="{{ $key }}" @selected($payroll->status === $key)>{{ $label }}</option>@endforeach</select></div>
                    <div><label class="mb-2 block text-sm font-semibold text-gray-700">Catatan</label><textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2.5" placeholder="Catatan pembayaran (opsional)">{{ $payroll->notes }}</textarea></div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t px-6 py-4"><button type="button" data-modal-hide="edit-tutor-payroll-modal-{{ $payroll->id }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button><button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
