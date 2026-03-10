@extends($layout)
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Aktivitas</h1>
            <p class="text-sm text-gray-500">Pantau aktivitas pengguna dan keamanan sistem.</p>
        </div>
    </div>

    <form method="GET" class="bg-white border border-gray-200 rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Email / Nama / IP"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Semua</option>
                    <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Sukses</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Gagal</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                <input type="date" name="start" value="{{ request('start') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                <input type="date" name="end" value="{{ request('end') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
        </div>
        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm">Filter</button>
            <a href="{{ request()->url() }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Reset</a>
        </div>
    </form>

    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="border-b border-gray-200 px-4">
            <div class="flex flex-wrap gap-2 py-3">
                @foreach($tabs as $key => $tabConfig)
                    <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}"
                        class="px-4 py-2 rounded-lg text-sm {{ $tab === $key ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $tabConfig['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs uppercase bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">IP</th>
                        <th class="px-4 py-3">Aksi</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="border-b border-gray-100">
                            <td class="px-4 py-3 text-gray-800">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $log->user?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $log->email ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $log->ip ?: '-' }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $actionLabels[$log->action] ?? $log->action }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold {{ $log->status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $log->status === 'success' ? 'Sukses' : 'Gagal' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if(is_array($log->meta) && count($log->meta))
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($log->meta as $key => $value)
                                            <span class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-700">
                                                {{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">Belum ada aktivitas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
