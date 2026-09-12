@props(['tryoutDetail'])

<aside role="status" class="fixed inset-x-0 top-0 z-[60] h-14 bg-primary text-white shadow-md">
    <div class="flex h-full items-center justify-between gap-3 px-3 sm:px-5">
        <p class="min-w-0 truncate text-sm">
            <i class="ri-checkbox-multiple-line mr-1.5 text-base"></i>
            <span class="font-semibold">Mode Pilih Soal aktif</span>
            <span class="hidden sm:inline">— {{ $tryoutDetail->tryout->name ?? 'Tryout' }} · {{ $tryoutDetail->display_name }}</span>
        </p>
        <a href="{{ route('admin.question.index', $tryoutDetail->tryout_detail_id) }}"
            class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-white/15 px-3 py-2 text-sm font-semibold text-white transition-colors hover:bg-white hover:text-primary">
            <i class="ri-close-line"></i>
            Batalkan
        </a>
    </div>
</aside>
