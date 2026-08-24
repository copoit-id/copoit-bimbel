<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('recurring_bills')) {
            Schema::create('recurring_bills', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('amount', 12, 0);
                $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly'])->default('monthly');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->unsignedTinyInteger('due_day')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_active', 'frequency']);
                $table->index(['start_date', 'end_date']);
            });
        }

        if (!Schema::hasTable('recurring_bill_targets')) {
            Schema::create('recurring_bill_targets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('recurring_bill_id')->constrained('recurring_bills')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->foreignId('class_id')->nullable()->constrained('classes', 'class_id')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['recurring_bill_id', 'user_id']);
                $table->unique(['recurring_bill_id', 'class_id']);
            });
        }

        if (!Schema::hasTable('bill_invoices')) {
            Schema::create('bill_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('recurring_bill_id')->nullable()->constrained('recurring_bills')->nullOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('invoice_number')->unique();
                $table->string('title');
                $table->decimal('amount', 12, 0);
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->date('due_date');
                $table->enum('status', ['unpaid', 'paid', 'overdue', 'cancelled'])->default('unpaid');
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status', 'due_date']);
                $table->index(['recurring_bill_id', 'period_start']);
            });
        }

        if (!Schema::hasTable('class_schedules')) {
            Schema::create('class_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_id')->constrained('classes', 'class_id')->cascadeOnDelete();
                $table->string('title');
                $table->enum('schedule_type', ['single', 'recurring'])->default('single');
                $table->enum('frequency', ['daily', 'weekly', 'monthly'])->nullable();
                $table->unsignedTinyInteger('day_of_week')->nullable();
                $table->unsignedTinyInteger('day_of_month')->nullable();
                $table->time('start_time');
                $table->time('end_time')->nullable();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->string('meeting_url')->nullable();
                $table->string('location')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['class_id', 'is_active']);
                $table->index(['schedule_type', 'frequency']);
            });
        }

        if (!Schema::hasTable('class_sessions')) {
            Schema::create('class_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_schedule_id')->constrained('class_schedules')->cascadeOnDelete();
                $table->foreignId('class_id')->constrained('classes', 'class_id')->cascadeOnDelete();
                $table->date('session_date');
                $table->dateTime('start_at');
                $table->dateTime('end_at')->nullable();
                $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
                $table->string('meeting_url')->nullable();
                $table->string('location')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['class_schedule_id', 'session_date', 'start_at'], 'class_sessions_unique_schedule_date_time');
                $table->index(['class_id', 'session_date']);
                $table->index(['status', 'start_at']);
            });
        }

        if (!Schema::hasTable('attendance_settings')) {
            Schema::create('attendance_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_schedule_id')->constrained('class_schedules')->cascadeOnDelete();
                $table->enum('mode', ['button', 'photo'])->default('button');
                $table->unsignedSmallInteger('open_minutes_before')->default(15);
                $table->unsignedSmallInteger('close_minutes_after')->default(30);
                $table->boolean('allow_admin_override')->default(true);
                $table->timestamps();

                $table->unique('class_schedule_id');
            });
        }

        if (!Schema::hasTable('class_attendances')) {
            Schema::create('class_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_session_id')->constrained('class_sessions')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('status', ['present', 'late', 'absent', 'excused'])->default('present');
                $table->timestamp('check_in_at')->nullable();
                $table->string('photo_path')->nullable();
                $table->enum('source', ['user', 'admin'])->default('user');
                $table->text('notes')->nullable();
                $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['class_session_id', 'user_id']);
                $table->index(['user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'class_attendances',
            'attendance_settings',
            'class_sessions',
            'class_schedules',
            'bill_invoices',
            'recurring_bill_targets',
            'recurring_bills',
        ] as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
            }
        }
    }
};
