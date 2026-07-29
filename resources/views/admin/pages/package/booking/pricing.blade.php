@extends('admin.layout.admin')

@section('content')
<div class="mx-auto max-w-3xl space-y-6" x-data="{
    learningMode: @js(old('learning_mode', $rule->learning_mode)),
    pricingMode: @js(old('group_pricing_mode', $rule->group_pricing_mode ?? 'same')),
    min: Number(@js(old('min_participants', $rule->min_participants))),
    max: Number(@js(old('max_participants', $rule->max_participants))),
    tierPrices: {{ Illuminate\Support\Js::from(old('tier_prices', $tierPrices)) }},
    packagePrice: {{ (int) $package->price }},
    counts() {
        const start = Math.max(1, Number(this.min) || 1)
        const end = Math.max(start, Number(this.max) || start)
        return Array.from({ length: Math.min(end - start + 1, 20) }, (_, index) => start + index)
    },
}">
    <div>
        <a href="{{ route('admin.package.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-gray-500 hover:text-primary">
            <i class="ri-arrow-left-line"></i>
            Kembali ke paket
        </a>
        <h1 class="mt-3 text-2xl font-bold text-gray-900">Pengaturan Rombel</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $package->name }} · tentukan apakah paket bisa personal, rombel, atau keduanya serta harga per siswa.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold">Pengaturan belum tersimpan.</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.package-booking.update', $package) }}" class="rounded-2xl border border-gray-200 bg-white">
        @csrf
        @method('PUT')

        <div class="space-y-6 p-5 sm:p-6">
            <section>
                <h2 class="font-bold text-gray-900">Cara belajar</h2>
                <p class="mt-1 text-sm text-gray-500">Personal dapat langsung booking. Rombel harus mengajukan anggota lalu menunggu persetujuan admin.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach(['personal' => ['Personal', 'Hanya booking satu siswa'], 'group' => ['Rombel', 'Hanya melalui rombel'], 'both' => ['Keduanya', 'Siswa dapat memilih personal atau rombel']] as $value => [$label, $description])
                        <label class="cursor-pointer rounded-xl border border-gray-200 p-4" :class="learningMode === '{{ $value }}' ? 'border-primary bg-primary/5' : ''">
                            <input type="radio" name="learning_mode" value="{{ $value }}" x-model="learningMode" class="sr-only">
                            <span class="block text-sm font-bold text-gray-900">{{ $label }}</span>
                            <span class="mt-1 block text-xs text-gray-500">{{ $description }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section x-show="learningMode === 'group' || learningMode === 'both'" x-cloak class="border-t border-gray-200 pt-6">
                <h2 class="font-bold text-gray-900">Ukuran rombel</h2>
                <p class="mt-1 text-sm text-gray-500">Admin hanya dapat menyetujui rombel ketika jumlah siswa sudah lengkap.</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Minimal siswa</span>
                        <input type="number" name="min_participants" min="1" max="20" x-model.number="min" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Maksimal siswa</span>
                        <input type="number" name="max_participants" min="1" max="20" x-model.number="max" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                    </label>
                </div>
            </section>

            <section x-show="learningMode === 'group' || learningMode === 'both'" x-cloak class="border-t border-gray-200 pt-6">
                <h2 class="font-bold text-gray-900">Harga per siswa</h2>
                <p class="mt-1 text-sm text-gray-500">Default-nya sama untuk semua jumlah siswa. Pilih bertingkat bila harga berubah sesuai jumlah anggota.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="cursor-pointer rounded-xl border border-gray-200 p-4" :class="pricingMode === 'same' ? 'border-primary bg-primary/5' : ''">
                        <input type="radio" name="group_pricing_mode" value="same" x-model="pricingMode" class="sr-only">
                        <span class="block text-sm font-bold text-gray-900">Harga sama</span>
                        <span class="mt-1 block text-xs text-gray-500">Satu harga untuk setiap siswa, berapa pun jumlah rombelnya.</span>
                    </label>
                    <label class="cursor-pointer rounded-xl border border-gray-200 p-4" :class="pricingMode === 'tiered' ? 'border-primary bg-primary/5' : ''">
                        <input type="radio" name="group_pricing_mode" value="tiered" x-model="pricingMode" class="sr-only">
                        <span class="block text-sm font-bold text-gray-900">Harga bertingkat</span>
                        <span class="mt-1 block text-xs text-gray-500">Atur harga per siswa untuk setiap jumlah anggota.</span>
                    </label>
                </div>

                <label x-show="pricingMode === 'same'" class="mt-4 block">
                    <span class="text-sm font-semibold text-gray-700">Harga tiap siswa</span>
                    <div class="relative mt-2">
                        <span class="absolute inset-y-0 left-3 flex items-center text-sm text-gray-500">Rp</span>
                        <input type="number" name="same_price" min="0" value="{{ $samePrice }}" class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 focus:border-primary focus:ring-primary">
                    </div>
                </label>

                <div x-show="pricingMode === 'tiered'" class="mt-4 space-y-3">
                    <template x-for="count in counts()" :key="count">
                        <label class="flex items-center gap-3 rounded-xl border border-gray-200 p-3">
                            <span class="w-20 text-sm font-semibold text-gray-700" x-text="`${count} siswa`"></span>
                            <div class="relative flex-1">
                                <span class="absolute inset-y-0 left-3 flex items-center text-sm text-gray-500">Rp</span>
                                <input type="number" :name="`tier_prices[${count}]`" min="0" :value="tierPrices[count] ?? packagePrice" class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 focus:border-primary focus:ring-primary">
                            </div>
                            <span class="text-xs text-gray-500">/ siswa</span>
                        </label>
                    </template>
                </div>
            </section>
        </div>

        <div class="flex justify-end gap-3 border-t border-gray-200 p-5 sm:p-6">
            <a href="{{ route('admin.package.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
            <button class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white">Simpan pengaturan</button>
        </div>
    </form>
</div>
@endsection
