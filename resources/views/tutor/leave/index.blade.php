@extends('tutor.layout')
@section('title', 'Pengajuan Cuti')
@section('content')
<div class="space-y-6"><div><h1 class="text-2xl font-bold text-gray-900">Pengajuan Cuti</h1><p class="mt-1 text-sm text-gray-500">Admin akan meninjau pengajuan Anda.</p></div>
<form method="POST" action="{{ route('tutor.leave.store') }}" class="grid gap-4 rounded-2xl border border-gray-200 bg-white p-5 md:grid-cols-3">@csrf
<input type="datetime-local" name="start_at" required class="rounded-lg border border-gray-300 px-3 py-2"><input type="datetime-local" name="end_at" required class="rounded-lg border border-gray-300 px-3 py-2"><textarea name="reason" required maxlength="1000" placeholder="Alasan cuti" class="rounded-lg border border-gray-300 px-3 py-2"></textarea><button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Ajukan cuti</button></form>
@foreach($leaves as $leave)<article class="rounded-xl border border-gray-200 bg-white p-4"><strong>{{ $leave->start_at->format('d M Y H:i') }} – {{ $leave->end_at->format('d M Y H:i') }}</strong><p class="mt-1 text-sm text-gray-600">{{ $leave->reason }}</p><span class="mt-2 inline-block text-xs font-bold {{ $leave->status === 'approved' ? 'text-green-700' : ($leave->status === 'rejected' ? 'text-red-700' : 'text-amber-700') }}">{{ ucfirst($leave->status) }}</span></article>@endforeach
{{ $leaves->links() }}</div>
@endsection
