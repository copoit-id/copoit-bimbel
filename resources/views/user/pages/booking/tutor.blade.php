@extends('user.layout.new-user')

@section('title', 'Profil Tutor')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <a href="{{ route('user.booking.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-gray-500 hover:text-primary">
        <i class="ri-arrow-left-line"></i>
        Kembali ke booking
    </a>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-7">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
            @if($tentor->profile_photo_path)
                <img src="{{ Storage::url($tentor->profile_photo_path) }}" alt="Foto {{ $tentor->name }}" loading="lazy" class="h-32 w-32 rounded-2xl border border-gray-200 object-cover">
            @else
                <div class="flex h-32 w-32 shrink-0 items-center justify-center rounded-2xl border border-gray-200 bg-gray-50 text-4xl font-bold text-primary">
                    {{ mb_strtoupper(mb_substr($tentor->name, 0, 1)) }}
                </div>
            @endif
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900">{{ $tentor->name }}</h1>
                <p class="mt-1 text-sm font-semibold text-primary">{{ $tentor->expertise ?: 'Tutor' }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-sm font-bold text-amber-700">
                        <i class="ri-star-fill"></i>
                        {{ $tentor->visible_reviews_avg_rating ? number_format($tentor->visible_reviews_avg_rating, 1) : 'Belum dinilai' }}
                    </span>
                    <span class="text-sm text-gray-500">{{ $tentor->visible_reviews_count }} review terverifikasi</span>
                    @if($tentor->experience_years !== null)
                        <span class="text-sm text-gray-500"><i class="ri-briefcase-line mr-1"></i>{{ $tentor->experience_years }} tahun pengalaman</span>
                    @endif
                </div>
                @if($tentor->bio)
                    <p class="mt-4 whitespace-pre-line text-sm leading-7 text-gray-600">{{ $tentor->bio }}</p>
                @endif
            </div>
        </div>
    </section>

    <div class="grid gap-5 md:grid-cols-2">
        @foreach([
            ['icon' => 'ri-graduation-cap-line', 'title' => 'Pendidikan', 'value' => $tentor->education],
            ['icon' => 'ri-briefcase-4-line', 'title' => 'Pengalaman Mengajar', 'value' => $tentor->experience],
            ['icon' => 'ri-award-line', 'title' => 'Sertifikasi & Pencapaian', 'value' => $tentor->certifications],
            ['icon' => 'ri-lightbulb-flash-line', 'title' => 'Metode Mengajar', 'value' => $tentor->teaching_method],
        ] as $section)
            @if($section['value'])
                <section class="rounded-2xl border border-gray-200 bg-white p-5">
                    <h2 class="flex items-center gap-2 font-bold text-gray-900"><i class="{{ $section['icon'] }} text-primary"></i>{{ $section['title'] }}</h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-7 text-gray-600">{{ $section['value'] }}</p>
                </section>
            @endif
        @endforeach
    </div>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Review Siswa</h2>
                <p class="mt-1 text-sm text-gray-500">Hanya berasal dari siswa yang telah menyelesaikan sesi booking.</p>
            </div>
            <p class="text-sm font-semibold text-gray-700">{{ $tentor->visible_reviews_count }} review</p>
        </div>

        <div class="mt-5 space-y-4">
            @forelse($reviews as $review)
                <article class="rounded-xl border border-gray-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $review->user->name }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $review->booking?->package?->name }} • {{ $review->created_at->translatedFormat('d M Y') }}</p>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-amber-500"><i class="ri-star-fill"></i> {{ $review->rating }}/5</span>
                    </div>
                    @if($review->comment)
                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $review->comment }}</p>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">Tutor ini belum memiliki review.</div>
            @endforelse
        </div>

        <div class="mt-5">{{ $reviews->links() }}</div>
    </section>
</div>
@endsection
