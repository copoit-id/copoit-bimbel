<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ptn_supporting_subjects')) {
            Schema::create('ptn_supporting_subjects', function (Blueprint $table) {
                $table->id();
                $table->string('kode_prodi')->unique();
                $table->string('perguruan_tinggi');
                $table->string('nama_prodi');
                $table->string('jenjang')->nullable();
                $table->json('mapel_pendukung')->nullable();
                $table->timestamp('imported_at')->nullable();
                $table->timestamps();

                $table->index('perguruan_tinggi');
                $table->index('nama_prodi');
            });

            return;
        }

        Schema::table('ptn_supporting_subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('ptn_supporting_subjects', 'kode_prodi')) {
                $table->string('kode_prodi')->unique();
            }

            if (! Schema::hasColumn('ptn_supporting_subjects', 'perguruan_tinggi')) {
                $table->string('perguruan_tinggi');
            }

            if (! Schema::hasColumn('ptn_supporting_subjects', 'nama_prodi')) {
                $table->string('nama_prodi');
            }

            if (! Schema::hasColumn('ptn_supporting_subjects', 'jenjang')) {
                $table->string('jenjang')->nullable();
            }

            if (! Schema::hasColumn('ptn_supporting_subjects', 'mapel_pendukung')) {
                $table->json('mapel_pendukung')->nullable();
            }

            if (! Schema::hasColumn('ptn_supporting_subjects', 'imported_at')) {
                $table->timestamp('imported_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ptn_supporting_subjects');
    }
};
