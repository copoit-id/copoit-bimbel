<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expenses') || ! Schema::hasTable('tutor_payrolls')) {
            return;
        }

        if (! Schema::hasColumn('expenses', 'tutor_payroll_id')) {
            Schema::table('expenses', function (Blueprint $table): void {
                $table->foreignId('tutor_payroll_id')
                    ->nullable()
                    ->unique()
                    ->constrained('tutor_payrolls')
                    ->nullOnDelete();
            });
        }

        DB::table('tutor_payrolls')
            ->leftJoin('expenses', 'expenses.tutor_payroll_id', '=', 'tutor_payrolls.id')
            ->leftJoin('tentors', 'tentors.id', '=', 'tutor_payrolls.tentor_id')
            ->where('tutor_payrolls.status', 'paid')
            ->whereNull('expenses.id')
            ->select([
                'tutor_payrolls.id as payroll_id',
                'tutor_payrolls.net_amount',
                'tutor_payrolls.period_start',
                'tutor_payrolls.period_end',
                'tutor_payrolls.paid_at',
                'tutor_payrolls.paid_by',
                'tentors.name as tentor_name',
            ])
            ->orderBy('tutor_payrolls.id')
            ->chunkById(100, function ($payrolls): void {
                $now = now();
                $expenses = $payrolls->map(fn (object $payroll): array => [
                    'tutor_payroll_id' => $payroll->payroll_id,
                    'title' => 'Gaji tutor: ' . ($payroll->tentor_name ?? 'Tutor'),
                    'amount' => $payroll->net_amount,
                    'spent_at' => $payroll->paid_at ?? $now,
                    'notes' => "Otomatis dari penggajian tutor periode {$payroll->period_start} - {$payroll->period_end}",
                    'created_by' => $payroll->paid_by,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if ($expenses !== []) {
                    DB::table('expenses')->insert($expenses);
                }
            }, 'tutor_payrolls.id', 'payroll_id');
    }

    public function down(): void
    {
        if (! Schema::hasTable('expenses') || ! Schema::hasColumn('expenses', 'tutor_payroll_id')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table): void {
            try {
                $table->dropForeign(['tutor_payroll_id']);
            } catch (\Throwable) {
                // The foreign key may not exist on older installations.
            }

            try {
                $table->dropUnique(['tutor_payroll_id']);
            } catch (\Throwable) {
                // The unique index may not exist on older installations.
            }

            $table->dropColumn('tutor_payroll_id');
        });
    }
};
