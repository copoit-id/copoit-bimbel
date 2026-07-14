<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tutor_attendances')
            && ! Schema::hasColumn('tutor_attendances', 'photo_path')) {
            Schema::table('tutor_attendances', function (Blueprint $table): void {
                $table->string('photo_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tutor_attendances')
            && Schema::hasColumn('tutor_attendances', 'photo_path')) {
            Schema::table('tutor_attendances', function (Blueprint $table): void {
                $table->dropColumn('photo_path');
            });
        }
    }
};
