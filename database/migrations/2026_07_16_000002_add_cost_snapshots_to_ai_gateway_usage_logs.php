<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_gateway_usage_logs')) {
            return;
        }

        Schema::table('ai_gateway_usage_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_gateway_usage_logs', 'input_per_million_usd')) {
                $table->decimal('input_per_million_usd', 16, 6)->nullable()->after('response_time_ms');
            }
            if (! Schema::hasColumn('ai_gateway_usage_logs', 'output_per_million_usd')) {
                $table->decimal('output_per_million_usd', 16, 6)->nullable()->after('input_per_million_usd');
            }
            if (! Schema::hasColumn('ai_gateway_usage_logs', 'usd_to_idr')) {
                $table->decimal('usd_to_idr', 16, 4)->nullable()->after('output_per_million_usd');
            }
            if (! Schema::hasColumn('ai_gateway_usage_logs', 'input_cost_idr')) {
                $table->decimal('input_cost_idr', 20, 6)->nullable()->after('usd_to_idr');
            }
            if (! Schema::hasColumn('ai_gateway_usage_logs', 'output_cost_idr')) {
                $table->decimal('output_cost_idr', 20, 6)->nullable()->after('input_cost_idr');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_gateway_usage_logs')) {
            return;
        }

        Schema::table('ai_gateway_usage_logs', function (Blueprint $table) {
            $columns = [
                'input_per_million_usd',
                'output_per_million_usd',
                'usd_to_idr',
                'input_cost_idr',
                'output_cost_idr',
            ];
            $existing = array_filter($columns, fn (string $column): bool => Schema::hasColumn('ai_gateway_usage_logs', $column));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
