@extends('admin.layout.admin')

@section('title', 'Detail Siswa')

@push('styles')
<style>[x-cloak] { display: none !important; }</style>
@endpush

@section('content')
@php
    $roleLabels = [
        'admin' => 'Admin',
        'admin_demo' => 'Admin Demo',
        'user' => 'Siswa',
        'konsultan' => 'Konsultan',
        'tutor' => 'Tutor',
    ];
    $roleClass = match($user->role) {
        'admin' => 'bg-red-100 text-red-700',
        'admin_demo' => 'bg-amber-100 text-amber-700',
        'user' => 'bg-emerald-100 text-emerald-700',
        'konsultan' => 'bg-blue-100 text-blue-700',
        'tutor' => 'bg-violet-100 text-violet-700',
        default => 'bg-gray-100 text-gray-700',
    };
    $activePackageAccess = $user->userPackageAccess->filter(fn ($access) => $access->is_active);
    $formatDate = fn ($date, $format = 'd M Y') => $date ? \Illuminate\Support\Carbon::parse($date)->translatedFormat($format) : '—';
    $formatMoney = fn ($amount) => 'Rp '.number_format((float) $amount, 0, ',', '.');
@endphp

<div class="space-y-6" x-data="{ activeTab: 'profile' }">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <nav class="flex items-center gap-2 text-sm text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('admin.user.index') }}" class="hover:text-primary">Manajemen User</a>
            <i class="ri-arrow-right-s-line text-gray-300"></i>
            <span class="font-medium text-gray-700">Detail {{ $user->role === 'user' ? 'Siswa' : 'User' }}</span>
        </nav>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.user.report', $user) }}" class="inline-flex items-center gap-2 rounded-lg border border-amber-200 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50">
                <i class="ri-bar-chart-line"></i>Laporan Belajar
            </a>
            <a href="{{ route('admin.user.edit', $user) }}" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90">
                <i class="ri-edit-line"></i>Edit Data
            </a>
            <a href="{{ route('admin.user.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                <i class="ri-arrow-left-line"></i>Kembali
            </a>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-primary/20 bg-gradient-to-br from-primary via-primary to-emerald-700 text-white">
        <div class="flex flex-col gap-5 p-6 md:flex-row md:items-center md:p-8">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=ffffff&color=166534&size=160" alt="{{ $user->name }}" class="h-20 w-20 rounded-2xl border-4 border-white/30">
            <div class="min-w-0 flex-1">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold md:text-3xl">{{ $user->name }}</h1>
                    <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold">{{ $roleLabels[$user->role] ?? \Illuminate\Support\Str::headline($user->role) }}</span>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $user->status === 'aktif' ? 'bg-emerald-200 text-emerald-950' : 'bg-gray-200 text-gray-700' }}">{{ $user->status === 'aktif' ? 'Akun Aktif' : 'Akun Nonaktif' }}</span>
                </div>
                <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-white/85">
                    <span class="inline-flex items-center gap-1.5"><i class="ri-mail-line"></i>{{ $user->email }}</span>
                    <span class="inline-flex items-center gap-1.5"><i class="ri-phone-line"></i>{{ $user->phone ?: 'Nomor belum diisi' }}</span>
                    <span class="inline-flex items-center gap-1.5"><i class="ri-calendar-line"></i>Bergabung {{ $formatDate($user->created_at) }}</span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm"><p class="text-xs text-white/70">Paket aktif</p><p class="mt-1 text-xl font-bold">{{ $activePackageAccess->count() }}</p></div>
                <div class="rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm"><p class="text-xs text-white/70">Kehadiran</p><p class="mt-1 text-xl font-bold">{{ $attendanceSummary['rate'] !== null ? $attendanceSummary['rate'].'%' : '—' }}</p></div>
                <div class="rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm"><p class="text-xs text-white/70">Rombel</p><p class="mt-1 text-xl font-bold">{{ $user->studyGroups->count() }}</p></div>
                <div class="rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm"><p class="text-xs text-white/70">Pembayaran</p><p class="mt-1 text-xl font-bold">{{ $user->payments->count() + $user->billInvoices->count() }}</p></div>
            </div>
        </div>
    </section>

    <div class="rounded-xl border border-gray-200 bg-white p-2">
        <div class="flex gap-1 overflow-x-auto" role="tablist" aria-label="Kategori detail user">
            @foreach ([
                ['profile', 'ri-user-3-line', 'Data Diri'],
                ['program', 'ri-book-open-line', 'Program & Periode'],
                ['payment', 'ri-bank-card-line', 'Pembayaran'],
                ['attendance', 'ri-calendar-check-line', 'Kehadiran'],
                ['class', 'ri-team-line', 'Kelas & Rombel'],
            ] as [$key, $icon, $label])
                <button type="button" @click="activeTab = '{{ $key }}'" :class="activeTab === '{{ $key }}' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50'" class="inline-flex shrink-0 items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition">
                    <i class="{{ $icon }} text-base"></i>{{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div x-show="activeTab === 'profile'" x-cloak>
        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-xl border border-gray-200 bg-white p-6">
                <div class="mb-5 flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-lg bg-primary/10 text-primary"><i class="ri-user-3-line text-xl"></i></span><div><h2 class="font-semibold text-gray-900">Identitas & Kontak</h2><p class="text-sm text-gray-500">Data utama akun siswa</p></div></div>
                <dl class="grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-gray-500">Nama lengkap</dt><dd class="mt-1 font-medium text-gray-900">{{ $user->name }}</dd></div>
                    <div><dt class="text-gray-500">Tanggal lahir</dt><dd class="mt-1 font-medium text-gray-900">{{ $formatDate($user->birthday) }}</dd></div>
                    <div><dt class="text-gray-500">Email</dt><dd class="mt-1 break-all font-medium text-gray-900">{{ $user->email }}</dd></div>
                    <div><dt class="text-gray-500">Nomor WhatsApp/telepon</dt><dd class="mt-1 font-medium text-gray-900">{{ $user->phone ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Username</dt><dd class="mt-1 font-medium text-gray-900">{{ $user->username }}</dd></div>
                    <div><dt class="text-gray-500">Email</dt><dd class="mt-1 font-medium text-gray-900">{{ $user->email_verified_at ? 'Terverifikasi '.$formatDate($user->email_verified_at, 'd M Y, H:i') : 'Belum terverifikasi' }}</dd></div>
                </dl>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6">
                <div class="mb-5 flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-lg bg-blue-50 text-blue-600"><i class="ri-graduation-cap-line text-xl"></i></span><div><h2 class="font-semibold text-gray-900">Tujuan & Informasi Akun</h2><p class="text-sm text-gray-500">Target pendidikan dan jejak pendaftaran</p></div></div>
                <dl class="grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-gray-500">Institusi tujuan</dt><dd class="mt-1 font-medium text-gray-900">{{ $user->participant_destination_institution_name ?: ($user->participantDestinationCategory?->parent?->name ?: '—') }}</dd></div>
                    <div><dt class="text-gray-500">Program tujuan</dt><dd class="mt-1 font-medium text-gray-900">{{ $user->participant_destination_program_name ?: ($user->participantDestinationCategory?->name ?: '—') }}</dd></div>
                    <div><dt class="text-gray-500">Role akun</dt><dd class="mt-1"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $roleClass }}">{{ $roleLabels[$user->role] ?? \Illuminate\Support\Str::headline($user->role) }}</span></dd></div>
                    <div><dt class="text-gray-500">Status akun</dt><dd class="mt-1 font-medium text-gray-900">{{ $user->status === 'aktif' ? 'Aktif' : 'Nonaktif' }}</dd></div>
                    <div><dt class="text-gray-500">Direferensikan oleh</dt><dd class="mt-1 font-medium text-gray-900">{{ $user->referredBy?->name ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Tanggal daftar</dt><dd class="mt-1 font-medium text-gray-900">{{ $formatDate($user->created_at, 'd M Y, H:i') }}</dd></div>
                    <div><dt class="text-gray-500">Terakhir diperbarui</dt><dd class="mt-1 font-medium text-gray-900">{{ $formatDate($user->updated_at, 'd M Y, H:i') }}</dd></div>
                    <div><dt class="text-gray-500">ID user</dt><dd class="mt-1 font-medium text-gray-900">#{{ $user->id }}</dd></div>
                </dl>
            </section>
        </div>
    </div>

    <div x-show="activeTab === 'program'" x-cloak class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Akses paket aktif</p><p class="mt-1 text-3xl font-bold text-primary">{{ $activePackageAccess->count() }}</p></div>
            <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Total riwayat paket</p><p class="mt-1 text-3xl font-bold text-primary">{{ $user->userPackageAccess->count() }}</p></div>
            <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Akses kelas langsung</p><p class="mt-1 text-3xl font-bold text-primary">{{ $user->classAccess->count() }}</p></div>
        </div>
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-6 py-5"><h2 class="font-semibold text-gray-900">Paket & Periode Les</h2><p class="mt-1 text-sm text-gray-500">Seluruh akses paket, termasuk masa aktif dan status pembayarannya.</p></div>
            @forelse ($user->userPackageAccess as $access)
                @php $accessStatus = $access->is_active ? 'Aktif' : ($access->is_expired ? 'Berakhir' : \Illuminate\Support\Str::headline($access->status)); @endphp
                <div class="grid gap-4 border-b border-gray-100 p-5 last:border-0 lg:grid-cols-[minmax(0,1fr)_auto_auto] lg:items-center">
                    <div><p class="font-semibold text-gray-900">{{ $access->package?->name ?: 'Paket tidak tersedia' }}</p><p class="mt-1 text-sm text-gray-500">Mulai {{ $formatDate($access->start_date) }} · Berakhir {{ $access->end_date ? $formatDate($access->end_date) : 'Tanpa batas waktu' }}</p>@if($access->notes)<p class="mt-2 text-sm text-gray-600">{{ $access->notes }}</p>@endif</div>
                    <div class="text-sm"><p class="text-gray-500">Nilai akses</p><p class="mt-1 font-semibold text-gray-900">{{ $access->payment_amount !== null ? $formatMoney($access->payment_amount) : '—' }}</p></div>
                    <div class="flex flex-wrap gap-2 lg:justify-end"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $access->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">{{ $accessStatus }}</span><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ \Illuminate\Support\Str::headline($access->payment_status) }}</span></div>
                </div>
            @empty
                <div class="px-6 py-12 text-center text-sm text-gray-500"><i class="ri-book-open-line mb-2 block text-3xl text-gray-300"></i>Belum ada paket les yang diberikan ke siswa ini.</div>
            @endforelse
        </section>
    </div>

    <div x-show="activeTab === 'payment'" x-cloak class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Pembayaran paket diterima</p><p class="mt-1 text-2xl font-bold text-primary">{{ $formatMoney($paymentSummary['paid']) }}</p></div>
            <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Tagihan rutin tertunggak</p><p class="mt-1 text-2xl font-bold text-primary">{{ $formatMoney($paymentSummary['outstanding']) }}</p></div>
            <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Total dokumen transaksi</p><p class="mt-1 text-3xl font-bold text-primary">{{ $user->payments->count() + $user->billInvoices->count() }}</p></div>
        </div>
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-6 py-5"><h2 class="font-semibold text-gray-900">Transaksi Paket</h2><p class="mt-1 text-sm text-gray-500">Riwayat pembelian paket dan progres cicilan bila tersedia.</p></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm"><thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-6 py-3">Transaksi</th><th class="px-6 py-3">Paket</th><th class="px-6 py-3">Nilai</th><th class="px-6 py-3">Status</th><th class="px-6 py-3">Tanggal</th></tr></thead><tbody class="divide-y divide-gray-100">
                @forelse ($user->payments as $payment)
                    @php $paidAmount = $payment->paid_amount; @endphp
                    <tr><td class="px-6 py-4"><p class="font-medium text-gray-900">{{ $payment->transaction_id }}</p><p class="mt-1 text-xs text-gray-500">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $payment->payment_method)) }}</p></td><td class="px-6 py-4 text-gray-700">{{ $payment->package?->name ?: '—' }}</td><td class="px-6 py-4"><p class="font-medium text-gray-900">{{ $formatMoney($payment->total_amount) }}</p><p class="mt-1 text-xs text-gray-500">Terbayar {{ $formatMoney($paidAmount) }}@if($payment->installments_count) <span>· {{ $payment->installments_count }} cicilan</span> @endif</p></td><td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $payment->status === 'success' ? 'bg-emerald-100 text-emerald-700' : ($payment->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">{{ \Illuminate\Support\Str::headline($payment->status) }}</span></td><td class="px-6 py-4 text-gray-600">{{ $formatDate($payment->paid_at ?: $payment->created_at, 'd M Y, H:i') }}</td></tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">Belum ada transaksi paket.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-6 py-5"><h2 class="font-semibold text-gray-900">Tagihan Berkala</h2><p class="mt-1 text-sm text-gray-500">Tagihan periodik yang ditetapkan untuk siswa ini.</p></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm"><thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-6 py-3">Tagihan</th><th class="px-6 py-3">Periode</th><th class="px-6 py-3">Pembayaran</th><th class="px-6 py-3">Jatuh tempo</th><th class="px-6 py-3">Status</th></tr></thead><tbody class="divide-y divide-gray-100">
                @forelse ($user->billInvoices as $invoice)
                    <tr><td class="px-6 py-4"><p class="font-medium text-gray-900">{{ $invoice->title }}</p><p class="mt-1 text-xs text-gray-500">{{ $invoice->invoice_number }}</p></td><td class="px-6 py-4 text-gray-600">{{ $formatDate($invoice->period_start) }} – {{ $formatDate($invoice->period_end) }}</td><td class="px-6 py-4"><p class="font-medium text-gray-900">{{ $formatMoney($invoice->paid_amount) }} / {{ $formatMoney($invoice->amount) }}</p><p class="mt-1 text-xs text-gray-500">Sisa {{ $formatMoney($invoice->remaining_amount) }}</p></td><td class="px-6 py-4 text-gray-600">{{ $formatDate($invoice->due_date) }}</td><td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $invoice->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($invoice->status === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ \Illuminate\Support\Str::headline($invoice->status) }}</span></td></tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">Belum ada tagihan berkala.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    </div>

    <div x-show="activeTab === 'attendance'" x-cloak class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Total sesi tercatat</p><p class="mt-1 text-3xl font-bold text-primary">{{ $attendanceSummary['total'] }}</p></div>
            <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Hadir</p><p class="mt-1 text-3xl font-bold text-primary">{{ $attendanceSummary['present'] }}</p></div>
            <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Terlambat</p><p class="mt-1 text-3xl font-bold text-primary">{{ $attendanceSummary['late'] }}</p></div>
            <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Tidak hadir</p><p class="mt-1 text-3xl font-bold text-primary">{{ $attendanceSummary['absent'] }}</p></div>
            <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Persentase hadir</p><p class="mt-1 text-3xl font-bold text-primary">{{ $attendanceSummary['rate'] !== null ? $attendanceSummary['rate'].'%' : '—' }}</p></div>
        </div>
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-6 py-5"><h2 class="font-semibold text-gray-900">Riwayat Kehadiran</h2><p class="mt-1 text-sm text-gray-500">Status pertemuan siswa, waktu check-in, dan catatan admin.</p></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[800px] text-left text-sm"><thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-6 py-3">Sesi kelas</th><th class="px-6 py-3">Rombel / Tutor</th><th class="px-6 py-3">Check-in</th><th class="px-6 py-3">Status</th><th class="px-6 py-3">Catatan</th></tr></thead><tbody class="divide-y divide-gray-100">
                @forelse ($user->classAttendances as $attendance)
                    @php $attendanceClass = match($attendance->status) { 'present' => 'bg-emerald-100 text-emerald-700', 'late' => 'bg-amber-100 text-amber-700', 'absent' => 'bg-red-100 text-red-700', default => 'bg-blue-100 text-blue-700' }; @endphp
                    <tr><td class="px-6 py-4"><p class="font-medium text-gray-900">{{ $attendance->session?->class?->title ?: 'Sesi kelas' }}</p><p class="mt-1 text-xs text-gray-500">{{ $formatDate($attendance->session?->session_date, 'd M Y') }}</p></td><td class="px-6 py-4 text-gray-600"><p>{{ $attendance->session?->studyGroup?->name ?: '—' }}</p><p class="mt-1 text-xs text-gray-500">{{ $attendance->session?->tentor?->name ?: 'Tutor belum ditentukan' }}</p></td><td class="px-6 py-4 text-gray-600">{{ $formatDate($attendance->check_in_at, 'd M Y, H:i') }}</td><td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $attendanceClass }}">{{ \Illuminate\Support\Str::headline($attendance->status) }}</span></td><td class="px-6 py-4 text-gray-600">{{ $attendance->notes ?: '—' }}</td></tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">Belum ada kehadiran yang tercatat.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    </div>

    <div x-show="activeTab === 'class'" x-cloak class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-xl border border-gray-200 bg-white"><div class="border-b border-gray-100 px-6 py-5"><h2 class="font-semibold text-gray-900">Rombel</h2><p class="mt-1 text-sm text-gray-500">Kelompok belajar yang sedang atau pernah diikuti.</p></div><div class="divide-y divide-gray-100">@forelse ($user->studyGroups as $group)<div class="px-6 py-4"><p class="font-medium text-gray-900">{{ $group->name }}</p><p class="mt-1 text-sm text-gray-500">{{ $group->description ?: 'Tanpa deskripsi' }}</p><span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $group->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">{{ $group->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>@empty<div class="px-6 py-12 text-center text-sm text-gray-500">Belum tergabung dalam rombel.</div>@endforelse</div></section>
        <section class="rounded-xl border border-gray-200 bg-white"><div class="border-b border-gray-100 px-6 py-5"><h2 class="font-semibold text-gray-900">Akses Kelas Langsung</h2><p class="mt-1 text-sm text-gray-500">Kelas yang diberikan di luar akses dari paket.</p></div><div class="divide-y divide-gray-100">@forelse ($user->classAccess as $classAccess)<div class="flex items-start justify-between gap-4 px-6 py-4"><div><p class="font-medium text-gray-900">{{ $classAccess->class?->title ?: 'Kelas tidak tersedia' }}</p><p class="mt-1 text-sm text-gray-500">Mulai {{ $formatDate($classAccess->started_at) }} · Berakhir {{ $classAccess->expires_at ? $formatDate($classAccess->expires_at) : 'Tanpa batas waktu' }}</p><p class="mt-1 text-xs text-gray-500">Sumber: {{ \Illuminate\Support\Str::headline($classAccess->access_source) }}</p></div><span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $classAccess->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">{{ $classAccess->is_active ? 'Aktif' : \Illuminate\Support\Str::headline($classAccess->status) }}</span></div>@empty<div class="px-6 py-12 text-center text-sm text-gray-500">Belum ada akses kelas langsung.</div>@endforelse</div></section>
    </div>
</div>
@endsection
