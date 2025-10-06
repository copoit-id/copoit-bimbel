@extends('user.layout.user')
@section('title', 'Preview Sertifikat')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white p-6 rounded-lg border border-border">
        <div class="text-center">
            <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-award-line text-2xl text-primary"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Sertifikat {{ $existingCertificate->certificate_name }}
            </h1>
            <p class="text-gray-600">Selamat! Anda telah berhasil menyelesaikan ujian sertifikasi</p>
        </div>
    </div>

    <!-- Certificate Information -->
    <div class="bg-white p-6 rounded-lg border border-border">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Sertifikat</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-gray-700 mb-3">Detail Sertifikat</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nomor Sertifikat:</span>
                        <span class="font-medium">{{ $existingCertificate->certificate_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nama Penerima:</span>
                        <span class="font-medium">{{ is_array($existingCertificate->metadata) ?
                            $existingCertificate->metadata['user_name'] : json_decode($existingCertificate->metadata,
                            true)['user_name'] ?? Auth::user()->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ujian:</span>
                        <span class="font-medium">{{ $tryout->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Paket:</span>
                        <span class="font-medium">{{ $package->name }}</span>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="font-semibold text-gray-700 mb-3">Detail Hasil</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tanggal Ujian:</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse(is_array($existingCertificate->metadata) ?
                            $existingCertificate->metadata['completion_date'] :
                            json_decode($existingCertificate->metadata,
                            true)['completion_date'])->format('F, d Y') ?? $existingCertificate->issued_date->format('F,
                            d
                            Y')
                            }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tanggal Terbit:</span>
                        <span class="font-medium">{{ $existingCertificate->issued_date->format('F, d Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Berlaku Hingga:</span>
                        <span class="font-medium">{{ $existingCertificate->expired_date->format('F, d Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Overall Score:</span>
                        <span class="font-medium text-primary text-lg">{{ round($overallPercentage, 1) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subtest Scores -->
        @php
        $metadata = is_array($existingCertificate->metadata) ? $existingCertificate->metadata :
        json_decode($existingCertificate->metadata, true);
        @endphp
        @if(isset($metadata['subtest_details']) && is_array($metadata['subtest_details']))
        <div class="mt-6">
            <h4 class="font-semibold text-gray-700 mb-3">Skor Per Subtest</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($metadata['subtest_details'] as $subtest)
                @if(is_array($subtest) && isset($subtest['name']) && isset($subtest['display_score']))
                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <div class="text-center">
                        <h5 class="font-semibold text-gray-800">{{ $subtest['name'] }}</h5>
                        <div class="text-2xl font-bold text-primary mt-2">{{ $subtest['display_score'] }}</div>
                        <div class="text-sm text-gray-600">{{ round($subtest['percentage'] ?? 0, 1) }}%</div>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Certificate Actions -->
    <div class="bg-white p-6 rounded-lg border border-border">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Aksi Sertifikat</h3>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <!-- Preview Certificate Button -->
            <a href="{{ route('user.certificate.view', ['certificate_id' => $existingCertificate->certificate_id,'token' => $token]) }}"
                target="_blank"
                class="px-6 py-3 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors text-center">
                <i class="ri-eye-line mr-2"></i>Preview Sertifikat
            </a>

            <!-- Download Certificate Button -->
            <a href="{{ route('user.certificate.download', ['certificate_id' => $existingCertificate->certificate_id,'token' => $token]) }}"
                class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-center">
                <i class="ri-download-line mr-2"></i>Download Sertifikat
            </a>

            <!-- Validate Certificate Button -->
            <a href="{{ route('user.certificate.validation') }}"
                class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-center">
                <i class="ri-search-line mr-2"></i>Validasi Sertifikat
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Add any JavaScript functionality here if needed
    console.log('Certificate preview page loaded');
});
</script>
@endsection