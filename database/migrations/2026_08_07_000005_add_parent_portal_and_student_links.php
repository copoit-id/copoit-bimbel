<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parent_student')) {
            Schema::create('parent_student', function (Blueprint $table): void {
                $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
                $table->string('relationship', 50)->default('Orang Tua');
                $table->boolean('receive_notifications')->default(true);
                $table->timestamps();

                $table->primary(['parent_id', 'child_id']);
                $table->index('child_id');
            });
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')->updateOrInsert(
                ['slug' => 'parent'],
                ['name' => 'Orang Tua', 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student');

        if (Schema::hasTable('roles')) {
            DB::table('roles')->where('slug', 'parent')->delete();
        }
    }
};
