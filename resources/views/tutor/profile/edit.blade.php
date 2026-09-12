@extends('tutor.layout')

@section('title', 'Profil Saya')

@push('styles')
    <style>[x-cloak] { display: none !important; }</style>
@endpush

@section('content')
@php
    $reviewTotal = (int) $tentor->visible_reviews_count;
    $averageRating = (float) ($tentor->visible_reviews_avg_rating ?? 0);
    $requestedTab = old('profile_tab', request('tab'));
    $initialTab = in_array($requestedTab, ['identity', 'professional'], true)
        ? $requestedTab
        : (request()->has('reviews_page') ? 'reviews' : 'identity');
@endphp

<div class="w-full space-y-6" x-data="{ tab: @js($initialTab) }">
    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
        <div class="bg-gradient-to-r from-primary to-primary/80 px-5 py-6 text-white sm:px-7">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    @if($tentor->profile_photo_path)
                        <img src="{{ Storage::url($tentor->profile_photo_path) }}" alt="Foto {{ $tentor->name }}" class="h-16 w-16 rounded-2xl border-2 border-white/50 object-cover">
                    @else
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border-2 border-white/40 bg-white/15 text-2xl font-bold">
                            {{ mb_strtoupper(mb_substr($tentor->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-white/75">Profil Tutor</p>
                        <h1 class="truncate text-2xl font-bold">{{ $tentor->name }}</h1>
                        <p class="mt-1 truncate text-sm text-white/80">{{ $tentor->expertise ?: 'Lengkapi keahlian Anda' }}</p>
                    </div>
                </div>
                <div class="rounded-xl bg-white/15 px-4 py-3 text-left sm:text-right">
                    <p class="text-xs font-medium text-white/75">Rating dari siswa</p>
                    <p class="mt-1 text-lg font-bold"><i class="ri-star-fill text-amber-300"></i> {{ $reviewTotal ? number_format($averageRating, 1) : 'Belum ada' }}</p>
                    <p class="text-xs text-white/75">{{ $reviewTotal }} review terpublikasi</p>
                </div>
            </div>
        </div>

        <nav class="flex overflow-x-auto border-t border-gray-100 px-3 sm:px-5" aria-label="Menu profil">
            <button type="button" @click="tab = 'identity'" :class="tab === 'identity' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-800'" class="inline-flex shrink-0 items-center gap-2 border-b-2 px-4 py-4 text-sm font-semibold transition"><i class="ri-user-line"></i>Data Diri</button>
            <button type="button" @click="tab = 'professional'" :class="tab === 'professional' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-800'" class="inline-flex shrink-0 items-center gap-2 border-b-2 px-4 py-4 text-sm font-semibold transition"><i class="ri-briefcase-4-line"></i>Profil Profesional</button>
            <button type="button" @click="tab = 'reviews'" :class="tab === 'reviews' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-800'" class="inline-flex shrink-0 items-center gap-2 border-b-2 px-4 py-4 text-sm font-semibold transition"><i class="ri-star-line"></i>Rating Siswa <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $reviewTotal }}</span></button>
        </nav>
    </section>

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

    <form method="POST" action="{{ route('tutor.profile.update') }}" enctype="multipart/form-data" x-show="tab !== 'reviews'">
        @csrf
        @method('PUT')
        <input type="hidden" name="profile_tab" x-model="tab">

        <div x-show="tab === 'identity'" class="grid gap-6 xl:grid-cols-[minmax(340px,0.7fr)_minmax(0,1.3fr)]">
            <section class="h-fit rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <div class="flex flex-col items-start gap-5 sm:flex-row xl:flex-col">
                    @if($tentor->profile_photo_path)
                        <img src="{{ Storage::url($tentor->profile_photo_path) }}" alt="Foto {{ $tentor->name }}" class="h-32 w-32 rounded-2xl border border-gray-200 object-cover">
                    @else
                        <div class="flex h-32 w-32 shrink-0 items-center justify-center rounded-2xl border border-gray-200 bg-gray-50 text-4xl font-bold text-primary">
                            {{ mb_strtoupper(mb_substr($tentor->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="w-full">
                        <h2 class="font-bold text-gray-900">Foto profil</h2>
                        <p class="mt-1 text-sm leading-6 text-gray-500">Digunakan pada profil publik saat siswa memilih tutor.</p>
                        <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="mt-4 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                        <p class="mt-2 text-xs text-gray-400">JPG, PNG, atau WebP, maksimal 2 MB.</p>
                        @if($tentor->profile_photo_path)
                            <label class="mt-4 inline-flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300 text-primary focus:ring-primary">
                                Hapus foto saat disimpan
                            </label>
                        @endif
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Data diri</h2>
                    <p class="mt-1 text-sm text-gray-500">Pastikan informasi kontak Anda selalu terbaru.</p>
                </div>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Nama lengkap</span>
                        <input type="text" value="{{ $tentor->name }}" disabled class="mt-2 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-gray-500">
                        <span class="mt-1.5 block text-xs text-gray-400">Perubahan nama dilakukan melalui Admin.</span>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Email</span>
                        <input type="email" value="{{ $tentor->email ?: auth()->user()->email }}" disabled class="mt-2 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-gray-500">
                        <span class="mt-1.5 block text-xs text-gray-400">Perubahan email dilakukan melalui Admin.</span>
                    </label>
                    <label class="block md:col-span-2">
                        <span class="text-sm font-semibold text-gray-700">Nomor WhatsApp / telepon</span>
                        <input type="text" name="phone" maxlength="30" value="{{ old('phone', $tentor->phone) }}" placeholder="Contoh: 0812 3456 7890" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                        <span class="mt-1.5 block text-xs text-gray-400">Nomor ini hanya digunakan untuk kebutuhan operasional bimbel.</span>
                    </label>
                </div>
            </section>
        </div>

        <section x-show="tab === 'professional'" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Profil profesional</h2>
                    <p class="mt-1 text-sm text-gray-500">Informasi ini dapat dilihat siswa pada saat memilih tutor.</p>
                </div>
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1.5 text-xs font-semibold text-primary"><i class="ri-eye-line"></i>Tampil di profil tutor</span>
            </div>
            <div class="mt-6 grid gap-5 lg:grid-cols-2">
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
                <label class="block lg:col-span-2">
                    <span class="text-sm font-semibold text-gray-700">Tentang saya</span>
                    <textarea name="bio" rows="4" maxlength="2000" placeholder="Perkenalan singkat untuk siswa" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">{{ old('bio', $tentor->bio) }}</textarea>
                </label>
                <label class="block lg:col-span-2">
                    <span class="text-sm font-semibold text-gray-700">Pendidikan</span>
                    <textarea name="education" rows="3" maxlength="2000" placeholder="Contoh: S1 Pendidikan Matematika, Universitas ..." class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">{{ old('education', $tentor->education) }}</textarea>
                </label>
                <label class="block lg:col-span-2">
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

        <div class="mt-6 flex justify-end border-t border-gray-200 pt-5">
            <button class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">
                <i class="ri-save-line"></i>Simpan perubahan
            </button>
        </div>
    </form>

    <section x-show="tab === 'reviews'" x-cloak class="space-y-6">
        <div class="grid gap-5 lg:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.2fr)]">
            <article class="rounded-2xl border border-gray-200 bg-white p-6">
                <p class="text-sm font-medium text-gray-500">Rata-rata rating</p>
                <div class="mt-3 flex items-end gap-3">
                    <p class="text-5xl font-bold tracking-tight text-gray-900">{{ $reviewTotal ? number_format($averageRating, 1) : '—' }}</p>
                    <p class="pb-1 text-sm text-gray-500">dari 5</p>
                </div>
                <div class="mt-3 flex text-xl text-amber-400">
                    @for($star = 1; $star <= 5; $star++)
                        <i class="{{ $averageRating >= $star ? 'ri-star-fill' : 'ri-star-line' }}"></i>
                    @endfor
                </div>
                <p class="mt-4 text-sm leading-6 text-gray-500">Berdasarkan {{ $reviewTotal }} review siswa yang ditampilkan.</p>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-6">
                <h2 class="font-bold text-gray-900">Rincian rating</h2>
                <div class="mt-5 space-y-3">
                    @for($rating = 5; $rating >= 1; $rating--)
                        @php
                            $ratingCount = (int) ($reviewCountsByRating->get($rating) ?? 0);
                            $ratingPercentage = $reviewTotal ? round(($ratingCount / $reviewTotal) * 100) : 0;
                        @endphp
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-12 shrink-0 font-medium text-gray-600">{{ $rating }} <i class="ri-star-fill text-amber-400"></i></span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-amber-400" style="width: {{ $ratingPercentage }}%"></div></div>
                            <span class="w-10 text-right text-gray-500">{{ $ratingCount }}</span>
                        </div>
                    @endfor
                </div>
            </article>
        </div>

        <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 class="font-bold text-gray-900">Review dari siswa</h2>
                    <p class="mt-1 text-sm text-gray-500">Masukan yang diberikan setelah sesi booking selesai.</p>
                </div>
                <span class="text-sm font-medium text-gray-500">{{ $reviewTotal }} review</span>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($reviews as $review)
                    <article class="px-5 py-5 sm:px-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">{{ mb_strtoupper(mb_substr($review->user?->name ?? 'S', 0, 1)) }}</div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $review->user?->name ?? 'Siswa' }}</p>
                                    <p class="mt-0.5 text-xs text-gray-400">{{ $review->created_at->translatedFormat('d M Y') }}</p>
                                </div>
                            </div>
                            <span class="inline-flex w-fit items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-sm font-bold text-amber-600"><i class="ri-star-fill"></i>{{ $review->rating }}.0</span>
                        </div>
                        @if($review->comment)
                            <p class="mt-4 max-w-4xl text-sm leading-6 text-gray-600">{{ $review->comment }}</p>
                        @else
                            <p class="mt-4 text-sm italic text-gray-400">Siswa memberikan rating tanpa komentar.</p>
                        @endif
                    </article>
                @empty
                    <div class="px-6 py-16 text-center">
                        <i class="ri-chat-smile-3-line text-4xl text-gray-300"></i>
                        <p class="mt-3 font-semibold text-gray-700">Belum ada review dari siswa</p>
                        <p class="mt-1 text-sm text-gray-500">Review akan muncul setelah siswa menyelesaikan sesi booking.</p>
                    </div>
                @endforelse
            </div>
            @if($reviews->hasPages())
                <div class="border-t border-gray-100 px-5 py-4 sm:px-6">{{ $reviews->withQueryString()->links() }}</div>
            @endif
        </article>
    </section>
</div>
@endsection
