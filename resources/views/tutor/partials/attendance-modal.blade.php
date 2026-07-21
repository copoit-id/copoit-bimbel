<div id="tutor-attendance-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="tutor-attendance-title">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="tutor-attendance-title" class="text-lg font-bold text-gray-900">Absen Kehadiran Saya</h2>
                <p id="tutor-attendance-session" class="mt-1 text-sm text-gray-500"></p>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-700" onclick="closeTutorAttendanceModal()" aria-label="Tutup"><i class="ri-close-line text-xl"></i></button>
        </div>
        <form id="tutor-attendance-form" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
            @csrf
            <div>
                <label for="tutor-attendance-photo" class="mb-2 block text-sm font-medium text-gray-700">Foto kehadiran</label>
                <input id="tutor-attendance-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" capture="user" required class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-500">Ambil atau pilih foto (JPG, PNG, atau WebP; maks. 5 MB).</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50" onclick="closeTutorAttendanceModal()">Batal</button>
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Hadir</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openTutorAttendanceModal(action, sessionTitle) {
        document.getElementById('tutor-attendance-form').action = action;
        document.getElementById('tutor-attendance-session').textContent = sessionTitle;
        document.getElementById('tutor-attendance-modal').classList.remove('hidden');
        document.getElementById('tutor-attendance-modal').classList.add('flex');
    }

    function closeTutorAttendanceModal() {
        document.getElementById('tutor-attendance-modal').classList.add('hidden');
        document.getElementById('tutor-attendance-modal').classList.remove('flex');
    }
</script>
@endpush
