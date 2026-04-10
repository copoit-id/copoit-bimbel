<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah kolom assessment_type dari ENUM menjadi STRING agar fleksibel
        Schema::table('tryouts', function (Blueprint $table) {
            $table->string('assessment_type')->default('standard')->change();
        });
    }

    public function down(): void
    {
        // Kembalikan ke ENUM (opsional, bisa juga dibiarkan string)
        Schema::table('tryouts', function (Blueprint $table) {
            // Note: Rollback ke ENUM bisa bermasalah jika sudah ada data 'kecermatan'
            // Jadi kita biarkan tetap string
        });
    }
};
