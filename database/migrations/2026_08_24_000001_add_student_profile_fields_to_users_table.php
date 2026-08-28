<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'education_level')) {
                $table->string('education_level', 100)->nullable();
            }

            if (! Schema::hasColumn('users', 'origin_institution')) {
                $table->string('origin_institution')->nullable();
            }

            if (! Schema::hasColumn('users', 'major_choice_1')) {
                $table->string('major_choice_1')->nullable();
            }

            if (! Schema::hasColumn('users', 'major_choice_2')) {
                $table->string('major_choice_2')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'major_choice_2')) {
                $table->dropColumn('major_choice_2');
            }

            if (Schema::hasColumn('users', 'major_choice_1')) {
                $table->dropColumn('major_choice_1');
            }

            if (Schema::hasColumn('users', 'origin_institution')) {
                $table->dropColumn('origin_institution');
            }

            if (Schema::hasColumn('users', 'education_level')) {
                $table->dropColumn('education_level');
            }
        });
    }
};
