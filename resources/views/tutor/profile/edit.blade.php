@extends('tutor.layout')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Profil Tutor</h1>
        <p class="mt-1 text-sm text-gray-500">Informasi ini ditampilkan kepada siswa ketika memilih Tutor untuk booking.</p>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold">Profil belum dapat disimpan.</p>
            <ul class="mt-1 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.7fr)]">
        <form method="POST" action="{{ route('tutor.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                    @if($tentor->profile_photo_path)
                        <img src="{{ Storage::url($tentor->profile_photo_path) }}" alt="Foto {{ $tentor->name }}" class="h-28 w-28 rounded-2xl border border-gray-200 object-cover">
                    @else
                        <div class="flex h-28 w-28 shrink-0 items-center justify-center rounded-2xl border border-gray-200 bg-gray-50 text-3xl font-bold text-primary">
                            {{ mb_strtoupper(mb_substr($tentor->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1">
                        <h2 class="font-bold text-gray-900">Foto profil</h2>
                        <p class="mt-1 text-sm text-gray-500">JPG, PNG, atau WebP maksimal 2 MB. Foto otomatis dioptimalkan menjadi 640×640.</p>
                        <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @if($tentor->profile_photo_path)
                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300 text-primary focus:ring-primary">
                                Hapus foto saat disimpan
                            </label>
                        @endif
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <h2 class="font-bold text-gray-900">Informasi profesional</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Nama</span>
                        <input type="text" value="{{ $tentor->name }}" disabled class="mt-2 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-gray-500">
                        <span class="mt-1 block text-xs text-gray-400">Perubahan nama dilakukan melalui admin.</span>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Bidang / keahlian</span>
                        <input type="text" name="expertise" maxlength="255" value="{{ old('expertise', $tentor->expertise) }}" placeholder="Contoh: Matematika, UTBK, Bahasa Inggris" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Lama pengalaman</span>
                        <div class="relative mt-2">
                            <input type="number" name="experience_years" min="0" max="100" value="{{ old('experience_years', $tentor->experience_years) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 pr-16 focus:border-primary focus:ring-primary">
                            <span class="absolute right-3 top-2.5 text-sm text-gray-400">tahun</span>
                        </div>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Pendidikan</span>
                        <textarea name="education" rows="3" maxlength="2000" placeholder="Contoh: S1 Pendidikan Matematika, Universitas ..." class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">{{ old('education', $tentor->education) }}</textarea>
                    </label>
                    <label class="block sm:col-span-2">
                        <span class="text-sm font-semibold text-gray-700">Tentang saya</span>
                        <textarea name="bio" rows="4" maxlength="2000" placeholder="Perkenalan singkat untuk siswa" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">{{ old('bio', $tentor->bio) }}</textarea>
                    </label>
                    <label class="block sm:col-span-2">
                        <span class="text-sm font-semibold text-gray-700">Pengalaman mengajar</span>
                        <textarea name="experience" rows="5" maxlength="4000" placeholder="Tuliskan pengalaman mengajar yang relevan" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">{{ old('experience', $tentor->experience) }}</textarea>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Sertifikasi / pencapaian</span>
                        <textarea name="certifications" rows="4" maxlength="3000" placeholder="Satu item per baris" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">{{ old('certifications', $tentor->certifications) }}</textarea>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Metode mengajar</span>
                        <textarea name="teaching_method" rows="4" maxlength="2000" placeholder="Ceritakan pendekatan belajar yang digunakan" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">{{ old('teaching_method', $tentor->teaching_method) }}</textarea>
                    </label>
                </div>
            </section>

            <div class="flex justify-end">
                <button class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">
                    <i class="ri-save-line"></i>
                    Simpan profil
                </button>
            </div>
        </form>

        <aside class="space-y-5">
            <section class="rounded-2xl border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">Rating Tutor</p>
                <div class="mt-2 flex items-end gap-2">
                    <p class="text-3xl font-bold text-gray-900">{{ $tentor->visible_reviews_avg_rating ? number_format($tentor->visible_reviews_avg_rating, 1) : '—' }}</p>
                    <p class="pb-1 text-sm text-gray-500">/ 5 dari {{ $tentor->visible_reviews_count }} review</p>
                </div>
                <div class="mt-2 text-lg text-amber-400">
                    @for($star = 1; $star <= 5; $star++)
                        <i class="{{ $tentor->visible_reviews_avg_rating >= $star ? 'ri-star-fill' : 'ri-star-line' }}"></i>
                    @endfor
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="font-bold text-gray-900">Review terbaru</h2>
                <div class="mt-4 space-y-4">
                    @forelse($recentReviews as $review)
                        <article class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-800">{{ $review->user->name }}</p>
                                <span class="text-xs font-bold text-amber-500"><i class="ri-star-fill"></i> {{ $review->rating }}</span>
                            </div>
                            @if($review->comment)
                                <p class="mt-2 text-sm leading-6 text-gray-600">{{ $review->comment }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada review dari siswa.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
