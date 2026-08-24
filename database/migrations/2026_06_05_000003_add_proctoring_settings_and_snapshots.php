<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tryouts', function (Blueprint $table) {
            if (!Schema::hasColumn('tryouts', 'enable_anti_copy')) {
                $table->boolean('enable_anti_copy')->default(true)->after('subtest_display_mode');
            }
            if (!Schema::hasColumn('tryouts', 'enable_tab_switch_detection')) {
                $table->boolean('enable_tab_switch_detection')->default(true)->after('enable_anti_copy');
            }
            if (!Schema::hasColumn('tryouts', 'enable_webcam_check')) {
                $table->boolean('enable_webcam_check')->default(false)->after('enable_tab_switch_detection');
            }
            if (!Schema::hasColumn('tryouts', 'enable_screen_check')) {
                $table->boolean('enable_screen_check')->default(false)->after('enable_webcam_check');
            }
        });

        if (!Schema::hasTable('proctoring_snapshots')) {
            Schema::create('proctoring_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('tryout_id')->constrained('tryouts', 'tryout_id')->cascadeOnDelete();
                $table->foreignId('user_answer_id')->nullable()->constrained('user_answers', 'user_answer_id')->nullOnDelete();
                $table->string('attempt_token');
                $table->string('type', 20);
                $table->string('file_path');
                $table->string('mime_type', 50)->default('image/jpeg');
                $table->unsignedInteger('file_size')->default(0);
                $table->timestamp('captured_at')->nullable();
                $table->timestamps();

                $table->index(['tryout_id', 'attempt_token']);
                $table->index(['user_id', 'tryout_id']);
                $table->index(['type', 'captured_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('proctoring_snapshots');

        Schema::table('tryouts', function (Blueprint $table) {
            foreach ([
                'enable_screen_check',
                'enable_webcam_check',
                'enable_tab_switch_detection',
                'enable_anti_copy',
            ] as $column) {
                if (Schema::hasColumn('tryouts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
