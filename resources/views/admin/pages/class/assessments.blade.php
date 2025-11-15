@extends('admin.layout.admin')
@section('title', 'Pengaturan Pre/Post Test')
@section('content')
<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.class.index') }}" title="Manajemen Kelas" />
            <x-breadcrumb-item href="" title="Pre & Post Test" />
        </x-slot>
    </x-breadcrumb>
    <x-btn title="Kembali" route="{{ route('admin.class.index') }}" icon="ri-arrow-left-line"></x-btn>
</div>

<div class="bg-white border border-border rounded-lg p-6 space-y-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">{{ $class->title }}</h2>
            <p class="text-sm text-gray-500">Atur tryout yang akan digunakan sebagai pre test maupun post test untuk kelas ini.</p>
        </div>
        @if($class->schedule_time)
        <div class="text-sm text-gray-600">
            <span class="font-medium">Jadwal:</span>
            {{ \Carbon\Carbon::parse($class->schedule_time)->translatedFormat('l, d F Y \pukul H:i') }} WIB
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="border border-gray-200 rounded-lg p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Pre Test</h3>
                @if($preAssignment)
                <form action="{{ route('admin.class.assessments.destroy', [$class->class_id, 'pre_test']) }}" method="POST"
                    onsubmit="return confirm('Hapus pre test dari kelas ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-red hover:underline">Hapus</button>
                </form>
                @endif
            </div>

            @if($preAssignment)
            <div class="bg-primary/5 border border-primary/40 rounded-lg p-4 mb-4">
                <p class="text-sm text-gray-700">Tryout saat ini:</p>
                <p class="text-base font-semibold text-primary">{{ $preAssignment->name }}</p>
                <p class="text-xs text-gray-500 mt-1">Periode: {{ optional($preAssignment->start_date)->translatedFormat('d M Y H:i') }} - {{ optional($preAssignment->end_date)->translatedFormat('d M Y H:i') }}</p>
            </div>
            @endif

            <form action="{{ route('admin.class.assessments.store', $class->class_id) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="assessment_type" value="pre_test">
                <div>
                    <label for="pre_tryout_id" class="block text-sm font-medium text-gray-700 mb-2">Pilih Tryout Pre Test</label>
                    <select id="pre_tryout_id" name="tryout_id"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" required>
                        <option value="">-- Pilih Tryout Pre Test --</option>
                        @foreach($preOptions as $option)
                        <option value="{{ $option->tryout_id }}" {{ $preAssignment && $preAssignment->tryout_id === $option->tryout_id ? 'selected' : '' }}>
                            {{ $option->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                    Simpan Pre Test
                </button>
            </form>
        </div>

        <div class="border border-gray-200 rounded-lg p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Post Test</h3>
                @if($postAssignment)
                <form action="{{ route('admin.class.assessments.destroy', [$class->class_id, 'post_test']) }}" method="POST"
                    onsubmit="return confirm('Hapus post test dari kelas ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-red hover:underline">Hapus</button>
                </form>
                @endif
            </div>

            @if($postAssignment)
            <div class="bg-green/5 border border-green/40 rounded-lg p-4 mb-4">
                <p class="text-sm text-gray-700">Tryout saat ini:</p>
                <p class="text-base font-semibold text-green">{{ $postAssignment->name }}</p>
                <p class="text-xs text-gray-500 mt-1">Periode: {{ optional($postAssignment->start_date)->translatedFormat('d M Y H:i') }} - {{ optional($postAssignment->end_date)->translatedFormat('d M Y H:i') }}</p>
            </div>
            @endif

            <form action="{{ route('admin.class.assessments.store', $class->class_id) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="assessment_type" value="post_test">
                <div>
                    <label for="post_tryout_id" class="block text-sm font-medium text-gray-700 mb-2">Pilih Tryout Post Test</label>
                    <select id="post_tryout_id" name="tryout_id"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" required>
                        <option value="">-- Pilih Tryout Post Test --</option>
                        @foreach($postOptions as $option)
                        <option value="{{ $option->tryout_id }}" {{ $postAssignment && $postAssignment->tryout_id === $option->tryout_id ? 'selected' : '' }}>
                            {{ $option->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                    Simpan Post Test
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
