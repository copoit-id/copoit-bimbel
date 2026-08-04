@extends('admin.layout.admin')

@section('title', 'Penggajian Tutor')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Penggajian Tutor</h1>
            <p class="text-sm text-gray-500">{{ $activeTab === 'payroll' ? 'Rekap dan nominal dihitung otomatis saat absensi Hadir/Terlambat disetujui admin.' : 'Atur honor per kehadiran tutor. Halaman ini tidak terikat periode penggajian.' }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <nav class="flex overflow-x-auto border-b border-gray-200" aria-label="Penggajian tutor">
        <a href="{{ route('admin.tutor-payrolls.index', $activeTab === 'payroll' && $hasPeriodFilter ? ['tab' => 'payroll', 'period_start' => $periodStart->toDateString(), 'period_end' => $periodEnd->toDateString()] : ['tab' => 'payroll']) }}" class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold {{ $activeTab === 'payroll' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"><i class="ri-wallet-3-line mr-1"></i>Penggajian</a>
        <a href="{{ route('admin.tutor-payrolls.index', ['tab' => 'honor']) }}" class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold {{ $activeTab === 'honor' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"><i class="ri-money-dollar-circle-line mr-1"></i>Honor Tutor</a>
    </nav>

    @if($activeTab === 'payroll')
    <form method="GET" action="{{ route('admin.tutor-payrolls.index') }}" class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4">
        <input type="hidden" name="tab" value="payroll">
        <div>
            <label class="mb-1 block text-xs font-semibold text-gray-600">Mulai periode</label>
            <input type="date" name="period_start" value="{{ $periodStart?->toDateString() }}" class="rounded-lg border border-gray-300 px-3 py-2">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold text-gray-600">Akhir periode</label>
            <input type="date" name="period_end" value="{{ $periodEnd?->toDateString() }}" class="rounded-lg border border-gray-300 px-3 py-2">
        </div>
        <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Terapkan periode</button>
        @if($hasPeriodFilter)
            <a href="{{ route('admin.tutor-payrolls.index', ['tab' => 'payroll']) }}" class="px-2 py-2 text-sm font-semibold text-primary hover:underline">Reset: semua periode</a>
        @endif
        <p class="w-full text-xs text-gray-500">Kosongkan kedua tanggal untuk menampilkan seluruh riwayat penggajian.</p>
    </form>

    <p class="text-sm text-gray-500">
        @if($hasPeriodFilter)
            Menampilkan rekap dengan sesi atau periode yang berada di antara {{ $periodStart->translatedFormat('d M Y') }}–{{ $periodEnd->translatedFormat('d M Y') }}.
        @else
            Menampilkan seluruh riwayat penggajian.
        @endif
    </p>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr>
                    <th class="px-4 py-3">Tutor</th>
                    <th class="px-4 py-3">Sesi / Absensi</th>
                    <th class="px-4 py-3">Bruto</th>
                    <th class="px-4 py-3">Penyesuaian</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payrolls as $payroll)
                    @php
                        $needsHonor = (int) $payroll->tentor->honor_per_attendance < 1;
                    @endphp
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
                        <td class="px-4 py-3">
                            @if($needsHonor)
                                <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Atur honor dulu</span>
                            @else
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $payroll->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700' }}">{{ $payroll->status === 'paid' ? 'Lunas' : 'Draft' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($needsHonor)
                                <a href="{{ route('admin.tutor-payrolls.index', ['tab' => 'honor']) }}" class="inline-flex items-center rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50">Atur honor</a>
                            @elseif($payroll->status === 'paid')
                                <button type="button" data-modal-target="edit-tutor-payroll-modal-{{ $payroll->id }}" data-modal-toggle="edit-tutor-payroll-modal-{{ $payroll->id }}" class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">Edit pembayaran</button>
                            @else
                                <button type="button" data-modal-target="edit-tutor-payroll-modal-{{ $payroll->id }}" data-modal-toggle="edit-tutor-payroll-modal-{{ $payroll->id }}" class="inline-flex items-center rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary/90">Atur pembayaran</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @foreach($unprocessedAttendances as $attendance)
                    @php
                        $sessionTitle = $attendance->session?->schedule?->title ?? $attendance->session?->class?->title ?? 'Sesi kelas';
                        $isPayableAttendance = $attendance->approval_status === 'approved' && in_array($attendance->status, ['present', 'late'], true);
                        $reason = $attendance->approval_status === 'pending'
                            ? 'Menunggu persetujuan admin'
                            : ($attendance->approval_status === 'rejected'
                                ? 'Absensi ditolak'
                                : ($isPayableAttendance ? 'Belum tersinkron ke rekap' : 'Tidak dihitung sebagai gaji'));
                    @endphp
                    <tr class="border-t border-amber-100 bg-amber-50/30 align-top">
                        <td class="px-4 py-3"><p class="font-semibold text-gray-900">{{ $attendance->tentor?->name ?? '-' }}</p><p class="text-xs text-gray-500">Belum ada rekap payroll</p></td>
                        <td class="px-4 py-3"><p class="font-medium text-gray-900">{{ $sessionTitle }}</p><p class="mt-1 text-xs text-gray-500">{{ $attendance->session?->session_date?->translatedFormat('d M Y') ?? '-' }}</p></td>
                        <td class="px-4 py-3">—</td>
                        <td class="px-4 py-3">—</td>
                        <td class="px-4 py-3 font-semibold text-gray-900">—</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($attendance->status) }}</span>
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $attendance->approval_status === 'approved' ? 'bg-green-100 text-green-700' : ($attendance->approval_status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ $reason }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs font-medium text-gray-500">Belum dapat dibayar</td>
                    </tr>
                @endforeach
                @if($payrolls->isEmpty() && $unprocessedAttendances->isEmpty())
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">Belum ada rekap atau absensi tutor pada periode ini.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        {{ $payrolls->links() }}
        @if($unprocessedAttendances->hasPages())
            <div class="text-sm text-gray-500">Absensi belum masuk gaji: {{ $unprocessedAttendances->links() }}</div>
        @endif
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
                <div><h3 class="text-lg font-semibold text-gray-900">Atur Pembayaran Tutor</h3><p class="mt-1 text-sm text-gray-500">{{ $payroll->tentor->name }} · {{ $payroll->items->count() }} kehadiran</p></div>
                <button type="button" data-modal-hide="edit-tutor-payroll-modal-{{ $payroll->id }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-900"><i class="ri-close-line text-xl"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.tutor-payrolls.update', $payroll) }}">
                @csrf
                @method('PUT')
                <div class="space-y-5 p-6">
                    <div class="grid grid-cols-2 gap-4 rounded-lg bg-gray-50 p-4 text-sm"><div><p class="text-gray-500">Bruto</p><p class="font-semibold text-gray-900">Rp {{ number_format($payroll->gross_amount, 0, ',', '.') }}</p></div><div><p class="text-gray-500">Sesi disetujui</p><p class="font-semibold text-gray-900">{{ $payroll->items->count() }} sesi</p></div></div>
                    <div><label class="mb-2 block text-sm font-semibold text-gray-700">Bonus / potongan</label><input type="number" name="adjustment_amount" value="{{ $payroll->adjustment_amount }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5"><p class="mt-1 text-xs text-gray-500">Gunakan angka negatif untuk potongan.</p></div>
                    <div><label class="mb-2 block text-sm font-semibold text-gray-700">Status pembayaran</label><select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2.5">@foreach(['draft' => 'Draft', 'paid' => 'Lunas'] as $key => $label)<option value="{{ $key }}" @selected($payroll->status === $key)>{{ $label }}</option>@endforeach</select></div>
                    <div><label class="mb-2 block text-sm font-semibold text-gray-700">Catatan</label><textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2.5" placeholder="Catatan pembayaran (opsional)">{{ $payroll->notes }}</textarea></div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t px-6 py-4"><button type="button" data-modal-hide="edit-tutor-payroll-modal-{{ $payroll->id }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button><button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach
@else
    <div class="border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-4 py-4 sm:px-5">
            <h2 class="font-semibold text-gray-900">Honor per Kehadiran</h2>
            <p class="mt-1 text-sm text-gray-500">Honor ini berlaku untuk semua kelas tutor dan langsung disinkronkan ke absensi yang sudah disetujui tetapi belum dibayar.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                    <tr>
                        <th class="px-4 py-3">Tutor</th>
                        <th class="px-4 py-3">Bidang</th>
                        <th class="px-4 py-3">Honor aktif</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($honorTentors as $tentor)
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900">{{ $tentor->name }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $tentor->email }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $tentor->expertise ?: '-' }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900">Rp {{ number_format($tentor->honor_per_attendance, 0, ',', '.') }} <span class="font-normal text-gray-500">/ kehadiran</span></td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" data-modal-target="edit-tutor-honor-modal-{{ $tentor->id }}" data-modal-toggle="edit-tutor-honor-modal-{{ $tentor->id }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">Edit honor</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-gray-500">Belum ada tutor aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $honorTentors->links() }}

    @foreach($honorTentors as $tentor)
        <div id="edit-tutor-honor-modal-{{ $tentor->id }}" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full overflow-y-auto overflow-x-hidden p-4 md:inset-0">
            <div class="relative max-h-full w-full max-w-md">
                <div class="relative overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 p-5">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Edit Honor Tutor</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $tentor->name }}</p>
                        </div>
                        <button type="button" data-modal-hide="edit-tutor-honor-modal-{{ $tentor->id }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-900"><i class="ri-close-line text-xl"></i></button>
                    </div>
                    <form method="POST" action="{{ route('admin.tutor-payrolls.honor.update') }}">
                        @csrf
                        <input type="hidden" name="tentor_id" value="{{ $tentor->id }}">
                        <div class="p-5">
                            <label for="honor_per_attendance_{{ $tentor->id }}" class="mb-2 block text-sm font-semibold text-gray-700">Honor per kehadiran</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">Rp</span>
                                <input id="honor_per_attendance_{{ $tentor->id }}" type="number" name="honor_per_attendance" min="1" step="1" required value="{{ old('honor_per_attendance', $tentor->honor_per_attendance) }}" class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Nilai ini tidak terikat periode dan digunakan untuk setiap kehadiran tutor yang disetujui admin.</p>
                        </div>
                        <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-4">
                            <button type="button" data-modal-hide="edit-tutor-honor-modal-{{ $tentor->id }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
                            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan honor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif
</div>
@endsection
