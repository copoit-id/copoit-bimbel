<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tryouts')) {
            return;
        }

        Schema::table('tryouts', function (Blueprint $table): void {
            if (! Schema::hasColumn('tryouts', 'lobby_token_enabled')) {
                $table->boolean('lobby_token_enabled')->default(false);
            }

            if (! Schema::hasColumn('tryouts', 'lobby_token_hash')) {
                $table->string('lobby_token_hash')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tryouts')) {
            return;
        }

        Schema::table('tryouts', function (Blueprint $table): void {
            if (Schema::hasColumn('tryouts', 'lobby_token_hash')) {
                $table->dropColumn('lobby_token_hash');
            }

            if (Schema::hasColumn('tryouts', 'lobby_token_enabled')) {
                $table->dropColumn('lobby_token_enabled');
            }
        });
    }
};
