@extends('admin.layout.admin')
@section('title', 'Pengajuan Cuti Tutor')
@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <div class="mb-2 flex items-center gap-2 text-sm text-gray-500"><i class="ri-user-settings-line"></i><span>Manajemen User</span><i class="ri-arrow-right-s-line"></i><span>Pengajuan Cuti Tutor</span></div>
        <h1 class="text-2xl font-bold text-gray-900">Pengajuan Cuti Tutor</h1>
        <p class="mt-1 text-sm text-gray-500">Tinjau waktu tidak tersedia yang diajukan tutor.</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach([['Menunggu', $summary['pending'], 'ri-time-line', 'amber'], ['Disetujui', $summary['approved'], 'ri-checkbox-circle-line', 'emerald'], ['Ditolak', $summary['rejected'], 'ri-close-circle-line', 'rose']] as [$label, $value, $icon, $tone])
            <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4"><div class="flex h-11 w-11 items-center justify-center rounded-lg bg-{{ $tone }}-50 text-xl text-{{ $tone }}-600"><i class="{{ $icon }}"></i></div><div><p class="text-sm text-gray-500">{{ $label }}</p><p class="text-2xl font-bold text-gray-900">{{ $value }}</p></div></div>
        @endforeach
    </div>
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-4"><h2 class="font-semibold text-gray-900">Daftar Pengajuan</h2><p class="mt-1 text-sm text-gray-500">Pengajuan terbaru ditampilkan terlebih dahulu.</p></div>
        @if($leaves->isNotEmpty())
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50"><tr><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Tutor</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Periode Cuti</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Alasan</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th><th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Aksi</th></tr></thead><tbody class="divide-y divide-gray-100 bg-white">
                @foreach($leaves as $leave)
                    @php $status = ['pending' => ['Menunggu', 'amber'], 'approved' => ['Disetujui', 'emerald'], 'rejected' => ['Ditolak', 'rose']][$leave->status] ?? [ucfirst($leave->status), 'gray']; @endphp
                    <tr class="align-top hover:bg-gray-50"><td class="whitespace-nowrap px-5 py-4"><div class="font-semibold text-gray-900">{{ $leave->tentor->name ?? 'Tutor tidak ditemukan' }}</div><div class="mt-1 text-xs text-gray-500">Diajukan {{ $leave->created_at->format('d M Y, H:i') }}</div></td><td class="whitespace-nowrap px-5 py-4 text-gray-700"><i class="ri-calendar-line mr-1 text-gray-400"></i>{{ $leave->start_at->format('d M Y, H:i') }}<div class="pl-5 text-xs text-gray-500">sampai {{ $leave->end_at->format('d M Y, H:i') }}</div></td><td class="min-w-52 max-w-sm px-5 py-4 text-gray-600">{{ $leave->reason }}@if($leave->admin_notes)<div class="mt-1 text-xs text-gray-400">Catatan: {{ $leave->admin_notes }}</div>@endif</td><td class="whitespace-nowrap px-5 py-4"><span class="rounded-full bg-{{ $status[1] }}-50 px-3 py-1 text-xs font-semibold text-{{ $status[1] }}-700">{{ $status[0] }}</span></td><td class="px-5 py-4"><div class="flex min-w-32 justify-end gap-2">@if($leave->status === 'pending')<form method="POST" action="{{ route('admin.tutor-leave.approve', $leave) }}">@csrf<button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Setujui</button></form><button type="button" onclick="openRejectLeaveModal(@js(route('admin.tutor-leave.reject', $leave)), @js($leave->tentor->name ?? 'Tutor'))" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">Tolak</button>@else<span class="text-xs text-gray-400">Tidak ada aksi</span>@endif</div></td></tr>
                @endforeach
            </tbody></table></div>
        @else
            <div class="px-5 py-14 text-center"><i class="ri-calendar-close-line text-4xl text-gray-300"></i><p class="mt-3 font-medium text-gray-700">Belum ada pengajuan cuti</p><p class="mt-1 text-sm text-gray-500">Pengajuan dari tutor akan muncul di sini.</p></div>
        @endif
    </div>
    @if($leaves->hasPages())<div>{{ $leaves->links() }}</div>@endif
</div>
@endsection

<div id="rejectLeaveModal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-gray-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="rejectLeaveTitle">
    <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white">
        <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4"><div><h2 id="rejectLeaveTitle" class="font-semibold text-gray-900">Tolak pengajuan cuti</h2><p id="rejectLeaveTutor" class="mt-1 text-sm text-gray-500"></p></div><button type="button" onclick="closeRejectLeaveModal()" class="text-xl text-gray-400 hover:text-gray-600" aria-label="Tutup"><i class="ri-close-line"></i></button></div>
        <form id="rejectLeaveForm" method="POST" class="space-y-4 p-5">@csrf<label class="block"><span class="mb-1.5 block text-sm font-medium text-gray-700">Alasan penolakan <span class="text-rose-500">*</span></span><textarea name="admin_notes" rows="4" required maxlength="1000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary" placeholder="Tuliskan alasan agar tutor dapat menindaklanjuti..."></textarea></label><div class="flex justify-end gap-2"><button type="button" onclick="closeRejectLeaveModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button><button class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Tolak Pengajuan</button></div></form>
    </div>
</div>

@push('scripts')
<script>
function openRejectLeaveModal(action, tutor) {
    const modal = document.getElementById('rejectLeaveModal');
    document.getElementById('rejectLeaveForm').action = action;
    document.getElementById('rejectLeaveTutor').textContent = `Pengajuan dari ${tutor}`;
    modal.classList.remove('hidden'); modal.classList.add('flex');
    document.querySelector('#rejectLeaveForm textarea')?.focus();
}
function closeRejectLeaveModal() {
    const modal = document.getElementById('rejectLeaveModal');
    modal.classList.add('hidden'); modal.classList.remove('flex');
    document.getElementById('rejectLeaveForm')?.reset();
}
document.getElementById('rejectLeaveModal')?.addEventListener('click', (event) => { if (event.target.id === 'rejectLeaveModal') closeRejectLeaveModal(); });
</script>
@endpush
