@extends('admin.layout.admin')
@section('title', 'Feedback Tryout')
@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Feedback Tryout</h2>
            <p class="text-gray-500">Atur pertanyaan feedback untuk setiap tryout.</p>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Total: <span class="font-medium text-gray-700">{{ $tryouts->total() }} Tryout</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($tryouts as $tryout)
        <div class="bg-white px-5 py-5 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between mb-3">
                <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                    {{ strtoupper($tryout->type_tryout) }}
                </span>
                @if($tryout->is_certification)
                <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs">
                    <i class="ri-award-line"></i> Sertifikasi
                </span>
                @endif
            </div>

            <p class="text-lg font-bold text-black text-center mb-4">{{ $tryout->name }}</p>

            <div class="flex flex-col gap-1 mb-4">
                <span class="flex items-center justify-between">
                    <p class="font-medium">Pertanyaan Aktif:</p>
                    <p class="font-light">{{ $tryout->feedback_questions_count ?? 0 }}</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Total Feedback:</p>
                    <p class="font-light">{{ $tryout->feedback_submissions_count ?? 0 }}</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Status:</p>
                    @if($tryout->start_date?->isFuture())
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">Akan Datang</span>
                    @elseif($tryout->end_date?->isPast())
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Selesai</span>
                    @else
                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Aktif</span>
                    @endif
                </span>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.feedback.show', $tryout->tryout_id) }}"
                    class="flex-1 flex justify-center bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/90">
                    Kelola
                </a>
                <a href="{{ route('admin.feedback.responses', $tryout->tryout_id) }}"
                    class="flex-1 flex justify-center border border-primary text-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary hover:text-white transition">
                    Respon
                </a>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg border border-gray-200 p-6 text-center text-gray-500">
            Belum ada data tryout.
        </div>
        @endforelse
    </div>

    <div>
        {{ $tryouts->links() }}
    </div>
</div>

@endsection
