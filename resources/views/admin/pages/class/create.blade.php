@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Tambah Kelas</h2>
            <p class="text-gray-500">Buat jadwal kelas baru</p>
        </div>
        <a href="{{ route('admin.class.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Create Form -->
    <div class="bg-white rounded-lg border border-gray-200">
        <form action="{{ route('admin.class.store') }}" method="POST">
            @csrf
            <div class="p-6 space-y-6">

                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Judul Kelas <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="title" name="title" value="{{ old('title') }}" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Contoh: Pengenalan Tes Wawasan Kebangsaan">
                    </div>

                    <div>
                        <label for="schedule_time" class="block text-sm font-medium text-gray-700 mb-2">Jadwal Kelas
                            <span class="text-red-500">*</span></label>
                        <input type="datetime-local" id="schedule_time" name="schedule_time"
                            value="{{ old('schedule_time') }}" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>

                <!-- Mentor -->
                <div>
                    <label for="mentor" class="block text-sm font-medium text-gray-700 mb-2">Mentor/Instruktur</label>
                    <input type="text" id="mentor" name="mentor" value="{{ old('mentor') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Nama mentor atau instruktur">
                </div>

                <!-- Links -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="zoom_link" class="block text-sm font-medium text-gray-700 mb-2">Link Zoom</label>
                        <input type="url" id="zoom_link" name="zoom_link" value="{{ old('zoom_link') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="https://zoom.us/j/123456789">
                    </div>

                    <div>
                        <label for="drive_link" class="block text-sm font-medium text-gray-700 mb-2">Link
                            Drive/Materi</label>
                        <input type="url" id="drive_link" name="drive_link" value="{{ old('drive_link') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="https://drive.google.com/...">
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status Kelas</label>
                    <select id="status" name="status"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="upcoming" {{ old('status', 'upcoming' )=='upcoming' ? 'selected' : '' }}>Akan
                            Datang</option>
                        <option value="completed" {{ old('status')=='completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="cancelled" {{ old('status')=='cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>

            </div>

            <div class="flex items-center justify-end px-6 py-5 space-x-2 border-t border-gray-200">
                <a href="{{ route('admin.class.index') }}"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                    Batal
                </a>
                <button type="submit"
                    class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    Simpan Kelas
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set default schedule time to current date + 1 hour
        const scheduleInput = document.getElementById('schedule_time');
        if (!scheduleInput.value) {
            const now = new Date();
            now.setHours(now.getHours() + 1);
            scheduleInput.value = now.toISOString().slice(0, 16);
        }

        console.log('Create class form scripts loaded');
    });
</script>
@endsection