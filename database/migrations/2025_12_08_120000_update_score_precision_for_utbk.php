<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            $table->decimal('score', 8, 2)->default(0)->change();
            $table->decimal('utbk_total_score', 8, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->default(0)->change();
            $table->decimal('utbk_total_score', 8, 2)->nullable()->change();
        });
    }
};
