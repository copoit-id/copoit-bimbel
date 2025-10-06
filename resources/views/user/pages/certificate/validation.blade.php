@extends('user.layout.user')
@section('title', 'Validasi Sertifikat')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white p-6 rounded-lg border border-border">
        <div class="text-center">
            <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-shield-check-line text-2xl text-primary"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Validasi Sertifikat</h1>
            <p class="text-gray-600">Verifikasi keaslian sertifikat dengan memasukkan nomor sertifikat</p>
        </div>
    </div>

    <!-- Validation Form -->
    <div class="bg-white p-6 rounded-lg border border-border">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Form Validasi</h3>

        <form id="validationForm" class="space-y-4">
            <div>
                <label for="certificate_number" class="block text-sm font-medium text-gray-700 mb-2">
                    Nomor Sertifikat
                </label>
                <div class="relative">
                    <input type="text" id="certificate_number" name="certificate_number"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                        placeholder="Contoh: 4463/CA/08/2025" required>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <i class="ri-file-text-line text-gray-400"></i>
                    </div>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Masukkan nomor sertifikat sesuai format yang tertera pada sertifikat Anda
                </p>
            </div>

            <button type="submit" id="validateBtn"
                class="w-full px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-medium">
                <span class="btn-text">
                    <i class="ri-search-line mr-2"></i>Validasi Sertifikat
                </span>
                <span class="btn-loading hidden">
                    <i class="ri-loader-4-line animate-spin mr-2"></i>Memvalidasi...
                </span>
            </button>
        </form>
    </div>

    <!-- Validation Result -->
    <div id="validationResult" class="hidden">
        <!-- Valid Certificate -->
        <div id="validCertificate" class="bg-white p-6 rounded-lg border border-border hidden">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                    <i class="ri-check-line text-2xl text-green-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-green-800">Sertifikat Valid</h3>
                    <p class="text-green-600">Sertifikat ini asli dan terdaftar dalam sistem kami</p>
                </div>
            </div>

            <!-- Certificate Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h4 class="font-semibold text-gray-700 mb-3">Informasi Sertifikat</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Nomor Sertifikat:</span>
                            <span class="font-medium" id="cert-number"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Nama Pemegang:</span>
                            <span class="font-medium" id="cert-holder"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tanggal Lahir:</span>
                            <span class="font-medium" id="cert-dob"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Program:</span>
                            <span class="font-medium" id="cert-program"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Institusi:</span>
                            <span class="font-medium" id="cert-institution"></span>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-700 mb-3">Detail Validitas</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tanggal Terbit:</span>
                            <span class="font-medium" id="cert-issued"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tanggal Kadaluarsa:</span>
                            <span class="font-medium" id="cert-expired"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Status:</span>
                            <span class="font-medium" id="cert-status"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Kode Verifikasi:</span>
                            <span class="font-medium" id="cert-verification"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scores Section -->
            <div class="mb-6">
                <h4 class="font-semibold text-gray-700 mb-3">Hasil Ujian</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <div class="text-lg font-bold text-primary" id="score-listening">-</div>
                        <div class="text-xs text-gray-600">Listening</div>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <div class="text-lg font-bold text-primary" id="score-reading">-</div>
                        <div class="text-xs text-gray-600">Reading</div>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <div class="text-lg font-bold text-primary" id="score-writing">-</div>
                        <div class="text-xs text-gray-600">Writing</div>
                    </div>
                    <div class="text-center p-3 bg-primary/10 rounded-lg">
                        <div class="text-lg font-bold text-primary" id="score-overall">-</div>
                        <div class="text-xs text-primary">Overall Score</div>
                    </div>
                </div>
            </div>

            <!-- Certificate Preview -->
            <div class="mb-6">
                <h4 class="font-semibold text-gray-700 mb-3">Preview Sertifikat</h4>
                <div class="border-2 border-gray-200 rounded-lg p-4 bg-gray-50">
                    <img id="cert-preview" src="" alt="Certificate Preview"
                        class="max-w-full h-auto mx-auto rounded-lg shadow-lg" style="max-height: 500px;">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button id="downloadCertBtn"
                    class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                    <i class="ri-download-line mr-2"></i>Download Sertifikat
                </button>
                <button onclick="resetValidation()"
                    class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="ri-refresh-line mr-2"></i>Validasi Lagi
                </button>
            </div>
        </div>

        <!-- Invalid Certificate -->
        <div id="invalidCertificate" class="bg-white p-6 rounded-lg border border-border hidden">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                    <i class="ri-close-line text-2xl text-red-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-red-800">Sertifikat Tidak Valid</h3>
                    <p class="text-red-600">Sertifikat tidak ditemukan atau tidak terdaftar dalam sistem kami</p>
                </div>
            </div>

            <div class="p-4 bg-red-50 border border-red-200 rounded-lg mb-4">
                <h4 class="font-semibold text-red-800 mb-2">Kemungkinan Penyebab:</h4>
                <ul class="text-sm text-red-700 space-y-1">
                    <li>• Nomor sertifikat salah atau tidak sesuai format</li>
                    <li>• Sertifikat sudah kadaluarsa atau dibatalkan</li>
                    <li>• Sertifikat palsu atau tidak dikeluarkan oleh CPNS Academy</li>
                </ul>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button onclick="resetValidation()"
                    class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                    <i class="ri-refresh-line mr-2"></i>Coba Lagi
                </button>
                <a href="#"
                    class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-center">
                    <i class="ri-customer-service-line mr-2"></i>Hubungi Support
                </a>
            </div>
        </div>
    </div>

    <!-- Information Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex items-start">
            <i class="ri-information-line text-blue-600 mr-3 mt-1"></i>
            <div>
                <h4 class="font-semibold text-blue-800 mb-2">Informasi Validasi</h4>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Pastikan nomor sertifikat diketik dengan benar sesuai format</li>
                    <li>• Validasi dilakukan secara real-time terhadap database resmi</li>
                    <li>• Sertifikat yang valid akan menampilkan semua informasi lengkap</li>
                    <li>• Hubungi support jika mengalami masalah dalam validasi</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
    let currentCertificateData = null;

    $('#validationForm').on('submit', function(e) {
        e.preventDefault();

        const certificateNumber = $('#certificate_number').val().trim();

        if (!certificateNumber) {
            showAlert('error', 'Nomor sertifikat wajib diisi');
            return;
        }

        validateCertificate(certificateNumber);
    });

    function validateCertificate(certificateNumber) {
        // Show loading state
        showLoading(true);
        hideResults();

        $.ajax({
            url: '{{ route("user.certificate.validate") }}',
            method: 'POST',
            data: {
                certificate_number: certificateNumber,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                showLoading(false);

                if (response.success && response.valid) {
                    showValidCertificate(response.data);
                    currentCertificateData = response.data;
                } else {
                    showInvalidCertificate();
                }
            },
            error: function(xhr) {
                showLoading(false);

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        const firstError = Object.values(errors)[0][0];
                        showAlert('error', firstError);
                    } else {
                        showAlert('error', 'Validasi gagal');
                    }
                } else {
                    showAlert('error', 'Terjadi kesalahan saat validasi');
                }
            }
        });
    }

    function showValidCertificate(data) {
        // Fill certificate data
        $('#cert-number').text(data.certificate_number || 'Unknown');
        $('#cert-holder').text(data.holder_name || 'Unknown');
        $('#cert-dob').text(data.date_of_birth || 'Unknown');
        $('#cert-program').text(data.package_name || 'Unknown');
        $('#cert-institution').text(data.institution_name || 'Unknown');
        $('#cert-issued').text(data.issued_date || 'Unknown');
        $('#cert-expired').text(data.expired_date || 'Unknown');
        $('#cert-verification').text(data.verification_code || 'Unknown');

        // Set status with color
        const statusEl = $('#cert-status');
        statusEl.removeClass('text-red-600 text-green-600'); // Clear previous classes
        if (data.is_expired) {
            statusEl.text('Kadaluarsa').addClass('text-red-600');
        } else {
            statusEl.text('Aktif').addClass('text-green-600');
        }

        // Fill scores
        $('#score-listening').text(data.scores?.listening || '-');
        $('#score-reading').text(data.scores?.reading || '-');
        $('#score-writing').text(data.scores?.writing || '-');
        $('#score-overall').text(Math.round(data.scores?.overall || 0));

        // Set certificate preview
        if (data.certificate_id) {
            const previewUrl = `{{ route('user.certificate.view', ['certificate_id' => ':cert_id', 'token' => 'public']) }}`.replace(':cert_id', data.certificate_id);
            $('#cert-preview').attr('src', previewUrl);
        }

        // Show valid certificate section
        $('#validationResult').removeClass('hidden');
        $('#validCertificate').removeClass('hidden');
        $('#invalidCertificate').addClass('hidden');
    }

    function showInvalidCertificate() {
        $('#validationResult').removeClass('hidden');
        $('#validCertificate').addClass('hidden');
        $('#invalidCertificate').removeClass('hidden');
    }

    function hideResults() {
        $('#validationResult').addClass('hidden');
        $('#validCertificate').addClass('hidden');
        $('#invalidCertificate').addClass('hidden');
    }

    function showLoading(show) {
        const btn = $('#validateBtn');
        if (show) {
            btn.prop('disabled', true);
            btn.find('.btn-text').addClass('hidden');
            btn.find('.btn-loading').removeClass('hidden');
        } else {
            btn.prop('disabled', false);
            btn.find('.btn-text').removeClass('hidden');
            btn.find('.btn-loading').addClass('hidden');
        }
    }

    // Download certificate - gunakan route baru yang hanya perlu certificate_id
    $('#downloadCertBtn').on('click', function() {
        if (!currentCertificateData || !currentCertificateData.certificate_id) {
            showAlert('error', 'Data sertifikat tidak ditemukan');
            return;
        }

        try {
            // Langsung redirect ke route download yang baru
            const downloadUrl = `{{ route("user.certificate.validation.download", ":cert_id") }}`.replace(':cert_id', currentCertificateData.certificate_id);
            window.open(downloadUrl, '_blank');
        } catch (error) {
            console.error('Download error:', error);
            showAlert('error', 'Gagal mengunduh sertifikat');
        }
    });

    function showAlert(type, message) {
        // Remove existing alerts
        $('#alert').remove();

        const alertClass = type === 'error' ? 'bg-red-100 text-red-700 border-red-300' : 'bg-green-100 text-green-700 border-green-300';

        const alertHtml = `
            <div class="fixed top-4 right-4 z-50 p-4 border rounded-lg ${alertClass}" id="alert">
                <div class="flex items-center">
                    <i class="ri-${type === 'error' ? 'error-warning' : 'check-circle'}-line mr-2"></i>
                    <span>${message}</span>
                    <button onclick="$('#alert').remove()" class="ml-4">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            </div>
        `;

        $('body').append(alertHtml);

        setTimeout(() => {
            $('#alert').remove();
        }, 5000);
    }

    // Global function for reset
    window.resetValidation = function() {
        $('#certificate_number').val('');
        hideResults();
        currentCertificateData = null;
        $('#cert-status').removeClass('text-red-600 text-green-600');
    };
});
</script>
@endsection

@section('styles')
<style>
    .animate-spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }
</style>
@endsection
