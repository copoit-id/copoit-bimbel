@extends('admin.layout.admin')

@section('title', 'Data Tryout Siswa')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Data Tryout Siswa</h1>
            <p class="mt-1 text-sm text-gray-500">Lihat ringkasan pengerjaan dan rekap skor subtest setiap siswa.</p>
        </div>
    </div>

    <section class="mt-6 rounded-xl border border-border bg-white p-5 sm:p-6">
        <form method="GET" class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="search" name="search" value="{{ $search }}" placeholder="Cari nama, email, atau username..."
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/10">
            </div>
            <button class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">Cari</button>
            @if($search !== '')
                <a href="{{ route('admin.school.student-tryouts.index') }}" class="rounded-lg border border-gray-200 px-4 py-2.5 text-center text-sm font-semibold text-gray-600 hover:bg-gray-50">Reset</a>
            @endif
        </form>

        <div class="mt-6 overflow-x-auto rounded-lg border border-gray-100">
            <table class="w-full min-w-[760px] text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                    <tr>
                        <th class="px-5 py-3">Siswa</th>
                        <th class="px-5 py-3 text-center">Total Pengerjaan</th>
                        <th class="px-5 py-3 text-center">Selesai</th>
                        <th class="px-5 py-3 text-center">Rata-rata Skor</th>
                        <th class="px-5 py-3">Aktivitas Tryout Terakhir</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr class="border-t border-gray-100 hover:bg-gray-50/70">
                            <td class="px-5 py-4"><p class="font-semibold text-gray-900">{{ $student->name }}</p><p class="mt-0.5 text-xs text-gray-500">{{ $student->email }}</p></td>
                            <td class="px-5 py-4 text-center font-semibold text-gray-800">{{ $student->tryout_attempts_count }}</td>
                            <td class="px-5 py-4 text-center font-semibold text-primary">{{ $student->completed_tryout_attempts_count }}</td>
                            <td class="px-5 py-4 text-center font-semibold text-gray-800">{{ $student->average_tryout_score === null ? '—' : number_format((float) $student->average_tryout_score, 1, ',', '.') }}</td>
                            <td class="px-5 py-4">{{ $student->last_tryout_at ? \Carbon\Carbon::parse($student->last_tryout_at)->translatedFormat('d M Y, H:i') : '-' }}</td>
                            <td class="px-5 py-4 text-center"><a href="{{ route('admin.school.student-tryouts.show', $student) }}" class="inline-flex items-center gap-1 rounded-lg border border-primary px-3 py-2 text-xs font-semibold text-primary hover:bg-primary hover:text-white"><i class="ri-bar-chart-2-line"></i> Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">Belum ada siswa dengan data tryout.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $students->links() }}</div>
    </section>
@endsection
