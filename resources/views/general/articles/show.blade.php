@extends('general.layout')

@php
    $articleContent = $content ?? [];
    $articleValue = fn (string $key, mixed $default = null) => data_get($articleContent, $key, $default);
@endphp

@section('title', $article->title)

@push('styles')
<style>
    .article-content {
        font-size: 1.05rem;
        line-height: 1.85;
        color: #334155; /* slate-700 */
    }
    
    .article-content h1 {
        font-size: 2rem;
        line-height: 2.25rem;
        margin-top: 2.25rem;
        margin-bottom: 1rem;
        font-weight: 800;
        color: #0f172a;
    }
    
    .article-content h2 {
        font-size: 1.625rem;
        line-height: 2rem;
        margin-top: 2rem;
        margin-bottom: 0.875rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.025em;
        position: relative;
        padding-bottom: 0.5rem;
    }
    
    .article-content h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 3rem;
        height: 3px;
        background-color: var(--client-color-primary);
        border-radius: 9999px;
    }

    .article-content h3 {
        font-size: 1.375rem;
        line-height: 1.75rem;
        margin-top: 1.75rem;
        margin-bottom: 0.75rem;
        font-weight: 700;
        color: #0f172a;
    }

    .article-content p {
        margin-top: 0;
        margin-bottom: 1.25rem;
    }

    .article-content ul {
        list-style-type: disc;
        margin-top: 0;
        margin-bottom: 1.25rem;
        padding-left: 1.5rem;
    }
    
    .article-content ul li {
        margin-top: 0.35rem;
        margin-bottom: 0.35rem;
    }

    .article-content ol {
        list-style-type: decimal;
        margin-top: 0;
        margin-bottom: 1.25rem;
        padding-left: 1.5rem;
    }
    
    .article-content ol li {
        margin-top: 0.35rem;
        margin-bottom: 0.35rem;
    }

    .article-content blockquote {
        border-left: 4px solid var(--client-color-primary);
        background: #f8fafc;
        border-top-right-radius: 0.75rem;
        border-bottom-right-radius: 0.75rem;
        margin: 1.75rem 0;
        padding: 1.25rem 1.5rem;
        color: #475569; /* slate-600 */
        font-style: italic;
    }
    
    .article-content blockquote p {
        margin-bottom: 0;
    }

    .article-content img {
        border-radius: 1rem;
        margin: 2rem auto;
        max-width: 100%;
        border: 1px solid rgb(226, 232, 240);
    }
    
    .article-content a {
        color: var(--client-color-primary);
        text-decoration: underline;
        font-weight: 600;
    }
    
    .article-content a:hover {
        opacity: 0.8;
    }
    
    .article-content hr {
        margin: 2.5rem 0;
        border: 0;
        border-top: 1px solid #e2e8f0;
    }
</style>
@endpush

@section('content')
<article class="bg-white">
    <div class="mx-auto max-w-7xl px-4 pt-32 pb-12 sm:pt-40 sm:pb-16 lg:px-8">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
            
            <!-- Main Content Column (Left) -->
            <div class="lg:col-span-8">
                <a href="{{ route('articles.index') }}" class="group inline-flex items-center gap-2 text-sm font-semibold text-primary hover:text-primary/80 transition-colors">
                    <i class="ri-arrow-left-line transition-transform duration-200 group-hover:-translate-x-1"></i>
                    {{ $articleValue('show.back_label', 'Kembali ke Artikel') }}
                </a>

                <div class="mt-8">
                    <span class="inline-flex items-center gap-1 rounded-md bg-primary/5 px-2.5 py-0.5 text-xs font-semibold text-primary tracking-wide">
                        <i class="ri-lightbulb-line"></i> {{ $articleValue('show.badge', 'Bimbel Insight') }}
                    </span>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl md:text-5xl leading-tight">
                        {{ $article->title }}
                    </h1>
                    
                    @if($article->excerpt)
                        <p class="mt-6 text-lg sm:text-xl leading-relaxed text-slate-600 border-l-2 border-primary/30 pl-4">
                            {{ $article->excerpt }}
                        </p>
                    @endif

                    <!-- Author & Share Row -->
                    <div class="mt-8 flex flex-wrap items-center justify-between gap-6 border-y border-slate-100 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary font-semibold text-base border border-primary/20">
                                {{ $article->author ? strtoupper(substr($article->author->name, 0, 1)) : 'A' }}
                            </div>
                            <div class="text-left">
                                <p class="text-sm font-semibold text-slate-800">{{ $article->author ? $article->author->name : $articleValue('show.author_fallback', 'Admin') }}</p>
                                <p class="text-xs text-slate-500">{{ $article->published_date_label }} &middot; {{ $article->reading_minutes }} {{ $articleValue('show.reading_suffix', 'menit baca') }}</p>
                            </div>
                        </div>
                        
                        <!-- Share Button Block -->
                        <div class="flex items-center gap-2" x-data="{
                            url: window.location.href,
                            title: '{{ addslashes($article->title) }}',
                            copied: false,
                            copyLink() {
                                navigator.clipboard.writeText(this.url).then(() => {
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 2000);
                                });
                            }
                        }">
                            <span class="text-xs font-medium text-slate-400 mr-1 hidden sm:inline">{{ $articleValue('show.share_label', 'Bagikan:') }}</span>
                            
                            <!-- WA Share -->
                            <a :href="'https://api.whatsapp.com/send?text=' + encodeURIComponent(title + ' ' + url)" 
                               target="_blank"
                               rel="noopener noreferrer"
                               class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-100 bg-white text-slate-500 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-100 transition-colors"
                               title="{{ $articleValue('show.whatsapp_share_title', 'Bagikan ke WhatsApp') }}">
                                <i class="ri-whatsapp-line text-lg"></i>
                            </a>

                            <!-- Telegram Share -->
                            <a :href="'https://t.me/share/url?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title)" 
                               target="_blank"
                               rel="noopener noreferrer"
                               class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-100 bg-white text-slate-500 hover:bg-sky-50 hover:text-sky-600 hover:border-sky-200 transition-colors"
                               title="{{ $articleValue('show.telegram_share_title', 'Bagikan ke Telegram') }}">
                                <i class="ri-telegram-line text-lg"></i>
                            </a>

                            <!-- Twitter / X Share -->
                            <a :href="'https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title)" 
                               target="_blank"
                               rel="noopener noreferrer"
                               class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-100 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-900 hover:border-slate-350 transition-colors"
                               title="{{ $articleValue('show.x_share_title', 'Bagikan ke X') }}">
                                <i class="ri-twitter-x-line text-base"></i>
                            </a>

                            <!-- Copy Link -->
                            <button @click="copyLink"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-100 bg-white text-slate-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors relative"
                                    title="{{ $articleValue('show.copy_title', 'Salin Tautan') }}">
                                <i class="ri-link text-lg" x-show="!copied"></i>
                                <i class="ri-check-line text-lg text-emerald-600" x-show="copied" style="display: none;"></i>
                                
                                <span x-show="copied" 
                                      x-transition 
                                      class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 whitespace-nowrap rounded bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white border border-slate-800"
                                      style="display: none;">
                                    {{ $articleValue('show.copied_label', 'Tautan disalin!') }}
                                  </span>
                              </button>
                          </div>
                      </div>
                  </div>
  
                  @if($article->cover_url)
                      <div class="mt-8 overflow-hidden rounded-2xl border-2 border-slate-200 aspect-[16/9] w-full">
                          <img src="{{ $article->cover_url }}" alt="{{ $article->title }}"
                              class="h-full w-full object-cover">
                      </div>
                  @endif
  
                  <div class="article-content mt-12 text-slate-700">
                      {!! $article->content !!}
                  </div>
  
                  <!-- Author Bio Card -->
                  <div class="mt-16 rounded-2xl border border-slate-200 bg-slate-50 p-6 sm:p-8 flex flex-col sm:flex-row items-center sm:items-start gap-5">
                      <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary font-semibold text-lg border-2 border-white">
                        {{ $article->author ? strtoupper(substr($article->author->name, 0, 1)) : 'A' }}
                    </div>
                    <div class="text-center sm:text-left space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-wider text-primary">{{ $articleValue('show.author_heading', 'Penulis Artikel') }}</p>
                        <h3 class="text-lg font-semibold text-slate-800">{{ $article->author ? $article->author->name : $articleValue('show.author_name_fallback', 'Tim BimbelHub') }}</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            {{ str_replace(':brand', $clientBranding['name'], $articleValue('show.author_description', 'Pengajar dan kontributor ahli di :brand. Berkomitmen menyajikan wawasan akademis terbaru dan bimbingan belajar terbaik untuk kesuksesan siswa meraih kampus impian.')) }}
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar Column (Right) -->
            <div class="lg:col-span-4 lg:pl-4">
                <div class="space-y-8 lg:sticky lg:top-24">
                    
                    <!-- Promotional CTA Widget -->
                    <div class="rounded-2xl border border-white/20 bg-gradient-to-br from-primary to-primary/90 p-6 text-white relative overflow-hidden group">
                        <!-- Decorative background vectors -->
                        <div class="absolute -right-16 -top-16 h-32 w-32 rounded-full bg-white/10 blur-xl group-hover:scale-110 transition-transform duration-500"></div>
                        <div class="absolute -left-16 -bottom-16 h-32 w-32 rounded-full bg-white/10 blur-xl group-hover:scale-110 transition-transform duration-500"></div>
                        
                        <div class="relative space-y-4">
                            <span class="inline-flex items-center gap-1 rounded-full bg-white/20 backdrop-blur-md px-2.5 py-0.5 text-2xs font-bold uppercase tracking-wider text-white">
                                {{ $articleValue('show.promo_badge', 'Program Unggulan') }}
                            </span>
                            <h4 class="text-xl font-bold leading-snug">
                                {{ $articleValue('show.promo_title', 'Ingin Lolos PTN Impianmu?') }}
                            </h4>
                            <p class="text-xs text-white/90 leading-relaxed">
                                {{ $articleValue('show.promo_description', 'Bergabunglah dengan program persiapan intensif UTBK-SNBT & Mandiri kami. Dapatkan akses tryout terakreditasi, materi lengkap, dan bimbingan guru ahli!') }}
                            </p>
                            <div class="pt-2">
                                <a href="{{ route('user.package.index') }}" class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-white border border-slate-200 px-4 py-2.5 text-sm font-bold text-primary hover:bg-slate-50 transition-colors">
                                    {{ $articleValue('show.promo_cta_label', 'Mulai Belajar Sekarang') }}
                                    <i class="ri-arrow-right-line"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Related Articles Widget -->
                    @if($relatedArticles->count())
                    <div class="rounded-2xl border border-slate-200 bg-white p-6">
                        <h3 class="text-lg font-semibold text-slate-800 flex items-center gap-2">
                            <i class="ri-article-line text-primary"></i>
                            {{ $articleValue('show.related_title', 'Artikel Terpopuler') }}
                        </h3>
                        <div class="mt-3 h-0.5 w-8 rounded bg-primary/30"></div>
                        
                        <div class="mt-6 space-y-4">
                            @foreach($relatedArticles as $related)
                            <a href="{{ route('articles.show', $related->slug) }}" class="group flex items-start gap-4 hover:text-primary transition-colors duration-200">
                                <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-slate-100 bg-slate-50 relative">
                                    @if($related->cover_url)
                                        <img src="{{ $related->cover_url }}" alt="{{ $related->title }}" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-350">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary/5 to-primary/10 text-primary/40">
                                            <i class="ri-article-line text-xl"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1 space-y-1">
                                    <h4 class="text-sm font-semibold text-slate-800 leading-snug line-clamp-2 group-hover:text-primary transition-colors">
                                        {{ $related->title }}
                                    </h4>
                                    <p class="text-2xs text-slate-500">{{ $related->published_date_label }}</p>
                                </div>
                            </a>
                            @if(!$loop->last)
                                <div class="h-px bg-slate-100/70 my-2"></div>
                            @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                </div>
            </div>
            
        </div>
    </div>
</article>
@endsection
