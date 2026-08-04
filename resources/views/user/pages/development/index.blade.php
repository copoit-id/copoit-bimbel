@extends('user.layout.new-user')

@section('title', 'Perkembangan Belajar')

@section('content')
<div class="space-y-6" x-data="{ tab: 'progress' }">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Perkembangan Belajar</h1>
        <p class="mt-1 text-sm text-gray-500">Lihat laporan progres dan catatan dari Tutor dalam satu tempat.</p>
    </div>

    <div class="flex gap-2 border-b border-gray-200">
        <button @click="tab = 'progress'" :class="tab === 'progress' ? 'border-primary text-primary' : 'border-transparent text-gray-500'" class="border-b-2 px-4 py-3 text-sm font-bold">Laporan progres</button>
        <button @click="tab = 'feedback'" :class="tab === 'feedback' ? 'border-primary text-primary' : 'border-transparent text-gray-500'" class="border-b-2 px-4 py-3 text-sm font-bold">Feedback tutor</button>
    </div>

    <section x-show="tab === 'progress'" class="space-y-4">
        @forelse($progress as $report)
            <article class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div>
                        <h2 class="font-bold text-gray-900">{{ $report->package->name }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $report->tentor->name }} · {{ $report->period_start->translatedFormat('d M') }}–{{ $report->period_end->translatedFormat('d M Y') }}</p>
                    </div>
                    @if($report->progress_percent !== null)
                        <span class="w-fit rounded-full border border-primary/20 bg-primary/5 px-3 py-1 text-sm font-bold text-primary">Progres {{ $report->progress_percent }}%</span>
                    @endif
                </div>
                <div class="mt-4 grid grid-cols-3 gap-3">
                    @foreach(['mastery_score' => 'Penguasaan', 'discipline_score' => 'Disiplin', 'participation_score' => 'Partisipasi'] as $field => $label)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-center">
                            <p class="text-xs text-gray-500">{{ $label }}</p>
                            <p class="mt-1 text-lg font-bold text-gray-900">{{ $report->{$field} ?? '-' }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-5 space-y-4 text-sm leading-6 text-gray-600">
                    <div><p class="font-bold text-gray-800">Ringkasan</p><p class="mt-1 whitespace-pre-line">{{ $report->summary }}</p></div>
                    @if($report->strengths)<div><p class="font-bold text-gray-800">Kekuatan</p><p class="mt-1 whitespace-pre-line">{{ $report->strengths }}</p></div>@endif
                    @if($report->improvements)<div><p class="font-bold text-gray-800">Perlu ditingkatkan</p><p class="mt-1 whitespace-pre-line">{{ $report->improvements }}</p></div>@endif
                    @if($report->next_target)<div><p class="font-bold text-gray-800">Target berikutnya</p><p class="mt-1 whitespace-pre-line">{{ $report->next_target }}</p></div>@endif
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">Tutor belum membuat laporan progres.</div>
        @endforelse
        {{ $progress->links() }}
    </section>

    <section x-show="tab === 'feedback'" x-cloak class="space-y-4">
        @forelse($feedback as $item)
            <article class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <div class="flex flex-col justify-between gap-2 sm:flex-row">
                    <div>
                        <h2 class="font-bold text-gray-900">{{ $item->title }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $item->tentor->name }} · {{ $item->studyGroup?->name ?? 'Personal' }}</p>
                    </div>
                    <span class="text-xs font-semibold text-gray-500">{{ $item->created_at->translatedFormat('d M Y') }}</span>
                </div>
                <p class="mt-4 whitespace-pre-line text-sm leading-7 text-gray-600">{{ $item->feedback }}</p>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">Belum ada feedback dari Tutor.</div>
        @endforelse
        {{ $feedback->links() }}
    </section>
</div>
@endsection
