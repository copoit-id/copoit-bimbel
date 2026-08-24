<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_gateway_subscriptions')) {
            return;
        }

        Schema::table('ai_gateway_subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('ai_gateway_subscriptions', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('ends_at');
            }
            if (! Schema::hasColumn('ai_gateway_subscriptions', 'revoked_reason')) {
                $table->string('revoked_reason')->nullable()->after('revoked_at');
            }
            if (! Schema::hasColumn('ai_gateway_subscriptions', 'revoked_by_user_id')) {
                $table->string('revoked_by_user_id', 120)->nullable()->after('revoked_reason');
            }
            if (! Schema::hasColumn('ai_gateway_subscriptions', 'revoked_by_name')) {
                $table->string('revoked_by_name')->nullable()->after('revoked_by_user_id');
            }
            if (! Schema::hasColumn('ai_gateway_subscriptions', 'revoked_by_email')) {
                $table->string('revoked_by_email')->nullable()->after('revoked_by_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_gateway_subscriptions')) {
            return;
        }

        $columns = [
            'revoked_at',
            'revoked_reason',
            'revoked_by_user_id',
            'revoked_by_name',
            'revoked_by_email',
        ];
        $existingColumns = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn('ai_gateway_subscriptions', $column),
        ));

        if ($existingColumns !== []) {
            Schema::table('ai_gateway_subscriptions', function (Blueprint $table) use ($existingColumns): void {
                $table->dropColumn($existingColumns);
            });
        }
    }
};
