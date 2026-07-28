<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('package_booking_rules')) {
            Schema::create('package_booking_rules', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('package_id')
                    ->constrained('packages', 'package_id')
                    ->cascadeOnDelete();
                $table->foreignId('class_id')
                    ->nullable()
                    ->constrained('classes', 'class_id')
                    ->nullOnDelete();
                $table->boolean('is_enabled')->default(false);
                $table->unsignedSmallInteger('session_quota')->default(1);
                $table->unsignedSmallInteger('duration_minutes')->default(60);
                $table->unsignedSmallInteger('min_notice_hours')->default(12);
                $table->unsignedSmallInteger('max_advance_days')->default(30);
                $table->unsignedSmallInteger('cancellation_hours')->default(6);
                $table->boolean('allow_custom_time')->default(true);
                $table->boolean('allow_all_tutors')->default(true);
                $table->timestamps();

                $table->unique('package_id');
                $table->index(['is_enabled', 'package_id']);
            });
        }

        if (! Schema::hasTable('package_booking_rule_tentor')) {
            Schema::create('package_booking_rule_tentor', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('package_booking_rule_id')
                    ->constrained('package_booking_rules')
                    ->cascadeOnDelete();
                $table->foreignId('tentor_id')
                    ->constrained('tentors')
                    ->cascadeOnDelete();
                $table->timestamps();

                $table->unique(
                    ['package_booking_rule_id', 'tentor_id'],
                    'booking_rule_tentor_unique'
                );
            });
        }

        if (! Schema::hasTable('schedule_booking_requests')) {
            Schema::create('schedule_booking_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('package_id')
                    ->constrained('packages', 'package_id')
                    ->cascadeOnDelete();
                $table->unsignedBigInteger('user_package_access_id');
                $table->foreign('user_package_access_id', 'booking_request_access_fk')
                    ->references('user_package_access_id')
                    ->on('user_package_access')
                    ->cascadeOnDelete();
                $table->foreignId('tentor_id')->constrained('tentors')->cascadeOnDelete();
                $table->dateTime('requested_start_at');
                $table->dateTime('requested_end_at');
                $table->dateTime('scheduled_start_at')->nullable();
                $table->dateTime('scheduled_end_at')->nullable();
                $table->string('status', 30)->default('pending');
                $table->text('student_notes')->nullable();
                $table->text('tutor_notes')->nullable();
                $table->foreignId('class_schedule_id')
                    ->nullable()
                    ->constrained('class_schedules')
                    ->nullOnDelete();
                $table->foreignId('class_session_id')
                    ->nullable()
                    ->constrained('class_sessions')
                    ->nullOnDelete();
                $table->foreignId('responded_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('responded_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('slot_key', 64)->nullable()->unique();
                $table->timestamps();

                $table->index(
                    ['tentor_id', 'status', 'requested_start_at'],
                    'booking_tutor_status_start_index'
                );
                $table->index(
                    ['user_id', 'status', 'created_at'],
                    'booking_user_status_created_index'
                );
                $table->index(
                    ['user_package_access_id', 'status'],
                    'booking_access_status_index'
                );
                $table->index(
                    ['tentor_id', 'scheduled_start_at', 'scheduled_end_at'],
                    'booking_tutor_scheduled_range_index'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_booking_requests');
        Schema::dropIfExists('package_booking_rule_tentor');
        Schema::dropIfExists('package_booking_rules');
    }
};
