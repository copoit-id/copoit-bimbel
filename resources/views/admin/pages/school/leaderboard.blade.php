@extends('admin.layout.admin')

@section('content')
<div class="p-4 sm:p-6">
    <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900">Leaderboard</h1><p class="mt-1 text-sm text-gray-500">Peringkat hanya untuk siswa pada rombel yang ditautkan.</p></div>
    <form method="GET" class="mb-5 max-w-md"><label class="mb-1 block text-sm font-medium text-gray-700">Tryout</label><select name="tryout_id" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300"><option value="">Pilih tryout</option>@foreach($tryouts as $tryout)<option value="{{ $tryout->tryout_id }}" @selected($selectedTryout?->tryout_id === $tryout->tryout_id)>{{ $tryout->name }}</option>@endforeach</select></form>
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white"><table class="min-w-full text-sm"><thead class="bg-gray-50 text-left text-gray-500"><tr><th class="px-4 py-3">Peringkat</th><th class="px-4 py-3">Siswa</th><th class="px-4 py-3">Skor</th><th class="px-4 py-3">Selesai</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($ranking as $index => $attempt)<tr><td class="px-4 py-3 font-semibold">{{ $ranking->firstItem() + $index }}</td><td class="px-4 py-3"><p class="font-medium text-gray-900">{{ $attempt->user?->name }}</p><p class="text-xs text-gray-500">{{ $attempt->user?->email }}</p></td><td class="px-4 py-3">{{ number_format((float) $attempt->score, 2, ',', '.') }}</td><td class="px-4 py-3 text-gray-500">{{ $attempt->finished_at?->format('d M Y H:i') ?? '-' }}</td></tr>@empty<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Belum ada hasil tryout siswa pada rombel ini.</td></tr>@endforelse</tbody></table></div>
    <div class="mt-4">{{ $ranking->links() }}</div>
</div>
@endsection
