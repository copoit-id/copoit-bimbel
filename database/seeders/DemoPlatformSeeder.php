<?php

namespace Database\Seeders;

use App\Models\AffiliateCommission;
use App\Models\AffiliateSetting;
use App\Models\DetailPackage;
use App\Models\Discount;
use App\Models\Expense;
use App\Models\Leaderboard;
use App\Models\MaterialCategory;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Models\User;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use App\Models\UserPackageAcces;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Populates a representative, repeatable local/demo dataset.
 *
 * This seeder deliberately uses the @bimbelhub.test domain so demo accounts
 * are unmistakable and cannot be confused with production accounts.
 */
class DemoPlatformSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = $this->seedUsers();
            $categories = MaterialCategory::query()->pluck('category_id', 'code');
            $tryouts = $this->seedTryouts($admin, $categories->all());
            $packages = $this->seedPackages($tryouts);
            $discounts = $this->seedDiscounts($packages, $tryouts);
            $this->seedPaymentsAndAccess($admin, $packages, $discounts);
            $this->seedAffiliateData($admin, $packages);
            $this->seedExpenses($admin);
            $this->seedExtendedModules($admin, $packages, $tryouts, $discounts);
            $this->seedAttempts($tryouts);
        });

        $this->command->info('Demo platform data seeded: accounts, learning, tryouts, packages, sales, affiliate, finance, classes, tutor, Tes Koran, billing, and public content.');
    }

    private function seedUsers(): User
    {
        $accounts = [
            ['name' => 'Demo Administrator', 'username' => 'demo_admin', 'email' => 'demo.admin@bimbelhub.test', 'role' => 'admin', 'affiliate_code' => null],
            ['name' => 'Nadia Affiliate', 'username' => 'nadia_affiliate', 'email' => 'nadia.affiliate@bimbelhub.test', 'role' => 'user', 'affiliate_code' => 'NADIAUTBK'],
            ['name' => 'Raka Affiliate', 'username' => 'raka_affiliate', 'email' => 'raka.affiliate@bimbelhub.test', 'role' => 'user', 'affiliate_code' => 'RAKASKD'],
            ['name' => 'Aulia Putri', 'username' => 'aulia_putri', 'email' => 'aulia.putri@bimbelhub.test', 'role' => 'user', 'affiliate_code' => null],
            ['name' => 'Bagas Pratama', 'username' => 'bagas_pratama', 'email' => 'bagas.pratama@bimbelhub.test', 'role' => 'user', 'affiliate_code' => null],
            ['name' => 'Citra Lestari', 'username' => 'citra_lestari', 'email' => 'citra.lestari@bimbelhub.test', 'role' => 'user', 'affiliate_code' => null],
            ['name' => 'Dimas Saputra', 'username' => 'dimas_saputra', 'email' => 'dimas.saputra@bimbelhub.test', 'role' => 'user', 'affiliate_code' => null],
            ['name' => 'Eka Rahma', 'username' => 'eka_rahma', 'email' => 'eka.rahma@bimbelhub.test', 'role' => 'user', 'affiliate_code' => null],
            ['name' => 'Farhan Akbar', 'username' => 'farhan_akbar', 'email' => 'farhan.akbar@bimbelhub.test', 'role' => 'user', 'affiliate_code' => null],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    ...$account,
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'status' => 'aktif',
                    'email_verified_at' => now(),
                ]
            );
        }

        $nadia = User::where('email', 'nadia.affiliate@bimbelhub.test')->firstOrFail();
        $raka = User::where('email', 'raka.affiliate@bimbelhub.test')->firstOrFail();

        User::whereIn('email', ['aulia.putri@bimbelhub.test', 'citra.lestari@bimbelhub.test', 'eka.rahma@bimbelhub.test'])
            ->update(['referred_by_user_id' => $nadia->id, 'referred_at' => now()->subMonths(2)]);
        User::whereIn('email', ['bagas.pratama@bimbelhub.test', 'dimas.saputra@bimbelhub.test'])
            ->update(['referred_by_user_id' => $raka->id, 'referred_at' => now()->subMonth()]);

        return User::where('email', 'demo.admin@bimbelhub.test')->firstOrFail();
    }

    /** @param array<string, int> $categories */
    private function seedTryouts(User $admin, array $categories): array
    {
        $definitions = [
            'utbk' => [
                'name' => 'UTBK TPS 2026 — Simulasi Nasional', 'type' => 'utbk_full', 'category' => 'utbk_full', 'price' => 79000,
                'description' => 'Simulasi UTBK TPS dengan tujuh subtes, analisis skor, dan leaderboard nasional.',
                'subtests' => ['penalaran_umum' => 30, 'pengetahuan_kuantitatif' => 25, 'pengetahuan_umum' => 20, 'pemahaman_bacaan_menulis' => 25, 'literasi_bahasa_indonesia' => 25, 'literasi_bahasa_inggris' => 25, 'penalaran_matematika' => 30],
            ],
            'skd' => [
                'name' => 'SKD CPNS 2026 — Simulasi CAT', 'type' => 'skd_full', 'category' => 'skd_full', 'price' => 69000,
                'description' => 'Latihan CAT SKD yang mencakup TWK, TIU, dan TKP.',
                'subtests' => ['twk' => 30, 'tiu' => 35, 'tkp' => 40],
            ],
            'toefl' => [
                'name' => 'TOEFL ITP Prediction Test', 'type' => 'certification', 'category' => 'certification', 'price' => 99000,
                'description' => 'Tryout TOEFL ITP prediction untuk mengukur kesiapan Listening, Structure, dan Reading.',
                'subtests' => ['listening' => 25, 'writing' => 25, 'reading' => 30],
            ],
            'general' => [
                'name' => 'Tes Potensi Akademik — Umum', 'type' => 'general', 'category' => 'general', 'price' => 49000,
                'description' => 'Tes umum untuk mengukur penalaran verbal dan numerik.',
                'subtests' => ['general' => 45, 'tpa' => 30],
            ],
        ];

        $tryouts = [];
        foreach ($definitions as $key => $definition) {
            $tryout = Tryout::updateOrCreate(
                ['name' => $definition['name']],
                [
                    'description' => $definition['description'],
                    'type_tryout' => $definition['type'],
                    'material_category_id' => $categories[$definition['category']] ?? null,
                    'assessment_type' => 'standard',
                    'is_certification' => $key === 'toefl',
                    'is_toefl' => $key === 'toefl',
                    'scoring_method' => match ($key) {
                        'utbk' => 'irt_utbk',
                        'toefl' => 'toefl_itp',
                        default => 'normal',
                    },
                    'start_date' => now()->subDays(14),
                    'end_date' => now()->addMonths(6),
                    'price' => $definition['price'],
                    'is_for_sale' => true,
                    'type_price' => 'paid',
                    'is_displayed' => true,
                    'is_active' => true,
                    'show_discussion' => true,
                    'show_leaderboard' => true,
                    'show_result_scores' => true,
                    'show_score_maximum' => true,
                    'show_passing_grade' => true,
                    'result_score_display' => 'total_and_subtest',
                    'result_score_scale' => 'raw',
                    'max_attempts' => 3,
                    'answer_persistence_mode' => 'hybrid_subtest',
                    'subtest_display_mode' => 'per_subtest',
                    'user_card_display' => 'icon',
                    'created_by' => $admin->id,
                ]
            );

            foreach ($definition['subtests'] as $subtest => $duration) {
                $detail = TryoutDetail::updateOrCreate(
                    ['tryout_id' => $tryout->tryout_id, 'type_subtest' => $subtest],
                    ['material_category_id' => $categories[$subtest] ?? null, 'duration' => $duration, 'passing_score' => 60, 'passing_type' => 'score']
                );
                $this->seedQuestions($detail, $subtest);
            }
            $tryouts[$key] = $tryout;
        }

        return $tryouts;
    }

    private function seedQuestions(TryoutDetail $detail, string $subtest): void
    {
        for ($number = 1; $number <= 5; $number++) {
            $question = Question::updateOrCreate(
                ['tryout_detail_id' => $detail->tryout_detail_id, 'question_text' => sprintf('[DEMO] %s — soal nomor %d', strtoupper($subtest), $number)],
                [
                    'question_type' => 'multiple_choice',
                    'explanation' => 'Pembahasan demo: pilih opsi yang paling tepat berdasarkan konsep pada subtes ini.',
                    'default_weight' => 1,
                    'custom_score' => 'no',
                ]
            );

            foreach (['A', 'B', 'C', 'D'] as $index => $label) {
                QuestionOption::updateOrCreate(
                    ['question_id' => $question->question_id, 'option_text' => "Pilihan {$label}"],
                    ['weight' => $index === 0 ? 1 : 0, 'is_correct' => $index === 0]
                );
            }
        }
    }

    /** @param array<string, Tryout> $tryouts */
    private function seedPackages(array $tryouts): array
    {
        $definitions = [
            'utbk' => ['name' => 'Paket UTBK Intensif 2026', 'price' => 249000, 'tryouts' => ['utbk', 'general'], 'features' => ['2 tryout', 'Analisis skor UTBK', 'Akses 90 hari']],
            'skd' => ['name' => 'Paket SKD CPNS Siap CAT', 'price' => 199000, 'tryouts' => ['skd', 'general'], 'features' => ['SKD lengkap', 'Tryout TPA', 'Akses 60 hari']],
            'toefl' => ['name' => 'Paket TOEFL Prediction', 'price' => 149000, 'tryouts' => ['toefl'], 'features' => ['TOEFL prediction', 'Hasil per section', 'Akses 30 hari']],
            'bundle' => ['name' => 'Paket Persiapan Lengkap', 'price' => 399000, 'tryouts' => ['utbk', 'skd', 'toefl', 'general'], 'features' => ['Semua tryout', 'Hemat hingga 35%', 'Akses 120 hari']],
        ];

        $packages = [];
        foreach ($definitions as $key => $definition) {
            $package = Package::updateOrCreate(
                ['name' => $definition['name']],
                [
                    'price' => $definition['price'], 'type_package' => 'tryout', 'type_price' => 'paid', 'status' => 'active',
                    'is_displayed' => true, 'description' => 'Paket demo ' . strtolower($definition['name']) . ' untuk eksplorasi fitur penjualan dan akses peserta.',
                    'features' => json_encode($definition['features']), 'access_duration_value' => $key === 'bundle' ? 120 : ($key === 'utbk' ? 90 : 60), 'access_duration_unit' => 'day',
                ]
            );

            foreach ($definition['tryouts'] as $order => $tryoutKey) {
                DetailPackage::updateOrCreate(
                    ['package_id' => $package->package_id, 'detailable_type' => Tryout::class, 'detailable_id' => $tryouts[$tryoutKey]->tryout_id],
                    ['order' => $order + 1]
                );
            }
            $packages[$key] = $package;
        }

        return $packages;
    }

    /** @param array<string, Package> $packages @param array<string, Tryout> $tryouts */
    private function seedDiscounts(array $packages, array $tryouts): array
    {
        $definitions = [
            'utbk' => ['code' => 'UTBKHEBAT', 'name' => 'Voucher UTBK Hebat', 'type' => 'percent', 'value' => 20, 'max' => 50000, 'packages' => ['utbk', 'bundle'], 'used' => 2],
            'skd' => ['code' => 'SKDSIAP', 'name' => 'Voucher SKD Siap CAT', 'type' => 'fixed', 'value' => 30000, 'max' => null, 'packages' => ['skd'], 'used' => 1],
            'toefl' => ['code' => 'TOEFL15', 'name' => 'Voucher TOEFL 15%', 'type' => 'percent', 'value' => 15, 'max' => 25000, 'packages' => ['toefl'], 'used' => 1],
        ];

        $discounts = [];
        foreach ($definitions as $key => $definition) {
            $discounts[$key] = Discount::updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'], 'description' => 'Voucher demo aktif untuk pembelian paket terkait.',
                    'application_type' => Discount::TYPE_VOUCHER, 'discount_type' => $definition['type'], 'discount_value' => $definition['value'],
                    'max_discount_amount' => $definition['max'], 'min_purchase_amount' => 0, 'usage_limit' => 100, 'used_count' => $definition['used'],
                    'per_user_limit' => 1, 'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonths(3), 'is_active' => true, 'is_public' => true,
                    'applicable_package_ids' => array_map(fn (string $package) => $packages[$package]->package_id, $definition['packages']),
                    'applicable_purchase_types' => ['package'],
                ]
            );
        }

        Discount::updateOrCreate(
            ['code' => 'AUTO-TRYOUT-DEMO'],
            [
                'name' => 'Promo Otomatis Tryout UTBK', 'description' => 'Diskon otomatis demo untuk pembelian tryout UTBK satuan.',
                'application_type' => Discount::TYPE_PACKAGE_TRYOUT, 'tryout_id' => $tryouts['utbk']->tryout_id,
                'discount_type' => 'percent', 'discount_value' => 10, 'max_discount_amount' => 10000, 'min_purchase_amount' => 50000,
                'starts_at' => now()->subWeek(), 'ends_at' => now()->addMonth(), 'is_active' => true, 'is_public' => true,
                'applicable_tryout_ids' => [$tryouts['utbk']->tryout_id], 'applicable_purchase_types' => ['tryout'],
            ]
        );

        return $discounts;
    }

    /** @param array<string, Package> $packages @param array<string, Discount> $discounts */
    private function seedPaymentsAndAccess(User $admin, array $packages, array $discounts): void
    {
        $payments = [
            ['transaction' => 'DEMO-UTBK-001', 'email' => 'aulia.putri@bimbelhub.test', 'package' => 'utbk', 'discount' => 'utbk', 'status' => 'success', 'months' => 3],
            ['transaction' => 'DEMO-SKD-001', 'email' => 'bagas.pratama@bimbelhub.test', 'package' => 'skd', 'discount' => 'skd', 'status' => 'success', 'months' => 2],
            ['transaction' => 'DEMO-TOEFL-001', 'email' => 'citra.lestari@bimbelhub.test', 'package' => 'toefl', 'discount' => 'toefl', 'status' => 'success', 'months' => 2],
            ['transaction' => 'DEMO-BUNDLE-001', 'email' => 'dimas.saputra@bimbelhub.test', 'package' => 'bundle', 'discount' => 'utbk', 'status' => 'success', 'months' => 1],
            ['transaction' => 'DEMO-UTBK-002', 'email' => 'eka.rahma@bimbelhub.test', 'package' => 'utbk', 'discount' => null, 'status' => 'success', 'months' => 1],
            ['transaction' => 'DEMO-SKD-002', 'email' => 'farhan.akbar@bimbelhub.test', 'package' => 'skd', 'discount' => null, 'status' => 'pending', 'months' => 0],
        ];

        foreach ($payments as $row) {
            $user = User::where('email', $row['email'])->firstOrFail();
            $package = $packages[$row['package']];
            $discount = $row['discount'] ? $discounts[$row['discount']] : null;
            $original = (int) $package->price;
            $discountAmount = $discount ? $discount->calculateDiscountAmount($original) : 0;
            $paidAt = now()->subMonths($row['months'])->subDays(3);

            $payment = Payment::updateOrCreate(
                ['transaction_id' => $row['transaction']],
                [
                    'user_id' => $user->id, 'package_id' => $package->package_id, 'discount_id' => $discount?->id, 'discount_code' => $discount?->code,
                    'original_amount' => $original, 'discount_amount' => $discountAmount, 'amount' => $original - $discountAmount, 'admin_fee' => 0,
                    'total_amount' => $original - $discountAmount, 'status' => $row['status'], 'payment_method' => 'manual',
                    'payment_details' => json_encode(['manual' => true, 'bank_name' => 'Bank BCA', 'account_name' => $user->name]),
                    'paid_at' => $row['status'] === 'success' ? $paidAt : null, 'confirmed_by' => $row['status'] === 'success' ? $admin->id : null,
                    'confirmed_at' => $row['status'] === 'success' ? $paidAt->copy()->addMinutes(10) : null,
                    'notes' => 'Transaksi data demo.',
                ]
            );

            UserPackageAcces::updateOrCreate(
                ['user_id' => $user->id, 'package_id' => $package->package_id],
                [
                    'start_date' => $paidAt, 'end_date' => $paidAt->copy()->addDays((int) $package->access_duration_value),
                    'status' => $row['status'] === 'success' ? 'active' : 'suspended', 'payment_amount' => $payment->total_amount,
                    'payment_status' => $row['status'] === 'success' ? 'paid' : 'pending', 'notes' => 'Akses paket dari seeder demo.', 'created_by' => $admin->id,
                ]
            );
        }
    }

    /** @param array<string, Package> $packages */
    private function seedAffiliateData(User $admin, array $packages): void
    {
        AffiliateSetting::query()->firstOrCreate([], [
            'is_active' => true, 'commission_type' => 'percent', 'commission_value' => 10,
            'invitee_discount_enabled' => true, 'invitee_discount_type' => 'percent', 'invitee_discount_value' => 5,
            'invitee_max_discount_amount' => 25000,
        ])->update(['is_active' => true, 'commission_type' => 'percent', 'commission_value' => 10, 'invitee_discount_enabled' => true, 'invitee_discount_type' => 'percent', 'invitee_discount_value' => 5, 'invitee_max_discount_amount' => 25000]);

        $commissionRows = [
            ['affiliate' => 'nadia.affiliate@bimbelhub.test', 'referred' => 'aulia.putri@bimbelhub.test', 'transaction' => 'DEMO-UTBK-001', 'package' => 'utbk', 'status' => 'paid'],
            ['affiliate' => 'raka.affiliate@bimbelhub.test', 'referred' => 'bagas.pratama@bimbelhub.test', 'transaction' => 'DEMO-SKD-001', 'package' => 'skd', 'status' => 'approved'],
            ['affiliate' => 'nadia.affiliate@bimbelhub.test', 'referred' => 'citra.lestari@bimbelhub.test', 'transaction' => 'DEMO-TOEFL-001', 'package' => 'toefl', 'status' => 'pending'],
        ];

        foreach ($commissionRows as $row) {
            $payment = Payment::where('transaction_id', $row['transaction'])->firstOrFail();
            $baseAmount = (int) $payment->total_amount;
            AffiliateCommission::updateOrCreate(
                ['payment_id' => $payment->payment_id],
                [
                    'affiliate_user_id' => User::where('email', $row['affiliate'])->value('id'), 'referred_user_id' => User::where('email', $row['referred'])->value('id'),
                    'package_id' => $packages[$row['package']]->package_id, 'commission_type' => 'percent', 'commission_value' => 10,
                    'base_amount' => $baseAmount, 'commission_amount' => (int) floor($baseAmount * 0.1), 'status' => $row['status'],
                    'approved_at' => $row['status'] !== 'pending' ? now()->subDays(10) : null, 'approved_by' => $row['status'] !== 'pending' ? $admin->id : null,
                    'paid_at' => $row['status'] === 'paid' ? now()->subDays(5) : null, 'paid_by' => $row['status'] === 'paid' ? $admin->id : null,
                    'notes' => 'Komisi afiliasi data demo.',
                ]
            );
        }
    }

    private function seedExpenses(User $admin): void
    {
        $expenses = [
            ['title' => 'Honor penyusun soal UTBK', 'amount' => 1750000, 'days' => 70],
            ['title' => 'Iklan digital kampanye UTBK', 'amount' => 1250000, 'days' => 54],
            ['title' => 'Langganan server dan database', 'amount' => 650000, 'days' => 38],
            ['title' => 'Honor reviewer soal SKD', 'amount' => 900000, 'days' => 22],
            ['title' => 'Desain materi promosi TOEFL', 'amount' => 450000, 'days' => 12],
        ];

        foreach ($expenses as $expense) {
            $spentAt = now()->subDays($expense['days']);
            Expense::updateOrCreate(
                ['title' => $expense['title']],
                ['amount' => $expense['amount'], 'notes' => 'Pengeluaran operasional data demo.', 'created_by' => $admin->id]
            );
        }
    }

    /** @param array<string, Package> $packages @param array<string, Tryout> $tryouts @param array<string, Discount> $discounts */
    private function seedExtendedModules(User $admin, array $packages, array $tryouts, array $discounts): void
    {
        $aulia = User::where('email', 'aulia.putri@bimbelhub.test')->firstOrFail();
        $bagas = User::where('email', 'bagas.pratama@bimbelhub.test')->firstOrFail();

        $tentor = $this->upsert('tentors', ['email' => 'ratih.tentor@bimbelhub.test'], [
            'name' => 'Ratih Wulandari', 'phone' => '081234567891', 'expertise' => 'UTBK dan Literasi Bahasa Indonesia',
            'bio' => 'Tentor demo untuk kelas persiapan UTBK.', 'is_active' => true, 'honor_per_attendance' => 150000,
            'education' => 'S.Pd. Bahasa Indonesia', 'experience_years' => 6, 'experience' => 'Mengajar persiapan UTBK sejak 2020.',
            'certifications' => json_encode(['Sertifikasi pengajar UTBK']), 'teaching_method' => 'Diskusi konsep dan bedah soal.',
        ]);

        $materialRows = [
            ['title' => 'Strategi Penalaran Umum UTBK', 'type' => 'video', 'duration' => 45, 'price' => 29000, 'category' => 'penalaran_umum'],
            ['title' => 'Ringkasan TWK CPNS', 'type' => 'document', 'duration' => 30, 'price' => 19000, 'category' => 'twk'],
            ['title' => 'TOEFL Structure Essentials', 'type' => 'video', 'duration' => 50, 'price' => 39000, 'category' => 'writing'],
            ['title' => 'Latihan TPA Numerik', 'type' => 'document', 'duration' => 35, 'price' => 15000, 'category' => 'tpa'],
        ];
        $materials = [];
        foreach ($materialRows as $order => $row) {
            $material = $this->upsert('materials', ['title' => $row['title']], [
                'description' => 'Materi pembelajaran demo untuk melengkapi paket persiapan.', 'type' => $row['type'],
                'content_url' => 'https://example.test/material/' . ($order + 1), 'duration_minutes' => $row['duration'],
                'is_active' => true, 'order_number' => $order + 1, 'price' => $row['price'], 'is_for_sale' => true,
                'type_price' => 'paid', 'is_displayed' => true, 'metadata' => json_encode(['demo' => true]),
                'created_by' => $admin->id, 'access_duration_value' => 30, 'access_duration_unit' => 'day',
            ]);
            $categoryId = DB::table('material_categories')->where('code', $row['category'])->value('category_id');
            if ($categoryId) {
                DB::table('material_category_pivot')->updateOrInsert(
                    ['material_id' => $material->material_id, 'category_id' => $categoryId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
            DB::table('package_materials')->updateOrInsert(
                ['package_id' => $packages[$order === 1 ? 'skd' : ($order === 2 ? 'toefl' : 'utbk')]->package_id, 'material_id' => $material->material_id],
                ['section_name' => 'Materi Utama', 'order_number' => $order + 1, 'is_required' => $order === 0, 'unlock_condition' => null, 'created_at' => now(), 'updated_at' => now()]
            );
            $materials[] = $material;
        }

        DB::table('user_material_access')->updateOrInsert(
            ['user_id' => $aulia->id, 'material_id' => $materials[0]->material_id],
            ['access_type' => 'purchased', 'access_source' => 'package', 'source_id' => $packages['utbk']->package_id, 'started_at' => now()->subWeek(), 'progress_percentage' => 75, 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('material_progress_logs')->updateOrInsert(
            ['user_id' => $aulia->id, 'material_id' => $materials[0]->material_id, 'event_type' => 'viewed'],
            ['progress_seconds' => 2025, 'metadata' => json_encode(['progress_percentage' => 75]), 'created_at' => now(), 'updated_at' => now()]
        );

        $class = $this->upsert('classes', ['title' => 'Kelas Intensif UTBK — Penalaran Umum'], [
            'schedule_time' => now()->addDays(2), 'mentor' => $tentor->name, 'tentor_id' => $tentor->id,
            'zoom_link' => 'https://example.test/kelas-utbk', 'status' => 'upcoming', 'price' => 129000,
            'is_for_sale' => true, 'is_displayed' => true, 'type_price' => 'paid', 'access_duration_value' => 60, 'access_duration_unit' => 'day',
        ]);
        DetailPackage::updateOrCreate(
            ['package_id' => $packages['utbk']->package_id, 'detailable_type' => \App\Models\ClassModel::class, 'detailable_id' => $class->class_id],
            ['order' => 10]
        );
        $group = $this->upsert('study_groups', ['invite_code' => 'UTBKDEMO26'], [
            'name' => 'Kelompok Belajar UTBK Demo', 'tentor_id' => $tentor->id, 'description' => 'Kelompok belajar peserta paket UTBK.',
            'is_active' => true, 'package_id' => $packages['utbk']->package_id, 'organizer_user_id' => $aulia->id,
            'target_participants' => 5, 'unit_price_snapshot' => 249000, 'status' => 'active', 'expires_at' => now()->addMonths(2),
        ]);
        foreach ([$aulia, $bagas] as $index => $student) {
            DB::table('study_group_user')->updateOrInsert(
                ['study_group_id' => $group->id, 'user_id' => $student->id],
                ['role' => $index === 0 ? 'organizer' : 'member', 'status' => 'paid', 'unit_price_snapshot' => 249000, 'paid_at' => now()->subDays(8), 'created_at' => now(), 'updated_at' => now()]
            );
        }
        $schedule = $this->upsert('class_schedules', ['class_id' => $class->class_id, 'title' => 'Bedah Soal Penalaran Umum'], [
            'study_group_id' => $group->id, 'tentor_id' => $tentor->id, 'schedule_type' => 'recurring', 'frequency' => 'weekly', 'day_of_week' => 6,
            'start_time' => '09:00:00', 'end_time' => '10:30:00', 'start_date' => now()->subWeek()->toDateString(), 'meeting_url' => 'https://example.test/kelas-utbk',
            'is_active' => true, 'created_by' => $admin->id, 'allow_custom_booking' => false, 'booking_session_quota' => 20,
        ]);
        $session = $this->upsert('class_sessions', ['class_schedule_id' => $schedule->id, 'session_date' => now()->subDays(3)->toDateString(), 'start_at' => now()->subDays(3)->setTime(9, 0)->toDateTimeString()], [
            'class_id' => $class->class_id, 'study_group_id' => $group->id, 'tentor_id' => $tentor->id, 'end_at' => now()->subDays(3)->setTime(10, 30)->toDateTimeString(),
            'status' => 'completed', 'meeting_url' => 'https://example.test/kelas-utbk', 'notes' => 'Sesi demo telah selesai.',
        ]);
        DB::table('user_class_access')->updateOrInsert(
            ['user_id' => $aulia->id, 'class_id' => $class->class_id],
            ['access_type' => 'package', 'access_source' => 'package', 'source_id' => $packages['utbk']->package_id, 'status' => 'active', 'started_at' => now()->subWeek(), 'expires_at' => now()->addMonths(2), 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('class_attendances')->updateOrInsert(
            ['class_session_id' => $session->id, 'user_id' => $aulia->id],
            ['status' => 'present', 'check_in_at' => now()->subDays(3)->setTime(9, 2), 'source' => 'user', 'marked_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]
        );
        $tutorAttendance = $this->upsert('tutor_attendances', ['class_session_id' => $session->id, 'tentor_id' => $tentor->id], [
            'status' => 'present', 'approval_status' => 'approved', 'check_in_at' => now()->subDays(3)->setTime(8, 55), 'check_out_at' => now()->subDays(3)->setTime(10, 32),
            'source' => 'admin', 'marked_by' => $admin->id, 'approved_by' => $admin->id, 'approved_at' => now()->subDays(2),
        ]);
        $payroll = $this->upsert('tutor_payrolls', ['tentor_id' => $tentor->id, 'period_start' => now()->startOfMonth()->toDateString(), 'period_end' => now()->endOfMonth()->toDateString()], [
            'rate_per_attendance' => 150000, 'gross_amount' => 150000, 'adjustment_amount' => 0, 'net_amount' => 150000, 'status' => 'paid',
            'notes' => 'Payroll tentor demo.', 'generated_by' => $admin->id, 'paid_by' => $admin->id, 'paid_at' => now()->subDay(),
        ]);
        DB::table('tutor_payroll_items')->updateOrInsert(
            ['tutor_payroll_id' => $payroll->id, 'tutor_attendance_id' => $tutorAttendance->id],
            ['class_session_id' => $session->id, 'package_id' => $packages['utbk']->package_id, 'session_date' => now()->subDays(3)->toDateString(), 'description' => 'Mengajar kelas UTBK.', 'amount' => 150000, 'created_at' => now(), 'updated_at' => now()]
        );

        $tesKoran = $this->upsert('tes_korans', ['name' => 'Tes Koran Kecermatan Dasar'], [
            'test_type' => 'pauli', 'logic_test_type' => 'standar', 'direction' => 'top_to_bottom', 'number_type' => 'satuan', 'operation_type' => 'addition',
            'column_duration_seconds' => 30, 'duration_minutes' => 15, 'columns_count' => 10, 'rows_count' => 20, 'price' => 25000,
            'is_for_sale' => true, 'type_price' => 'paid', 'is_displayed' => true, 'is_active' => true, 'access_duration_value' => 30, 'access_duration_unit' => 'day',
        ]);
        DB::table('tes_koran_sheets')->updateOrInsert(
            ['tes_koran_id' => $tesKoran->id, 'sheet_order' => 1],
            ['name' => 'Lembar Dasar', 'number_type' => 'satuan', 'operation_type' => 'addition', 'column_duration_seconds' => 30, 'columns_count' => 10, 'rows_count' => 20, 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('tes_koran_results')->updateOrInsert(
            ['tes_koran_id' => $tesKoran->id, 'user_id' => $aulia->id, 'attempt_token' => 'demo-koran-' . $aulia->id],
            ['total_correct' => 165, 'total_wrong' => 20, 'total_skipped' => 15, 'column_scores' => json_encode([16, 17, 15, 18, 17, 16, 17, 15, 17, 17]),
            'speed_score' => 82.5, 'accuracy_score' => 89.2, 'stability_score' => 88, 'stability_status' => 'datar', 'final_result' => 'tinggi',
            'started_at' => now()->subDay(), 'finished_at' => now()->subDay()->addMinutes(15), 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()]
        );

        $payment = Payment::where('transaction_id', 'DEMO-UTBK-001')->firstOrFail();
        DB::table('payment_installments')->updateOrInsert(
            ['receipt_number' => 'RCPT-DEMO-UTBK-001'],
            ['payment_id' => $payment->payment_id, 'amount' => $payment->total_amount, 'payment_method' => 'manual', 'notes' => 'Pelunasan transaksi demo.', 'paid_at' => $payment->paid_at, 'paid_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]
        );
        $individual = $this->upsert('individual_purchases', ['transaction_id' => 'DEMO-IND-TOEFL-001'], [
            'user_id' => $bagas->id, 'purchasable_type' => Tryout::class, 'purchasable_id' => $tryouts['toefl']->tryout_id,
            'discount_id' => $discounts['toefl']->id, 'discount_code' => $discounts['toefl']->code, 'discount_amount' => 14850,
            'price' => 99000, 'admin_fee' => 0, 'total_amount' => 84150, 'payment_method' => 'manual', 'status' => 'approved',
            'payment_details' => json_encode(['manual' => true]), 'approved_at' => now()->subDays(10), 'access_expires_at' => now()->addDays(20), 'approved_by' => $admin->id,
        ]);
        DB::table('user_tryout_access')->updateOrInsert(
            ['user_id' => $bagas->id, 'tryout_id' => $tryouts['toefl']->tryout_id],
            ['access_type' => 'purchased', 'access_source' => 'direct', 'source_id' => $individual->id, 'started_at' => now()->subDays(10), 'expires_at' => now()->addDays(20), 'progress_percentage' => 0, 'status' => 'not_started', 'created_at' => now(), 'updated_at' => now()]
        );

        $bill = $this->upsert('recurring_bills', ['name' => 'Tagihan Mentor Bulanan Demo'], [
            'description' => 'Tagihan rutin untuk program pendampingan UTBK.', 'amount' => 75000, 'frequency' => 'monthly', 'start_date' => now()->startOfMonth()->toDateString(), 'due_day' => 10, 'is_active' => true, 'created_by' => $admin->id,
        ]);
        DB::table('recurring_bill_targets')->updateOrInsert(['recurring_bill_id' => $bill->id, 'user_id' => $aulia->id], ['created_at' => now(), 'updated_at' => now()]);
        $invoice = $this->upsert('bill_invoices', ['invoice_number' => 'INV-DEMO-202609-001'], [
            'recurring_bill_id' => $bill->id, 'user_id' => $aulia->id, 'title' => 'Tagihan Mentor September 2026', 'amount' => 75000,
            'period_start' => now()->startOfMonth()->toDateString(), 'period_end' => now()->endOfMonth()->toDateString(), 'due_date' => now()->day(10)->toDateString(), 'status' => 'paid', 'paid_at' => now()->subDay(), 'paid_by' => $admin->id, 'notes' => 'Tagihan demo telah dibayar.',
        ]);
        DB::table('bill_invoice_payments')->updateOrInsert(['receipt_number' => 'RCPT-BILL-DEMO-001'], ['bill_invoice_id' => $invoice->id, 'amount' => 75000, 'payment_method' => 'manual', 'paid_at' => now()->subDay(), 'paid_by' => $admin->id, 'notes' => 'Pembayaran tagihan demo.', 'created_at' => now(), 'updated_at' => now()]);

        $this->seedPublicAndEngagementData($admin, $aulia, $tryouts['utbk']);
    }

    private function seedPublicAndEngagementData(User $admin, User $student, Tryout $tryout): void
    {
        $article = $this->upsert('articles', ['slug' => 'tips-mengatur-waktu-utbk-demo'], [
            'author_id' => $admin->id, 'title' => 'Tips Mengatur Waktu Saat UTBK', 'excerpt' => 'Strategi singkat agar setiap subtes selesai tepat waktu.',
            'content' => '<p>Prioritaskan soal yang paling dikuasai dan sisakan waktu untuk evaluasi.</p>', 'status' => 'published', 'published_at' => now()->subDays(5),
        ]);
        $this->upsert('faqs', ['question' => 'Bagaimana cara mengakses tryout setelah pembayaran?'], ['answer' => 'Akses aktif otomatis setelah pembayaran dikonfirmasi.', 'category' => 'Pembayaran', 'is_active' => true, 'sort_order' => 1]);
        $this->upsert('general_pages', ['page_key' => 'about'], ['template_key' => 'default', 'content' => json_encode(['body' => '<p>Platform belajar dan tryout demo.</p>']), 'settings' => json_encode([]), 'seo' => json_encode(['title' => 'Tentang Kami']), 'is_active' => true]);
        $question = $this->upsert('feedback_questions', ['tryout_id' => $tryout->tryout_id, 'question_text' => 'Seberapa puas Anda dengan tryout ini?'], ['sort_order' => 1, 'is_active' => true]);
        $submission = $this->upsert('feedback_submissions', ['user_id' => $student->id, 'tryout_id' => $tryout->tryout_id, 'attempt_token' => 'demo-utbk-' . $student->id], ['submitted_at' => now()->subDay()]);
        DB::table('feedback_answers')->updateOrInsert(['feedback_submission_id' => $submission->feedback_submission_id, 'feedback_question_id' => $question->feedback_question_id], ['score' => 5, 'created_at' => now(), 'updated_at' => now()]);
        $discussion = $this->upsert('discussions', ['title' => '[DEMO] Strategi menaklukkan Penalaran Umum'], ['user_id' => $student->id]);
        $this->upsert('discussion_comments', ['discussion_id' => $discussion->discussion_id, 'user_id' => $admin->id], ['content' => 'Mulailah dari tipe soal yang paling sering muncul dan evaluasi pembahasannya.']);
        $this->upsert('certificates', ['certificate_number' => 'DEMO-TOEFL-2026-001'], ['certificate_name' => $student->name, 'date_of_birth' => '2006-05-14', 'description' => 'Sertifikat demo TOEFL prediction.', 'institution_name' => 'BimbelHub', 'issued_date' => now()->subWeek()->toDateString(), 'status' => 'active', 'metadata' => json_encode(['score' => 530]), 'verification_code' => 'DEMO-TOEFL-VERIFY', 'issued_by' => $admin->id, 'tryout_id' => $tryout->tryout_id]);
        $this->upsert('demo_requests', ['email' => 'calon.mitra@bimbelhub.test'], ['name' => 'SMA Harapan Bangsa', 'phone' => '081234567892', 'origin_institution' => 'SMA Harapan Bangsa', 'request_note' => 'Ingin mencoba platform untuk sekolah.', 'status' => 'approved', 'reviewed_at' => now()->subDay(), 'approved_by' => $admin->id, 'approved_admin_id' => $admin->id]);
        $this->upsert('activity_logs', ['email' => $student->email, 'action' => 'demo_tryout_completed'], ['user_id' => $student->id, 'ip' => '127.0.0.1', 'user_agent' => 'Demo Seeder', 'status' => 'success', 'meta' => json_encode(['tryout_id' => $tryout->tryout_id])]);
        $this->upsert('update_notifications', ['title' => 'Tryout UTBK September telah tersedia'], ['summary' => 'Simulasi nasional UTBK dapat dikerjakan sekarang.', 'body' => 'Kerjakan tryout UTBK terbaru dan cek hasilnya di dashboard.', 'published_at' => now()->subDays(2), 'created_by' => $admin->id]);
    }

    private function upsert(string $table, array $identity, array $values): object
    {
        $timestamps = [];
        if (Schema::hasColumn($table, 'created_at')) {
            $timestamps['created_at'] = now();
        }
        if (Schema::hasColumn($table, 'updated_at')) {
            $timestamps['updated_at'] = now();
        }

        DB::table($table)->updateOrInsert($identity, [...$values, ...$timestamps]);

        return DB::table($table)->where($identity)->firstOrFail();
    }

    /** @param array<string, Tryout> $tryouts */
    private function seedAttempts(array $tryouts): void
    {
        $participants = User::query()
            ->whereIn('email', ['aulia.putri@bimbelhub.test', 'bagas.pratama@bimbelhub.test', 'citra.lestari@bimbelhub.test'])
            ->get(['id', 'name']);

        foreach ($tryouts as $tryoutKey => $tryout) {
            foreach ($participants as $index => $participant) {
                $token = "demo-{$tryoutKey}-{$participant->id}";
                UserAnswer::query()->where('attempt_token', $token)->each(function (UserAnswer $answer): void {
                    UserAnswerDetail::where('user_answer_id', $answer->user_answer_id)->delete();
                    $answer->delete();
                });

                $details = TryoutDetail::where('tryout_id', $tryout->tryout_id)->get();
                $correctTotal = 0;
                $questionTotal = 0;
                foreach ($details as $detail) {
                    $questions = Question::where('tryout_detail_id', $detail->tryout_detail_id)->get();
                    $correct = max(2, 5 - $index);
                    $answer = UserAnswer::create([
                        'user_id' => $participant->id, 'tryout_id' => $tryout->tryout_id, 'tryout_detail_id' => $detail->tryout_detail_id,
                        'attempt_token' => $token, 'started_at' => now()->subDays($index + 2), 'finished_at' => now()->subDays($index + 2)->addMinutes($detail->duration),
                        'subtest_submitted_at' => now()->subDays($index + 2)->addMinutes($detail->duration), 'correct_answers' => $correct, 'wrong_answers' => 5 - $correct,
                        'unanswered' => 0, 'score' => $correct * 20, 'utbk_total_score' => $tryoutKey === 'utbk' ? 620 + (($index * -35) + 35) : null,
                        'is_passed' => $correct >= 3, 'status' => 'completed',
                    ]);
                    foreach ($questions as $questionIndex => $question) {
                        $option = QuestionOption::where('question_id', $question->question_id)->orderBy('question_option_id')->skip($questionIndex < $correct ? 0 : 1)->firstOrFail();
                        UserAnswerDetail::create(['user_answer_id' => $answer->user_answer_id, 'question_id' => $question->question_id, 'question_option_id' => $option->question_option_id, 'is_correct' => $questionIndex < $correct, 'answered_at' => $answer->finished_at]);
                    }
                    $correctTotal += $correct;
                    $questionTotal += $questions->count();
                }
                Leaderboard::updateOrCreate(
                    ['user_id' => $participant->id, 'tryout_id' => $tryout->tryout_id, 'attempt_token' => $token],
                    ['total_score' => round(($correctTotal / max(1, $questionTotal)) * 100, 2), 'total_correct' => $correctTotal, 'total_questions' => $questionTotal, 'rank' => $index + 1, 'completed_at' => now()->subDays($index + 2)]
                );
            }
        }
    }
}
