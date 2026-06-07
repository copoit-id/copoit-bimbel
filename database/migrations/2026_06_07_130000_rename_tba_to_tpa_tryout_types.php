<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $this->modifyTryoutsTypeEnum(true);
        $this->modifyTryoutDetailsTypeEnum(true);

        DB::table('tryouts')
            ->where('type_tryout', 'tba')
            ->update(['type_tryout' => 'tpa']);

        DB::table('tryout_details')
            ->where('type_subtest', 'tba')
            ->update(['type_subtest' => 'tpa']);

        $this->modifyTryoutsTypeEnum(false);
        $this->modifyTryoutDetailsTypeEnum(false);
    }

    public function down(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $this->modifyTryoutsTypeEnum(true);
        $this->modifyTryoutDetailsTypeEnum(true);

        DB::table('tryouts')
            ->where('type_tryout', 'tpa')
            ->update(['type_tryout' => 'tba']);

        DB::table('tryout_details')
            ->where('type_subtest', 'tpa')
            ->update(['type_subtest' => 'tba']);

        $this->modifyTryoutsTypeEnum(false, false);
        $this->modifyTryoutDetailsTypeEnum(false, false);
    }

    private function modifyTryoutsTypeEnum(bool $includeBoth, bool $useTpa = true): void
    {
        $types = [
            'tiu',
            'twk',
            'tkp',
            'skd_full',
            'general',
            'certification',
            'listening',
            'reading',
            'writing',
            'pppk_full',
            'teknis',
            'social culture',
            'management',
            'interview',
            'word',
            'excel',
            'ppt',
            'computer',
        ];

        $types = array_merge($types, $includeBoth ? ['tba', 'tpa'] : [$useTpa ? 'tpa' : 'tba']);

        $types = array_merge($types, [
            'tbi',
            'utbk_full',
            'utbk_section',
            'utbk_penalaran_umum',
            'utbk_pengetahuan_umum',
            'utbk_pengetahuan_kuantitatif',
            'utbk_pemahaman_bacaan_menulis',
            'utbk_literasi_bahasa_indonesia',
            'utbk_literasi_bahasa_inggris',
            'utbk_penalaran_matematika',
        ]);

        DB::statement('ALTER TABLE tryouts MODIFY type_tryout ENUM(' . $this->enumValues($types) . ') NOT NULL');
    }

    private function modifyTryoutDetailsTypeEnum(bool $includeBoth, bool $useTpa = true): void
    {
        $types = [
            'twk',
            'tiu',
            'tkp',
            'general',
            'listening',
            'reading',
            'writing',
            'teknis',
            'social culture',
            'management',
            'interview',
            'word',
            'excel',
            'ppt',
        ];

        $types = array_merge($types, $includeBoth ? ['tba', 'tpa'] : [$useTpa ? 'tpa' : 'tba']);

        $types = array_merge($types, [
            'tbi',
            'penalaran_umum',
            'pengetahuan_umum',
            'pengetahuan_kuantitatif',
            'pemahaman_bacaan_menulis',
            'literasi_bahasa_indonesia',
            'literasi_bahasa_inggris',
            'penalaran_matematika',
        ]);

        DB::statement('ALTER TABLE tryout_details MODIFY type_subtest ENUM(' . $this->enumValues($types) . ') NOT NULL');
    }

    private function enumValues(array $values): string
    {
        return collect($values)
            ->map(fn (string $value) => DB::getPdo()->quote($value))
            ->implode(',');
    }
};
