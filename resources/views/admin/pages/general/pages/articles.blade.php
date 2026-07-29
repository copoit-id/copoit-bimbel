@extends('admin.layout.admin')

@php
    $value = fn (string $key, mixed $default = '') => old('content.' . $key, data_get($content, $key, $default));
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Tampilan Bimbel News & Updates</h2>
            <p class="text-sm text-gray-500">Kelola seluruh wording yang tampil di halaman daftar artikel tanpa mengubah desainnya.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.artikel.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <i class="ri-arrow-left-line"></i>
                Kembali
            </a>
            <a href="{{ route('articles.index') }}" target="_blank"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <i class="ri-external-link-line"></i>
                Preview
            </a>
        </div>
    </div>

    <form action="{{ route('admin.artikel.settings.update') }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <p class="font-semibold">Pengaturan belum tersimpan. Periksa error berikut:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-lg border border-gray-200 bg-white p-5">
            <h3 class="text-base font-semibold text-gray-900">Hero & Daftar Artikel</h3>
            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <x-admin-input name="content[meta][title]" label="Meta Title" :value="$value('meta.title')" />
                <x-admin-input name="content[index][badge]" label="Badge" :value="$value('index.badge')" />
                <x-admin-input name="content[index][title]" label="Judul Hero" :value="$value('index.title')" />
                <x-admin-input name="content[index][latest_label]" label="Label Terbaru" :value="$value('index.latest_label')" />
                <x-admin-input name="content[index][featured_label]" label="Label Artikel Unggulan" :value="$value('index.featured_label')" />
                <x-admin-input name="content[index][read_cta_label]" label="CTA Baca Artikel" :value="$value('index.read_cta_label')" />
                <x-admin-input name="content[index][all_title]" label="Judul Semua Artikel" :value="$value('index.all_title')" />
                <x-admin-input name="content[index][all_description]" label="Deskripsi Semua Artikel" :value="$value('index.all_description')" />
                <x-admin-input name="content[index][total_prefix]" label="Prefix Total" :value="$value('index.total_prefix')" />
                <x-admin-input name="content[index][total_suffix]" label="Suffix Total" :value="$value('index.total_suffix')" />
                <x-admin-input name="content[index][category_label]" label="Label Kategori" :value="$value('index.category_label')" />
                <x-admin-input name="content[index][reading_suffix]" label="Suffix Durasi Baca" :value="$value('index.reading_suffix')" />
                <x-admin-input name="content[index][author_fallback]" label="Fallback Nama Penulis" :value="$value('index.author_fallback')" />
                <x-admin-input name="content[index][empty_title]" label="Judul Saat Kosong" :value="$value('index.empty_title')" />
            </div>
            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Deskripsi Hero</label>
                    <textarea name="content[index][description]" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('index.description') }}</textarea>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Deskripsi Saat Kosong</label>
                    <textarea name="content[index][empty_description]" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('index.empty_description') }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5">
            <h3 class="text-base font-semibold text-gray-900">Detail Artikel</h3>
            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <x-admin-input name="content[show][back_label]" label="Label Kembali" :value="$value('show.back_label')" />
                <x-admin-input name="content[show][badge]" label="Badge Artikel" :value="$value('show.badge')" />
                <x-admin-input name="content[show][reading_suffix]" label="Suffix Durasi Baca" :value="$value('show.reading_suffix')" />
                <x-admin-input name="content[show][author_fallback]" label="Fallback Penulis" :value="$value('show.author_fallback')" />
                <x-admin-input name="content[show][share_label]" label="Label Bagikan" :value="$value('show.share_label')" />
                <x-admin-input name="content[show][whatsapp_share_title]" label="Tooltip WhatsApp" :value="$value('show.whatsapp_share_title')" />
                <x-admin-input name="content[show][telegram_share_title]" label="Tooltip Telegram" :value="$value('show.telegram_share_title')" />
                <x-admin-input name="content[show][x_share_title]" label="Tooltip X" :value="$value('show.x_share_title')" />
                <x-admin-input name="content[show][copy_title]" label="Tooltip Salin Tautan" :value="$value('show.copy_title')" />
                <x-admin-input name="content[show][copied_label]" label="Notifikasi Tautan Disalin" :value="$value('show.copied_label')" />
                <x-admin-input name="content[show][author_heading]" label="Judul Penulis" :value="$value('show.author_heading')" />
                <x-admin-input name="content[show][author_name_fallback]" label="Fallback Nama Penulis" :value="$value('show.author_name_fallback')" />
                <x-admin-input name="content[show][promo_badge]" label="Badge Promo" :value="$value('show.promo_badge')" />
                <x-admin-input name="content[show][promo_title]" label="Judul Promo" :value="$value('show.promo_title')" />
                <x-admin-input name="content[show][promo_cta_label]" label="CTA Promo" :value="$value('show.promo_cta_label')" />
                <x-admin-input name="content[show][related_title]" label="Judul Artikel Terkait" :value="$value('show.related_title')" />
            </div>
            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Deskripsi Penulis</label>
                    <textarea name="content[show][author_description]" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('show.author_description') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Gunakan <code>:brand</code> untuk menampilkan nama brand.</p>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Deskripsi Promo</label>
                    <textarea name="content[show][promo_description]" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('show.promo_description') }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5">
            <h3 class="text-base font-semibold text-gray-900">Header & Navigasi Halaman General</h3>
            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <x-admin-input name="content[layout][tagline]" label="Tagline Header" :value="$value('layout.tagline')" />
                <x-admin-input name="content[layout][home_label]" label="Label Home" :value="$value('layout.home_label')" />
                <x-admin-input name="content[layout][statistics_label]" label="Label Statistik PTN" :value="$value('layout.statistics_label')" />
                <x-admin-input name="content[layout][statistics_snbp_label]" label="Label Statistik SNBP" :value="$value('layout.statistics_snbp_label')" />
                <x-admin-input name="content[layout][statistics_snbt_label]" label="Label Statistik SNBT" :value="$value('layout.statistics_snbt_label')" />
                <x-admin-input name="content[layout][footer_snbp_label]" label="Label Footer SNBP" :value="$value('layout.footer_snbp_label')" />
                <x-admin-input name="content[layout][footer_snbt_label]" label="Label Footer SNBT" :value="$value('layout.footer_snbt_label')" />
                <x-admin-input name="content[layout][articles_label]" label="Label Artikel" :value="$value('layout.articles_label')" />
                <x-admin-input name="content[layout][tryout_label]" label="Label Try Out" :value="$value('layout.tryout_label')" />
                <x-admin-input name="content[layout][materials_label]" label="Label Kelas & Materi" :value="$value('layout.materials_label')" />
                <x-admin-input name="content[layout][packages_label]" label="Label Paket" :value="$value('layout.packages_label')" />
                <x-admin-input name="content[layout][dashboard_label]" label="Label Dashboard" :value="$value('layout.dashboard_label')" />
                <x-admin-input name="content[layout][login_label]" label="Label Login" :value="$value('layout.login_label')" />
            </div>
        </section>

        <button type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">
            <i class="ri-save-line"></i>
            Simpan Pengaturan
        </button>
    </form>
</div>
@endsection
