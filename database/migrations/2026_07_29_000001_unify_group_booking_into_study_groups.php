<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('study_groups')) {
            Schema::table('study_groups', function (Blueprint $table): void {
                if (! Schema::hasColumn('study_groups', 'package_id')) {
                    $table->unsignedBigInteger('package_id')->nullable()->index();
                }
                if (! Schema::hasColumn('study_groups', 'package_booking_rule_id')) {
                    $table->unsignedBigInteger('package_booking_rule_id')->nullable()->index();
                }
                if (! Schema::hasColumn('study_groups', 'package_booking_price_tier_id')) {
                    $table->unsignedBigInteger('package_booking_price_tier_id')->nullable();
                }
                if (! Schema::hasColumn('study_groups', 'organizer_user_id')) {
                    $table->unsignedBigInteger('organizer_user_id')->nullable()->index();
                }
                if (! Schema::hasColumn('study_groups', 'invite_code')) {
                    $table->string('invite_code', 12)->nullable()->unique();
                }
                if (! Schema::hasColumn('study_groups', 'target_participants')) {
                    $table->unsignedSmallInteger('target_participants')->nullable();
                }
                if (! Schema::hasColumn('study_groups', 'unit_price_snapshot')) {
                    $table->unsignedBigInteger('unit_price_snapshot')->nullable();
                }
                if (! Schema::hasColumn('study_groups', 'status')) {
                    $table->string('status', 30)->default('active')->index();
                }
                if (! Schema::hasColumn('study_groups', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('study_group_user')) {
            Schema::table('study_group_user', function (Blueprint $table): void {
                if (! Schema::hasColumn('study_group_user', 'role')) {
                    $table->string('role', 20)->default('member');
                }
                if (! Schema::hasColumn('study_group_user', 'status')) {
                    $table->string('status', 30)->default('paid')->index();
                }
                if (! Schema::hasColumn('study_group_user', 'bill_invoice_id')) {
                    $table->unsignedBigInteger('bill_invoice_id')->nullable();
                }
                if (! Schema::hasColumn('study_group_user', 'user_package_access_id')) {
                    $table->unsignedBigInteger('user_package_access_id')->nullable();
                }
                if (! Schema::hasColumn('study_group_user', 'unit_price_snapshot')) {
                    $table->unsignedBigInteger('unit_price_snapshot')->nullable();
                }
                if (! Schema::hasColumn('study_group_user', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable();
                }
            });

            if (! Schema::hasIndex('study_group_user', 'study_group_user_invoice_unique')) {
                Schema::table('study_group_user', function (Blueprint $table): void {
                    $table->unique('bill_invoice_id', 'study_group_user_invoice_unique');
                });
            }
            if (! Schema::hasIndex('study_group_user', 'study_group_user_status_index')) {
                Schema::table('study_group_user', function (Blueprint $table): void {
                    $table->index(['user_id', 'status'], 'study_group_user_status_index');
                });
            }
        }

        if (Schema::hasTable('schedule_booking_requests')
            && ! Schema::hasColumn('schedule_booking_requests', 'study_group_id')) {
            Schema::table('schedule_booking_requests', function (Blueprint $table): void {
                $table->unsignedBigInteger('study_group_id')->nullable()->index();
            });
        }

        if (Schema::hasTable('package_booking_rules')
            && ! Schema::hasColumn('package_booking_rules', 'group_pricing_mode')) {
            Schema::table('package_booking_rules', function (Blueprint $table): void {
                $table->string('group_pricing_mode', 20)->default('same');
            });
        }

        $this->migrateLegacyCohorts();
    }

    public function down(): void
    {
        if (Schema::hasTable('schedule_booking_requests')
            && Schema::hasColumn('schedule_booking_requests', 'study_group_id')) {
            Schema::table('schedule_booking_requests', function (Blueprint $table): void {
                $table->dropIndex(['study_group_id']);
                $table->dropColumn('study_group_id');
            });
        }

        if (Schema::hasTable('study_group_user')) {
            Schema::table('study_group_user', function (Blueprint $table): void {
                foreach (['study_group_user_invoice_unique', 'study_group_user_status_index'] as $index) {
                    if (Schema::hasIndex('study_group_user', $index)) {
                        $table->dropIndex($index);
                    }
                }
                foreach (['role', 'status', 'bill_invoice_id', 'user_package_access_id', 'unit_price_snapshot', 'paid_at'] as $column) {
                    if (Schema::hasColumn('study_group_user', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('study_groups')) {
            Schema::table('study_groups', function (Blueprint $table): void {
                foreach (['package_id', 'package_booking_rule_id', 'package_booking_price_tier_id', 'organizer_user_id', 'invite_code', 'target_participants', 'unit_price_snapshot', 'status', 'expires_at'] as $column) {
                    if (Schema::hasColumn('study_groups', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('package_booking_rules')
            && Schema::hasColumn('package_booking_rules', 'group_pricing_mode')) {
            Schema::table('package_booking_rules', function (Blueprint $table): void {
                $table->dropColumn('group_pricing_mode');
            });
        }
    }

    private function migrateLegacyCohorts(): void
    {
        if (! Schema::hasTable('booking_cohorts')
            || ! Schema::hasTable('booking_cohort_participants')
            || ! Schema::hasTable('study_groups')
            || ! Schema::hasTable('study_group_user')) {
            return;
        }

        DB::table('booking_cohorts')
            ->orderBy('id')
            ->chunkById(100, function ($cohorts): void {
                foreach ($cohorts as $cohort) {
                    $groupId = $cohort->study_group_id;
                    $groupValues = [
                        'package_id' => $cohort->package_id,
                        'package_booking_rule_id' => $cohort->package_booking_rule_id,
                        'package_booking_price_tier_id' => $cohort->package_booking_price_tier_id,
                        'organizer_user_id' => $cohort->organizer_user_id,
                        'invite_code' => $cohort->invite_code,
                        'target_participants' => $cohort->target_participants,
                        'unit_price_snapshot' => $cohort->unit_price_snapshot,
                        'status' => match ($cohort->status) {
                            'ready' => 'active',
                            'forming' => 'pending_payment',
                            default => $cohort->status,
                        },
                        'expires_at' => $cohort->expires_at,
                        'updated_at' => now(),
                    ];

                    if ($groupId && DB::table('study_groups')->where('id', $groupId)->exists()) {
                        DB::table('study_groups')->where('id', $groupId)->update($groupValues);
                    } else {
                        $packageName = DB::table('packages')
                            ->where('package_id', $cohort->package_id)
                            ->value('name') ?? 'Paket';
                        $groupId = DB::table('study_groups')->insertGetId([
                            ...$groupValues,
                            'name' => $packageName.' · '.$cohort->invite_code,
                            'description' => 'Migrasi dari kelompok booking lama.',
                            'is_active' => $cohort->status === 'ready',
                            'created_at' => $cohort->created_at ?? now(),
                        ]);
                    }

                    DB::table('booking_cohort_participants')
                        ->where('booking_cohort_id', $cohort->id)
                        ->orderBy('id')
                        ->each(function ($participant) use ($groupId): void {
                            DB::table('study_group_user')->updateOrInsert(
                                [
                                    'study_group_id' => $groupId,
                                    'user_id' => $participant->user_id,
                                ],
                                [
                                    'role' => $participant->role,
                                    'status' => $participant->status,
                                    'bill_invoice_id' => $participant->bill_invoice_id,
                                    'user_package_access_id' => $participant->user_package_access_id,
                                    'unit_price_snapshot' => $participant->unit_price_snapshot,
                                    'paid_at' => $participant->paid_at,
                                    'updated_at' => now(),
                                    'created_at' => $participant->created_at ?? now(),
                                ]
                            );
                        });

                    if (Schema::hasColumn('schedule_booking_requests', 'booking_cohort_id')) {
                        DB::table('schedule_booking_requests')
                            ->where('booking_cohort_id', $cohort->id)
                            ->update(['study_group_id' => $groupId]);
                    }
                }
            });
    }
};
