@extends('admin.layout.admin')

@php
    $value = fn (string $key, mixed $default = '') => old('content.' . $key, data_get($content, $key, $default));
    $programCards = old('content.program.cards', data_get($content, 'program.cards', []));
    $testimonials = old('content.testimonials.items', data_get($content, 'testimonials.items', []));
    $achievements = old('content.achievements.items', data_get($content, 'achievements.items', []));
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
            programCards: @js($programCards),
            testimonials: @js($testimonials),
            achievements: @js($achievements),
            faqs: @js($faqs),
        })">
        @csrf
        @method('PUT')

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
                        'program' => 'Program',
                        'community' => 'Komunitas',
                        'testimonials' => 'Testimoni',
                        'achievements' => 'Pencapaian',
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
                        <p class="mb-3 text-sm font-medium text-gray-700">Logo Stack</p>
                        <div class="grid gap-3 md:grid-cols-2">
                            @for($i = 0; $i < 4; $i++)
                            <div class="rounded-lg border border-gray-200 p-3">
                                <x-admin-input name="content[hero][logo_stack][{{ $i }}][src]" label="Logo {{ $i + 1 }} Path" :value="data_get($logoStack, $i . '.src')" />
                                <label class="mt-3 block">
                                    <span class="mb-2 block text-sm font-medium text-gray-700">Upload Logo {{ $i + 1 }}</span>
                                    <input type="file" name="landing_images[logo_stack][{{ $i }}][src]" accept="image/*" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-primary/90">
                                </label>
                                @error("landing_images.logo_stack.{$i}.src")
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                                <div class="mt-3">
                                    <x-admin-input name="content[hero][logo_stack][{{ $i }}][alt]" label="Logo {{ $i + 1 }} Alt" :value="data_get($logoStack, $i . '.alt')" />
                                </div>
                            </div>
                            @endfor
                        </div>
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
                    <div class="flex justify-end">
                        <button type="button" @click="addProgramCard()" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                            <i class="ri-add-line"></i>
                            Tambah Program
                        </button>
                    </div>
                    <div class="grid gap-5 xl:grid-cols-2">
                        <template x-for="(card, cardIndex) in programCards" :key="card._key">
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <p class="font-semibold text-gray-900">Kartu <span x-text="cardIndex + 1"></span></p>
                                    <button type="button" @click="removeProgramCard(cardIndex)" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">
                                        <i class="ri-delete-bin-line"></i>
                                        Hapus
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <label class="block">
                                            <span class="mb-2 block text-sm font-medium text-gray-700">Badge</span>
                                            <input type="text" :name="`content[program][cards][${cardIndex}][badge]`" x-model="card.badge" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                        </label>
                                        <label class="block">
                                            <span class="mb-2 block text-sm font-medium text-gray-700">Eyebrow</span>
                                            <input type="text" :name="`content[program][cards][${cardIndex}][eyebrow]`" x-model="card.eyebrow" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                        </label>
                                    </div>
                                    <label class="block">
                                        <span class="mb-2 block text-sm font-medium text-gray-700">Nama Paket</span>
                                        <input type="text" :name="`content[program][cards][${cardIndex}][title]`" x-model="card.title" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                    </label>
                                    <div class="grid gap-3 md:grid-cols-3">
                                        <label class="block">
                                            <span class="mb-2 block text-sm font-medium text-gray-700">Harga Coret</span>
                                            <input type="text" :name="`content[program][cards][${cardIndex}][original_price]`" x-model="card.original_price" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                        </label>
                                        <label class="block">
                                            <span class="mb-2 block text-sm font-medium text-gray-700">Harga</span>
                                            <input type="text" :name="`content[program][cards][${cardIndex}][price]`" x-model="card.price" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                        </label>
                                        <label class="block">
                                            <span class="mb-2 block text-sm font-medium text-gray-700">Catatan Harga</span>
                                            <input type="text" :name="`content[program][cards][${cardIndex}][price_note]`" x-model="card.price_note" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                        </label>
                                    </div>
                                    <textarea :name="`content[program][cards][${cardIndex}][description]`" x-model="card.description" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Deskripsi"></textarea>

                                    <div class="rounded-lg bg-gray-50 p-3">
                                        <div class="mb-3 flex items-center justify-between">
                                            <p class="text-sm font-semibold text-gray-800">Fitur</p>
                                            <button type="button" @click="addProgramFeature(card)" class="text-xs font-semibold text-primary hover:underline">Tambah Fitur</button>
                                        </div>
                                        <div class="space-y-2">
                                            <template x-for="(feature, featureIndex) in card.features" :key="feature._key">
                                                <div class="flex gap-2">
                                                    <input type="text" :name="`content[program][cards][${cardIndex}][features][${featureIndex}][label]`" x-model="feature.label" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Teks fitur">
                                                    <button type="button" @click="removeProgramFeature(card, featureIndex)" class="rounded-lg border border-red-200 px-3 text-red-600 hover:bg-red-50">
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <label class="block">
                                        <span class="mb-2 block text-sm font-medium text-gray-700">Highlight Box</span>
                                        <input type="text" :name="`content[program][cards][${cardIndex}][highlight]`" x-model="card.highlight" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                    </label>
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <label class="block">
                                            <span class="mb-2 block text-sm font-medium text-gray-700">CTA Label</span>
                                            <input type="text" :name="`content[program][cards][${cardIndex}][cta][label]`" x-model="card.cta.label" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                        </label>
                                        <label class="block">
                                            <span class="mb-2 block text-sm font-medium text-gray-700">CTA URL</span>
                                            <input type="text" :name="`content[program][cards][${cardIndex}][cta][href]`" x-model="card.cta.href" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
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
                        <x-admin-input name="content[footer][instagram_label]" label="Instagram Label" :value="$value('footer.instagram_label')" />
                        <x-admin-input name="content[footer][instagram_href]" label="Instagram URL" :value="$value('footer.instagram_href')" />
                        <x-admin-input name="content[footer][whatsapp_label]" label="WhatsApp Label" :value="$value('footer.whatsapp_label')" />
                        <x-admin-input name="content[footer][whatsapp_href]" label="WhatsApp URL" :value="$value('footer.whatsapp_href')" />
                        <x-admin-input name="content[footer][email_label]" label="Email Label" :value="$value('footer.email_label')" />
                        <x-admin-input name="content[footer][email_href]" label="Email URL" :value="$value('footer.email_href')" />
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
            programCards: [],
            testimonials: [],
            achievements: [],
            faqs: [],

            init() {
                this.programCards = this.withKeys(initial.programCards || [], this.normalizeProgramCard.bind(this));
                this.testimonials = this.withKeys(initial.testimonials || [], this.normalizeTestimonial.bind(this));
                this.achievements = this.withKeys(initial.achievements || [], this.normalizeAchievement.bind(this));
                this.faqs = this.withKeys(initial.faqs || [], this.normalizeFaq.bind(this));
            },

            makeKey() {
                return Date.now().toString(36) + Math.random().toString(36).slice(2);
            },

            withKeys(items, normalizer) {
                return Array.isArray(items) ? items.map((item) => normalizer(item)) : [];
            },

            normalizeProgramCard(item = {}) {
                const card = {
                    _key: this.makeKey(),
                    badge: item.badge || '',
                    eyebrow: item.eyebrow || '',
                    title: item.title || '',
                    original_price: item.original_price || '',
                    price: item.price || '',
                    price_note: item.price_note || '',
                    description: item.description || '',
                    highlight: item.highlight || '',
                    cta: {
                        label: item.cta?.label || '',
                        href: item.cta?.href || '',
                    },
                    features: [],
                };

                card.features = this.withKeys(item.features || [], this.normalizeProgramFeature.bind(this));

                return card;
            },

            normalizeProgramFeature(item = {}) {
                return {
                    _key: this.makeKey(),
                    label: item.label || item.label_html || '',
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

            normalizeFaq(item = {}) {
                return {
                    _key: this.makeKey(),
                    question: item.question || '',
                    answer: item.answer || '',
                };
            },

            addProgramCard() {
                this.programCards.push(this.normalizeProgramCard({
                    title: 'Program Baru',
                    cta: { label: 'Daftar Sekarang', href: '/login' },
                }));
            },

            removeProgramCard(index) {
                this.programCards.splice(index, 1);
            },

            addProgramFeature(card) {
                card.features.push(this.normalizeProgramFeature());
            },

            removeProgramFeature(card, index) {
                card.features.splice(index, 1);
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
