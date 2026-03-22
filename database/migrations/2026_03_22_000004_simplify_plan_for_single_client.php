<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop client_profile_id dari subscriptions karena 1 project = 1 client
        Schema::table('client_plan_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['client_profile_id']);
            $table->dropColumn('client_profile_id');
        });

        Schema::table('essay_ai_usage_logs', function (Blueprint $table) {
            $table->dropForeign(['client_profile_id']);
            $table->dropColumn('client_profile_id');
        });

        // Drop current_plan_id dari client_profile (ga perlu, pakai subscription langsung)
        Schema::table('client_profile', function (Blueprint $table) {
            $table->dropForeign(['current_plan_id']);
            $table->dropColumn('current_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('client_plan_subscriptions', function (Blueprint $table) {
            $table->foreignId('client_profile_id')->constrained('client_profile')->onDelete('cascade');
        });

        Schema::table('essay_ai_usage_logs', function (Blueprint $table) {
            $table->foreignId('client_profile_id')->constrained('client_profile')->onDelete('cascade');
        });

        Schema::table('client_profile', function (Blueprint $table) {
            $table->foreignId('current_plan_id')->nullable()->constrained('plans')->onDelete('set null');
        });
    }
};
