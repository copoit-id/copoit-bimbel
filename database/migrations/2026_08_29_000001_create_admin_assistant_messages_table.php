<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_assistant_messages')) {
            $indexes = collect(Schema::getIndexes('admin_assistant_messages'))->pluck('name');
            if (! $indexes->contains('admin_assistant_context_idx')) {
                Schema::table('admin_assistant_messages', function (Blueprint $table): void {
                    $table->index(
                        ['user_id', 'source', 'context_hash', 'created_at'],
                        'admin_assistant_context_idx'
                    );
                });
            }

            return;
        }

        Schema::create('admin_assistant_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('portal', 30)->default('admin');
            $table->string('question_hash', 64);
            $table->json('question_token_hashes')->nullable();
            $table->text('question_text');
            $table->text('answer_text');
            $table->string('answer_type', 80)->nullable();
            $table->string('source', 40)->nullable();
            $table->string('confidence', 30)->nullable();
            $table->unsignedInteger('usage_total')->default(0);
            $table->string('context_hash', 64)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'portal', 'created_at']);
            $table->index(['user_id', 'question_hash']);
            $table->index(
                ['user_id', 'source', 'context_hash', 'created_at'],
                'admin_assistant_context_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_assistant_messages');
    }
};
