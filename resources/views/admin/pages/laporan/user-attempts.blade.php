@extends('admin.layout.admin')

@section('title', 'Riwayat Attempt Siswa')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('admin.laporan.show', $tryout->tryout_id) }}" class="text-sm font-semibold text-primary hover:underline">← Kembali ke laporan tryout</a>
            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Riwayat pengerjaan</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $tryout->name }} · {{ $user->email }}</p>
        </div>
        <span class="inline-flex w-fit rounded-full bg-primary/10 px-3 py-1.5 text-sm font-semibold text-primary">{{ $attempts->count() }} attempt</span>
    </div>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-4"><h2 class="font-semibold text-gray-900">Pilih attempt</h2><p class="mt-1 text-sm text-gray-500">Setiap attempt menyimpan jawaban dan hasilnya sendiri.</p></div>
        <div class="divide-y divide-gray-100">
            @foreach($attempts as $row)
                @php
                    $attempt = $row['attempt'];
                    $startedAt = $attempt->started_at ? \Carbon\Carbon::parse($attempt->started_at) : null;
                    $finishedAt = $attempt->finished_at ? \Carbon\Carbon::parse($attempt->finished_at) : null;
                @endphp
                <article class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div><p class="font-semibold text-gray-900">Attempt ke-{{ $row['number'] }}</p><p class="mt-1 text-sm text-gray-500">{{ $startedAt?->format('d M Y, H:i') ?? '-' }} · {{ $finishedAt ? 'Selesai '.$finishedAt->format('d M Y, H:i') : 'Belum selesai' }}</p><p class="mt-2 text-sm font-semibold text-primary">{{ $row['score_display'] }}</p></div>
                    <a href="{{ route('admin.laporan.attempt', [$tryout->tryout_id, $attempt->attempt_token]) }}" class="inline-flex min-h-10 w-fit items-center gap-2 rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary transition hover:bg-primary hover:text-white"><i class="ri-file-search-line"></i>Lihat detail</a>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection
