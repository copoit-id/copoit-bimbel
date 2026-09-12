@extends('tutor.layout')

@section('title', 'Pembayaran Siswa')

@section('content')
<div class="space-y-6">
    <div>
        <p class="text-sm text-gray-500">Penerimaan pembayaran paket</p>
        <h1 class="text-2xl font-bold text-gray-900">Pembayaran siswa</h1>
        <p class="mt-1 text-sm text-gray-500">Pilih pertemuan. Siswa diambil dari rombel, sedangkan nominal dan siklus mengikuti paket.</p>
    </div>

    <x-tab :tabs="collect(['all' => 'Semua', 'week' => 'Minggu', 'month' => 'Bulan'])->map(fn ($label, $range) => ['id' => $range, 'label' => $label, 'active' => $paymentRange === $range, 'href' => route('tutor.package-payments.index', ['range' => $range])])->values()->all()" variant="pills" class="overflow-x-auto" />
    @if($paymentPeriod)
        <form method="GET" action="{{ route('tutor.package-payments.index') }}" class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 sm:flex-row sm:items-end"><input type="hidden" name="range" value="{{ $paymentRange }}"><label class="block w-full sm:w-52"><span class="mb-1.5 block text-xs font-semibold text-gray-600">Bulan</span><select name="period_month" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach(range(1, 12) as $month)<option value="{{ $month }}" @selected($paymentPeriod['selected_month'] === $month)>{{ \Carbon\Carbon::create()->month($month)->locale('id')->translatedFormat('F') }}</option>@endforeach</select></label><label class="block w-full sm:w-36"><span class="mb-1.5 block text-xs font-semibold text-gray-600">Tahun</span><select name="period_year" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach($periodYears as $year)<option value="{{ $year }}" @selected($paymentPeriod['selected_year'] === $year)>{{ $year }}</option>@endforeach</select></label>@if($paymentRange === 'week')<label class="block w-full sm:w-64"><span class="mb-1.5 block text-xs font-semibold text-gray-600">Pekan</span><select name="period_week" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach($paymentPeriod['week_options'] as $week)<option value="{{ $week['value'] }}" @selected($paymentPeriod['selected_week'] === $week['value'])>{{ $week['label'] }}</option>@endforeach</select></label>@endif<x-ui.button type="submit" icon="ri-filter-3-line">Terapkan</x-ui.button></form>
    @endif
    <x-ui.card variant="flat" padding="none" class="overflow-visible rounded-2xl border border-gray-200">
    <div class="divide-y divide-gray-100">
        @forelse($paymentDays as $day)
            <section class="relative px-4 py-5 sm:px-6">@if(! $loop->last)<span class="absolute bottom-0 left-[2.35rem] top-[4.8rem] w-px bg-gray-100 sm:left-[3.35rem]"></span>@endif<div class="relative grid grid-cols-[3.25rem_minmax(0,1fr)] gap-3 sm:grid-cols-[4.5rem_minmax(0,1fr)] sm:gap-5"><div class="flex flex-col items-center"><div class="flex h-14 w-14 flex-col items-center justify-center rounded-2xl border text-center sm:h-16 sm:w-16 {{ $day['date_class'] }}"><span class="text-lg font-bold leading-none">{{ $day['date']->format('d') }}</span><span class="mt-1 text-[10px] font-semibold uppercase tracking-wide">{{ \Illuminate\Support\Str::substr($day['date']->locale('id')->translatedFormat('l'), 0, 3) }}</span></div><span class="relative mt-3 h-2.5 w-2.5 rounded-full ring-4 {{ $day['timeline_class'] }}"></span></div><div class="min-w-0 pb-1"><div class="mb-3 flex items-center justify-between gap-3"><div><h2 class="text-sm font-bold text-gray-900">{{ $day['label'] }}</h2><p class="mt-0.5 text-xs text-gray-500">{{ $day['sessions']->count() }} pertemuan</p></div><span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $day['badge_class'] }}">{{ $day['state'] }}</span></div><div class="space-y-2.5">@foreach($day['sessions'] as $session)@php($package = $session->payment_package)<article class="rounded-xl border border-gray-200 bg-white p-4 transition hover:border-primary/30 sm:flex sm:items-center sm:gap-5"><div class="shrink-0 sm:w-20"><p class="font-bold tabular-nums text-gray-900">{{ $session->start_at->format('H:i') }}</p><p class="text-xs text-gray-500">WIB</p></div><div class="mt-3 min-w-0 flex-1 sm:mt-0"><h3 class="font-bold text-gray-900">{{ $session->schedule?->title ?? 'Sesi belajar' }}</h3><p class="mt-1 text-xs text-gray-500"><i class="ri-group-line mr-1"></i>{{ $session->studyGroup?->name ?? 'Rombel' }} · {{ $package?->name ?? 'Paket belum jelas' }}</p>@if($package)<p class="mt-1 text-xs font-semibold text-primary">Rp {{ number_format($package->price, 0, ',', '.') }} · {{ \App\Services\TutorPackagePaymentService::billingFrequencyLabel($package->tutor_payment_frequency) }}</p>@endif</div>@if($package)<form method="POST" action="{{ route('tutor.package-payments.prepare', $session) }}" class="mt-3 sm:mt-0">@csrf<x-ui.button type="submit" size="sm" icon="ri-checkbox-circle-line">Konfirmasi bayar</x-ui.button></form>@endif</article>@endforeach</div></div></div></section>
        @empty
            <x-ui.card variant="flat" class="rounded-2xl border border-dashed border-gray-300 px-6 py-14 text-center">Belum ada jadwal rombel dengan pembayaran tutor aktif pada periode ini.</x-ui.card>
        @endforelse
        @if($sessions->hasPages()){{ $sessions->links() }}@endif
    </div></x-ui.card>
</div>
@endsection
