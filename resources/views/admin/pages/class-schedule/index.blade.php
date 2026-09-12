@extends('admin.layout.admin')

@section('title', 'Kelas & Jadwal')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 font-sans tracking-tight">Kelas & Jadwal</h1>
            <p class="text-sm text-gray-500">
                @if($filteredPackage)
                    Pilih kelas dan jadwal yang diberikan oleh paket <span class="font-semibold text-gray-700">{{ $filteredPackage->name }}</span>.
                @else
                    Kelola kelas sekali jalan, kelas rutin, absensi, dan request jadwal custom.
                @endif
            </p>
        </div>
        @if($activeTab === 'zoom')
            <x-btn title="Tambah Kelas Zoom" route="{{ route('admin.class.create') }}" icon="ri-add-fill"></x-btn>
        @else
            <x-btn title="Tambah Kelas & Jadwal" route="{{ route('admin.class-schedules.create', request()->only('package_id', 'range')) }}" icon="ri-add-fill"></x-btn>
        @endif
    </div>

    <form method="GET" action="{{ route('admin.class-schedules.index') }}" class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 sm:flex-row sm:items-end sm:justify-between">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <input type="hidden" name="range" value="{{ $scheduleRange }}">
        <label class="block w-full max-w-md">
            <span class="mb-2 block text-sm font-semibold text-gray-700">Atur untuk Paket</span>
            <select name="package_id" onchange="this.form.submit()"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                <option value="">Mode master — tanpa assignment paket</option>
                @foreach($packageOptions as $packageOption)
                    <option value="{{ $packageOption->package_id }}" @selected($filteredPackage?->package_id === $packageOption->package_id)>
                        {{ $packageOption->name }}
                    </option>
                @endforeach
            </select>
        </label>
        @if($filteredPackage)
            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                <span class="rounded-full border border-gray-200 px-3 py-1.5">
                    <strong id="selected-schedule-count">{{ $selectedScheduleIds->count() }}</strong> jadwal dipilih
                </span>
                <span class="rounded-full border border-gray-200 px-3 py-1.5">
                    <strong id="selected-class-count">{{ $selectedClassIds->count() }}</strong> kelas Zoom dipilih
                </span>
                <a href="{{ route('admin.class-schedules.index', ['tab' => $activeTab, 'range' => $scheduleRange]) }}" class="font-semibold text-primary hover:underline">
                    Keluar dari mode paket
                </a>
            </div>
        @else
            <p class="max-w-xl text-xs leading-5 text-gray-500">Pilih paket untuk menampilkan checklist assignment. Tanpa memilih paket, halaman ini tetap menjadi master seluruh kelas dan jadwal.</p>
        @endif
    </form>

    <x-tab :tabs="$scheduleTabs" variant="underline" class="overflow-x-auto" />

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
            <i class="ri-checkbox-circle-line text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('info'))
        <div class="flex items-center gap-2 rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-primary">
            <i class="ri-information-line text-lg"></i>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center gap-2">
            <i class="ri-error-warning-line text-lg"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($activeTab === 'schedules')
    <x-tab :tabs="$scheduleRangeTabs" variant="pills" class="overflow-x-auto" />

    @if($scheduleRange !== 'all')
        <section aria-labelledby="schedule-range-heading">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Agenda kelas</p>
                    <h2 id="schedule-range-heading" class="mt-1 text-xl font-bold tracking-tight text-gray-900">{{ $rangeLabel }}</h2>
                    <p class="mt-1 text-sm text-gray-500">Pertemuan tersusun kronologis agar mudah ditinjau dan ditindaklanjuti.</p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-600">{{ $scheduleSessions->total() }} sesi</span>
            </div>

            @if($scheduleSessionDays->isNotEmpty())
                <x-ui.card variant="flat" padding="none" class="overflow-visible rounded-2xl border border-gray-200 shadow-sm">
                    <div class="divide-y divide-gray-100">
                        @foreach($scheduleSessionDays as $day)
                            <section class="relative px-4 py-5 sm:px-6" aria-label="{{ $day['day_name'] }}, {{ $day['date_label'] }} — {{ $day['state_label'] }}">
                                @if(! $loop->last)
                                    <span class="absolute bottom-0 left-[2.35rem] top-[4.8rem] w-px bg-gray-100 sm:left-[3.35rem]"></span>
                                @endif
                                <div class="relative grid grid-cols-[3.25rem_minmax(0,1fr)] gap-3 sm:grid-cols-[4.5rem_minmax(0,1fr)] sm:gap-5">
                                    <div class="flex flex-col items-center">
                                        <div class="flex h-14 w-14 flex-col items-center justify-center rounded-2xl border text-center sm:h-16 sm:w-16 {{ $day['date_class'] }}">
                                            <span class="text-lg font-bold leading-none">{{ $day['day_number'] }}</span>
                                            <span class="mt-1 text-[10px] font-semibold uppercase tracking-wide">{{ \Illuminate\Support\Str::substr($day['day_name'], 0, 3) }}</span>
                                        </div>
                                        <span class="relative mt-3 h-2.5 w-2.5 rounded-full {{ $day['timeline_class'] }}"></span>
                                    </div>
                                    <div class="min-w-0 pb-1">
                                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                            <div><h3 class="text-sm font-bold text-gray-900">{{ $day['day_name'] }}</h3><p class="mt-0.5 text-xs text-gray-500">{{ $day['date_label'] }}</p></div>
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $day['state_class'] }}">{{ $day['state_label'] }}</span>
                                        </div>
                                        <div class="space-y-2.5">
                                            @foreach($day['sessions'] as $session)
                                                <article class="group rounded-xl border border-gray-200 bg-white p-3.5 transition duration-200 hover:border-primary/30 hover:shadow-md sm:flex sm:items-center sm:gap-5 sm:p-4">
                                                    <p class="shrink-0 text-sm font-bold tabular-nums text-gray-900 sm:w-24">{{ $session->agenda_time_label }}</p>
                                                    <div class="mt-3 min-w-0 flex-1 sm:mt-0">
                                                        <div class="flex flex-wrap items-start justify-between gap-2"><h4 class="text-sm font-bold leading-snug text-gray-900">{{ $session->agenda_title }}</h4><span class="rounded-full px-2 py-1 text-[10px] font-bold {{ $session->agenda_status_class }}">{{ $session->agenda_status_label }}</span></div>
                                                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-gray-500"><span class="inline-flex items-center gap-1.5"><i class="ri-group-line text-gray-400"></i>{{ $session->agenda_group_label }}</span><span class="inline-flex items-center gap-1.5"><i class="ri-user-star-line text-gray-400"></i>{{ $session->agenda_tutor_label }}</span>@if($session->agenda_location)<span class="inline-flex items-center gap-1.5"><i class="ri-map-pin-line text-gray-400"></i>{{ $session->agenda_location }}</span>@elseif($session->agenda_meeting_url)<a href="{{ $session->agenda_meeting_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 font-semibold text-primary hover:underline"><i class="ri-video-chat-line"></i>Online meeting</a>@endif</div>
                                                    </div>
                                                    <a href="{{ route('admin.class-schedules.show', ['classSchedule' => $session->class_schedule_id, 'session_id' => $session->id]) }}" class="mt-3 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-primary/30 hover:bg-primary/5 hover:text-primary sm:mt-0" title="Lihat sesi" aria-label="Lihat sesi"><i class="ri-arrow-right-line text-lg"></i></a>
                                                </article>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </section>
                        @endforeach
                    </div>
                </x-ui.card>
            @else
                <x-ui.card variant="flat" class="rounded-2xl border border-dashed border-gray-300 px-6 py-14 text-center"><i class="ri-calendar-event-line mb-3 block text-4xl text-gray-300"></i><p class="font-semibold text-gray-700">Tidak ada pertemuan pada periode ini.</p><p class="mt-1 text-sm text-gray-500">Pilih rentang lain atau tambahkan jadwal baru.</p></x-ui.card>
            @endif
            @if($scheduleSessions->hasPages())<div class="mt-4">{{ $scheduleSessions->links() }}</div>@endif
        </section>
    @else
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="flex flex-col gap-1 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-bold text-gray-900">Semua jadwal</h2>
                    <p class="mt-1 text-sm text-gray-500">Satu baris untuk satu jadwal agar mudah ditinjau dan diedit.</p>
                </div>
                <span class="w-fit rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ $allSchedules->total() }} jadwal</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[1480px] w-full table-fixed text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            @if($filteredPackage)<th scope="col" class="w-16 px-5 py-3 text-center">Pilih</th>@endif
                            <th scope="col" class="w-full min-w-[360px] px-5 py-3">Jadwal</th>
                            <th scope="col" class="w-52 px-5 py-3">Pengulangan</th>
                            <th scope="col" class="w-32 px-5 py-3">Waktu</th>
                            <th scope="col" class="w-40 px-5 py-3">Rombel</th>
                            <th scope="col" class="w-40 px-5 py-3">Tutor</th>
                            <th scope="col" class="w-48 px-5 py-3">Paket</th>
                            <th scope="col" class="w-28 px-5 py-3">Status</th>
                            <th scope="col" class="sticky right-0 z-20 w-44 border-l border-gray-200 bg-gray-50 px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($allSchedules as $schedule)
                            <tr data-assignment-item class="group transition-colors hover:bg-gray-50 {{ $filteredPackage && $schedule->list_is_selected ? 'bg-primary/5' : '' }}">
                                @if($filteredPackage)
                                    <td class="px-5 py-4 text-center">
                                        <input type="checkbox" aria-label="Masukkan {{ $schedule->title }} ke paket" class="package-assignment-checkbox h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary" data-kind="schedule" data-url="{{ route('admin.package.schedule.toggle', ['package' => $filteredPackage, 'classSchedule' => $schedule]) }}" @checked($schedule->list_is_selected)>
                                    </td>
                                @endif
                                <td class="w-full min-w-[360px] px-5 py-4"><p class="font-semibold text-gray-900">{{ $schedule->title }}</p>@if($schedule->location)<p class="mt-1 text-xs text-gray-500"><i class="ri-map-pin-line mr-1"></i>{{ $schedule->location }}</p>@elseif($schedule->meeting_url)<a href="{{ $schedule->meeting_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex items-center text-xs font-medium text-primary hover:underline"><i class="ri-video-chat-line mr-1"></i>Online meeting</a>@endif</td>
                                <td class="px-5 py-4 text-gray-700">{{ $schedule->list_recurrence_label }}</td>
                                <td class="px-5 py-4 whitespace-nowrap font-medium text-gray-700">{{ $schedule->list_time_label }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $schedule->studyGroup?->name ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $schedule->tentor?->name ?? 'Belum ditetapkan' }}</td>
                                <td class="max-w-xs px-5 py-4 text-gray-700"><p class="truncate" title="{{ $schedule->list_package_label }}">{{ $schedule->list_package_label }}</p></td>
                                <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $schedule->list_status_class }}">{{ $schedule->list_status_label }}</span></td>
                                <td class="sticky right-0 z-10 border-l border-gray-100 px-5 py-4 {{ $filteredPackage && $schedule->list_is_selected ? 'bg-primary/5' : 'bg-white group-hover:bg-gray-50' }}"><div class="flex items-center justify-end gap-2">@if($canUseAttendance)<a href="{{ route('admin.class-schedules.show', $schedule) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-primary/10 hover:text-primary" title="Lihat detail" aria-label="Lihat detail"><i class="ri-eye-line text-base"></i></a>@endif<a href="{{ route('admin.class-schedules.edit', $schedule) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-primary/10 hover:text-primary" title="Edit" aria-label="Edit"><i class="ri-edit-line text-base"></i></a><form method="POST" action="{{ route('admin.class-schedules.destroy', $schedule) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?');">@csrf @method('DELETE')<button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600" title="Hapus" aria-label="Hapus"><i class="ri-delete-bin-line text-base"></i></button></form></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $filteredPackage ? 9 : 8 }}" class="px-5 py-14 text-center text-gray-500"><i class="ri-calendar-event-line mb-2 block text-3xl text-gray-300"></i>Belum ada jadwal. Tambahkan jadwal pertama untuk mulai mengelola sesi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($allSchedules->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $allSchedules->links() }}</div>@endif
        </section>
    @endif
    @else
        <div class="package-bimbel bg-white p-8 rounded-lg border border-border">
            <x-page-desc title="Kelas Zoom" description="{{ $filteredPackage ? 'Centang kelas Zoom yang ingin dimasukkan ke paket ' . $filteredPackage->name . '.' : 'Data kelas live/Zoom lama. Pilih paket di atas untuk mengatur assignment.' }}"></x-page-desc>

            <div class="relative overflow-x-auto mt-4">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            @if($filteredPackage)
                                <th scope="col" class="w-16 px-6 py-3 text-center">Pilih</th>
                            @endif
                            <th scope="col" class="px-6 py-3">Tanggal & Waktu</th>
                            <th scope="col" class="px-6 py-3 text-center">Judul</th>
                            <th scope="col" class="px-6 py-3 text-center">Mentor</th>
                            <th scope="col" class="px-6 py-3 text-center">Status</th>
                            <th scope="col" class="px-6 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($liveClasses as $class)
                            @php($isClassSelected = $selectedClassIds->contains((int) $class->class_id))
                            <tr data-assignment-item class="border-b border-dashed text-grey3 {{ $filteredPackage && $isClassSelected ? 'border-primary bg-primary/5' : 'border-gray-200 bg-white' }}">
                                @if($filteredPackage)
                                    <td class="px-6 py-4 text-center">
                                        <input type="checkbox"
                                            aria-label="Masukkan {{ $class->title }} ke paket"
                                            class="package-assignment-checkbox h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                            data-kind="class"
                                            data-url="{{ route('admin.package.class.toggle', ['package_id' => $filteredPackage->package_id, 'class_id' => $class->class_id]) }}"
                                            @checked($isClassSelected)>
                                    </td>
                                @endif
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-semibold">
                                            {{ \Carbon\Carbon::parse($class->schedule_time)->translatedFormat('l, d F Y') }}
                                        </p>
                                        <p>Pukul {{ \Carbon\Carbon::parse($class->schedule_time)->format('H:i') }} WIB</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">{{ $class->title }}</td>
                                <td class="px-6 py-4 text-center">{{ $class->tentor?->name ?? $class->mentor ?? '-' }}</td>
                                <td class="px-6 py-4 text-center">
                                    @if($class->status == 'upcoming')
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">Akan Datang</span>
                                    @elseif($class->status == 'completed')
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Selesai</span>
                                    @else
                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Dibatalkan</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center items-center gap-2">
                                        <a href="{{ route('admin.class.assessments', $class->class_id) }}"
                                            class="text-gray-500 hover:text-primary" title="Kelola Pre/Post Test">
                                            <i class="ri-file-list-3-line text-xl"></i>
                                        </a>
                                        @if($class->zoom_link)
                                            <a href="{{ $class->zoom_link }}" target="_blank" class="text-gray-500 hover:text-primary" title="Buka Zoom">
                                                <i class="ri-video-on-line text-xl"></i>
                                            </a>
                                        @endif
                                        @if($class->drive_link)
                                            <a href="{{ $class->drive_link }}" target="_blank" class="text-gray-500 hover:text-blue-600" title="Buka Materi">
                                                <i class="ri-folder-line text-xl"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.class.edit', array_merge(request()->query(), ['class' => $class->class_id, 'tab' => 'zoom'])) }}"
                                            class="text-gray-500 hover:text-yellow-500" title="Edit">
                                            <i class="ri-edit-line text-xl"></i>
                                        </a>
                                        <form action="{{ route('admin.class.destroy', $class->class_id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-gray-500 hover:text-red-500"
                                                title="Hapus" onclick="return confirm('Yakin ingin menghapus kelas ini?')">
                                                <i class="ri-delete-bin-line text-xl"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $filteredPackage ? 6 : 5 }}" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i class="ri-video-chat-line text-4xl text-gray-300 mb-2"></i>
                                        <p>Belum ada kelas Zoom tersedia</p>
                                        <a href="{{ route('admin.class.create') }}" class="text-primary hover:underline mt-2">
                                            Buat kelas Zoom baru
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($liveClasses->hasPages())
                <div class="flex justify-center mt-4">
                    {{ $liveClasses->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection

@section('scripts')
@if($filteredPackage)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.package-assignment-checkbox');
    const totals = {
        schedule: Number(document.getElementById('selected-schedule-count')?.textContent || 0),
        class: Number(document.getElementById('selected-class-count')?.textContent || 0)
    };

    checkboxes.forEach(function (checkbox) {
        checkbox.defaultChecked = checkbox.checked;
        checkbox.addEventListener('change', async function () {
            const previousState = checkbox.defaultChecked;
            const requestedState = checkbox.checked;
            checkbox.disabled = true;

            try {
                const response = await fetch(checkbox.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ selected: requestedState })
                });
                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    const validationMessage = payload.errors
                        ? Object.values(payload.errors).flat()[0]
                        : null;
                    throw new Error(validationMessage || payload.message || 'Perubahan gagal disimpan.');
                }

                const savedState = Boolean(payload.selected);
                checkbox.checked = savedState;
                checkbox.defaultChecked = savedState;

                if (savedState !== previousState) {
                    totals[checkbox.dataset.kind] = Math.max(
                        0,
                        totals[checkbox.dataset.kind] + (savedState ? 1 : -1)
                    );
                    updateCount(checkbox.dataset.kind);
                }

                updateItemState(checkbox, savedState);
                showAssignmentNotice(payload.message, 'success');
            } catch (error) {
                checkbox.checked = previousState;
                updateItemState(checkbox, previousState);
                showAssignmentNotice(error.message || 'Perubahan gagal disimpan.', 'error');
            } finally {
                checkbox.disabled = false;
            }
        });
    });

    function updateCount(kind) {
        const targetId = kind === 'schedule' ? 'selected-schedule-count' : 'selected-class-count';
        const target = document.getElementById(targetId);
        if (target) {
            target.textContent = totals[kind];
        }
    }

    function updateItemState(checkbox, isSelected) {
        const item = checkbox.closest('[data-assignment-item]');
        if (!item) {
            return;
        }

        item.classList.toggle('border-primary', isSelected);
        item.classList.toggle('bg-primary/5', isSelected);
        item.classList.toggle('border-gray-200', !isSelected);
    }

    function showAssignmentNotice(message, type) {
        const existing = document.getElementById('package-assignment-notice');
        if (existing) {
            existing.remove();
        }

        const notice = document.createElement('div');
        notice.id = 'package-assignment-notice';
        notice.className = 'fixed bottom-4 right-4 z-50 max-w-sm rounded-lg border px-4 py-3 text-sm ' +
            (type === 'success'
                ? 'border-green-200 bg-green-50 text-green-700'
                : 'border-red-200 bg-red-50 text-red-700');
        notice.textContent = message;
        document.body.appendChild(notice);

        window.setTimeout(function () {
            notice.remove();
        }, 3500);
    }
});
</script>
@endif
@endsection
