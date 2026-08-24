<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_gateway_clients')) {
            Schema::table('ai_gateway_clients', function (Blueprint $table) {
                if (!Schema::hasColumn('ai_gateway_clients', 'free_token_limit')) {
                    $table->unsignedBigInteger('free_token_limit')->default(0)->after('monthly_token_limit');
                }
                if (!Schema::hasColumn('ai_gateway_clients', 'free_chat_limit')) {
                    $table->unsignedInteger('free_chat_limit')->default(0)->after('free_token_limit');
                }
            });
        }

        if (!Schema::hasTable('ai_gateway_user_trials')) {
            Schema::create('ai_gateway_user_trials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_gateway_client_id')->constrained()->cascadeOnDelete();
                $table->string('external_user_id', 120);
                $table->string('external_user_name', 255)->nullable();
                $table->string('external_user_email', 255)->nullable();
                $table->unsignedBigInteger('tokens_used')->default(0);
                $table->unsignedInteger('chats_used')->default(0);
                $table->timestamps();
                $table->unique(['ai_gateway_client_id', 'external_user_id'], 'aigw_trial_user_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_gateway_user_trials');

        if (Schema::hasTable('ai_gateway_clients')) {
            Schema::table('ai_gateway_clients', function (Blueprint $table) {
                $columns = array_filter(['free_token_limit', 'free_chat_limit'], fn (string $column) => Schema::hasColumn('ai_gateway_clients', $column));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
