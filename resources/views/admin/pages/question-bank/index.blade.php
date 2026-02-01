@extends('admin.layout.admin')
@section('title', __('Bank Soal'))
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Bank Soal') }}</h1>
            <p class="text-gray-500">{{ __('Atur koleksi soal dan sub bank untuk mempermudah penyusunan tryout.') }}</p>
        </div>
        <button id="openCreateBank"
            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-white hover:bg-primary/90">
            <i class="ri-add-circle-line text-lg"></i>
            {{ __('Tambah Bank') }}
        </button>
    </div>

    @if (session('success'))
    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
        {{ session('success') }}
    </div>
    @endif

    @if (session('error'))
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
        {{ session('error') }}
    </div>
    @endif

    @if ($tryoutDetail)
    <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-800">
        {{ __('Kamu sedang memilih soal untuk tryout') }} <span class="font-semibold">{{ $tryoutDetail->tryout->name ?? '-' }}</span>
        ({{ __('Subtest:') }} <span class="font-semibold">{{ strtoupper($tryoutDetail->type_subtest ?? '-') }}</span>). {{ __('Pilih bank dan gunakan soal yang sesuai.') }}
    </div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-primary/20 bg-primary/5 px-5 py-4">
            <p class="text-sm text-primary">{{ __('Total Bank') }}</p>
            <p class="text-3xl font-semibold text-primary mt-1">{{ number_format($stats['total_banks']) }}</p>
        </div>
        <div class="rounded-2xl border border-primary/20 bg-primary/5 px-5 py-4">
            <p class="text-sm text-primary">{{ __('Sub Bank') }}</p>
            <p class="text-3xl font-semibold text-primary mt-1">{{ number_format($stats['child_banks']) }}</p>
        </div>
        <div class="rounded-2xl border border-primary/20 bg-primary/5 px-5 py-4">
            <p class="text-sm text-primary">{{ __('Total Soal Tersimpan') }}</p>
            <p class="text-3xl font-semibold text-primary mt-1">{{ number_format($stats['total_questions']) }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-border bg-white p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Daftar Bank Soal') }}</h2>
                <p class="text-sm text-gray-500">{{ __('Pilih bank untuk melihat sub bank dan soal di dalamnya.') }}</p>
            </div>
        </div>

        @if ($rootBanks->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-200 p-8 text-center text-gray-500">
            {{ __('Belum ada bank soal. Klik tombol') }} <span class="font-semibold">{{ __('Tambah Bank') }}</span> {{ __('untuk mulai membuat.') }}
        </div>
        @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($rootBanks as $bank)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-400">{{ __('Bank Soal') }}</p>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $bank->name }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ \Illuminate\Support\Str::limit($bank->description, 80) }}</p>
                    </div>
                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                        {{ $bank->questions_count }} {{ __('Soal') }}
                    </span>
                </div>
                <div class="mt-4 flex flex-wrap gap-2 text-xs text-gray-500">
                    <span class="rounded-full bg-gray-100 px-3 py-1">{{ __('Sub bank: :count', ['count' => $bank->children->count()]) }}</span>
                </div>
                <a href="{{ route('admin.question-bank.show', ['questionBank' => $bank->id, 'import_for' => $importTarget]) }}"
                    class="mt-4 inline-flex w-full items-center justify-center rounded-lg border border-primary text-primary px-4 py-2 text-sm font-semibold hover:bg-primary/5">
                    {{ $tryoutDetail ? __('Pilih Bank') : __('Kelola Bank') }}
                </a>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

<!-- Create Bank Modal -->
<div id="createBankModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 transition">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-semibold text-gray-900">{{ __('Tambah Bank Soal') }}</h3>
            <button type="button" id="closeCreateBank" class="text-gray-400 hover:text-gray-600">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        <form action="{{ route('admin.question-bank.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Nama Bank') }}</label>
                <input type="text" name="name" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    placeholder="{{ __('Contoh: Bank Soal TPS TKA') }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Sub Bank Dari') }}</label>
                <select name="parent_id"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <option value="">{{ __('(Tidak ada - Bank utama)') }}</option>
                    @foreach ($bankOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Deskripsi') }}</label>
                <textarea name="description" rows="3"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    placeholder="{{ __('Tuliskan ringkasan isi bank soal') }}"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="cancelCreateBank"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">{{ __('Batal') }}</button>
                <button type="submit"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">{{ __('Simpan') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('createBankModal');
        const openBtn = document.getElementById('openCreateBank');
        const closeBtn = document.getElementById('closeCreateBank');
        const cancelBtn = document.getElementById('cancelCreateBank');

        function toggleModal(show) {
            if (!modal) return;
            if (show) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            } else {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }
        }

        openBtn?.addEventListener('click', () => toggleModal(true));
        closeBtn?.addEventListener('click', () => toggleModal(false));
        cancelBtn?.addEventListener('click', () => toggleModal(false));
        modal?.addEventListener('click', (event) => {
            if (event.target === modal) {
                toggleModal(false);
            }
        });
    });
</script>
@endpush
