<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_conversations')) {
            return;
        }

        if (! Schema::hasColumn('chat_conversations', 'class_schedule_id')) {
            Schema::table('chat_conversations', function (Blueprint $table): void {
                $table->foreignId('class_schedule_id')
                    ->nullable()
                    ->after('class_id')
                    ->constrained('class_schedules')
                    ->nullOnDelete();
            });
        }

        // MySQL uses the old composite unique key to support the class_id
        // foreign key. Add a dedicated replacement before dropping it.
        if (! $this->hasIndex('chat_conversations', 'chat_conversation_class_id_index')) {
            Schema::table('chat_conversations', function (Blueprint $table): void {
                $table->index('class_id', 'chat_conversation_class_id_index');
            });
        }

        if ($this->hasIndex('chat_conversations', 'chat_conversation_context_unique')) {
            Schema::table('chat_conversations', function (Blueprint $table): void {
                $table->dropUnique('chat_conversation_context_unique');
            });
        }

        if (! $this->hasIndex('chat_conversations', 'chat_conversation_schedule_context_unique')) {
            Schema::table('chat_conversations', function (Blueprint $table): void {
                $table->unique(
                    ['class_schedule_id', 'student_user_id', 'tentor_id'],
                    'chat_conversation_schedule_context_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('chat_conversations')) {
            return;
        }

        if ($this->hasIndex('chat_conversations', 'chat_conversation_schedule_context_unique')) {
            Schema::table('chat_conversations', function (Blueprint $table): void {
                $table->dropUnique('chat_conversation_schedule_context_unique');
            });
        }

        if (! $this->hasIndex('chat_conversations', 'chat_conversation_context_unique') && ! $this->hasLegacyContextDuplicates()) {
            Schema::table('chat_conversations', function (Blueprint $table): void {
                $table->unique(['class_id', 'student_user_id', 'tentor_id'], 'chat_conversation_context_unique');
            });
        }

        if (Schema::hasColumn('chat_conversations', 'class_schedule_id')) {
            Schema::table('chat_conversations', function (Blueprint $table): void {
                try {
                    $table->dropForeign(['class_schedule_id']);
                } catch (\Throwable) {
                    // The foreign key may already be absent on an older deployment.
                }

                $table->dropColumn('class_schedule_id');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index): bool => $index['name'] === $indexName);
    }

    private function hasLegacyContextDuplicates(): bool
    {
        return DB::table('chat_conversations')
            ->select(['class_id', 'student_user_id', 'tentor_id'])
            ->groupBy(['class_id', 'student_user_id', 'tentor_id'])
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }
};
