@extends('admin.layout.admin')

@section('content')
<div class="space-y-6" x-data="{ tab: @js(old('form_tab', 'feedback')), feedbackScope: @js(old('scope', 'personal')) }">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Perkembangan Siswa</h1>
        <p class="mt-1 text-sm text-gray-500">Catat feedback personal atau rombel dan laporan progres terukur.</p>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="flex gap-2 border-b border-gray-200">
        <button @click="tab = 'feedback'" :class="tab === 'feedback' ? 'border-primary text-primary' : 'border-transparent text-gray-500'" class="border-b-2 px-4 py-3 text-sm font-bold">Feedback</button>
        <button @click="tab = 'progress'" :class="tab === 'progress' ? 'border-primary text-primary' : 'border-transparent text-gray-500'" class="border-b-2 px-4 py-3 text-sm font-bold">Laporan progres</button>
    </div>

    <section x-show="tab === 'feedback'" class="grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <form method="POST" action="{{ route('tutor.development.feedback.store') }}" class="h-fit space-y-4 rounded-2xl border border-gray-200 bg-white p-5">
            @csrf
            <input type="hidden" name="form_tab" value="feedback">
            <h2 class="font-bold text-gray-900">Tulis feedback</h2>
            <label class="block">
                <span class="text-sm font-semibold text-gray-700">Jenis feedback</span>
                <select name="scope" x-model="feedbackScope" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5">
                    <option value="personal">Personal siswa</option>
                    <option value="group">Seluruh rombel</option>
                </select>
            </label>
            <label x-show="feedbackScope === 'personal'" class="block">
                <span class="text-sm font-semibold text-gray-700">Siswa</span>
                <select name="student_target" :required="feedbackScope === 'personal'" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5">
                    <option value="">Pilih siswa</option>
                    @foreach($studentTargets as $target)
                        <option value="{{ $target['value'] }}" @selected(old('student_target') === $target['value'])>{{ $target['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label x-show="feedbackScope === 'group'" class="block">
                <span class="text-sm font-semibold text-gray-700">Rombel</span>
                <select name="study_group_id" :required="feedbackScope === 'group'" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5">
                    <option value="">Pilih rombel</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" @selected(old('study_group_id') == $group->id)>{{ $group->name }} · {{ $group->users->count() }} siswa</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-gray-700">Judul</span>
                <input type="text" name="title" maxlength="255" required value="{{ old('title') }}" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-gray-700">Feedback</span>
                <textarea name="feedback" rows="5" maxlength="5000" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5">{{ old('feedback') }}</textarea>
            </label>
            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                <input type="hidden" name="is_visible_to_student" value="0">
                <input type="checkbox" name="is_visible_to_student" value="1" @checked(old('is_visible_to_student', 1)) class="rounded border-gray-300 text-primary">
                Tampilkan kepada siswa
            </label>
            <button class="w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-bold text-white">Simpan feedback</button>
        </form>

        <div class="space-y-3">
            <h2 class="font-bold text-gray-900">Riwayat feedback</h2>
            @forelse($feedbackHistory as $item)
                <article class="rounded-xl border border-gray-200 bg-white p-4">
                    <div class="flex justify-between gap-3">
                        <div>
                            <p class="font-bold text-gray-900">{{ $item->title }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $item->user?->name ?? $item->studyGroup?->name ?? 'Rombel' }} · {{ $item->created_at->translatedFormat('d M Y') }}</p>
                        </div>
                        <span class="text-xs font-semibold text-gray-500">{{ $item->is_visible_to_student ? 'Terlihat siswa' : 'Internal' }}</span>
                    </div>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $item->feedback }}</p>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">Belum ada feedback.</div>
            @endforelse
            {{ $feedbackHistory->links() }}
        </div>
    </section>

    <section x-show="tab === 'progress'" x-cloak class="grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <form method="POST" action="{{ route('tutor.development.progress.store') }}" class="h-fit space-y-4 rounded-2xl border border-gray-200 bg-white p-5">
            @csrf
            <input type="hidden" name="form_tab" value="progress">
            <h2 class="font-bold text-gray-900">Buat laporan progres</h2>
            <label class="block">
                <span class="text-sm font-semibold text-gray-700">Siswa dan paket</span>
                <select name="student_target" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5">
                    <option value="">Pilih siswa</option>
                    @foreach($studentTargets->where('can_report_progress', true) as $target)
                        <option value="{{ $target['value'] }}" @selected(old('student_target') === $target['value'])>{{ $target['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label><span class="text-xs font-semibold text-gray-700">Periode mulai</span><input type="date" name="period_start" required value="{{ old('period_start', now()->startOfMonth()->toDateString()) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"></label>
                <label><span class="text-xs font-semibold text-gray-700">Periode akhir</span><input type="date" name="period_end" required value="{{ old('period_end', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"></label>
            </div>
            <div class="grid grid-cols-2 gap-3">
                @foreach(['progress_percent' => 'Progres', 'mastery_score' => 'Penguasaan', 'discipline_score' => 'Disiplin', 'participation_score' => 'Partisipasi'] as $field => $label)
                    <label><span class="text-xs font-semibold text-gray-700">{{ $label }} (0–100)</span><input type="number" name="{{ $field }}" min="0" max="100" value="{{ old($field) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"></label>
                @endforeach
            </div>
            @foreach(['summary' => 'Ringkasan', 'strengths' => 'Kekuatan', 'improvements' => 'Yang perlu ditingkatkan', 'next_target' => 'Target berikutnya'] as $field => $label)
                <label class="block"><span class="text-sm font-semibold text-gray-700">{{ $label }}</span><textarea name="{{ $field }}" rows="{{ $field === 'summary' ? 4 : 2 }}" @if($field === 'summary') required @endif class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5">{{ old($field) }}</textarea></label>
            @endforeach
            <button class="w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-bold text-white">Simpan laporan progres</button>
        </form>

        <div class="space-y-3">
            <h2 class="font-bold text-gray-900">Riwayat laporan</h2>
            @forelse($progressHistory as $report)
                <article class="rounded-xl border border-gray-200 bg-white p-4">
                    <p class="font-bold text-gray-900">{{ $report->user->name }} · {{ $report->package->name }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $report->period_start->translatedFormat('d M') }}–{{ $report->period_end->translatedFormat('d M Y') }} · Progres {{ $report->progress_percent ?? '-' }}%</p>
                    <p class="mt-3 text-sm leading-6 text-gray-600">{{ $report->summary }}</p>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">Belum ada laporan progres.</div>
            @endforelse
            {{ $progressHistory->links() }}
        </div>
    </section>
</div>
@endsection
