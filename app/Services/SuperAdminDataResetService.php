<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SuperAdminDataResetService
{
    public const CATEGORIES = [
        'tryouts' => ['label' => 'Tryout lengkap', 'description' => 'Tryout, subtest, soal, dan hasil tryout yang terkait.'],
        'attempts' => ['label' => 'Pengerjaan peserta', 'description' => 'Jawaban, nilai, peringkat, waktu tambahan, dan artefak AI belajar.'],
        'packages' => ['label' => 'Paket', 'description' => 'Paket tanpa transaksi, isi paket, akses peserta, dan pengaturan booking.'],
        'materials' => ['label' => 'Materi', 'description' => 'Materi, kategori, akses, dan progres materi peserta.'],
        'participants' => ['label' => 'Peserta', 'description' => 'Akun peserta tanpa transaksi pembayaran; akun bertansaksi dipertahankan demi audit keuangan.'],
    ];

    /** @param list<string> $categories */
    public function reset(array $categories): array
    {
        $categories = array_values(array_intersect($categories, array_keys(self::CATEGORIES)));

        return DB::transaction(function () use ($categories): array {
            $deleted = [];

            if (in_array('tryouts', $categories, true)) {
                $this->clearAttempts();
                $this->deleteTable('tryouts');
                $deleted[] = self::CATEGORIES['tryouts']['label'];
            } elseif (in_array('attempts', $categories, true)) {
                $this->clearAttempts();
                $deleted[] = self::CATEGORIES['attempts']['label'];
            }

            if (in_array('packages', $categories, true)) {
                $this->clearPackagesWithoutPayments();
                $deleted[] = self::CATEGORIES['packages']['label'];
            }

            if (in_array('materials', $categories, true)) {
                $this->deleteTables(['material_progress_logs', 'user_material_accesses', 'material_category_pivot', 'package_materials']);
                $this->deleteTable('materials');
                $this->deleteTable('material_categories');
                $deleted[] = self::CATEGORIES['materials']['label'];
            }

            if (in_array('participants', $categories, true)) {
                $this->clearAttempts();
                $this->deleteTables(['user_material_accesses', 'material_progress_logs', 'user_package_access', 'user_tryout_accesses']);
                if (Schema::hasTable('payments')) {
                    $this->deleteTable('users', function ($query): void {
                        $query->where('role', 'user')->whereNotIn('id', DB::table('payments')->select('user_id')->whereNotNull('user_id'));
                    });
                } else {
                    $this->deleteTable('users', fn ($query) => $query->where('role', 'user'));
                }
                $deleted[] = self::CATEGORIES['participants']['label'];
            }

            return $deleted;
        });
    }

    private function clearAttempts(): void
    {
        $this->deleteTables(['ai_learning_artifacts', 'essay_correction_jobs', 'user_answer_details', 'user_answers', 'leaderboards', 'proctoring_snapshots', 'tryout_user_time_adjustments', 'user_tryout_accesses']);
    }

    private function clearPackagesWithoutPayments(): void
    {
        if (! Schema::hasTable('packages')) {
            return;
        }

        $packageIds = DB::table('packages')
            ->when(Schema::hasTable('payments'), function ($query): void {
                $query->whereNotIn('package_id', DB::table('payments')->select('package_id')->whereNotNull('package_id'));
            })
            ->pluck('package_id');

        if ($packageIds->isEmpty()) {
            return;
        }

        foreach (['schedule_booking_requests', 'booking_cohorts', 'package_booking_rules', 'package_materials', 'detail_packages', 'user_package_access'] as $table) {
            $this->deleteTable($table, fn ($query) => $query->whereIn('package_id', $packageIds));
        }

        DB::table('packages')->whereIn('package_id', $packageIds)->delete();
    }

    /** @param list<string> $tables */
    private function deleteTables(array $tables): void
    {
        foreach ($tables as $table) {
            $this->deleteTable($table);
        }
    }

    private function deleteTable(string $table, ?callable $scope = null): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $query = DB::table($table);
        if ($scope) {
            $scope($query);
        }
        $query->delete();
    }
}
