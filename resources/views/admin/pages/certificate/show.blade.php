@extends('admin.layout.admin')
@section('title', 'Detail Sertifikat')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.certificate.index') }}" title="Manajemen Sertifikat" />
            <x-breadcrumb-item href="" title="Detail Sertifikat" />
        </x-slot>
    </x-breadcrumb>
</div>

<x-page-desc title="Detail Sertifikat - {{ $certificate->certificate_name }}">
    <x-slot name="description">
        Nomor: {{ $certificate->certificate_number }} • Status: {{ ucfirst($certificate->status) }}
    </x-slot>
</x-page-desc>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mt-4">
    <p>{{ session('success') }}</p>
</div>
@endif

@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mt-4">
    <p>{{ session('error') }}</p>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
    <!-- Main Certificate Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Basic Information -->
        <div class="bg-white p-6 rounded-lg border border-border">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Sertifikat</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Nama Sertifikat</label>
                    <p class="text-gray-900 font-medium">{{ $certificate->certificate_name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Nomor Sertifikat</label>
                    <p class="text-gray-900 font-mono">{{ $certificate->certificate_number }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Institusi</label>
                    <p class="text-gray-900">{{ $certificate->institution_name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                    <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full
                        @if($certificate->status === 'active') bg-green-100 text-green-800
                        @elseif($certificate->status === 'revoked') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800 @endif">
                        {{ ucfirst($certificate->status) }}
                    </span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Tanggal Terbit</label>
                    <p class="text-gray-900">{{ \Carbon\Carbon::parse($certificate->issued_date)->format('d F Y') }}</p>
                </div>

                @if($certificate->expired_date)
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Tanggal Kadaluarsa</label>
                    <p class="text-gray-900">{{ \Carbon\Carbon::parse($certificate->expired_date)->format('d F Y') }}
                    </p>
                </div>
                @endif
            </div>

            @if($certificate->description)
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-500 mb-1">Deskripsi</label>
                <p class="text-gray-900">{{ $certificate->description }}</p>
            </div>
            @endif
        </div>

        <!-- Recipient Information -->
        @if($certificate->metadata)
        @php
        $metadata = is_array($certificate->metadata) ? $certificate->metadata : json_decode($certificate->metadata,
        true);
        @endphp
        <div class="bg-white p-6 rounded-lg border border-border">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Penerima</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if(isset($metadata['user_name']))
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Nama Lengkap</label>
                    <p class="text-gray-900 font-medium">{{ $metadata['user_name'] }}</p>
                </div>
                @endif

                @if(isset($metadata['user_email']))
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                    <p class="text-gray-900">{{ $metadata['user_email'] }}</p>
                </div>
                @endif

                @if($certificate->date_of_birth)
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Tanggal Lahir</label>
                    <p class="text-gray-900">{{ \Carbon\Carbon::parse($certificate->date_of_birth)->format('d F Y') }}
                    </p>
                </div>
                @endif

                @if(isset($metadata['package_name']))
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Paket</label>
                    <p class="text-gray-900">{{ $metadata['package_name'] }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Test Results -->
        @if(isset($metadata['listening_score']) || isset($metadata['reading_score']) ||
        isset($metadata['writing_score']))
        <div class="bg-white p-6 rounded-lg border border-border">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Hasil Ujian</h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @if(isset($metadata['listening_score']))
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm text-gray-600 mb-1">Listening</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $metadata['listening_score'] }}</p>
                </div>
                @endif

                @if(isset($metadata['reading_score']))
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <p class="text-sm text-gray-600 mb-1">Reading</p>
                    <p class="text-2xl font-bold text-green-600">{{ $metadata['reading_score'] }}</p>
                </div>
                @endif

                @if(isset($metadata['writing_score']))
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <p class="text-sm text-gray-600 mb-1">Writing</p>
                    <p class="text-2xl font-bold text-purple-600">{{ $metadata['writing_score'] }}</p>
                </div>
                @endif

                @if(isset($metadata['score']))
                <div class="text-center p-4 bg-primary/10 rounded-lg">
                    <p class="text-sm text-gray-600 mb-1">Total Score</p>
                    <p class="text-2xl font-bold text-primary">{{ round($metadata['score']) }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Actions -->
        <div class="bg-white p-6 rounded-lg border border-border">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h3>

            <div class="space-y-3">
                <a href="{{ route('admin.certificate.downloadTemplate', $certificate->certificate_id) }}"
                    class="w-full flex justify-center items-center gap-3 px-4 py-2 bg-green text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="ri-download-line"></i>
                    Download Sertifikat
                </a>
                @if($certificate->status === 'active')
                <form action="{{ route('admin.certificate.bulkAction') }}" method="POST" class="w-full">
                    @csrf
                    <input type="hidden" name="action" value="revoke">
                    <input type="hidden" name="certificate_ids[]" value="{{ $certificate->certificate_id }}">
                    <button type="submit" onclick="return confirm('Yakin ingin mencabut sertifikat ini?')"
                        class="w-full flex justify-center items-center gap-3 px-4 py-2 border border-red text-red-500 rounded-lg hover:bg-red-500 hover:text-white transition-colors">
                        <i class="ri-close-circle-line"></i>
                        Cabut Sertifikat
                    </button>
                </form>
                @elseif($certificate->status === 'revoked')
                <form action="{{ route('admin.certificate.bulkAction') }}" method="POST" class="w-full">
                    @csrf
                    <input type="hidden" name="action" value="activate">
                    <input type="hidden" name="certificate_ids[]" value="{{ $certificate->certificate_id }}">
                    <button type="submit" onclick="return confirm('Yakin ingin mengaktifkan kembali sertifikat ini?')"
                        class="w-full flex justify-center items-center gap-3 px-4 py-2 border border-green text-green-600 rounded-lg hover:bg-green hover:text-white transition-colors">
                        <i class="ri-check-circle-line"></i>
                        Aktifkan Sertifikat
                    </button>
                </form>
                @endif
            </div>
        </div>

        <!-- Certificate Info -->
        <div class="bg-white p-6 rounded-lg border border-border">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Tambahan</h3>

            <div class="space-y-3">
                @if($certificate->verification_code)
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Kode Verifikasi</label>
                    <p class="text-gray-900 font-mono text-sm bg-gray-50 p-2 rounded">{{ $certificate->verification_code
                        }}</p>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Diterbitkan Oleh</label>
                    <p class="text-gray-900">{{ $certificate->issuedBy->name ?? 'Admin' }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Dibuat</label>
                    <p class="text-gray-900">{{ $certificate->created_at->format('d F Y H:i') }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Diperbarui</label>
                    <p class="text-gray-900">{{ $certificate->updated_at->format('d F Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
