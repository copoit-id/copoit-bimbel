@extends('admin.layout.admin')

@php
    $value = fn (string $key, mixed $default = '') => old('content.' . $key, data_get($content, $key, $default));
    $selectedPackageIds = collect(old('content.program.package_ids', data_get($content, 'program.package_ids', [])))
        ->map(fn ($packageId) => (int) $packageId)
        ->values()
        ->all();
    $testimonials = old('content.testimonials.items', data_get($content, 'testimonials.items', []));
    $achievements = old('content.achievements.items', data_get($content, 'achievements.items', []));
    $partners = old('content.partners.items', data_get($content, 'partners.items', []));
    $faqs = old('content.faq.items', data_get($content, 'faq.items', []));
    $logoStack = old('content.hero.logo_stack', data_get($content, 'hero.logo_stack', []));
    $seoValue = fn (string $key, mixed $default = '') => old('seo.' . $key, data_get($seo ?? [], $key, $default));
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Landing Page</h2>
            <p class="text-sm text-gray-500">Edit konten landing per section. Penyimpanan backend tetap fleksibel.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('landing') }}" target="_blank"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <i class="ri-external-link-line"></i>
                Preview
            </a>
            <a href="{{ route('admin.artikel.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Artikel
            </a>
        </div>
    </div>

    <form action="{{ route('admin.general-pages.landing.update') }}" method="POST" enctype="multipart/form-data" class="space-y-5"
        x-data="landingPageEditor({
            selectedPackageIds: @js($selectedPackageIds),
            logoStack: @js($logoStack),
            testimonials: @js($testimonials),
            achievements: @js($achievements),
            partners: @js($partners),
            faqs: @js($faqs),
        })">
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <p class="font-semibold">Landing page belum tersimpan. Periksa error berikut:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <div>
                <label for="template_key" class="mb-2 block text-sm font-medium text-gray-700">Tipe Template</label>
                <input type="text" id="template_key" name="template_key"
                    value="{{ old('template_key', $page->template_key ?? 'default') }}"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                <p class="mt-2 text-xs text-gray-500">Gunakan <code>default</code> untuk tampilan landing saat ini. Aktif/nonaktif menu General diatur dari Super Admin.</p>
                @error('template_key')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-4 py-3">
                <div class="flex gap-2 overflow-x-auto text-sm font-semibold">
                    @foreach([
                        'hero' => 'Hero',
                        'navigation' => 'Navigasi',
                        'program' => 'Program',
                        'community' => 'Komunitas',
                        'testimonials' => 'Testimoni',
                        'achievements' => 'Pencapaian',
                        'partners' => 'Mitra',
                        'faq' => 'FAQ',
                        'footer' => 'Footer',
                        'advanced' => 'SEO',
                    ] as $key => $label)
                        <button type="button" @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'bg-primary text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'"
                            class="whitespace-nowrap rounded-lg px-3 py-2">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="p-5">
                <section x-show="tab === 'hero'" class="space-y-5">
                    <div class="grid gap-5 lg:grid-cols-2">
                        <x-admin-input name="content[meta][title]" label="Meta Title" :value="$value('meta.title')" />
                        <x-admin-input name="content[hero][badge]" label="Badge" :value="$value('hero.badge')" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Headline HTML</label>
                        <textarea name="content[hero][title_html]" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('hero.title_html') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Boleh pakai HTML kecil seperti <code>&lt;br&gt;</code> dan <code>&lt;span&gt;</code>.</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Deskripsi Hero</label>
                        <textarea name="content[hero][description]" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('hero.description') }}</textarea>
                    </div>
                    <div class="grid gap-5 lg:grid-cols-2">
                        <x-admin-input name="content[hero][primary_cta][label]" label="Primary CTA Label" :value="$value('hero.primary_cta.label')" />
                        <x-admin-input name="content[hero][primary_cta][href]" label="Primary CTA URL" :value="$value('hero.primary_cta.href')" />
                        <x-admin-input name="content[hero][secondary_cta][label]" label="Secondary CTA Label" :value="$value('hero.secondary_cta.label')" />
                        <x-admin-input name="content[hero][secondary_cta][href]" label="Secondary CTA URL" :value="$value('hero.secondary_cta.href')" />
                        <div>
                            <x-admin-input name="content[hero][image]" label="Hero Image Path/URL" :value="$value('hero.image')" />
                            <label class="mt-3 block">
                                <span class="mb-2 block text-sm font-medium text-gray-700">Upload Hero Image</span>
                                <input type="file" name="landing_images[hero_image]" accept="image/*" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-primary/90">
                                <span class="mt-1 block text-xs text-gray-500">Ukuran ideal: 1200 × 1200 px (rasio 1:1).</span>
                            </label>
                            @error('landing_images.hero_image')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <x-admin-input name="content[hero][image_alt]" label="Hero Image Alt" :value="$value('hero.image_alt')" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Social Proof HTML</label>
                        <textarea name="content[hero][social_proof_html]" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('hero.social_proof_html') }}</textarea>
                    </div>
                    <div>
                        <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Logo Hero</p>
                                <p class="mt-1 text-xs text-gray-500">Logo ini tampil di hero dekat teks social proof. Cukup upload gambar dan isi nama/alt logo.</p>
                            </div>
                            <button type="button" @click="addLogo()" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                                <i class="ri-add-line"></i>
                                Tambah Logo Hero
                            </button>
                        </div>
                        <div x-show="logoStack.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-5 text-center">
                            <p class="text-sm font-semibold text-gray-700">Belum ada logo hero.</p>
                            <p class="mt-1 text-xs text-gray-500">Klik tombol di bawah untuk menambahkan logo pertama.</p>
                            <button type="button" @click="addLogo()" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                                <i class="ri-add-line"></i>
                                Tambah Logo Hero
                            </button>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <template x-for="(item, index) in logoStack" :key="item._key">
                                <div class="rounded-lg border border-gray-200 p-3">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <p class="font-semibold text-gray-900">Logo <span x-text="index + 1"></span></p>
                                        <button type="button" @click="removeLogo(index)" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">
                                            <i class="ri-delete-bin-line"></i>
                                            Hapus
                                        </button>
                                    </div>
                                    <div class="space-y-3">
                                        <input type="hidden" :name="`content[hero][logo_stack][${index}][src]`" x-model="item.src">
                                        <template x-if="item.src">
                                            <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                                                <img :src="assetUrl(item.src)" :alt="item.alt || 'Logo hero'" class="h-12 w-12 rounded-lg object-contain bg-white p-1">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-gray-800">Logo tersimpan</p>
                                                    <p class="truncate text-xs text-gray-500" x-text="item.src"></p>
                                                </div>
                                            </div>
                                        </template>
                                        <label class="block">
                                            <span class="mb-2 block text-sm font-medium text-gray-700">Upload Logo</span>
                                            <input type="file" :name="`landing_images[logo_stack][${index}][src]`" accept="image/*" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-primary/90">
                                            <span class="mt-1 block text-xs text-gray-500">Gunakan rasio 1:1, minimal 512 × 512 px; PNG transparan direkomendasikan. Sistem akan menyimpan path otomatis.</span>
                                        </label>
                                        <input type="text" :name="`content[hero][logo_stack][${index}][alt]`" x-model="item.alt" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Nama/Alt logo">
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </section>

                <section x-show="tab === 'navigation'" class="space-y-5">
                    <div class="grid gap-5 lg:grid-cols-2">
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

                <section x-show="tab === 'program'" class="space-y-5">
                    <div class="grid gap-5 lg:grid-cols-2">
                        <x-admin-input name="content[program][eyebrow]" label="Eyebrow" :value="$value('program.eyebrow')" />
                        <x-admin-input name="content[program][title]" label="Judul Section" :value="$value('program.title')" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Deskripsi Program</label>
                        <textarea name="content[program][description]" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('program.description') }}</textarea>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="mb-4 text-sm font-semibold text-gray-900">Wording Kartu & Form Paket</p>
                        <div class="grid gap-4 lg:grid-cols-2">
                            <x-admin-input name="content[program][paid_suffix]" label="Label Sekali Bayar" :value="$value('program.paid_suffix')" />
                            <x-admin-input name="content[program][empty_message]" label="Pesan Program Kosong" :value="$value('program.empty_message')" />
                            <x-admin-input name="content[program][paid_cta_label]" label="CTA Paket Berbayar" :value="$value('program.paid_cta_label')" />
                            <x-admin-input name="content[program][conditional_cta_label]" label="CTA Gratis Bersyarat" :value="$value('program.conditional_cta_label')" />
                            <x-admin-input name="content[program][free_cta_label]" label="CTA Paket Gratis" :value="$value('program.free_cta_label')" />
                            <x-admin-input name="content[program][conditional_price_label]" label="Harga Gratis Bersyarat" :value="$value('program.conditional_price_label')" />
                            <x-admin-input name="content[program][free_price_label]" label="Harga Paket Gratis" :value="$value('program.free_price_label')" />
                            <x-admin-input name="content[program][conditional][eyebrow]" label="Eyebrow Form Syarat" :value="$value('program.conditional.eyebrow')" />
                            <x-admin-input name="content[program][conditional][detail_title]" label="Judul Detail Syarat" :value="$value('program.conditional.detail_title')" />
                            <x-admin-input name="content[program][conditional][proof_label]" label="Label Upload Bukti" :value="$value('program.conditional.proof_label')" />
                            <x-admin-input name="content[program][conditional][notes_label]" label="Label Catatan" :value="$value('program.conditional.notes_label')" />
                            <x-admin-input name="content[program][conditional][optional_label]" label="Label Opsional" :value="$value('program.conditional.optional_label')" />
                            <x-admin-input name="content[program][conditional][notes_placeholder]" label="Placeholder Catatan" :value="$value('program.conditional.notes_placeholder')" />
                            <x-admin-input name="content[program][conditional][cancel_label]" label="Label Batal" :value="$value('program.conditional.cancel_label')" />
                            <x-admin-input name="content[program][conditional][submit_label]" label="Label Kirim Bukti" :value="$value('program.conditional.submit_label')" />
                        </div>
                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Deskripsi Default Paket</label>
                                <textarea name="content[program][description_fallback]" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('program.description_fallback') }}</textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Deskripsi Form Syarat</label>
                                <textarea name="content[program][conditional][description]" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('program.conditional.description') }}</textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Fallback Persyaratan</label>
                                <textarea name="content[program][conditional][requirement_fallback]" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('program.conditional.requirement_fallback') }}</textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Bantuan Upload Bukti</label>
                                <textarea name="content[program][conditional][proof_help]" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('program.conditional.proof_help') }}</textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Bantuan Catatan</label>
                                <textarea name="content[program][conditional][notes_help]" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('program.conditional.notes_help') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                        <div class="flex items-start gap-3">
                            <i class="ri-information-line mt-0.5 text-lg text-blue-600"></i>
                            <div>
                                <p class="text-sm font-semibold text-blue-900">Pilih maksimal 3 paket</p>
                                <p class="mt-1 text-xs text-blue-700">Nama, harga, deskripsi, fitur, dan thumbnail pada landing page otomatis mengikuti data paket.</p>
                            </div>
                        </div>
                    </div>
                    @error('content.program.package_ids')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('content.program.package_ids.*')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @forelse($packages as $package)
                            @php
                                $thumbnailUrl = $package->image ? \Illuminate\Support\Facades\Storage::url($package->image) : null;
                                $isVideoThumbnail = $package->image
                                    && in_array(strtolower(pathinfo($package->image, PATHINFO_EXTENSION)), ['mp4', 'webm', 'mov', 'm4v'], true);
                                $isPubliclyAvailable = $package->status === 'active' && (bool) $package->is_displayed;
                            @endphp
                            <label class="relative overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:border-primary/40"
                                :class="selectedPackageIds.includes({{ $package->package_id }}) ? 'ring-2 ring-primary border-primary' : ''">
                                <div class="h-32 bg-gray-100">
                                    @if($thumbnailUrl)
                                        @if($isVideoThumbnail)
                                            <video src="{{ $thumbnailUrl }}" class="h-full w-full object-cover" muted preload="metadata"></video>
                                        @else
                                            <img src="{{ $thumbnailUrl }}" alt="{{ $package->name }}" class="h-full w-full object-cover">
                                        @endif
                                    @else
                                        <div class="flex h-full items-center justify-center text-gray-400">
                                            <i class="ri-image-line text-4xl"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="space-y-2 p-4">
                                    <div class="flex items-start gap-3">
                                        <input type="checkbox" name="content[program][package_ids][]" value="{{ $package->package_id }}"
                                            x-model.number="selectedPackageIds"
                                            :disabled="selectedPackageIds.length >= 3 && !selectedPackageIds.includes({{ $package->package_id }})"
                                            class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900">{{ $package->name }}</p>
                                            <p class="mt-1 text-sm font-bold text-primary">
                                                {{ $package->type_price === 'paid' ? 'Rp ' . number_format($package->price, 0, ',', '.') : ($package->type_price === 'free_conditional' ? 'Gratis Bersyarat' : 'Gratis') }}
                                            </p>
                                        </div>
                                    </div>
                                    @unless($isPubliclyAvailable)
                                        <p class="rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700">Paket tidak akan tampil karena nonaktif atau hidden.</p>
                                    @endunless
                                </div>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">Belum ada paket yang dapat dipilih.</p>
                        @endforelse
                    </div>
                    <p class="text-sm text-gray-500"><span x-text="selectedPackageIds.length"></span>/3 paket dipilih</p>
                </section>

                <section x-show="tab === 'community'" class="space-y-5">
                    <div class="grid gap-5 lg:grid-cols-2">
                        <x-admin-input name="content[community][badge]" label="Badge" :value="$value('community.badge')" />
                        <x-admin-input name="content[community][title]" label="Judul" :value="$value('community.title')" />
                        <x-admin-input name="content[community][cta][label]" label="CTA Label" :value="$value('community.cta.label')" />
                        <x-admin-input name="content[community][cta][href]" label="CTA URL" :value="$value('community.cta.href')" />
                    </div>
                    <textarea name="content[community][description]" rows="4" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('community.description') }}</textarea>
                </section>

                <section x-show="tab === 'testimonials'" class="space-y-5">
                    <div class="grid gap-5 lg:grid-cols-2">
                        <x-admin-input name="content[testimonials][eyebrow]" label="Eyebrow" :value="$value('testimonials.eyebrow')" />
                        <x-admin-input name="content[testimonials][title]" label="Judul" :value="$value('testimonials.title')" />
                        <x-admin-input name="content[testimonials][verified_label]" label="Label Alumni Terverifikasi" :value="$value('testimonials.verified_label')" />
                    </div>
                    <textarea name="content[testimonials][description]" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('testimonials.description') }}</textarea>
                    <div class="flex justify-end">
                        <button type="button" @click="addTestimonial()" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                            <i class="ri-add-line"></i>
                            Tambah Testimoni
                        </button>
                    </div>
                    <div class="grid gap-5 xl:grid-cols-2">
                        <template x-for="(item, index) in testimonials" :key="item._key">
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <p class="font-semibold text-gray-900">Testimoni <span x-text="index + 1"></span></p>
                                    <button type="button" @click="removeTestimonial(index)" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">
                                        <i class="ri-delete-bin-line"></i>
                                        Hapus
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    <input type="text" :name="`content[testimonials][items][${index}][name]`" x-model="item.name" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Nama">
                                    <input type="text" :name="`content[testimonials][items][${index}][result]`" x-model="item.result" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Hasil/Lolos">
                                    <input type="text" :name="`content[testimonials][items][${index}][image]`" x-model="item.image" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Foto Path/URL">
                                    <label class="block">
                                        <span class="mb-2 block text-sm font-medium text-gray-700">Upload Foto</span>
                                        <input type="file" :name="`landing_images[testimonials][${index}][image]`" accept="image/*" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-primary/90">
                                        <span class="mt-1 block text-xs text-gray-500">Ukuran ideal: 512 × 512 px (rasio 1:1).</span>
                                    </label>
                                    <textarea :name="`content[testimonials][items][${index}][quote]`" x-model="item.quote" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Testimoni"></textarea>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                <section x-show="tab === 'achievements'" class="space-y-5">
                    <div class="grid gap-5 lg:grid-cols-2">
                        <x-admin-input name="content[achievements][eyebrow]" label="Eyebrow" :value="$value('achievements.eyebrow')" />
                        <x-admin-input name="content[achievements][title]" label="Judul" :value="$value('achievements.title')" />
                    </div>
                    <div class="flex justify-end">
                        <button type="button" @click="addAchievement()" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                            <i class="ri-add-line"></i>
                            Tambah Pencapaian
                        </button>
                    </div>
                    <div class="grid gap-5 lg:grid-cols-3">
                        <template x-for="(item, index) in achievements" :key="item._key">
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <p class="font-semibold text-gray-900">Pencapaian <span x-text="index + 1"></span></p>
                                    <button type="button" @click="removeAchievement(index)" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">
                                        <i class="ri-delete-bin-line"></i>
                                        Hapus
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    <input type="text" :name="`content[achievements][items][${index}][value]`" x-model="item.value" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Angka">
                                    <input type="text" :name="`content[achievements][items][${index}][label]`" x-model="item.label" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Label">
                                    <textarea :name="`content[achievements][items][${index}][description]`" x-model="item.description" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Deskripsi"></textarea>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                <section x-show="tab === 'partners'" class="space-y-5">
                    <div class="grid gap-5 lg:grid-cols-2">
                        <x-admin-input name="content[partners][eyebrow]" label="Eyebrow" :value="$value('partners.eyebrow')" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Deskripsi Mitra</label>
                        <textarea name="content[partners][description]" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('partners.description') }}</textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" @click="addPartner()" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                            <i class="ri-add-line"></i>
                            Tambah Mitra
                        </button>
                    </div>
                    <div class="grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
                        <template x-for="(item, index) in partners" :key="item._key">
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <p class="font-semibold text-gray-900">Mitra <span x-text="index + 1"></span></p>
                                    <button type="button" @click="removePartner(index)" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">
                                        <i class="ri-delete-bin-line"></i>
                                        Hapus
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    <input type="text" :name="`content[partners][items][${index}][name]`" x-model="item.name" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Nama mitra/sekolah">
                                    <input type="text" :name="`content[partners][items][${index}][location]`" x-model="item.location" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Lokasi">
                                    <input type="text" :name="`content[partners][items][${index}][logo]`" x-model="item.logo" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Logo Path/URL">
                                    <label class="block">
                                        <span class="mb-2 block text-sm font-medium text-gray-700">Upload Logo</span>
                                        <input type="file" :name="`landing_images[partners][${index}][logo]`" accept="image/*" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-primary/90">
                                        <span class="mt-1 block text-xs text-gray-500">Ukuran ideal: 512 × 512 px (rasio 1:1); PNG transparan direkomendasikan.</span>
                                    </label>
                                    <input type="text" :name="`content[partners][items][${index}][alt]`" x-model="item.alt" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Alt logo">
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                <section x-show="tab === 'faq'" class="space-y-5">
                    <div class="grid gap-5 lg:grid-cols-2">
                        <x-admin-input name="content[faq][eyebrow]" label="Eyebrow" :value="$value('faq.eyebrow')" />
                        <x-admin-input name="content[faq][title]" label="Judul" :value="$value('faq.title')" />
                    </div>
                    <div class="flex justify-end">
                        <button type="button" @click="addFaq()" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                            <i class="ri-add-line"></i>
                            Tambah FAQ
                        </button>
                    </div>
                    <div class="space-y-4">
                        <template x-for="(item, index) in faqs" :key="item._key">
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <p class="font-semibold text-gray-900">FAQ <span x-text="index + 1"></span></p>
                                    <button type="button" @click="removeFaq(index)" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">
                                        <i class="ri-delete-bin-line"></i>
                                        Hapus
                                    </button>
                                </div>
                                <input type="text" :name="`content[faq][items][${index}][question]`" x-model="item.question" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Pertanyaan">
                                <textarea :name="`content[faq][items][${index}][answer]`" x-model="item.answer" rows="3" class="mt-3 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Jawaban"></textarea>
                            </div>
                        </template>
                    </div>
                </section>

                <section x-show="tab === 'footer'" class="space-y-5">
                    <div class="grid gap-5 lg:grid-cols-2">
                        <x-admin-input name="content[footer][tagline]" label="Tagline" :value="$value('footer.tagline')" />
                        <x-admin-input name="content[footer][navigation_title]" label="Judul Navigasi" :value="$value('footer.navigation_title')" />
                        <x-admin-input name="content[footer][nav_landing_label]" label="Label Home Landing" :value="$value('footer.nav_landing_label')" />
                        <x-admin-input name="content[footer][nav_statistics_snbp_label]" label="Label Statistik SNBP" :value="$value('footer.nav_statistics_snbp_label')" />
                        <x-admin-input name="content[footer][nav_statistics_snbt_label]" label="Label Statistik SNBT" :value="$value('footer.nav_statistics_snbt_label')" />
                        <x-admin-input name="content[footer][nav_articles_label]" label="Label Artikel" :value="$value('footer.nav_articles_label')" />
                        <x-admin-input name="content[footer][nav_login_label]" label="Label Login" :value="$value('footer.nav_login_label')" />
                        <x-admin-input name="content[footer][contact_title]" label="Judul Kontak" :value="$value('footer.contact_title')" />
                        <x-admin-input name="content[footer][instagram_label]" label="Instagram Label" :value="$value('footer.instagram_label')" />
                        <x-admin-input name="content[footer][instagram_href]" label="Instagram URL" :value="$value('footer.instagram_href')" />
                        <x-admin-input name="content[footer][whatsapp_label]" label="WhatsApp Label" :value="$value('footer.whatsapp_label')" />
                        <x-admin-input name="content[footer][whatsapp_href]" label="WhatsApp URL" :value="$value('footer.whatsapp_href')" />
                        <x-admin-input name="content[footer][email_label]" label="Email Label" :value="$value('footer.email_label')" />
                        <x-admin-input name="content[footer][email_href]" label="Email URL" :value="$value('footer.email_href')" />
                        <x-admin-input name="content[footer][terms_label]" label="Label Syarat" :value="$value('footer.terms_label')" />
                        <x-admin-input name="content[footer][terms_href]" label="URL Syarat" :value="$value('footer.terms_href')" />
                        <x-admin-input name="content[footer][privacy_label]" label="Label Privasi" :value="$value('footer.privacy_label')" />
                        <x-admin-input name="content[footer][privacy_href]" label="URL Privasi" :value="$value('footer.privacy_href')" />
                        <x-admin-input name="content[footer][copyright_suffix]" label="Teks Copyright" :value="$value('footer.copyright_suffix')" />
                    </div>
                    <textarea name="content[footer][description]" rows="4" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $value('footer.description') }}</textarea>
                </section>

                <section x-show="tab === 'advanced'" class="space-y-5">
                    <div class="grid gap-5 lg:grid-cols-2">
                        <x-admin-input name="seo[title]" label="SEO Title" :value="$seoValue('title')" />
                        <div>
                            <x-admin-input name="seo[image]" label="SEO Image Path/URL" :value="$seoValue('image')" />
                            <label class="mt-3 block">
                                <span class="mb-2 block text-sm font-medium text-gray-700">Upload SEO Image</span>
                                <input type="file" name="landing_images[seo_image]" accept="image/*" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-primary/90">
                                <span class="mt-1 block text-xs text-gray-500">Ukuran ideal: 1200 × 630 px (rasio 1.91:1).</span>
                            </label>
                            @error('landing_images.seo_image')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <label for="seo_description" class="mb-2 block text-sm font-medium text-gray-700">SEO Description</label>
                        <textarea id="seo_description" name="seo[description]" rows="4" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ $seoValue('description') }}</textarea>
                        @error('seo.description')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </section>
            </div>
        </div>

        @error('content')
            <p class="text-sm text-red-500">{{ $message }}</p>
        @enderror

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">
                <i class="ri-save-line"></i>
                Simpan Landing Page
            </button>
            <a href="{{ route('landing') }}" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Lihat Halaman
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        if (window.__landingPageEditorRegistered) {
            return;
        }

        window.__landingPageEditorRegistered = true;

        Alpine.data('landingPageEditor', (initial) => ({
            tab: 'hero',
            selectedPackageIds: [],
            logoStack: [],
            testimonials: [],
            achievements: [],
            partners: [],
            faqs: [],

            init() {
                this.selectedPackageIds = Array.isArray(initial.selectedPackageIds)
                    ? initial.selectedPackageIds.map(Number)
                    : [];
                this.logoStack = this.withKeys(initial.logoStack || [], this.normalizeLogo.bind(this));
                this.testimonials = this.withKeys(initial.testimonials || [], this.normalizeTestimonial.bind(this));
                this.achievements = this.withKeys(initial.achievements || [], this.normalizeAchievement.bind(this));
                this.partners = this.withKeys(initial.partners || [], this.normalizePartner.bind(this));
                this.faqs = this.withKeys(initial.faqs || [], this.normalizeFaq.bind(this));
            },

            makeKey() {
                return Date.now().toString(36) + Math.random().toString(36).slice(2);
            },

            assetUrl(path) {
                if (!path) {
                    return '';
                }

                if (/^(https?:)?\/\//.test(path) || path.startsWith('/')) {
                    return path;
                }

                if (path.startsWith('general/landing/')) {
                    return `/storage/${path}`;
                }

                return `/${path}`;
            },

            withKeys(items, normalizer) {
                return Array.isArray(items) ? items.map((item) => normalizer(item)) : [];
            },

            normalizeLogo(item = {}) {
                return {
                    _key: this.makeKey(),
                    src: item.src || '',
                    alt: item.alt || '',
                };
            },

            normalizeTestimonial(item = {}) {
                return {
                    _key: this.makeKey(),
                    name: item.name || '',
                    result: item.result || '',
                    image: item.image || '',
                    quote: item.quote || '',
                };
            },

            normalizeAchievement(item = {}) {
                return {
                    _key: this.makeKey(),
                    value: item.value || '',
                    label: item.label || '',
                    description: item.description || '',
                };
            },

            normalizePartner(item = {}) {
                return {
                    _key: this.makeKey(),
                    name: item.name || '',
                    location: item.location || '',
                    logo: item.logo || '',
                    alt: item.alt || '',
                };
            },

            normalizeFaq(item = {}) {
                return {
                    _key: this.makeKey(),
                    question: item.question || '',
                    answer: item.answer || '',
                };
            },

            addTestimonial() {
                this.testimonials.push(this.normalizeTestimonial());
            },

            removeTestimonial(index) {
                this.testimonials.splice(index, 1);
            },

            addAchievement() {
                this.achievements.push(this.normalizeAchievement());
            },

            removeAchievement(index) {
                this.achievements.splice(index, 1);
            },

            addLogo() {
                this.logoStack.push(this.normalizeLogo());
            },

            removeLogo(index) {
                this.logoStack.splice(index, 1);
            },

            addPartner() {
                this.partners.push(this.normalizePartner());
            },

            removePartner(index) {
                this.partners.splice(index, 1);
            },

            addFaq() {
                this.faqs.push(this.normalizeFaq());
            },

            removeFaq(index) {
                this.faqs.splice(index, 1);
            },
        }));
    });
</script>
@endpush
