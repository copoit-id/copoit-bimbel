<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function index()
    {
        $faqs = [
            [
                'question' => 'Bagaimana cara membeli paket?',
                'answer' => 'Anda dapat membeli paket dengan memilih paket yang diinginkan, kemudian klik tombol "Beli Sekarang" dan ikuti proses pembayaran.',
                'category' => 'Pembelian'
            ],
            [
                'question' => 'Berapa lama akses paket berlaku?',
                'answer' => 'Akses paket berlaku selama 30 hari sejak tanggal pembelian. Anda dapat melihat masa berlaku di halaman "Paket Aktif Saya".',
                'category' => 'Akses'
            ],
            [
                'question' => 'Bagaimana cara mengerjakan tryout?',
                'answer' => 'Masuk ke paket yang sudah dibeli, pilih tryout yang ingin dikerjakan, lalu klik "Kerjakan". Ikuti petunjuk di halaman lobby sebelum memulai.',
                'category' => 'Tryout'
            ],
            [
                'question' => 'Apakah bisa mengerjakan tryout berulang kali?',
                'answer' => 'Ya, Anda dapat mengerjakan tryout berulang kali selama masa akses paket masih berlaku.',
                'category' => 'Tryout'
            ],
            [
                'question' => 'Bagaimana cara melihat pembahasan soal?',
                'answer' => 'Setelah menyelesaikan tryout, Anda dapat melihat pembahasan dengan klik "Lihat Pembahasan" di halaman hasil atau riwayat tryout.',
                'category' => 'Pembahasan'
            ],
            [
                'question' => 'Metode pembayaran apa saja yang tersedia?',
                'answer' => 'Kami menerima pembayaran melalui transfer bank, e-wallet (OVO, GoPay, DANA), dan kartu kredit/debit melalui gateway Xendit.',
                'category' => 'Pembayaran'
            ]
        ];

        return view('user.pages.help.index', compact('faqs'));
    }
}
