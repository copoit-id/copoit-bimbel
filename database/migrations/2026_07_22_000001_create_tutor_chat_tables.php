<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_conversations')) {
            Schema::create('chat_conversations', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->foreignId('class_id')->constrained('classes', 'class_id')->cascadeOnDelete();
                $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('tutor_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('tentor_id')->constrained('tentors')->cascadeOnDelete();
                $table->unsignedBigInteger('last_message_id')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();

                // One thread per student, class, and assigned tutor. This is also the
                // concurrency guard when two devices open the same chat together.
                $table->unique(['class_id', 'student_user_id', 'tentor_id'], 'chat_conversation_context_unique');
                $table->index(['student_user_id', 'last_message_at'], 'chat_conversation_student_last_index');
                $table->index(['tutor_user_id', 'last_message_at'], 'chat_conversation_tutor_last_index');
            });
        }

        if (! Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignUlid('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->uuid('client_message_id');
                $table->string('type', 20)->default('text');
                $table->text('body');
                $table->timestamps();

                // A retried mobile/web request returns the original message instead of
                // creating a duplicate.
                $table->unique(['conversation_id', 'sender_id', 'client_message_id'], 'chat_message_idempotency_unique');
                $table->index(['conversation_id', 'id'], 'chat_message_conversation_id_index');
            });
        }

        if (! Schema::hasTable('chat_read_states')) {
            Schema::create('chat_read_states', function (Blueprint $table) {
                $table->id();
                $table->foreignUlid('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('last_read_message_id')->nullable();
                $table->timestamp('last_read_at')->nullable();
                $table->timestamps();

                $table->unique(['conversation_id', 'user_id'], 'chat_read_state_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chat_read_states')) {
            Schema::drop('chat_read_states');
        }

        if (Schema::hasTable('chat_messages')) {
            Schema::drop('chat_messages');
        }

        if (Schema::hasTable('chat_conversations')) {
            Schema::drop('chat_conversations');
        }
    }
};
