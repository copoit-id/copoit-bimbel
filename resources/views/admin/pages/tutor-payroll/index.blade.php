@extends('admin.layout.admin')

@section('title', 'Penggajian Tutor')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Penggajian Tutor</h1>
            <p class="text-sm text-gray-500">Rekap dan nominal dihitung otomatis saat absensi Hadir/Terlambat disetujui admin.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-modal-target="set-tutor-honor-modal" data-modal-toggle="set-tutor-honor-modal"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary hover:text-white">
                <i class="ri-money-dollar-circle-line"></i>
                Atur Honor Tutor
            </button>
        </div>
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
                    <th class="px-4 py-3">Sesi Penggajian</th>
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
                        <td class="px-4 py-3"><p class="font-semibold text-gray-900">{{ $payroll->tentor->name }}</p><p class="text-xs text-gray-500">Honor aktif: Rp {{ number_format($payroll->tentor->honor_per_attendance, 0, ',', '.') }} / kehadiran</p></td>
                        <td class="px-4 py-3">
                            <p>{{ $payroll->items->count() }} sesi masuk gaji</p>
                            @if($pendingAttendanceCounts->get($payroll->tentor_id, 0))
                                <p class="mt-1 text-xs font-medium text-amber-700">{{ $pendingAttendanceCounts->get($payroll->tentor_id) }} sesi menunggu persetujuan</p>
                            @endif
                            <button type="button" data-modal-target="tutor-payroll-sessions-modal-{{ $payroll->id }}" data-modal-toggle="tutor-payroll-sessions-modal-{{ $payroll->id }}" class="mt-2 text-xs font-semibold text-primary hover:underline">Lihat detail sesi</button>
                        </td>
                        <td class="px-4 py-3">Rp {{ number_format($payroll->gross_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($payroll->adjustment_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900">Rp {{ number_format($payroll->net_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $payroll->status === 'paid' ? 'bg-green-100 text-green-700' : ($payroll->status === 'approved' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700') }}">{{ ucfirst($payroll->status) }}</span></td>
                        <td class="px-4 py-3">
                            <button type="button" data-modal-target="edit-tutor-payroll-modal-{{ $payroll->id }}" data-modal-toggle="edit-tutor-payroll-modal-{{ $payroll->id }}" class="font-semibold text-primary hover:underline">Kelola</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">Belum ada rekap. Rekap dibuat otomatis saat absensi tutor disetujui.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $payrolls->links() }}
</div>

<div id="set-tutor-honor-modal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full overflow-y-auto overflow-x-hidden p-4 md:inset-0">
    <div class="relative max-h-full w-full max-w-xl">
        <div class="relative rounded-lg bg-white shadow">
            <div class="flex items-center justify-between border-b p-5">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Atur Honor Tutor</h3>
                    <p class="mt-1 text-sm text-gray-500">Honor ini dipakai otomatis setiap absensi tutor disetujui admin.</p>
                </div>
                <button type="button" data-modal-hide="set-tutor-honor-modal" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-900"><i class="ri-close-line text-xl"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.tutor-payrolls.honor.update') }}">
                @csrf
                <div class="space-y-5 p-6">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700">Tutor</label>
                        <select name="tentor_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5">
                            <option value="">Pilih Tutor</option>
                            @foreach($tentors as $tentor)
                                <option value="{{ $tentor->id }}" @selected((string) old('tentor_id') === (string) $tentor->id)>{{ $tentor->name }} · saat ini Rp {{ number_format($tentor->honor_per_attendance, 0, ',', '.') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700">Honor per Kehadiran</label>
                        <input type="number" name="honor_per_attendance" value="{{ old('honor_per_attendance') }}" min="1" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5" placeholder="Contoh: 150000">
                        <p class="mt-2 text-xs text-gray-500">Dihitung untuk absensi berstatus Hadir atau Terlambat yang sudah disetujui admin.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t px-6 py-4">
                    <button type="button" data-modal-hide="set-tutor-honor-modal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
                    <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan Honor</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($payrolls as $payroll)
@php
    $attendanceDetails = $attendanceDetailsByTutor->get($payroll->tentor_id, collect());
    $payrollAttendanceIds = $payroll->items->pluck('tutor_attendance_id')->filter()->map(fn ($id) => (int) $id)->all();
    $attendanceStatusCounts = $attendanceDetails->countBy('status');
    $payrollSessionCount = $attendanceDetails->filter(
        fn ($attendance) => in_array((int) $attendance->id, $payrollAttendanceIds, true)
    )->count();
    $statusBadges = [
        'present' => ['label' => 'Hadir', 'class' => 'border-green-200 bg-green-50 text-green-700'],
        'late' => ['label' => 'Terlambat', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'],
        'absent' => ['label' => 'Tidak Hadir', 'class' => 'border-red-200 bg-red-50 text-red-700'],
        'excused' => ['label' => 'Izin', 'class' => 'border-blue-200 bg-blue-50 text-blue-700'],
    ];
@endphp
<div id="tutor-payroll-sessions-modal-{{ $payroll->id }}" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full overflow-y-auto overflow-x-hidden p-4 md:inset-0">
    <div class="relative max-h-full w-full max-w-2xl">
        <div class="relative overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 p-5">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Detail Sesi Tutor</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $payroll->tentor->name }} · {{ $payroll->period_start->translatedFormat('d M Y') }} - {{ $payroll->period_end->translatedFormat('d M Y') }}</p>
                </div>
                <button type="button" data-modal-hide="tutor-payroll-sessions-modal-{{ $payroll->id }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-900"><i class="ri-close-line text-xl"></i></button>
            </div>
            <div class="grid grid-cols-2 gap-px border-b border-gray-100 bg-gray-100 sm:grid-cols-5">
                @foreach([
                    ['label' => 'Hadir', 'count' => $attendanceStatusCounts->get('present', 0), 'class' => 'border-green-200 bg-green-50 text-green-700'],
                    ['label' => 'Terlambat', 'count' => $attendanceStatusCounts->get('late', 0), 'class' => 'border-amber-200 bg-amber-50 text-amber-700'],
                    ['label' => 'Izin', 'count' => $attendanceStatusCounts->get('excused', 0), 'class' => 'border-blue-200 bg-blue-50 text-blue-700'],
                    ['label' => 'Tidak Hadir', 'count' => $attendanceStatusCounts->get('absent', 0), 'class' => 'border-red-200 bg-red-50 text-red-700'],
                    ['label' => 'Masuk Gaji', 'count' => $payrollSessionCount, 'class' => 'border-primary/30 bg-primary/5 text-primary'],
                ] as $stat)
                    <div class="bg-white px-4 py-3 text-center">
                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $stat['class'] }}">{{ $stat['label'] }}</span>
                        <p class="mt-2 text-lg font-bold text-gray-900">{{ $stat['count'] }}</p>
                    </div>
                @endforeach
            </div>
            <div class="max-h-[60vh] space-y-2 overflow-y-auto p-4">
                @forelse($attendanceDetails as $attendance)
                    @php
                        $statusBadge = $statusBadges[$attendance->status] ?? ['label' => ucfirst($attendance->status), 'class' => 'border-slate-200 bg-slate-50 text-slate-700'];
                        $isInPayroll = in_array((int) $attendance->id, $payrollAttendanceIds, true);
                        $sessionTitle = $attendance->session?->schedule?->title ?? $attendance->session?->class?->title ?? 'Sesi kelas';
                    @endphp
                    <div class="flex flex-col gap-3 border border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $sessionTitle }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $attendance->session?->session_date?->translatedFormat('l, d M Y') }} · {{ $attendance->session?->start_at?->format('H:i') }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span>
                                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $attendance->approval_status === 'approved' ? 'border-green-200 bg-green-50 text-green-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">{{ $attendance->approval_status === 'approved' ? 'Disetujui admin' : 'Menunggu persetujuan' }}</span>
                            </div>
                        </div>
                        <span class="w-fit rounded-full border px-3 py-1.5 text-xs font-semibold {{ $isInPayroll ? 'border-primary/30 bg-primary/5 text-primary' : 'border-slate-200 bg-slate-50 text-slate-600' }}">{{ $isInPayroll ? 'Masuk gaji' : 'Tidak masuk gaji' }}</span>
                    </div>
                @empty
                    <div class="p-10 text-center text-sm text-gray-500">Belum ada absensi tutor pada periode ini.</div>
                @endforelse
            </div>
            <div class="flex justify-end border-t border-gray-100 p-4"><button type="button" data-modal-hide="tutor-payroll-sessions-modal-{{ $payroll->id }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Tutup</button></div>
        </div>
    </div>
</div>

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
                    <div class="grid grid-cols-2 gap-4 rounded-lg bg-gray-50 p-4 text-sm"><div><p class="text-gray-500">Bruto</p><p class="font-semibold text-gray-900">Rp {{ number_format($payroll->gross_amount, 0, ',', '.') }}</p></div><div><p class="text-gray-500">Sesi disetujui</p><p class="font-semibold text-gray-900">{{ $payroll->items->count() }} sesi</p></div></div>
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
