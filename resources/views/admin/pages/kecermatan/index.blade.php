@extends('admin.layout.admin')

@section('title', 'Daftar Kolom Kecermatan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Kolom Kecermatan</h2>
            <p class="text-gray-500">Tryout: <strong>{{ $tryout->name }}</strong></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.kecermatan.create', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id]) }}"
                class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 flex items-center gap-2">
                <i class="ri-add-line"></i>
                Tambah Kolom
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
    @endif

    <!-- List Kolom -->
    @if($tryout->kecermatanColumns && $tryout->kecermatanColumns->count() > 0)
        <div class="grid grid-cols-1 gap-6">
            @foreach($tryout->kecermatanColumns as $index => $column)
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <!-- Header -->
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-bold">
                            {{ $index + 1 }}
                        </span>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $column->nama_kolom }}</h3>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.kecermatan.preview', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id, 'column_id' => $column->column_id]) }}"
                            class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-200 flex items-center gap-1 text-sm">
                            <i class="ri-eye-line"></i> Preview Soal
                        </a>
                        <a href="{{ route('admin.kecermatan.edit', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id, 'column_id' => $column->column_id]) }}"
                            class="bg-primary/10 text-primary px-3 py-2 rounded-lg hover:bg-primary/20 flex items-center gap-1 text-sm">
                            <i class="ri-pencil-line"></i> Edit
                        </a>
                        <form action="{{ route('admin.kecermatan.regenerate', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id, 'column_id' => $column->column_id]) }}" 
                            method="POST" class="inline"
                            onsubmit="return confirm('Yakin ingin generate ulang semua soal? Soal yang ada akan diganti dengan yang baru.');">
                            @csrf
                            <button type="submit" class="bg-yellow-100 text-yellow-700 px-3 py-2 rounded-lg hover:bg-yellow-200 flex items-center gap-1 text-sm">
                                <i class="ri-refresh-line"></i> Regenerate Soal
                            </button>
                        </form>
                        <form action="{{ route('admin.kecermatan.destroy', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id, 'column_id' => $column->column_id]) }}" 
                            method="POST" class="inline" 
                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus kolom ini? Semua soal terkait akan ikut terhapus.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-100 text-red-700 px-3 py-2 rounded-lg hover:bg-red-200 flex items-center gap-1 text-sm">
                                <i class="ri-delete-bin-line"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Info -->
                    <div class="flex flex-wrap gap-3 mb-6">
                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ $column->jumlah_soal }} Soal per Baris
                        </span>
                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                            {{ $column->durasi_kolom }} Menit
                        </span>
                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-purple-100 text-purple-800 capitalize">
                            Tipe: {{ $column->tipe_kolom }}
                        </span>
                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-orange-100 text-orange-800">
                            {{ $column->rows->count() }} Baris
                        </span>
                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                            Total: {{ $column->jumlah_soal * $column->rows->count() }} Soal
                        </span>
                    </div>

                    <!-- Preview Kolom -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <p class="text-sm text-gray-600 mb-3">Isi Kolom:</p>
                        <div class="flex gap-4 justify-center flex-wrap">
                            @foreach($column->kolom_data as $item)
                                <div class="w-14 h-14 bg-white border-2 border-primary rounded-lg flex items-center justify-center text-2xl font-bold text-primary shadow-sm">
                                    {{ $item }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Baris Preview -->
                    <div class="space-y-2">
                        <p class="text-sm text-gray-600 font-medium">Baris Soal:</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                            @foreach($column->rows as $row)
                                <div class="bg-gray-50 px-4 py-3 rounded-lg text-sm">
                                    <span class="font-medium text-primary">Baris {{ $row->row_number }}:</span> 
                                    <span class="text-gray-700">{!! Str::limit(strip_tags($row->row_text), 50) !!}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-12 text-center">
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-questionnaire-line text-4xl text-blue-500"></i>
            </div>
            <h3 class="text-xl font-semibold text-blue-900 mb-2">Belum Ada Kolom Kecermatan</h3>
            <p class="text-blue-700 mb-6 max-w-md mx-auto">Tambahkan kolom kecermatan untuk mulai membuat soal. Setiap kolom akan digenerate soal otomatis berdasarkan tipe yang dipilih.</p>
            <a href="{{ route('admin.kecermatan.create', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id]) }}"
                class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary/90">
                <i class="ri-add-line"></i> Tambah Kolom Kecermatan
            </a>
        </div>
    @endif
</div>
@endsection
