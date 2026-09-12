@props(['package'])

<aside role="status" class="fixed inset-x-0 top-0 z-[60] h-14 bg-primary text-white shadow-md">
    <div class="flex h-full items-center justify-between gap-3 px-3 sm:px-5">
        <p class="min-w-0 truncate text-sm">
            <i class="ri-calendar-schedule-line mr-1.5 text-base"></i>
            <span class="font-semibold">Mode Pilih Jadwal aktif</span>
            <span class="hidden sm:inline">— {{ $package->name }}</span>
        </p>
        <a href="{{ route('admin.package.edit', $package->package_id) }}"
            class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-white/15 px-3 py-2 text-sm font-semibold text-white transition-colors hover:bg-white hover:text-primary">
            <i class="ri-check-line"></i>
            Selesai
        </a>
    </div>
</aside>
