<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_session_id')
                ->constrained('practice_sessions')
                ->cascadeOnDelete();
            $table->unsignedInteger('session_number')->default(1);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();

            $table->index(['practice_session_id', 'ended_at']);
        });

        Schema::table('practice_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('practice_sessions', 'active_study_session_id')) {
                $table->unsignedBigInteger('active_study_session_id')->nullable()->after('session_start');
            }
        });
    }

    public function down(): void
    {
        Schema::table('practice_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('practice_sessions', 'active_study_session_id')) {
                $table->dropColumn('active_study_session_id');
            }
        });

        Schema::dropIfExists('practice_study_sessions');
    }
};
