<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tutor_leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tentor_id')->constrained('tentors')->cascadeOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('reason', 1000);
            $table->string('status', 20)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['tentor_id', 'status', 'start_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('tutor_leave_requests'); }
};
