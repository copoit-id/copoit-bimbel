<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('certificate_templates')) {
            Schema::create('certificate_templates', function (Blueprint $table): void {
                $table->id('certificate_template_id');
                $table->unsignedBigInteger('client_profile_id')->nullable()->index();
                $table->string('name');
                $table->string('background_path');
                $table->json('layout');
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('tryouts') && ! Schema::hasColumn('tryouts', 'certificate_template_id')) {
            Schema::table('tryouts', function (Blueprint $table): void {
                $table->unsignedBigInteger('certificate_template_id')->nullable()->index()->after('is_certification');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tryouts') && Schema::hasColumn('tryouts', 'certificate_template_id')) {
            Schema::table('tryouts', function (Blueprint $table): void {
                $table->dropColumn('certificate_template_id');
            });
        }

        if (Schema::hasTable('certificate_templates')) {
            Schema::drop('certificate_templates');
        }
    }
};
