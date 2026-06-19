@extends('user.layout.new-user')

@section('title', 'Jadwal Kelas')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl border border-gray-100 bg-white p-6">
        <h1 class="text-2xl font-bold text-gray-900">Jadwal Kelas</h1>
        <p class="mt-1 text-sm text-gray-500">Lihat jadwal kelas aktif dan lakukan absensi sesuai waktu yang dibuka.</p>
    </div>

    <div class="grid gap-4">
        @forelse($sessions as $session)
            @php
                $attendance = $session->attendances->first();
                $setting = $session->schedule->attendanceSetting;
                $openAt = $session->start_at->copy()->subMinutes($setting?->open_minutes_before ?? 15);
                $closeAt = ($session->end_at ?? $session->start_at)->copy()->addMinutes($setting?->close_minutes_after ?? 30);
                $canAttend = now()->between($openAt, $closeAt) && !$attendance && $session->status === 'scheduled';
            @endphp
            <div class="rounded-2xl border border-gray-100 bg-white p-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ $session->start_at->translatedFormat('l, d M Y') }}</p>
                        <h2 class="text-lg font-bold text-gray-900">{{ $session->class->title ?? 'Kelas' }}</h2>
                        <p class="text-sm text-gray-600">{{ $session->start_at->format('H:i') }}{{ $session->end_at ? ' - ' . $session->end_at->format('H:i') : '' }}</p>
                        @if($session->meeting_url)
                            <a href="{{ $session->meeting_url }}" target="_blank" class="mt-2 inline-flex text-sm font-medium text-primary hover:underline">Buka meeting</a>
                        @endif
                    </div>
                    <div class="md:w-72">
                        @if($attendance)
                            <div class="rounded-xl bg-green-50 p-3 text-sm text-green-700">
                                Sudah absen: {{ ucfirst($attendance->status) }} pada {{ $attendance->check_in_at?->format('H:i') }}
                            </div>
                        @elseif($canAttend)
                            <form method="POST" action="{{ route('user.class-schedule.attend', $session) }}" enctype="multipart/form-data" class="space-y-2">
                                @csrf
                                @if(($setting?->mode ?? 'button') === 'photo')
                                    <input type="file" name="photo" accept="image/*" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                @endif
                                <button class="w-full rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white">Absen Sekarang</button>
                            </form>
                        @else
                            <div class="rounded-xl bg-gray-50 p-3 text-sm text-gray-500">
                                Absensi dibuka {{ $openAt->format('H:i') }} - {{ $closeAt->format('H:i') }}.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center text-gray-500">
                Belum ada jadwal kelas.
            </div>
        @endforelse
    </div>

    {{ $sessions->links() }}
</div>
@endsection
