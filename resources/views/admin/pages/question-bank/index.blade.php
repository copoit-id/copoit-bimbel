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
            <form method="GET" action="{{ route('admin.question-bank.index') }}" class="w-full sm:w-auto">
                @if($importTarget)
                <input type="hidden" name="import_for" value="{{ $importTarget }}">
                @endif
                <div class="flex items-center gap-2">
                    <input type="text" name="q" value="{{ $search ?? '' }}"
                        class="w-full sm:w-80 rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                        placeholder="{{ __('Cari soal / bank soal...') }}">
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                        {{ __('Cari') }}
                    </button>
                    @if(!empty($search))
                    <a href="{{ route('admin.question-bank.index', array_filter(['import_for' => $importTarget])) }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        {{ __('Reset') }}
                    </a>
                    @endif
                </div>
            </form>
        </div>

        @if(!empty($search))
        <div class="mb-5 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            {{ __('Hasil pencarian untuk') }} <span class="font-semibold">"{{ $search }}"</span>:
            <span class="font-semibold">{{ number_format($searchResults->total()) }}</span> {{ __('soal ditemukan') }}.
        </div>

        @if($searchResults->isEmpty())
        <div class="mb-6 rounded-lg border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500">
            {{ __('Tidak ada soal yang cocok dengan kata kunci tersebut.') }}
        </div>
        @else
        <div class="mb-6 overflow-hidden rounded-xl border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Soal') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Bank') }}</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($searchResults as $question)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 align-top text-gray-700">
                                {{ \Illuminate\Support\Str::limit(strip_tags($question->question_text), 140) }}
                            </td>
                            <td class="px-4 py-3 align-top text-gray-700">
                                {{ $question->bank->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 align-top text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-2 flex-nowrap">
                                    <a href="{{ route('admin.question-bank.questions.edit', ['question' => $question->id, 'import_for' => $importTarget]) }}"
                                        class="inline-flex items-center rounded-lg border border-primary px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/5 whitespace-nowrap shrink-0">
                                        {{ __('Edit Soal') }}
                                    </a>
                                    <a href="{{ route('admin.question-bank.show', ['questionBank' => $question->question_bank_id, 'import_for' => $importTarget]) }}"
                                        class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 whitespace-nowrap shrink-0">
                                        {{ __('Lihat Bank') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 bg-white px-4 py-3">
                {{ $searchResults->links() }}
            </div>
        </div>
        @endif
        @endif

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
