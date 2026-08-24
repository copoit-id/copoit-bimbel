@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Edit Kelas</h2>
            <p class="text-gray-500">Edit jadwal kelas live/Zoom</p>
        </div>
        <a href="{{ route('admin.class-schedules.index', ['tab' => 'zoom']) }}"
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

    <!-- Edit Form -->
    <div class="bg-white rounded-lg border border-gray-200">
        <form action="{{ route('admin.class.update', array_merge(request()->query(), ['class' => $class->class_id])) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="p-6 space-y-6">

                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Judul Kelas <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="title" name="title" value="{{ old('title', $class->title) }}" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Contoh: Pengenalan Tes Wawasan Kebangsaan">
                    </div>

                    <div>
                        <label for="schedule_time" class="block text-sm font-medium text-gray-700 mb-2">Jadwal Kelas
                            <span class="text-red-500">*</span></label>
                        <input type="datetime-local" id="schedule_time" name="schedule_time"
                            value="{{ old('schedule_time', $class->schedule_time->format('Y-m-d\TH:i')) }}" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>

                <!-- Tutor -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="tentor_id" class="block text-sm font-medium text-gray-700 mb-2">Tentor</label>
                        <select id="tentor_id" name="tentor_id"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Pilih Tutor</option>
                            @foreach($tentors as $tentor)
                                <option value="{{ $tentor->id }}" @selected(old('tentor_id', $class->tentor_id) == $tentor->id)>
                                    {{ $tentor->name }}{{ $tentor->expertise ? ' - ' . $tentor->expertise : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="mentor" class="block text-sm font-medium text-gray-700 mb-2">Mentor Manual</label>
                        <input type="text" id="mentor" name="mentor" value="{{ old('mentor', $class->tentor_id ? null : $class->mentor) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Diisi jika belum ada di master Tutor">
                    </div>
                </div>

                <!-- Links -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="zoom_link" class="block text-sm font-medium text-gray-700 mb-2">Link Zoom</label>
                        <input type="url" id="zoom_link" name="zoom_link"
                            value="{{ old('zoom_link', $class->zoom_link) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="https://zoom.us/j/123456789">
                    </div>

                    <div>
                        <label for="drive_link" class="block text-sm font-medium text-gray-700 mb-2">Link
                            Drive/Materi</label>
                        <input type="url" id="drive_link" name="drive_link"
                            value="{{ old('drive_link', $class->drive_link) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="https://drive.google.com/...">
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status Kelas</label>
                    <select id="status" name="status"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="upcoming" {{ old('status', $class->status)=='upcoming' ? 'selected' : '' }}>Akan
                            Datang</option>
                        <option value="completed" {{ old('status', $class->status)=='completed' ? 'selected' : ''
                            }}>Selesai</option>
                        <option value="cancelled" {{ old('status', $class->status)=='cancelled' ? 'selected' : ''
                            }}>Dibatalkan</option>
                    </select>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <h3 class="mb-4 font-semibold text-gray-900">Pre & Post Test</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="pre_test_tryout_id" class="block text-sm font-medium text-gray-700 mb-2">Pre Test</label>
                            <select id="pre_test_tryout_id" name="pre_test_tryout_id"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Tidak pakai pre test</option>
                                @foreach($preOptions as $option)
                                <option value="{{ $option->tryout_id }}" @selected((int) old('pre_test_tryout_id', $preAssignment?->tryout_id) === (int) $option->tryout_id)>
                                    {{ $option->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="post_test_tryout_id" class="block text-sm font-medium text-gray-700 mb-2">Post Test</label>
                            <select id="post_test_tryout_id" name="post_test_tryout_id"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Tidak pakai post test</option>
                                @foreach($postOptions as $option)
                                <option value="{{ $option->tryout_id }}" @selected((int) old('post_test_tryout_id', $postAssignment?->tryout_id) === (int) $option->tryout_id)>
                                    {{ $option->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <h3 class="mb-4 font-semibold text-gray-900">Akses Mandiri</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_displayed" value="1" class="rounded border-gray-300 text-primary focus:ring-primary" {{ old('is_displayed', $class->is_displayed ?? true) ? 'checked' : '' }}>
                            Tampilkan kelas
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_for_sale" value="1" class="rounded border-gray-300 text-primary focus:ring-primary" {{ old('is_for_sale', $class->is_for_sale ?? false) ? 'checked' : '' }}>
                            Bisa dibeli/diakses mandiri
                        </label>
                        <div>
                            <label for="type_price" class="block text-sm font-medium text-gray-700 mb-2">Tipe Harga</label>
                            <select id="type_price" name="type_price" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="paid" {{ old('type_price', $class->type_price ?? 'paid') === 'paid' ? 'selected' : '' }}>Berbayar</option>
                                <option value="free_unconditional" {{ old('type_price', $class->type_price ?? 'paid') === 'free_unconditional' ? 'selected' : '' }}>Gratis</option>
                                <option value="free_conditional" {{ old('type_price', $class->type_price ?? 'paid') === 'free_conditional' ? 'selected' : '' }}>Gratis Bersyarat</option>
                            </select>
                        </div>
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Harga</label>
                            <input type="number" id="price" name="price" value="{{ old('price', $class->price ?? 0) }}" min="0"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label for="access_duration_unit" class="block text-sm font-medium text-gray-700 mb-2">Durasi Akses</label>
                            <select id="access_duration_unit" name="access_duration_unit" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                @php($durationUnit = old('access_duration_unit', $class->access_duration_unit ?? 'forever'))
                                <option value="forever" {{ $durationUnit === 'forever' ? 'selected' : '' }}>Selamanya</option>
                                <option value="day" {{ $durationUnit === 'day' ? 'selected' : '' }}>Hari</option>
                                <option value="week" {{ $durationUnit === 'week' ? 'selected' : '' }}>Minggu</option>
                                <option value="month" {{ $durationUnit === 'month' ? 'selected' : '' }}>Bulan</option>
                                <option value="year" {{ $durationUnit === 'year' ? 'selected' : '' }}>Tahun</option>
                            </select>
                        </div>
                        <div>
                            <label for="access_duration_value" class="block text-sm font-medium text-gray-700 mb-2">Jumlah Durasi</label>
                            <input type="number" id="access_duration_value" name="access_duration_value" value="{{ old('access_duration_value', $class->access_duration_value) }}" min="1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="conditional_requirement" class="block text-sm font-medium text-gray-700 mb-2">Syarat Akses Gratis Bersyarat</label>
                        <textarea id="conditional_requirement" name="conditional_requirement" rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('conditional_requirement', $class->conditional_requirement) }}</textarea>
                    </div>
                </div>

            </div>

            <div class="flex items-center justify-end px-6 py-5 space-x-2 border-t border-gray-200">
                <a href="{{ route('admin.class-schedules.index', ['tab' => 'zoom']) }}"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                    Batal
                </a>
                <button type="submit"
                    class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    Update Kelas
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
