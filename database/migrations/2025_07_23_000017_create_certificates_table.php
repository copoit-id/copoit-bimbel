<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id('certificate_id');
            $table->string('certificate_number')->unique();
            $table->string('certificate_name');
            $table->date('date_of_birth');
            $table->text('description')->nullable();
            $table->string('template_path')->nullable();
            $table->string('institution_name')->default('CPNS Academy');
            $table->date('issued_date');
            $table->date('expired_date')->nullable();
            $table->enum('status', ['active', 'revoked', 'expired'])->default('active');
            $table->json('metadata')->nullable();
            $table->string('verification_code', 32)->unique();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->unsignedBigInteger('tryout_id')->nullable();
            $table->timestamps();

            $table->foreign('issued_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('tryout_id')->references('tryout_id')->on('tryouts')->onDelete('cascade');
            $table->index(['certificate_number', 'verification_code']);
            $table->index(['status', 'issued_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('certificates');
    }
};