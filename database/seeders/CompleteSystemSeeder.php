<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Package;
use App\Models\ClassModel;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\DetailPackage;
use App\Models\Payment;
use App\Models\UserPackageAcces;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use App\Models\Certificate;
use App\Models\Leaderboard;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CompleteSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // 1. Create Users
            $users = $this->createUsers();
            
            // 2. Create Packages
            $packages = $this->createPackages();
            
            // 3. Create Classes
            $classes = $this->createClasses();
            
            // 4. Create Tryouts
            $tryouts = $this->createTryouts();
            
            // 5. Create Tryout Details & Questions
            $this->createTryoutDetailsAndQuestions($tryouts);
            
            // 6. Create Detail Packages (Relations)
            $this->createDetailPackages($packages, $tryouts, $classes);
            
            // 7. Create Payments & User Package Access
            $this->createPaymentsAndAccess($users, $packages);
            
            // 8. Create User Answers (Simulasi pengerjaan tryout)
            $this->createUserAnswers($users, $tryouts);
            
            // 9. Create Certificates & Leaderboards
            $this->createCertificatesAndLeaderboards($users, $tryouts);
            
            $this->command->info('Complete system seeder finished successfully!');
            
        } catch (\Exception $e) {
            $this->command->error('Seeder failed: ' . $e->getMessage());
            $this->command->error('Line: ' . $e->getLine());
            throw $e;
        }
    }

    private function createUsers()
    {
        $this->command->info('Creating users...');
        
        $users = collect();
        
        // Create admin user
        $this->command->info('Creating admin user...');
        $admin = User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@copoit.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'aktif',
            'email_verified_at' => now(),
        ]);
        $users->push($admin);
        
        // Create student users
        $studentsData = [
            ['name' => 'Budi Santoso', 'username' => 'budi_santoso', 'email' => 'budi.santoso@gmail.com'],
            ['name' => 'Siti Nurhaliza', 'username' => 'siti_nurhaliza', 'email' => 'siti.nurhaliza@gmail.com'],
            ['name' => 'Ahmad Rizki', 'username' => 'ahmad_rizki', 'email' => 'ahmad.rizki@gmail.com'],
            ['name' => 'Dewi Sartika', 'username' => 'dewi_sartika', 'email' => 'dewi.sartika@gmail.com'],
            ['name' => 'Rudi Hartono', 'username' => 'rudi_hartono', 'email' => 'rudi.hartono@gmail.com'],
        ];
        
        foreach ($studentsData as $studentData) {
            $this->command->info('Creating student: ' . $studentData['name']);
            $student = User::create([
                'name' => $studentData['name'],
                'username' => $studentData['username'],
                'email' => $studentData['email'],
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'aktif',
                'email_verified_at' => now(),
            ]);
            $users->push($student);
        }

        $this->command->info('Created ' . $users->count() . ' users');
        return $users;
    }

    private function createPackages()
    {
        $this->command->info('Creating packages...');
        
        $packagesData = [
            [
                'name' => 'CPNS Premium 2024',
                'price' => 299000,
                'type_package' => 'bimbel',
                'type_price' => 'paid',
                'status' => 'active',
                'description' => 'Paket lengkap persiapan CPNS dengan tryout unlimited, kelas online, dan bimbingan intensif.',
                'features' => json_encode([
                    'Tryout SKD Unlimited',
                    'Kelas Online Live 2x/minggu',
                    'E-book Materi Lengkap',
                    'Video Pembahasan',
                    'Konsultasi dengan Mentor',
                    'Sertifikat Digital'
                ]),
                'image' => 'cpns-premium.jpg'
            ],
            [
                'name' => 'TOEFL Preparation',
                'price' => 199000,
                'type_package' => 'sertifikasi',
                'type_price' => 'paid',
                'status' => 'active',
                'description' => 'Persiapan TOEFL lengkap dengan target skor 550+',
                'features' => json_encode([
                    'TOEFL Practice Tests',
                    'Listening Skills Training',
                    'Reading Comprehension',
                    'Grammar & Structure',
                    'Vocabulary Building',
                    'Speaking Practice'
                ]),
                'image' => 'toefl-prep.jpg'
            ],
            [
                'name' => 'Tryout SKD Gratis',
                'price' => 0,
                'type_package' => 'tryout',
                'type_price' => 'free',
                'status' => 'active',
                'description' => 'Tryout gratis untuk latihan CPNS bagian SKD',
                'features' => json_encode([
                    '3 Paket Tryout SKD',
                    'Analisis Hasil',
                    'Ranking Nasional'
                ]),
                'image' => 'tryout-gratis.jpg'
            ],
            [
                'name' => 'PPPK Guru Complete',
                'price' => 249000,
                'type_package' => 'bimbel',
                'type_price' => 'paid',
                'status' => 'active',
                'description' => 'Persiapan lengkap PPPK Guru dengan materi terbaru',
                'features' => json_encode([
                    'Tryout PPPK Unlimited',
                    'Materi Pedagogik',
                    'Materi Profesional',
                    'Kelas Online',
                    'Bank Soal Terlengkap'
                ]),
                'image' => 'pppk-guru.jpg'
            ],
            [
                'name' => 'Sertifikasi Microsoft Office',
                'price' => 149000,
                'type_package' => 'sertifikasi',
                'type_price' => 'paid',
                'status' => 'active',
                'description' => 'Persiapan sertifikasi Microsoft Office (Word, Excel, PowerPoint)',
                'features' => json_encode([
                    'Microsoft Word Advanced',
                    'Microsoft Excel Expert',
                    'PowerPoint Specialist',
                    'Practice Tests',
                    'Video Tutorials'
                ]),
                'image' => 'ms-office.jpg'
            ]
        ];

        return collect($packagesData)->map(function ($packageData) {
            return Package::create($packageData);
        });
    }

    private function createClasses()
    {
        $this->command->info('Creating classes...');
        
        $classesData = [
            [
                'title' => 'Kelas TIU Intensif',
                'mentor' => 'Dr. Budi Raharjo, M.Pd',
                'schedule_time' => Carbon::now()->addDays(7)->setTime(19, 0),
                'zoom_link' => 'https://zoom.us/j/123456789',
                'drive_link' => 'https://drive.google.com/materials/tiu',
                'status' => 'upcoming'
            ],
            [
                'title' => 'Kelas TWK Fundamental', 
                'mentor' => 'Prof. Siti Aminah, S.H., M.H',
                'schedule_time' => Carbon::now()->addDays(8)->setTime(19, 30),
                'zoom_link' => 'https://zoom.us/j/123456790',
                'drive_link' => 'https://drive.google.com/materials/twk',
                'status' => 'upcoming'
            ],
            [
                'title' => 'Kelas TKP Strategy',
                'mentor' => 'Drs. Ahmad Supardi, M.Psi',
                'schedule_time' => Carbon::now()->addDays(9)->setTime(20, 0),
                'zoom_link' => 'https://zoom.us/j/123456791',
                'drive_link' => 'https://drive.google.com/materials/tkp',
                'status' => 'upcoming'
            ],
            [
                'title' => 'TOEFL Listening Master Class',
                'mentor' => 'Ms. Sarah Johnson, TESOL Certified',
                'schedule_time' => Carbon::now()->addDays(10)->setTime(19, 0),
                'zoom_link' => 'https://zoom.us/j/123456792',
                'drive_link' => 'https://drive.google.com/materials/toefl-listening',
                'status' => 'upcoming'
            ],
            [
                'title' => 'Microsoft Excel Expert',
                'mentor' => 'Ir. Bambang Sutrisno, MCP',
                'schedule_time' => Carbon::now()->addDays(11)->setTime(18, 30),
                'zoom_link' => 'https://zoom.us/j/123456793',
                'drive_link' => 'https://drive.google.com/materials/excel-expert',
                'status' => 'upcoming'
            ]
        ];

        return collect($classesData)->map(function ($classData) {
            return ClassModel::create($classData);
        });
    }

    private function createTryouts()
    {
        $this->command->info('Creating tryouts...');
        
        $tryoutsData = [
            // SKD Tryouts
            [
                'name' => 'Tryout SKD #1 - TIU Focus',
                'description' => 'Tryout khusus untuk latihan soal TIU (Tes Intelegensi Umum)',
                'type_tryout' => 'tiu',
                'is_certification' => false,
                'is_toefl' => false,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(3),
                'is_active' => true
            ],
            [
                'name' => 'Tryout SKD #2 - TWK Focus',
                'description' => 'Tryout khusus untuk latihan soal TWK (Tes Wawasan Kebangsaan)',
                'type_tryout' => 'twk',
                'is_certification' => false,
                'is_toefl' => false,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(3),
                'is_active' => true
            ],
            [
                'name' => 'Tryout SKD #3 - TKP Focus',
                'description' => 'Tryout khusus untuk latihan soal TKP (Tes Karakteristik Pribadi)',
                'type_tryout' => 'tkp',
                'is_certification' => false,
                'is_toefl' => false,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(3),
                'is_active' => true
            ],
            [
                'name' => 'Simulasi SKD Lengkap',
                'description' => 'Simulasi lengkap SKD dengan format seperti ujian asli',
                'type_tryout' => 'skd_full',
                'is_certification' => false,
                'is_toefl' => false,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(3),
                'is_active' => true
            ],
            // TOEFL Tryouts
            [
                'name' => 'TOEFL Practice Test #1',
                'description' => 'Practice test TOEFL lengkap dengan listening, reading, dan structure',
                'type_tryout' => 'listening',
                'is_certification' => true,
                'is_toefl' => true,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(6),
                'is_active' => true
            ],
            [
                'name' => 'TOEFL Reading Comprehension',
                'description' => 'Focus pada section reading comprehension TOEFL',
                'type_tryout' => 'reading',
                'is_certification' => true,
                'is_toefl' => true,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(6),
                'is_active' => true
            ],
            // PPPK Tryouts
            [
                'name' => 'PPPK Guru - Kompetensi Teknis',
                'description' => 'Tryout untuk kompetensi teknis PPPK Guru',
                'type_tryout' => 'teknis',
                'is_certification' => false,
                'is_toefl' => false,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(4),
                'is_active' => true
            ],
            [
                'name' => 'PPPK Full Simulation',
                'description' => 'Simulasi lengkap PPPK dengan semua kompetensi',
                'type_tryout' => 'pppk_full',
                'is_certification' => false,
                'is_toefl' => false,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(4),
                'is_active' => true
            ],
            // Microsoft Office Certification
            [
                'name' => 'Microsoft Word Certification Test',
                'description' => 'Test sertifikasi Microsoft Word Specialist',
                'type_tryout' => 'word',
                'is_certification' => true,
                'is_toefl' => false,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(12),
                'is_active' => true
            ],
            [
                'name' => 'Microsoft Excel Expert Test',
                'description' => 'Test sertifikasi Microsoft Excel Expert',
                'type_tryout' => 'excel',
                'is_certification' => true,
                'is_toefl' => false,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(12),
                'is_active' => true
            ]
        ];

        return collect($tryoutsData)->map(function ($tryoutData) {
            return Tryout::create($tryoutData);
        });
    }

    private function createTryoutDetailsAndQuestions($tryouts)
    {
        $this->command->info('Creating tryout details and questions...');

        foreach ($tryouts as $tryout) {
            $this->createTryoutDetailsForTryout($tryout);
        }
    }

    private function createTryoutDetailsForTryout($tryout)
    {
        $tryoutDetailsConfig = $this->getTryoutDetailsConfig($tryout->type_tryout);

        foreach ($tryoutDetailsConfig as $detailConfig) {
            $tryoutDetail = TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => $detailConfig['type_subtest'],
                'duration' => $detailConfig['duration'],
                'passing_score' => $detailConfig['passing_score']
            ]);

            $this->createQuestionsForTryoutDetail($tryoutDetail, $detailConfig);
        }
    }

    private function getTryoutDetailsConfig($tryoutType)
    {
        $configs = [
            'tiu' => [
                ['type_subtest' => 'tiu', 'duration' => 35, 'question_count' => 35, 'passing_score' => 245],
            ],
            'twk' => [
                ['type_subtest' => 'twk', 'duration' => 35, 'question_count' => 35, 'passing_score' => 245],
            ],
            'tkp' => [
                ['type_subtest' => 'tkp', 'duration' => 40, 'question_count' => 45, 'passing_score' => 166],
            ],
            'skd_full' => [
                ['type_subtest' => 'tiu', 'duration' => 35, 'question_count' => 35, 'passing_score' => 245],
                ['type_subtest' => 'twk', 'duration' => 35, 'question_count' => 35, 'passing_score' => 245],
                ['type_subtest' => 'tkp', 'duration' => 40, 'question_count' => 45, 'passing_score' => 166],
            ],
            'listening' => [
                ['type_subtest' => 'listening', 'duration' => 30, 'question_count' => 30, 'passing_score' => 24],
            ],
            'reading' => [
                ['type_subtest' => 'reading', 'duration' => 55, 'question_count' => 40, 'passing_score' => 32],
            ],
            'teknis' => [
                ['type_subtest' => 'teknis', 'duration' => 60, 'question_count' => 40, 'passing_score' => 280],
            ],
            'pppk_full' => [
                ['type_subtest' => 'teknis', 'duration' => 60, 'question_count' => 40, 'passing_score' => 280],
                ['type_subtest' => 'management', 'duration' => 40, 'question_count' => 25, 'passing_score' => 175],
                ['type_subtest' => 'social culture', 'duration' => 35, 'question_count' => 20, 'passing_score' => 140],
            ],
            'word' => [
                ['type_subtest' => 'word', 'duration' => 50, 'question_count' => 30, 'passing_score' => 700],
            ],
            'excel' => [
                ['type_subtest' => 'excel', 'duration' => 50, 'question_count' => 30, 'passing_score' => 700],
            ]
        ];

        return $configs[$tryoutType] ?? [
            ['type_subtest' => 'general', 'duration' => 60, 'question_count' => 40, 'passing_score' => 70]
        ];
    }

    private function createQuestionsForTryoutDetail($tryoutDetail, $detailConfig)
    {
        $questionTemplates = $this->getQuestionTemplates($tryoutDetail->tryout->type_tryout);
        
        for ($i = 1; $i <= $detailConfig['question_count']; $i++) {
            $template = $questionTemplates[($i - 1) % count($questionTemplates)];
            
            $question = Question::create([
                'tryout_detail_id' => $tryoutDetail->tryout_detail_id,
                'question_text' => str_replace('[NUMBER]', $i, $template['question']),
                'question_type' => $template['type'],
                'default_weight' => $template['weight'],
                'explanation' => $template['explanation'] ?? null,
                'custom_score' => 'no',
                'sound' => null
            ]);

            // Create options for the question
            foreach ($template['options'] as $index => $optionText) {
                QuestionOption::create([
                    'question_id' => $question->question_id,
                    'option_text' => $optionText,
                    'is_correct' => $index === $template['correct_answer'],
                    'weight' => $index === $template['correct_answer'] ? $template['weight'] : 0
                ]);
            }
        }
    }

    private function getQuestionTemplates($tryoutType)
    {
        $templates = [
            'tiu' => [
                [
                    'question' => 'Soal [NUMBER]: Jika 2x + 3 = 11, maka nilai x adalah...',
                    'type' => 'multiple_choice',
                    'weight' => 5.00,
                    'options' => ['2', '3', '4', '5', '6'],
                    'correct_answer' => 2, // C (4)
                    'explanation' => '2x + 3 = 11, maka 2x = 8, sehingga x = 4'
                ],
                [
                    'question' => 'Soal [NUMBER]: Antonim dari kata "optimis" adalah...',
                    'type' => 'multiple_choice',
                    'weight' => 5.00,
                    'options' => ['Pesimis', 'Realistis', 'Idealis', 'Praktis', 'Positif'],
                    'correct_answer' => 0, // A (Pesimis)
                    'explanation' => 'Optimis artinya berpandangan baik, sedangkan pesimis artinya berpandangan buruk'
                ],
                [
                    'question' => 'Soal [NUMBER]: Deret angka 2, 6, 18, 54, ... angka selanjutnya adalah...',
                    'type' => 'multiple_choice',
                    'weight' => 5.00,
                    'options' => ['108', '162', '216', '324', '486'],
                    'correct_answer' => 1, // B (162)
                    'explanation' => 'Pola: dikali 3. 2×3=6, 6×3=18, 18×3=54, 54×3=162'
                ]
            ],
            'twk' => [
                [
                    'question' => 'Soal [NUMBER]: Pancasila sebagai dasar negara Indonesia ditetapkan pada tanggal...',
                    'type' => 'multiple_choice',
                    'weight' => 5.00,
                    'options' => ['17 Agustus 1945', '18 Agustus 1945', '1 Juni 1945', '22 Juni 1945', '29 Mei 1945'],
                    'correct_answer' => 1, // B (18 Agustus 1945)
                    'explanation' => 'Pancasila ditetapkan sebagai dasar negara pada tanggal 18 Agustus 1945'
                ],
                [
                    'question' => 'Soal [NUMBER]: Jumlah provinsi di Indonesia saat ini adalah...',
                    'type' => 'multiple_choice',
                    'weight' => 5.00,
                    'options' => ['32', '33', '34', '35', '36'],
                    'correct_answer' => 2, // C (34)
                    'explanation' => 'Indonesia memiliki 34 provinsi'
                ],
                [
                    'question' => 'Soal [NUMBER]: Lagu kebangsaan Indonesia adalah...',
                    'type' => 'multiple_choice',
                    'weight' => 5.00,
                    'options' => ['Garuda Pancasila', 'Indonesia Raya', 'Bagimu Negeri', 'Tanah Airku', 'Rayuan Pulau Kelapa'],
                    'correct_answer' => 1, // B (Indonesia Raya)
                    'explanation' => 'Indonesia Raya adalah lagu kebangsaan Republik Indonesia'
                ]
            ],
            'tkp' => [
                [
                    'question' => 'Soal [NUMBER]: Ketika rekan kerja melakukan kesalahan yang merugikan tim, sikap saya adalah...',
                    'type' => 'multiple_choice',
                    'weight' => 5.00,
                    'options' => [
                        'Mengkritik keras di depan umum',
                        'Melaporkan langsung kepada atasan',
                        'Memberikan masukan secara pribadi dengan baik',
                        'Tidak peduli karena bukan tanggung jawab saya',
                        'Membicarakan dengan rekan lain'
                    ],
                    'correct_answer' => 2, // C
                    'explanation' => 'Memberikan masukan secara pribadi menunjukkan sikap yang konstruktif'
                ],
                [
                    'question' => 'Soal [NUMBER]: Dalam menghadapi perbedaan pendapat, saya cenderung...',
                    'type' => 'multiple_choice',
                    'weight' => 5.00,
                    'options' => [
                        'Mempertahankan pendapat saya dengan tegas',
                        'Mengalah untuk menghindari konflik',
                        'Mendengarkan dan mencari solusi terbaik bersama',
                        'Meminta orang lain yang menentukan',
                        'Tidak mau terlibat dalam diskusi'
                    ],
                    'correct_answer' => 2, // C
                    'explanation' => 'Mendengarkan dan mencari solusi bersama menunjukkan sikap kolaboratif'
                ]
            ],
            'listening' => [
                [
                    'question' => 'Soal [NUMBER]: Listen to the conversation. What is the man\'s occupation?',
                    'type' => 'multiple_choice',
                    'weight' => 4.00,
                    'options' => ['Teacher', 'Doctor', 'Engineer', 'Lawyer', 'Businessman'],
                    'correct_answer' => 1, // B (Doctor)
                    'explanation' => 'From the audio, the man mentions working at a hospital'
                ]
            ],
            'reading' => [
                [
                    'question' => 'Soal [NUMBER]: According to the passage, the main cause of global warming is...',
                    'type' => 'multiple_choice',
                    'weight' => 4.00,
                    'options' => [
                        'Natural climate cycles',
                        'Greenhouse gas emissions',
                        'Solar radiation changes',
                        'Ocean current shifts',
                        'Volcanic activities'
                    ],
                    'correct_answer' => 1, // B
                    'explanation' => 'The passage clearly states that greenhouse gas emissions are the primary cause'
                ]
            ],
            'teknis' => [
                [
                    'question' => 'Soal [NUMBER]: Dalam kurikulum 2013, pendekatan pembelajaran yang digunakan adalah...',
                    'type' => 'multiple_choice',
                    'weight' => 4.00,
                    'options' => [
                        'Teacher centered',
                        'Student centered',
                        'Content centered',
                        'Technology centered',
                        'Assessment centered'
                    ],
                    'correct_answer' => 1, // B
                    'explanation' => 'Kurikulum 2013 menggunakan pendekatan student centered learning'
                ]
            ],
            'word' => [
                [
                    'question' => 'Soal [NUMBER]: Untuk membuat daftar isi otomatis di Microsoft Word, fitur yang digunakan adalah...',
                    'type' => 'multiple_choice',
                    'weight' => 4.00,
                    'options' => ['Index', 'Table of Contents', 'References', 'Citations', 'Bibliography'],
                    'correct_answer' => 1, // B
                    'explanation' => 'Table of Contents digunakan untuk membuat daftar isi otomatis'
                ]
            ],
            'excel' => [
                [
                    'question' => 'Soal [NUMBER]: Fungsi yang digunakan untuk menjumlahkan data berdasarkan kriteria tertentu adalah...',
                    'type' => 'multiple_choice',
                    'weight' => 4.00,
                    'options' => ['SUM', 'SUMIF', 'COUNT', 'AVERAGE', 'MAX'],
                    'correct_answer' => 1, // B
                    'explanation' => 'SUMIF digunakan untuk menjumlahkan data yang memenuhi kriteria tertentu'
                ]
            ]
        ];

        return $templates[$tryoutType] ?? $templates['tiu'];
    }

    private function createDetailPackages($packages, $tryouts, $classes)
    {
        $this->command->info('Creating detail packages (relationships)...');

        // CPNS Premium Package
        $cpnsPremium = $packages->where('name', 'CPNS Premium 2024')->first();
        $skdTryouts = $tryouts->whereIn('type_tryout', ['tiu', 'twk', 'tkp', 'skd_full']);
        $cpnsClasses = $classes->whereIn('title', ['Kelas TIU Intensif', 'Kelas TWK Fundamental', 'Kelas TKP Strategy']);

        foreach ($skdTryouts as $tryout) {
            DetailPackage::create([
                'package_id' => $cpnsPremium->package_id,
                'detailable_type' => Tryout::class,
                'detailable_id' => $tryout->tryout_id
            ]);
        }

        foreach ($cpnsClasses as $class) {
            DetailPackage::create([
                'package_id' => $cpnsPremium->package_id,
                'detailable_type' => ClassModel::class,
                'detailable_id' => $class->class_id
            ]);
        }

        // TOEFL Preparation Package
        $toeflPackage = $packages->where('name', 'TOEFL Preparation')->first();
        $toeflTryouts = $tryouts->whereIn('type_tryout', ['listening', 'reading']);
        $toeflClass = $classes->where('title', 'TOEFL Listening Master Class')->first();

        foreach ($toeflTryouts as $tryout) {
            DetailPackage::create([
                'package_id' => $toeflPackage->package_id,
                'detailable_type' => Tryout::class,
                'detailable_id' => $tryout->tryout_id
            ]);
        }

        DetailPackage::create([
            'package_id' => $toeflPackage->package_id,
            'detailable_type' => ClassModel::class,
            'detailable_id' => $toeflClass->class_id
        ]);

        // Free Tryout Package
        $freePackage = $packages->where('name', 'Tryout SKD Gratis')->first();
        $freeTryouts = $tryouts->whereIn('name', ['Tryout SKD #1 - TIU Focus', 'Tryout SKD #2 - TWK Focus', 'Simulasi SKD Lengkap']);

        foreach ($freeTryouts as $tryout) {
            DetailPackage::create([
                'package_id' => $freePackage->package_id,
                'detailable_type' => Tryout::class,
                'detailable_id' => $tryout->tryout_id
            ]);
        }

        // PPPK Package
        $pppkPackage = $packages->where('name', 'PPPK Guru Complete')->first();
        $pppkTryouts = $tryouts->whereIn('type_tryout', ['teknis', 'pppk_full']);

        foreach ($pppkTryouts as $tryout) {
            DetailPackage::create([
                'package_id' => $pppkPackage->package_id,
                'detailable_type' => Tryout::class,
                'detailable_id' => $tryout->tryout_id
            ]);
        }

        // Microsoft Office Package
        $msOfficePackage = $packages->where('name', 'Sertifikasi Microsoft Office')->first();
        $msOfficeTryouts = $tryouts->whereIn('type_tryout', ['word', 'excel']);
        $msOfficeClass = $classes->where('title', 'Microsoft Excel Expert')->first();

        foreach ($msOfficeTryouts as $tryout) {
            DetailPackage::create([
                'package_id' => $msOfficePackage->package_id,
                'detailable_type' => Tryout::class,
                'detailable_id' => $tryout->tryout_id
            ]);
        }

        DetailPackage::create([
            'package_id' => $msOfficePackage->package_id,
            'detailable_type' => ClassModel::class,
            'detailable_id' => $msOfficeClass->class_id
        ]);
    }

    private function createPaymentsAndAccess($users, $packages)
    {
        $this->command->info('Creating payments and user package access...');

        // Skip admin user (first user)
        $students = $users->skip(1);
        
        foreach ($students as $student) {
            // Each student buys 1-3 random packages
            $purchasedPackages = $packages->random(rand(1, 3));
            
            foreach ($purchasedPackages as $package) {
                $paymentDate = Carbon::now()->subDays(rand(1, 30));
                
                // Create payment record
                $payment = Payment::create([
                    'transaction_id' => 'TXN' . time() . rand(1000, 9999),
                    'user_id' => $student->id,
                    'package_id' => $package->package_id,
                    'amount' => $package->price,
                    'admin_fee' => 0,
                    'total_amount' => $package->price,
                    'status' => 'success',
                    'payment_method' => collect(['bank_transfer', 'gopay', 'ovo', 'dana'])->random(),
                    'paid_at' => $paymentDate,
                    'confirmed_at' => $paymentDate->addMinutes(5)
                ]);

                // Create user package access
                UserPackageAcces::create([
                    'user_id' => $student->id,
                    'package_id' => $package->package_id,
                    'start_date' => $paymentDate,
                    'end_date' => $paymentDate->addMonths(6),
                    'status' => 'active',
                    'payment_amount' => $package->price,
                    'payment_status' => 'paid'
                ]);
            }
        }
    }

    private function createUserAnswers($users, $tryouts)
    {
        $this->command->info('Creating user answers (simulating tryout attempts)...');

        // Skip admin user
        $students = $users->skip(1);
        
        foreach ($students as $student) {
            // Each student attempts 2-5 random tryouts
            $attemptedTryouts = $tryouts->random(rand(2, 5));
            
            foreach ($attemptedTryouts as $tryout) {
                // Check if user has access to this tryout through packages
                $hasAccess = $this->checkUserTryoutAccess($student, $tryout);
                
                if ($hasAccess) {
                    $this->createUserAnswerForTryout($student, $tryout);
                }
            }
        }
    }

    private function checkUserTryoutAccess($user, $tryout)
    {
        // Check if user has access through purchased packages
        $userPackages = UserPackageAcces::where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('package_id');
            
        $tryoutPackages = DetailPackage::where('detailable_type', Tryout::class)
            ->where('detailable_id', $tryout->tryout_id)
            ->pluck('package_id');
            
        return $userPackages->intersect($tryoutPackages)->isNotEmpty();
    }

    private function createUserAnswerForTryout($user, $tryout)
    {
        $startTime = Carbon::now()->subDays(rand(1, 20))->subHours(rand(1, 12));
        
        // Get the first tryout detail for this tryout
        $tryoutDetail = $tryout->tryoutDetails()->first();
        if (!$tryoutDetail) return;
        
        $attemptToken = 'ATT-' . time() . rand(1000, 9999);
        
        // Check structure of user_answers table based on migration
        $userAnswer = UserAnswer::create([
            'user_id' => $user->id,
            'tryout_id' => $tryout->tryout_id,
            'tryout_detail_id' => $tryoutDetail->tryout_detail_id,
            'attempt_token' => $attemptToken,
            'started_at' => $startTime,
            'finished_at' => $startTime->addMinutes($tryoutDetail->duration),
            'correct_answers' => 0,
            'wrong_answers' => 0,
            'unanswered' => 0,
            'score' => 0,
            'is_passed' => false,
            'status' => 'completed'
        ]);

        $correctAnswers = 0;
        $wrongAnswers = 0;
        $totalScore = 0;

        // Create answer details for questions
        $questions = $tryoutDetail->questions()->get();
        foreach ($questions as $question) {
            $options = $question->questionOptions;
            if ($options->isEmpty()) continue;
            
            // Simulate answering with 70% accuracy
            $isCorrect = rand(1, 100) <= 70;
            
            if ($isCorrect) {
                $selectedOption = $options->where('is_correct', true)->first();
                $correctAnswers++;
                $totalScore += $selectedOption ? $selectedOption->weight : 0;
            } else {
                $selectedOption = $options->where('is_correct', false)->first();
                $wrongAnswers++;
            }
            
            if ($selectedOption) {
                UserAnswerDetail::create([
                    'user_answer_id' => $userAnswer->user_answer_id,
                    'question_id' => $question->question_id,
                    'question_option_id' => $selectedOption->question_option_id,
                    'is_correct' => $isCorrect,
                    'answered_at' => $startTime->addMinutes(rand(1, 5))
                ]);
            }
        }

        // Update user answer with calculated values
        $userAnswer->update([
            'correct_answers' => $correctAnswers,
            'wrong_answers' => $wrongAnswers,
            'unanswered' => $questions->count() - $correctAnswers - $wrongAnswers,
            'score' => $totalScore,
            'is_passed' => $totalScore >= $tryoutDetail->passing_score
        ]);
    }

    private function simulateAnswerAccuracy($question, $tryoutType)
    {
        // Different accuracy rates based on tryout type and question difficulty
        $accuracyRates = [
            'tiu' => 0.65,      // Math/logic questions are harder
            'twk' => 0.75,      // Knowledge-based, higher accuracy
            'tkp' => 0.80,      // Personality questions, highest accuracy
            'skd_full' => 0.70, // Mixed difficulty
            'listening' => 0.60, // TOEFL listening is challenging
            'reading' => 0.70,   // TOEFL reading
            'teknis' => 0.65,    // Technical questions
            'pppk_full' => 0.68, // Mixed PPPK
            'word' => 0.75,      // MS Office applications
            'excel' => 0.70,
            'default' => 0.70
        ];

        $baseAccuracy = $accuracyRates[$tryoutType] ?? $accuracyRates['default'];
        
        // Add some randomness
        $randomFactor = (rand(70, 130) / 100); // 0.7 to 1.3 multiplier
        $finalAccuracy = min(0.95, $baseAccuracy * $randomFactor); // Cap at 95%
        
        return rand(1, 100) <= ($finalAccuracy * 100);
    }

    private function createCertificatesAndLeaderboards($users, $tryouts)
    {
        $this->command->info('Creating certificates and leaderboards...');

        // Create leaderboards for each tryout
        foreach ($tryouts as $tryout) {
            $userAnswers = UserAnswer::where('tryout_id', $tryout->tryout_id)
                ->where('status', 'completed')
                ->orderBy('score', 'desc')
                ->get();

            $rank = 1;
            foreach ($userAnswers as $userAnswer) {
                Leaderboard::create([
                    'user_id' => $userAnswer->user_id,
                    'tryout_id' => $tryout->tryout_id,
                    'attempt_token' => $userAnswer->attempt_token,
                    'total_score' => $userAnswer->score,
                    'total_correct' => $userAnswer->correct_answers,
                    'total_questions' => $userAnswer->correct_answers + $userAnswer->wrong_answers + $userAnswer->unanswered,
                    'rank' => $rank,
                    'completed_at' => $userAnswer->finished_at
                ]);
                $rank++;
            }
        }

        // Create simple certificates for certification tryouts
        $certificationTryouts = $tryouts->where('is_certification', true);
        
        foreach ($certificationTryouts as $tryout) {
            $passedAnswers = UserAnswer::where('tryout_id', $tryout->tryout_id)
                ->where('is_passed', true)
                ->get();

            foreach ($passedAnswers as $userAnswer) {
                $user = $userAnswer->user;
                Certificate::create([
                    'certificate_number' => 'CERT-' . time() . rand(100, 999),
                    'certificate_name' => $tryout->name . ' Certificate',
                    'date_of_birth' => $user->birthday ?? Carbon::now()->subYears(25),
                    'description' => 'Certificate for completing ' . $tryout->name,
                    'issued_date' => $userAnswer->finished_at->format('Y-m-d'),
                    'status' => 'active',
                    'verification_code' => substr(md5($userAnswer->user_answer_id), 0, 32),
                    'issued_by' => 1, // Admin user ID
                    'tryout_id' => $tryout->tryout_id
                ]);
            }
        }
    }

    private function calculateGrade($score, $passingScore)
    {
        $percentage = ($score / $passingScore) * 100;
        
        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        return 'E';
    }
}
