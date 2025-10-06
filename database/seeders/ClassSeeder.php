<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = [
            [
                'name' => 'Kelas A - CPNS Umum',
                'description' => 'Kelas persiapan CPNS untuk umum dengan fokus pada SKD (TIU, TWK, TKP)'
            ],
            [
                'name' => 'Kelas B - CPNS Guru',
                'description' => 'Kelas persiapan CPNS khusus untuk formasi guru'
            ],
            [
                'name' => 'Kelas C - CPNS Kesehatan',
                'description' => 'Kelas persiapan CPNS khusus untuk formasi tenaga kesehatan'
            ],
        ];

        foreach ($classes as $class) {
            ClassModel::create($class);
        }
    }
}
