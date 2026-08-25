@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Manajemen Tryout</h2>
            <p class="text-gray-500">Kelola semua tryout dan ujian</p>
        </div>
        <a href="{{ route('admin.tryout.create') }}"
            class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 flex items-center gap-2">
            <i class="ri-add-line"></i>
            Tambah Tryout
        </a>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <form method="GET" action="{{ route('admin.tryout.index') }}" class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 lg:gap-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 w-full lg:w-auto">
                <div class="relative w-full sm:w-auto">
                    <input type="search" name="search" value="{{ $search }}" placeholder="Cari tryout..."
                        class="pl-10 pr-4 py-2 w-full sm:w-64 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <select name="type"
                        class="border border-gray-300 rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Semua Tipe</option>
                        <option value="tiu" @selected($type === 'tiu')>TIU</option>
                        <option value="twk" @selected($type === 'twk')>TWK</option>
                        <option value="tkp" @selected($type === 'tkp')>TKP</option>
                        <option value="tpa" @selected($type === 'tpa')>TPA</option>
                        <option value="tbi" @selected($type === 'tbi')>TBI</option>
                        <option value="skd_full" @selected($type === 'skd_full')>SKD Full</option>
                        <option value="general" @selected($type === 'general')>General</option>
                        <option value="certification" @selected($type === 'certification')>Certification</option>
                    </select>
                    <select name="status"
                        class="border border-gray-300 rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Semua Status</option>
                        <option value="akan_datang" @selected($filterStatus === 'akan_datang')>Akan Datang</option>
                        <option value="aktif" @selected($filterStatus === 'aktif')>Aktif</option>
                        <option value="selesai" @selected($filterStatus === 'selesai')>Selesai</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 w-full sm:w-auto">
                    <i class="ri-search-line"></i> Cari
                </button>
                <a href="{{ route('admin.tryout.index') }}"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 w-full sm:w-auto">
                    <i class="ri-refresh-line"></i> Reset
                </a>
            </div>
            <div class="text-sm text-gray-500 w-full lg:w-auto text-left lg:text-right">
                Total: <span class="font-medium text-gray-700">{{ $tryouts->total() }} Tryout</span>
            </div>
        </form>
    </div>

    <!-- Tryout Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="tryout-grid">
        @forelse($tryouts as $tryout)
        <div class="tryout-card bg-white px-5 py-5 rounded-lg border border-gray-200 h-full flex flex-col"
            data-name="{{ strtolower($tryout->name) }}" data-type="{{ strtoupper($tryout->type_tryout) }}"
            data-assessment="{{ $tryout->assessment_type ?? 'standard' }}"
            data-status="{{ $tryout->start_date?->isFuture() ? 'akan_datang' : ($tryout->end_date?->isPast() ? 'selesai' : 'aktif') }}">
            @php
                $scoringLabel = $tryout->requiresIrtScoring()
                    ? 'IRT'
                    : ($tryout->is_toefl ? 'TOEFL ITP' : null);
                $formattedTotalDuration = rtrim(rtrim(number_format((float) ($tryout->total_duration ?? 0), 2, '.', ''), '0'), '.');
            @endphp
            <div class="flex items-center justify-between mb-3">
                <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                    {{ strtoupper($tryout->type_tryout) }} {{ $scoringLabel ? '- ' . $scoringLabel : '' }}
                </span>
                <div class="flex items-center gap-1">
                    @if($tryout->is_certification)
                    <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs">
                        <i class="ri-award-line"></i>
                    </span>
                    @endif
                    @if($tryout->isIndividuallyAvailable())
                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">
                        <i class="{{ $tryout->isPaidIndividualAccess() ? 'ri-shopping-cart-fill' : 'ri-gift-line' }}"></i>
                    </span>
                    @endif
                    <span class="px-2 py-1 {{ $tryout->is_displayed ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }} rounded-full text-xs">
                        <i class="ri-{{ $tryout->is_displayed ? 'eye-line' : 'eye-off-line' }}"></i>
                    </span>
                </div>
            </div>

            @if(($tryout->assessment_type ?? 'standard') !== 'standard')
            <div class="flex justify-center mb-3">
                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $tryout->assessment_type === 'pre_test' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                    {{ strtoupper(str_replace('_', ' ', $tryout->assessment_type)) }}
                </span>
            </div>
            @endif

            <p class="text-lg font-bold text-black text-center mb-4">{{ $tryout->name }}</p>

            <div class="flex flex-col gap-1 mb-4">
                <span class="flex items-center justify-between">
                    <p class="font-medium">Total Soal:</p>
                    <p class="font-light">{{ $tryout->total_questions ?? 0 }} Soal</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Durasi:</p>
                    <p class="font-light">{{ $formattedTotalDuration }} Menit</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Subtest:</p>
                    <p class="font-light">{{ $tryout->tryoutDetails->count() }} Bagian</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Status:</p>
                    @if($tryout->start_date?->isFuture())
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">Akan Datang</span>
                    @elseif($tryout->end_date?->isPast()) <span
                        class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Selesai
                </span>
                @else
                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Aktif</span>
                @endif
                </span>
            </div>

            <div class="space-y-2 mt-auto">
                @php
                    $canManualRelease = method_exists($tryout, 'requiresIrtScoring') && $tryout->requiresIrtScoring();
                    $pendingCount = $tryout->utbk_pending_count ?? 0;
                    $releasedCount = $tryout->utbk_released_count ?? 0;
                    $showReleaseButton = $canManualRelease && $pendingCount > 0;
                    $showResetButton = $canManualRelease && ! $showReleaseButton && $releasedCount > 0;
                @endphp

                <div class="flex items-center gap-2">
                    @if ($tryout->tryoutDetails->count() > 1)
                    <!-- Multiple Subtest (SKD Full, Certification Full, PPPK Full, etc.) -->
                    <button data-modal-target="modal-{{ $tryout->tryout_id }}"
                        data-modal-toggle="modal-{{ $tryout->tryout_id }}"
                        class="flex-1 flex cursor-pointer justify-center bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/90">
                        <i class="ri-list-check mr-2"></i>
                        Kelola Soal ({{ $tryout->tryoutDetails->count() }} Subtest)
                    </button>
                    @else
                    <!-- Single Subtest -->
                    @if($tryout->tryoutDetails->first())
                    <a href="{{ route('admin.question.index', $tryout->tryoutDetails->first()->tryout_detail_id) }}"
                        class="flex-1 flex justify-center bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/90">
                        <i class="ri-list-check mr-2"></i>
                        Kelola Soal ({{ $tryout->tryoutDetails->first()->questions->count() ?? 0 }})
                    </a>
                    @else
                    <button disabled
                        class="flex-1 flex justify-center bg-gray-300 text-gray-500 px-4 py-2 rounded-lg text-sm">
                        <i class="ri-list-check mr-2"></i>
                        Belum ada subtest
                    </button>
                    @endif
                    @endif

                    @if($canManualRelease)
                        @if($showResetButton)
                        <form action="{{ route('admin.tryout.reset-utbk', $tryout->tryout_id) }}" method="POST"
                            onsubmit="return window.appConfirmSubmit(this, 'Reset skor IRT? Nilai yang sudah rilis akan dihapus dan harus dirilis ulang.', { title: 'Reset skor IRT', confirmText: 'Ya, reset', variant: 'danger' });">
                            @csrf
                            <button type="submit"
                                title="Reset skor IRT agar bisa dirilis ulang"
                                class="p-2 rounded-lg border border-red-400 text-red-500 text-sm flex items-center justify-center hover:bg-red-500 hover:text-white">
                                <i class="ri-refresh-line text-base"></i>
                            </button>
                        </form>
                        @elseif($showReleaseButton)
                        <form action="{{ route('admin.tryout.release-utbk', $tryout->tryout_id) }}" method="POST"
                            onsubmit="return window.appConfirmSubmit(this, 'Rilis hasil IRT sekarang? Pastikan semua peserta sudah selesai.', { title: 'Rilis hasil IRT', confirmText: 'Ya, rilis', variant: 'warning' });">
                            @csrf
                            <button type="submit"
                                title="Rilis nilai IRT secara manual"
                                class="p-2 rounded-lg border border-primary text-primary text-sm flex items-center justify-center hover:bg-primary hover:text-white">
                                <i class="ri-sparkling-line text-base"></i>
                            </button>
                        </form>
                        @endif
                    @endif
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('admin.tryout.edit', array_merge(request()->query(), ['tryout' => $tryout->tryout_id])) }}"
                        class="flex-1 flex justify-center border border-primary text-primary px-3 py-2 rounded-lg text-sm hover:bg-primary hover:text-white transition-colors">
                        <i class="ri-edit-line"></i>
                    </a>
                    <form action="{{ route('admin.tryout.clone', array_merge(request()->query(), ['tryout' => $tryout->tryout_id])) }}" method="POST" class="flex-1"
                        onsubmit="return window.appConfirmSubmit(this, 'Clone tryout ini beserta semua subtest, soal, dan opsi jawaban?', { title: 'Duplikat tryout', confirmText: 'Ya, duplikat', variant: 'warning' })">
                        @csrf
                        <button type="submit"
                            class="w-full flex justify-center border border-blue-500 text-blue-500 px-3 py-2 rounded-lg text-sm hover:bg-blue-500 hover:text-white transition-colors"
                            title="Clone tryout">
                            <i class="ri-file-copy-line"></i>
                        </button>
                    </form>
                    <form action="{{ route('admin.tryout.destroy', $tryout->tryout_id) }}" method="POST" class="flex-1"
                        onsubmit="return window.appConfirmSubmit(this, 'Apakah Anda yakin ingin menghapus tryout ini?', { title: 'Hapus tryout', confirmText: 'Ya, hapus', variant: 'danger' })">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full flex justify-center border border-red-500 text-red-500 px-3 py-2 rounded-lg text-sm hover:bg-red-500 hover:text-white transition-colors">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </form>
                    <a href="{{ route('admin.tryout.preview', $tryout->tryout_id) }}"
                        class="flex-1 flex justify-center border border-gray-300 text-gray-500 px-3 py-2 rounded-lg text-sm hover:bg-gray-100 transition-colors">
                        <i class="ri-eye-line"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Modal for Multiple Subtests (SKD Full, Certification Full, PPPK Full, etc.) -->
        @if ($tryout->tryoutDetails->count() > 1)
        <div id="modal-{{ $tryout->tryout_id }}" tabindex="-1" aria-hidden="true"
            class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-2xl max-h-full">
                <div class="relative bg-white rounded-lg shadow">
                    <div class="flex items-center justify-between p-4 md:p-5 border-b border-border rounded-t">
                        <h3 class="text-xl font-semibold text-gray-900">
                            Pilih Subtest - {{ $tryout->name }}
                        </h3>
                        <button type="button"
                            class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center"
                            data-modal-hide="modal-{{ $tryout->tryout_id }}">
                            <i class="ri-close-line text-lg"></i>
                        </button>
                    </div>
                    <div class="p-4 md:p-5 space-y-4">
                        @foreach($tryout->tryoutDetails as $detail)
                        @php
                            $formattedDuration = rtrim(rtrim(number_format((float) $detail->duration, 2, '.', ''), '0'), '.');
                        @endphp
                        <a href="{{ route('admin.question.index', $detail->tryout_detail_id) }}"
                            class="flex items-center justify-between w-full p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <div>
                                <h4 class="font-medium text-gray-900">{{ $detail->subtest_name }}</h4>
                                <p class="text-xs text-gray-400">
                                    {{ $formattedDuration }} menit • Passing Score:
                                    @if(($detail->passing_type ?? 'score') === 'percentage')
                                        {{ number_format($detail->passing_score ?? 0, 1) }}%
                                    @else
                                        {{ $detail->passing_score }}
                                    @endif
                                    • {{ $detail->questions->count() ?? 0 }} soal
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="flex items-center gap-2">
                                    @if($detail->questions->count() > 0)
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">
                                        {{ $detail->questions->count() }} Soal
                                    </span>
                                    @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded-full text-xs">
                                        Belum ada soal
                                    </span>
                                    @endif
                                    <i class="ri-arrow-right-line text-gray-400"></i>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        @empty
        <div class="col-span-full">
            <div class="text-center py-12">
                <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="ri-draft-line text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada tryout</h3>
                <p class="text-gray-500 mb-4">Mulai dengan membuat tryout pertama Anda</p>
                <a href="{{ route('admin.tryout.create') }}"
                    class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 inline-flex items-center gap-2">
                    <i class="ri-add-line"></i>
                    Tambah Tryout
                </a>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($tryouts->hasPages())
    <div class="flex justify-center">
        {{ $tryouts->links() }}
    </div>
    @endif
</div>
@endsection
