<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LandingpageHero;
use App\Models\LandingpageFeature;
use App\Models\LandingpageGallery;
use App\Models\LandingpageTestimonial;
use App\Models\LandingpageCta;

class LandingPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Hero Section
        LandingpageHero::create([
            'title' => 'Belajar Efektif, Meraih Prestasi Terbaik',
            'subtitle' => 'Platform pembelajaran terdepan untuk membantu Anda meraih cita-cita akademik.',
            'description' => 'Dengan metode pembelajaran yang terbukti efektif, instruktur berpengalaman, dan fasilitas lengkap, kami siap mendampingi perjalanan belajar Anda menuju kesuksesan.',
            'button_text' => 'Mulai Belajar',
            'button_link' => '/register',
            'stat_1_number' => '1000+',
            'stat_1_text' => 'Siswa Aktif',
            'stat_2_number' => '95%',
            'stat_2_text' => 'Tingkat Kelulusan',
            'stat_3_number' => '50+',
            'stat_3_text' => 'Instruktur Expert',
            'is_active' => true
        ]);

        // Create Features
        $features = [
            [
                'title' => 'Instruktur Berpengalaman',
                'description' => 'Tim pengajar profesional dengan pengalaman bertahun-tahun di bidangnya masing-masing.',
                'icon' => 'ri-user-star-line',
                'order' => 1,
                'is_active' => true
            ],
            [
                'title' => 'Materi Pembelajaran Lengkap',
                'description' => 'Kurikulum yang disusun secara sistematis dan sesuai dengan standar pendidikan terkini.',
                'icon' => 'ri-book-open-line',
                'order' => 2,
                'is_active' => true
            ],
            [
                'title' => 'Tryout Berkualitas',
                'description' => 'Latihan soal dan simulasi ujian yang membantu mempersiapkan diri dengan maksimal.',
                'icon' => 'ri-file-edit-line',
                'order' => 3,
                'is_active' => true
            ],
            [
                'title' => 'Pembelajaran Fleksibel',
                'description' => 'Akses materi pembelajaran kapan saja dan di mana saja sesuai dengan kebutuhan Anda.',
                'icon' => 'ri-time-line',
                'order' => 4,
                'is_active' => true
            ],
            [
                'title' => 'Monitoring Progress',
                'description' => 'Pantau perkembangan belajar dengan sistem pelaporan yang detail dan komprehensif.',
                'icon' => 'ri-bar-chart-line',
                'order' => 5,
                'is_active' => true
            ],
            [
                'title' => 'Dukungan 24/7',
                'description' => 'Tim support yang siap membantu menjawab pertanyaan dan mengatasi kendala belajar.',
                'icon' => 'ri-customer-service-2-line',
                'order' => 6,
                'is_active' => true
            ]
        ];

        foreach ($features as $feature) {
            LandingpageFeature::create($feature);
        }

        // Create Testimonials
        $testimonials = [
            [
                'name' => 'Andi Pratama',
                'position' => 'Siswa Kelas 12 IPA',
                'content' => 'Bimbel ini sangat membantu saya dalam mempersiapkan UTBK. Materi yang diberikan lengkap dan mudah dipahami. Instruktur juga sangat sabar dalam menjelaskan.',
                'rating' => 5,
                'order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'Sari Dewi',
                'position' => 'Alumni - Lulus PTN Favorit',
                'content' => 'Metode pembelajaran di sini benar-benar efektif. Saya bisa meningkatkan pemahaman materi dengan cepat dan alhamdulillah berhasil lulus di PTN impian.',
                'rating' => 5,
                'order' => 2,
                'is_active' => true
            ],
            [
                'name' => 'Budi Santoso',
                'position' => 'Orang Tua Siswa',
                'content' => 'Sebagai orang tua, saya sangat puas dengan pelayanan dan kualitas pembelajaran. Anak saya menjadi lebih percaya diri dan nilai akademiknya meningkat.',
                'rating' => 5,
                'order' => 3,
                'is_active' => true
            ],
            [
                'name' => 'Maya Kusuma',
                'position' => 'Siswa Kelas 11',
                'content' => 'Tryout yang disediakan sangat membantu untuk latihan dan mengukur kemampuan. Soal-soalnya berkualitas dan sesuai dengan standar ujian.',
                'rating' => 4,
                'order' => 4,
                'is_active' => true
            ]
        ];

        foreach ($testimonials as $testimonial) {
            LandingpageTestimonial::create($testimonial);
        }

        // Create CTA Section
        LandingpageCta::create([
            'title' => 'Siap Meraih Impian Akademik Terbaik?',
            'description' => 'Bergabunglah dengan ribuan siswa yang telah merasakan transformasi belajar dan meraih prestasi gemilang bersama kami.',
            'primary_button_text' => 'Daftar Sekarang',
            'secondary_button_text' => 'Login',
            'is_active' => true
        ]);
    }
}
