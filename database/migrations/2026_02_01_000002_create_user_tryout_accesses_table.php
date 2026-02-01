<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_tryout_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('tryout_id')
                ->constrained('tryouts', 'tryout_id')
                ->cascadeOnDelete();
            $table->foreignId('granted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('granted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tryout_id'], 'unique_user_tryout_access');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tryout_accesses');
    }
};
