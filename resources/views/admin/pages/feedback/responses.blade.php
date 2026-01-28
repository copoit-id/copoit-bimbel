@extends('admin.layout.admin')
@section('title', 'Respon Feedback')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.feedback.index') }}" title="Feedback Tryout" />
            <x-breadcrumb-item href="{{ route('admin.feedback.show', $tryout->tryout_id) }}" title="{{ $tryout->name }}" />
            <x-breadcrumb-item href="" title="Respon" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="Respon Feedback - {{ $tryout->name }}" description="Daftar respon feedback dari peserta." />

<div class="bg-white p-6 rounded-lg border border-border mt-6">
    <div class="relative overflow-x-auto">
        <table class="w-full text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">User</th>
                    <th scope="col" class="px-6 py-3 text-center">Jumlah Jawaban</th>
                    <th scope="col" class="px-6 py-3 text-center">Rata-rata Skor</th>
                    <th scope="col" class="px-6 py-3 text-center">Waktu</th>
                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $submission)
                <tr class="bg-white border-b border-dashed border-gray-200 text-grey3">
                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-900">{{ $submission->user->name ?? 'User' }}</p>
                        <p class="text-xs text-gray-500">{{ $submission->user->email ?? '-' }}</p>
                    </td>
                    <td class="px-6 py-4 text-center">
                        {{ $submission->answers_count ?? 0 }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        {{ number_format((float) ($submission->answers_avg_score ?? 0), 2) }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        {{ optional($submission->submitted_at)->format('d M Y H:i') ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <a href="{{ route('admin.feedback.responses.detail', [$tryout->tryout_id, $submission->feedback_submission_id]) }}"
                            class="px-3 py-1 text-xs font-semibold rounded-full border border-primary text-primary hover:bg-primary hover:text-white transition">
                            Lihat Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                        Belum ada respon feedback.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $submissions->links() }}
    </div>
</div>

@endsection
