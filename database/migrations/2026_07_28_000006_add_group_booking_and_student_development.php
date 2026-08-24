<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('package_booking_rules')) {
            Schema::table('package_booking_rules', function (Blueprint $table): void {
                if (! Schema::hasColumn('package_booking_rules', 'delivery_mode')) {
                    $table->string('delivery_mode', 20)->default('online');
                }
                if (! Schema::hasColumn('package_booking_rules', 'learning_mode')) {
                    $table->string('learning_mode', 20)->default('personal');
                }
                if (! Schema::hasColumn('package_booking_rules', 'min_participants')) {
                    $table->unsignedSmallInteger('min_participants')->default(1);
                }
                if (! Schema::hasColumn('package_booking_rules', 'max_participants')) {
                    $table->unsignedSmallInteger('max_participants')->default(1);
                }
                if (! Schema::hasColumn('package_booking_rules', 'default_location')) {
                    $table->string('default_location')->nullable();
                }
                if (! Schema::hasColumn('package_booking_rules', 'payment_deadline_hours')) {
                    $table->unsignedSmallInteger('payment_deadline_hours')->default(48);
                }
            });
        }

        if (! Schema::hasTable('package_booking_price_tiers')) {
            Schema::create('package_booking_price_tiers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('package_booking_rule_id')
                    ->constrained('package_booking_rules')
                    ->cascadeOnDelete();
                $table->unsignedSmallInteger('participant_count');
                $table->unsignedBigInteger('price_per_person');
                $table->timestamps();

                $table->unique(
                    ['package_booking_rule_id', 'participant_count'],
                    'booking_price_tier_unique'
                );
            });
        }

        if (! Schema::hasTable('booking_cohorts')) {
            Schema::create('booking_cohorts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('package_id')
                    ->constrained('packages', 'package_id')
                    ->cascadeOnDelete();
                $table->foreignId('package_booking_rule_id')
                    ->constrained('package_booking_rules')
                    ->cascadeOnDelete();
                $table->foreignId('package_booking_price_tier_id')
                    ->nullable()
                    ->constrained('package_booking_price_tiers')
                    ->nullOnDelete();
                $table->foreignId('organizer_user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $table->foreignId('study_group_id')
                    ->nullable()
                    ->constrained('study_groups')
                    ->nullOnDelete();
                $table->string('invite_code', 12)->unique();
                $table->unsignedSmallInteger('target_participants');
                $table->unsignedBigInteger('unit_price_snapshot');
                $table->string('status', 30)->default('forming');
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(
                    ['package_id', 'status', 'created_at'],
                    'booking_cohort_package_status_index'
                );
            });
        }

        if (! Schema::hasTable('booking_cohort_participants')) {
            Schema::create('booking_cohort_participants', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('booking_cohort_id')
                    ->constrained('booking_cohorts')
                    ->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('bill_invoice_id')
                    ->nullable()
                    ->constrained('bill_invoices')
                    ->nullOnDelete();
                $table->unsignedBigInteger('user_package_access_id')->nullable();
                $table->foreign(
                    'user_package_access_id',
                    'cohort_participant_access_fk'
                )->references('user_package_access_id')
                    ->on('user_package_access')
                    ->nullOnDelete();
                $table->string('role', 20)->default('member');
                $table->string('status', 30)->default('awaiting_payment');
                $table->unsignedBigInteger('unit_price_snapshot');
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['booking_cohort_id', 'user_id'],
                    'booking_cohort_user_unique'
                );
                $table->unique('bill_invoice_id');
                $table->index(
                    ['user_id', 'status'],
                    'booking_cohort_participant_user_status_index'
                );
            });
        }

        if (Schema::hasTable('schedule_booking_requests')
            && ! Schema::hasColumn('schedule_booking_requests', 'booking_cohort_id')) {
            Schema::table('schedule_booking_requests', function (Blueprint $table): void {
                $table->foreignId('booking_cohort_id')
                    ->nullable()
                    ->constrained('booking_cohorts')
                    ->nullOnDelete();
                $table->index(
                    ['booking_cohort_id', 'status'],
                    'schedule_booking_cohort_status_index'
                );
            });
        }

        if (! Schema::hasTable('student_feedback')) {
            Schema::create('student_feedback', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tentor_id')->constrained('tentors')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->foreignId('study_group_id')->nullable()->constrained('study_groups')->cascadeOnDelete();
                $table->foreignId('class_session_id')->nullable()->constrained('class_sessions')->nullOnDelete();
                $table->string('scope', 20);
                $table->string('title');
                $table->text('feedback');
                $table->boolean('is_visible_to_student')->default(true);
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['study_group_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('student_progress_reports')) {
            Schema::create('student_progress_reports', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tentor_id')->constrained('tentors')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('package_id')
                    ->constrained('packages', 'package_id')
                    ->cascadeOnDelete();
                $table->foreignId('study_group_id')->nullable()->constrained('study_groups')->nullOnDelete();
                $table->unsignedBigInteger('user_package_access_id')->nullable();
                $table->foreign(
                    'user_package_access_id',
                    'student_progress_access_fk'
                )->references('user_package_access_id')
                    ->on('user_package_access')
                    ->nullOnDelete();
                $table->date('period_start');
                $table->date('period_end');
                $table->unsignedTinyInteger('progress_percent')->nullable();
                $table->unsignedTinyInteger('mastery_score')->nullable();
                $table->unsignedTinyInteger('discipline_score')->nullable();
                $table->unsignedTinyInteger('participation_score')->nullable();
                $table->text('summary');
                $table->text('strengths')->nullable();
                $table->text('improvements')->nullable();
                $table->text('next_target')->nullable();
                $table->timestamps();

                $table->index(
                    ['user_id', 'package_id', 'period_end'],
                    'student_progress_user_package_period_index'
                );
                $table->index(['study_group_id', 'period_end']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_progress_reports');
        Schema::dropIfExists('student_feedback');

        if (Schema::hasTable('schedule_booking_requests')
            && Schema::hasColumn('schedule_booking_requests', 'booking_cohort_id')) {
            Schema::table('schedule_booking_requests', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('booking_cohort_id');
            });
        }

        Schema::dropIfExists('booking_cohort_participants');
        Schema::dropIfExists('booking_cohorts');
        Schema::dropIfExists('package_booking_price_tiers');

        if (! Schema::hasTable('package_booking_rules')) {
            return;
        }

        Schema::table('package_booking_rules', function (Blueprint $table): void {
            foreach ([
                'delivery_mode',
                'learning_mode',
                'min_participants',
                'max_participants',
                'default_location',
                'payment_deadline_hours',
            ] as $column) {
                if (Schema::hasColumn('package_booking_rules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
