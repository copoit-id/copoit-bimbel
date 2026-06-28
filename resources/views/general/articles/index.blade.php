@extends('general.layout')

@section('title', 'Artikel')

@section('content')
<section class="relative overflow-hidden border-b border-slate-200/80 bg-gradient-to-br from-white via-white to-primary/5 pt-32 pb-16 sm:pt-40 sm:pb-20">
    <!-- Decorative background elements -->
    <div class="absolute -top-40 -right-40 h-80 w-80 rounded-full bg-primary/5 blur-3xl"></div>
    <div class="absolute -bottom-40 -left-40 h-80 w-80 rounded-full bg-primary/5 blur-3xl"></div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center text-center mb-12 sm:mb-16">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                <i class="ri-newspaper-line text-sm"></i>
                Bimbel News & Updates
            </span>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
                Insight & Panduan Belajar
            </h1>
            <p class="mt-4 max-w-2xl text-base sm:text-lg leading-relaxed text-slate-600">
                Kumpulan artikel terbaru, tips & trik lolos PTN, strategi ujian, serta informasi pendidikan terpercaya untuk mendukung perjalanan belajarmu.
            </p>
            <div class="mt-6 h-1 w-12 rounded-full bg-primary/30"></div>
        </div>

        @if($featuredArticle)
        <div class="mt-8">
            <a href="{{ route('articles.show', $featuredArticle->slug) }}"
                class="group relative block overflow-hidden rounded-2xl border-2 border-slate-200 bg-white hover:border-primary/60 transition-all duration-300">
                <div class="grid lg:grid-cols-12 gap-0">
                    <div class="lg:col-span-7 relative aspect-[16/10] sm:aspect-[16/9] lg:aspect-auto min-h-[300px] sm:min-h-[350px] lg:min-h-[420px] overflow-hidden bg-slate-100">
                        @if($featuredArticle->cover_url)
                            <img src="{{ $featuredArticle->cover_url }}" alt="{{ $featuredArticle->title }}"
                                class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105">
                        @else
                            <div class="absolute inset-0 flex h-full w-full items-center justify-center bg-gradient-to-br from-primary/5 to-primary/15 text-primary/40">
                                <i class="ri-article-line text-6xl"></i>
                            </div>
                        @endif
                        <span class="absolute top-4 left-4 z-10 inline-flex items-center gap-1.5 rounded-full bg-slate-900/80 backdrop-blur-md px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-white border border-white/10">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                            Terbaru
                        </span>
                    </div>
                    <div class="lg:col-span-5 flex flex-col justify-between p-6 sm:p-8 lg:p-10 bg-white">
                        <div class="space-y-4">
                            <div class="flex items-center gap-3 text-xs font-semibold text-primary">
                                <span class="uppercase tracking-wider">Artikel Unggulan</span>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 group-hover:text-primary transition-colors duration-200 leading-tight">
                                {{ $featuredArticle->title }}
                            </h2>
                            <p class="text-sm sm:text-base leading-relaxed text-slate-600 line-clamp-4">
                                {{ $featuredArticle->excerpt }}
                            </p>
                        </div>
                        
                        <div class="mt-8 pt-6 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary font-bold text-sm border border-primary/20">
                                    {{ $featuredArticle->author ? strtoupper(substr($featuredArticle->author->name, 0, 1)) : 'A' }}
                                </div>
                                <div class="text-left">
                                    <p class="text-sm font-semibold text-slate-900">{{ $featuredArticle->author ? $featuredArticle->author->name : 'Admin' }}</p>
                                    <p class="text-xs text-slate-500">{{ $featuredArticle->published_date_label }} &middot; {{ $featuredArticle->reading_minutes }} mnt baca</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 text-sm font-bold text-primary group-hover:text-primary/80 transition-colors">
                                Baca Artikel
                                <i class="ri-arrow-right-line transition-transform duration-200 group-hover:translate-x-1.5"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endif
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:py-20 lg:px-8">
    <div class="flex items-center justify-between mb-10">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">Semua Artikel</h2>
            <p class="text-sm text-slate-500 mt-1">Jelajahi wawasan baru dari pengajar kami</p>
        </div>
        <div class="h-px flex-1 bg-slate-200/80 mx-8 hidden sm:block"></div>
        <span class="text-xs font-medium text-slate-500 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
            Total: {{ $articles->total() }} Artikel
        </span>
    </div>

    @if($articles->count())
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            @foreach($articles as $article)
            <a href="{{ route('articles.show', $article->slug) }}"
                class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white hover:border-primary/50 hover:-translate-y-0.5 transition-all duration-250">
                <div class="relative aspect-[16/10] overflow-hidden bg-slate-100">
                    @if($article->cover_url)
                        <img src="{{ $article->cover_url }}" alt="{{ $article->title }}"
                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary/5 to-primary/10 text-primary/40">
                            <i class="ri-article-line text-4xl"></i>
                        </div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </div>
                <div class="flex flex-1 flex-col justify-between p-6">
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <span class="inline-flex items-center gap-1 font-semibold text-primary/95 bg-primary/5 px-2.5 py-0.5 rounded-md">
                                Tips & Info
                            </span>
                            <span>&middot;</span>
                            <span>{{ $article->reading_minutes }} mnt baca</span>
                        </div>
                        <h3 class="text-base font-semibold leading-snug text-slate-800 group-hover:text-primary transition-colors duration-200 line-clamp-2">
                            {{ $article->title }}
                        </h3>
                        <p class="text-sm leading-relaxed text-slate-600 line-clamp-3">
                            {{ $article->excerpt }}
                        </p>
                    </div>

                    <div class="mt-6 pt-5 border-t border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-600 font-bold text-xs">
                                {{ $article->author ? strtoupper(substr($article->author->name, 0, 1)) : 'A' }}
                            </div>
                            <span class="text-xs font-semibold text-slate-700 truncate max-w-[120px]">
                                {{ $article->author ? $article->author->name : 'Admin' }}
                            </span>
                        </div>
                        <span class="text-xs text-slate-500">{{ $article->published_date_label }}</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        @if($articles->hasPages())
        <div class="mt-12 pt-8 border-t border-slate-100">
            {{ $articles->links() }}
        </div>
        @endif
    @else
        <div class="rounded-2xl border border-dashed border-slate-200/70 bg-white px-6 py-16 text-center max-w-xl mx-auto">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-50 border border-slate-200 text-slate-400">
                <i class="ri-article-line text-3xl"></i>
            </div>
            <h3 class="mt-4 text-xl font-bold text-slate-900">Belum ada artikel</h3>
            <p class="mt-2 text-sm text-slate-500 max-w-sm mx-auto">
                Artikel edukasi dan wawasan pembelajaran baru akan segera hadir. Silakan cek kembali dalam waktu dekat.
            </p>
        </div>
    @endif
</section>
@endsection
