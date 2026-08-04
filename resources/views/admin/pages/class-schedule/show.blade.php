@extends('admin.layout.admin')

@section('title', 'Absensi Jadwal')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $classSchedule->title }}</h1>
            <p class="text-sm text-gray-500">
                {{ $classSchedule->schedule_type === 'recurring' ? 'Jadwal Rutin Mingguan' : 'Jadwal Sekali Jalan' }}
                @if($classSchedule->start_time)
                    • {{ substr($classSchedule->start_time, 0, 5) }}{{ $classSchedule->end_time ? ' - ' . substr($classSchedule->end_time, 0, 5) : '' }}
                @endif
            </p>
            @if($classSchedule->studyGroup)
                <p class="mt-1 text-xs text-gray-500">Rombel: {{ $classSchedule->studyGroup->name }}</p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.class-schedules.edit', $classSchedule) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"><i class="ri-edit-line mr-1"></i>Edit Jadwal</a>
            <form method="POST" action="{{ route('admin.class-schedules.generate', $classSchedule) }}">
                @csrf
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Generate Sesi</button>
            </form>
        </div>
    </div>

    <div class="border border-gray-200 bg-white p-4">
        <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-end">
            <form method="GET" action="{{ route('admin.class-schedules.show', $classSchedule) }}">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <label class="mb-2 block text-sm font-semibold text-gray-700">Pilih Sesi</label>
                <select name="session_id" onchange="this.form.submit()" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @forelse($sessionOptions as $option)
                        <option value="{{ $option->id }}" @selected($selectedSession?->id === $option->id)>{{ $option->session_date->translatedFormat('D, d M Y') }} • {{ $option->start_at->format('H:i') }}</option>
                    @empty
                        <option value="">Belum ada sesi kelas</option>
                    @endforelse
                </select>
            </form>

            <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-gray-600">
                <p class="font-semibold text-gray-900">{{ $participants->count() }} peserta</p>
                <p>{{ $selectedSession ? $selectedSession->session_date->translatedFormat('d M Y') . ' · ' . $selectedSession->start_at->format('H:i') : 'Pilih sesi dulu' }}</p>
            </div>
        </div>
    </div>

    @if($selectedSession)
        @php
            $statusCounts = $attendances->countBy('status');
            $statusBadges = [
                'present' => ['label' => 'Hadir', 'class' => 'bg-green-100 text-green-700'],
                'late' => ['label' => 'Terlambat', 'class' => 'bg-amber-100 text-amber-700'],
                'excused' => ['label' => 'Izin', 'class' => 'bg-blue-100 text-blue-700'],
                'absent' => ['label' => 'Tidak Hadir', 'class' => 'bg-red-100 text-red-700'],
            ];
            $tutorAttendance = $selectedSession->tutorAttendance;
            $approvalStatus = $tutorAttendance?->approval_status ?? 'pending';
        @endphp

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Sesi Dipilih</p>
                <p class="mt-1 text-sm font-bold text-gray-900">{{ $selectedSession->session_date->translatedFormat('l, d M Y') }}</p>
                <p class="text-xs text-gray-500">{{ $selectedSession->start_at->format('H:i') }}{{ $selectedSession->end_at ? ' - ' . $selectedSession->end_at->format('H:i') : '' }}</p>
            </div>
            @foreach($statusBadges as $status => $meta)
                <div class="border border-gray-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase text-gray-500">{{ $meta['label'] }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $statusCounts->get($status, 0) }}</p>
                </div>
            @endforeach
        </div>

        <nav class="flex overflow-x-auto border-b border-gray-200" aria-label="Detail absensi">
            <a href="{{ route('admin.class-schedules.show', [$classSchedule, 'session_id' => $selectedSession->id, 'tab' => 'tutor']) }}" class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold {{ $activeTab === 'tutor' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"><i class="ri-user-star-line mr-1"></i>Tutor</a>
            <a href="{{ route('admin.class-schedules.show', [$classSchedule, 'session_id' => $selectedSession->id, 'tab' => 'participants']) }}" class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold {{ $activeTab === 'participants' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"><i class="ri-team-line mr-1"></i>Peserta <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $participants->count() }}</span></a>
        </nav>

        @if($activeTab === 'tutor')
            @if($selectedSession->tentor)
                <section class="overflow-hidden border border-gray-200 bg-white">
                    <div class="flex flex-col gap-5 p-5 md:flex-row md:items-center md:justify-between">
                        <div class="flex min-w-0 items-center gap-4">
                            @if($tutorAttendance?->photo_path)
                                <button type="button" onclick="openTutorProofModal(@js(Storage::url($tutorAttendance->photo_path)))" class="shrink-0 overflow-hidden rounded-lg border border-gray-200" title="Lihat bukti foto">
                                    <img src="{{ Storage::url($tutorAttendance->photo_path) }}" alt="Bukti absensi {{ $selectedSession->tentor->name }}" class="h-20 w-20 object-cover">
                                </button>
                            @else
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400"><i class="ri-camera-off-line text-2xl"></i></div>
                            @endif
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-primary">Absensi tutor</p>
                                <h2 class="mt-1 text-lg font-bold text-gray-900">{{ $selectedSession->tentor->name }}</h2>
                                <p class="mt-1 text-sm text-gray-500">{{ $tutorAttendance?->check_in_at?->format('d M Y H:i') ?? 'Tutor belum mengirim absensi.' }}</p>
                                @if($tutorAttendance)
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($tutorAttendance->status) }}</span>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $approvalStatus === 'approved' ? 'bg-green-100 text-green-700' : ($approvalStatus === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ $approvalStatus === 'approved' ? 'Disetujui admin' : ($approvalStatus === 'rejected' ? 'Ditolak admin' : 'Menunggu persetujuan') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if($tutorAttendance?->photo_path)
                                <button type="button" onclick="openTutorProofModal(@js(Storage::url($tutorAttendance->photo_path)))" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"><i class="ri-image-line mr-1"></i>Lihat Foto</button>
                            @endif
                            <button type="button" onclick="openTutorVerificationModal(@js($tutorAttendance?->status), @js($tutorAttendance?->notes))" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $approvalStatus === 'approved' ? 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' : 'bg-primary text-white hover:bg-primary/90' }}">
                                <i class="{{ $approvalStatus === 'approved' ? 'ri-edit-line' : 'ri-checkbox-circle-line' }} mr-1"></i>{{ $approvalStatus === 'approved' ? 'Edit Verifikasi' : 'Verifikasi Absensi' }}
                            </button>
                        </div>
                    </div>
                </section>
            @else
                <div class="border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">Tutor belum ditetapkan untuk sesi ini.</div>
            @endif
        @else
            <section class="overflow-hidden border border-gray-200 bg-white">
                <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                    <h2 class="font-bold text-gray-900">Absensi Peserta</h2>
                    <p class="mt-1 text-sm text-gray-500">Klik tombol aksi untuk mencatat atau mengubah absensi peserta.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700"><tr><th class="px-5 py-3">Peserta</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Waktu</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($participants as $participant)
                                @php
                                    $attendance = $attendances->get($participant->id);
                                    $badge = $statusBadges[$attendance?->status ?? ''] ?? ['label' => 'Belum absen', 'class' => 'bg-gray-100 text-gray-600'];
                                @endphp
                                <tr class="border-t border-gray-100 hover:bg-gray-50">
                                    <td class="px-5 py-4"><p class="font-semibold text-gray-900">{{ $participant->name }}</p><p class="text-xs text-gray-500">{{ $participant->email }}</p></td>
                                    <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span></td>
                                    <td class="px-5 py-4">{{ $attendance?->check_in_at?->format('d M Y H:i') ?? '-' }}</td>
                                    <td class="px-5 py-4 text-right"><button type="button" onclick="openParticipantAttendanceModal(@js($participant->id), @js($participant->name), @js($attendance?->status), @js($attendance?->notes))" class="inline-flex items-center gap-1 rounded-lg border border-primary px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary hover:text-white"><i class="{{ $attendance ? 'ri-edit-line' : 'ri-checkbox-circle-line' }}"></i>{{ $attendance ? 'Edit' : 'Absen' }}</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-10 text-center text-gray-500">Belum ada peserta untuk rombel ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <div id="tutor-verification-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="tutor-verification-title">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between gap-4"><div><h2 id="tutor-verification-title" class="text-lg font-bold text-gray-900">Verifikasi Absensi Tutor</h2><p class="mt-1 text-sm text-gray-500">{{ $selectedSession->tentor?->name }}</p></div><button type="button" onclick="closeModal('tutor-verification-modal')" class="text-gray-400 hover:text-gray-700" aria-label="Tutup"><i class="ri-close-line text-xl"></i></button></div>
                <form method="POST" action="{{ route('admin.class-attendance.tutor.mark', $selectedSession) }}" class="mt-5 space-y-4">
                    @csrf
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Status kehadiran</label><select id="tutor-attendance-status" name="status" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">@foreach(['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Tidak Hadir', 'excused' => 'Izin'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Catatan</label><textarea id="tutor-attendance-notes" name="notes" rows="3" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="Opsional"></textarea></div>
                    <div class="flex justify-end gap-3"><button type="button" onclick="closeModal('tutor-verification-modal')" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">Batal</button><button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Setujui & Hitung Gaji</button></div>
                </form>
            </div>
        </div>

        <div id="participant-attendance-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="participant-attendance-title">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between gap-4"><div><h2 id="participant-attendance-title" class="text-lg font-bold text-gray-900">Absensi Peserta</h2><p id="participant-attendance-name" class="mt-1 text-sm text-gray-500"></p></div><button type="button" onclick="closeModal('participant-attendance-modal')" class="text-gray-400 hover:text-gray-700" aria-label="Tutup"><i class="ri-close-line text-xl"></i></button></div>
                <form method="POST" action="{{ route('admin.class-attendance.mark', $selectedSession) }}" class="mt-5 space-y-4">
                    @csrf
                    <input id="participant-attendance-user-id" type="hidden" name="user_id">
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Status kehadiran</label><select id="participant-attendance-status" name="status" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">@foreach(['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Tidak Hadir', 'excused' => 'Izin'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Catatan</label><textarea id="participant-attendance-notes" name="notes" rows="3" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="Opsional"></textarea></div>
                    <div class="flex justify-end gap-3"><button type="button" onclick="closeModal('participant-attendance-modal')" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">Batal</button><button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan Absensi</button></div>
                </form>
            </div>
        </div>

        <div id="tutor-proof-modal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/70 p-4" role="dialog" aria-modal="true" aria-labelledby="tutor-proof-title">
            <div class="relative w-full max-w-2xl rounded-xl bg-white p-4 shadow-xl"><div class="mb-3 flex items-center justify-between"><h2 id="tutor-proof-title" class="font-bold text-gray-900">Bukti Foto Absensi Tutor</h2><button type="button" onclick="closeModal('tutor-proof-modal')" class="text-gray-400 hover:text-gray-700" aria-label="Tutup"><i class="ri-close-line text-xl"></i></button></div><img id="tutor-proof-image" src="" alt="Bukti absensi tutor" class="max-h-[70vh] w-full rounded-lg object-contain"></div>
        </div>
    @else
        <div class="border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500">Belum ada sesi kelas untuk jadwal ini. Klik <span class="font-semibold text-gray-700">Generate Sesi</span> untuk membuat sesi berdasarkan jadwal.</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.getElementById(id).classList.add('flex');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.getElementById(id).classList.remove('flex');
    }

    function openTutorVerificationModal(status, notes) {
        document.getElementById('tutor-attendance-status').value = status || 'present';
        document.getElementById('tutor-attendance-notes').value = notes || '';
        openModal('tutor-verification-modal');
    }

    function openParticipantAttendanceModal(userId, name, status, notes) {
        document.getElementById('participant-attendance-user-id').value = userId;
        document.getElementById('participant-attendance-name').textContent = name;
        document.getElementById('participant-attendance-status').value = status || 'present';
        document.getElementById('participant-attendance-notes').value = notes || '';
        openModal('participant-attendance-modal');
    }

    function openTutorProofModal(url) {
        document.getElementById('tutor-proof-image').src = url;
        openModal('tutor-proof-modal');
    }
</script>
@endpush
