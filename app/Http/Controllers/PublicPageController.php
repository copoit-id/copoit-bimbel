<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PublicPageController extends Controller
{
    public function terms(): View
    {
        return view('public.legal', $this->pageData('terms'));
    }

    public function paymentPolicy(): View
    {
        return view('public.legal', $this->pageData('payment'));
    }

    public function refundPolicy(): View
    {
        return view('public.legal', $this->pageData('refund'));
    }

    private function pageData(string $type): array
    {
        $pages = [
            'terms' => [
                'title' => 'Syarat dan Ketentuan',
                'subtitle' => 'Ketentuan penggunaan layanan pembelajaran, pembelian paket, dan akses konten digital.',
                'updated' => '15 Juni 2026',
                'sections' => [
                    [
                        'heading' => '1. Penerimaan Ketentuan',
                        'body' => [
                            'Dengan membuat akun, mengakses materi, mengikuti tryout, membeli paket, atau menggunakan layanan pada platform ini, pengguna dianggap telah membaca, memahami, dan menyetujui Syarat dan Ketentuan ini.',
                            'Jika pengguna tidak menyetujui ketentuan ini, pengguna dapat berhenti menggunakan layanan dan tidak melakukan transaksi pada platform.',
                        ],
                    ],
                    [
                        'heading' => '2. Akun Pengguna',
                        'body' => [
                            'Pengguna wajib memberikan data yang benar saat mendaftar dan bertanggung jawab menjaga kerahasiaan email, kata sandi, serta aktivitas yang terjadi pada akun.',
                            'Akun, akses belajar, dan paket yang dibeli tidak boleh dipindahtangankan, dijual ulang, dibagikan secara massal, atau digunakan bersama tanpa izin tertulis dari pengelola.',
                        ],
                    ],
                    [
                        'heading' => '3. Layanan dan Konten',
                        'body' => [
                            'Layanan dapat mencakup paket belajar, materi video, dokumen belajar, live session, tryout, tes koran, sertifikat, dan fitur pembelajaran lain yang tersedia di platform.',
                            'Konten digital disediakan untuk penggunaan pribadi pengguna. Pengguna dilarang menyalin, merekam ulang, mengunggah ulang, memperjualbelikan, atau mendistribusikan konten tanpa izin.',
                        ],
                    ],
                    [
                        'heading' => '4. Akses Paket',
                        'body' => [
                            'Akses paket atau produk digital diberikan sesuai jenis paket, durasi akses, dan status pembayaran atau verifikasi admin.',
                            'Pengelola dapat memperbarui, menambah, mengurangi, atau menyesuaikan konten demi menjaga kualitas layanan tanpa mengurangi akses utama yang telah dibeli pengguna.',
                        ],
                    ],
                    [
                        'heading' => '5. Pembayaran',
                        'body' => [
                            'Pembayaran dilakukan melalui metode yang tersedia di platform, termasuk payment gateway, QRIS, virtual account, e-wallet, transfer bank manual, atau metode lain yang diaktifkan pengelola.',
                            'Pengguna bertanggung jawab memastikan nominal, metode pembayaran, dan data transaksi benar sebelum menyelesaikan pembayaran.',
                        ],
                    ],
                    [
                        'heading' => '6. Pelanggaran',
                        'body' => [
                            'Pengelola berhak membatasi, menangguhkan, atau menghentikan akses akun jika ditemukan penyalahgunaan, pelanggaran hak cipta, manipulasi sistem, percobaan kecurangan tryout, atau aktivitas lain yang merugikan platform dan pengguna lain.',
                            'Jika pelanggaran berdampak pada transaksi atau akses, pengelola dapat menolak klaim refund sesuai Kebijakan Refund.',
                        ],
                    ],
                    [
                        'heading' => '7. Perubahan Ketentuan',
                        'body' => [
                            'Syarat dan Ketentuan dapat diperbarui sewaktu-waktu. Versi terbaru akan ditampilkan pada halaman ini dan berlaku sejak tanggal pembaruan.',
                        ],
                    ],
                ],
            ],
            'payment' => [
                'title' => 'Kebijakan Pembayaran',
                'subtitle' => 'Informasi metode pembayaran, proses verifikasi, aktivasi akses, dan kendala transaksi.',
                'updated' => '15 Juni 2026',
                'sections' => [
                    [
                        'heading' => '1. Metode Pembayaran',
                        'body' => [
                            'Platform menerima pembayaran melalui metode yang tersedia pada halaman checkout, seperti payment gateway, QRIS, virtual account, e-wallet, transfer bank, atau metode manual lain yang diaktifkan pengelola.',
                            'Ketersediaan metode pembayaran dapat berubah mengikuti konfigurasi platform dan penyedia payment gateway.',
                        ],
                    ],
                    [
                        'heading' => '2. Nominal dan Biaya',
                        'body' => [
                            'Harga yang harus dibayar adalah nominal yang tampil pada halaman checkout atau invoice transaksi.',
                            'Biaya admin, biaya gateway, kode unik, atau biaya lain yang muncul dari penyedia pembayaran dapat ditambahkan sesuai metode yang dipilih.',
                        ],
                    ],
                    [
                        'heading' => '3. Aktivasi Akses',
                        'body' => [
                            'Untuk pembayaran melalui payment gateway, akses akan diaktifkan otomatis setelah sistem menerima status pembayaran berhasil dari penyedia pembayaran.',
                            'Untuk pembayaran manual, akses akan diaktifkan setelah pengguna mengunggah bukti pembayaran dan admin menyetujui verifikasi pembayaran.',
                        ],
                    ],
                    [
                        'heading' => '4. Batas Waktu Pembayaran',
                        'body' => [
                            'Invoice atau instruksi pembayaran dapat memiliki batas waktu tertentu. Jika pembayaran dilakukan melewati batas waktu, transaksi dapat kedaluwarsa dan pengguna perlu membuat transaksi baru.',
                        ],
                    ],
                    [
                        'heading' => '5. Pembayaran Gagal atau Pending',
                        'body' => [
                            'Jika pembayaran berstatus gagal, pending, atau belum terdeteksi, pengguna dapat memeriksa kembali status pembayaran dari halaman riwayat pembelian atau menghubungi admin dengan menyertakan bukti transaksi.',
                            'Pengelola akan melakukan pengecekan berdasarkan data transaksi, bukti pembayaran, dan status dari penyedia payment gateway.',
                        ],
                    ],
                    [
                        'heading' => '6. Keamanan Pembayaran',
                        'body' => [
                            'Data pembayaran diproses melalui penyedia payment gateway atau kanal pembayaran yang tersedia. Pengguna tidak diminta memberikan data sensitif seperti PIN, OTP, atau kata sandi perbankan kepada admin.',
                        ],
                    ],
                ],
            ],
            'refund' => [
                'title' => 'Kebijakan Refund',
                'subtitle' => 'Ketentuan pengembalian dana untuk pembelian paket dan produk digital.',
                'updated' => '15 Juni 2026',
                'sections' => [
                    [
                        'heading' => '1. Prinsip Umum',
                        'body' => [
                            'Produk yang dijual pada platform bersifat digital, seperti akses paket belajar, materi, tryout, tes koran, live session, dan layanan pembelajaran lain.',
                            'Setelah pembayaran berhasil dan akses digital aktif, transaksi pada prinsipnya tidak dapat dibatalkan atau dikembalikan dananya, kecuali memenuhi kondisi refund yang disebutkan dalam kebijakan ini.',
                        ],
                    ],
                    [
                        'heading' => '2. Kondisi yang Dapat Dipertimbangkan untuk Refund',
                        'body' => [
                            'Refund dapat dipertimbangkan jika terjadi pembayaran ganda untuk produk yang sama, pembayaran berhasil tetapi akses tidak dapat diberikan karena kendala sistem, atau produk tidak tersedia secara permanen setelah pembayaran berhasil.',
                            'Permintaan refund harus disertai bukti pembayaran, detail akun, nomor transaksi, dan alasan pengajuan refund.',
                        ],
                    ],
                    [
                        'heading' => '3. Kondisi yang Tidak Memenuhi Refund',
                        'body' => [
                            'Refund tidak berlaku untuk pengguna yang sudah mengakses materi, mengikuti tryout, membuka pembahasan, mengikuti live session, atau menggunakan fitur utama produk digital yang dibeli.',
                            'Refund juga tidak berlaku untuk kesalahan pembelian oleh pengguna, ketidaksesuaian jadwal pribadi pengguna, perubahan keputusan setelah membeli, pelanggaran akun, atau penyalahgunaan layanan.',
                        ],
                    ],
                    [
                        'heading' => '4. Proses Pengajuan',
                        'body' => [
                            'Pengguna dapat mengajukan refund dengan menghubungi admin melalui kontak resmi yang tersedia di platform maksimal 3 x 24 jam setelah pembayaran berhasil.',
                            'Pengelola akan meninjau pengajuan refund berdasarkan status pembayaran, riwayat akses produk, bukti transaksi, dan data internal platform.',
                        ],
                    ],
                    [
                        'heading' => '5. Waktu Pengembalian Dana',
                        'body' => [
                            'Jika refund disetujui, proses pengembalian dana mengikuti metode pembayaran, bank, atau penyedia payment gateway yang digunakan.',
                            'Biaya admin, biaya gateway, biaya transfer, atau potongan lain dari penyedia pembayaran dapat mengurangi nominal refund jika berlaku.',
                        ],
                    ],
                    [
                        'heading' => '6. Keputusan Akhir',
                        'body' => [
                            'Keputusan persetujuan atau penolakan refund berada pada pengelola setelah mempertimbangkan data transaksi dan riwayat penggunaan layanan.',
                        ],
                    ],
                ],
            ],
        ];

        return $pages[$type];
    }
}
