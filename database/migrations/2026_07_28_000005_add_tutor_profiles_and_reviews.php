<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tentors')) {
            Schema::table('tentors', function (Blueprint $table): void {
                if (! Schema::hasColumn('tentors', 'profile_photo_path')) {
                    $table->string('profile_photo_path')->nullable();
                }
                if (! Schema::hasColumn('tentors', 'education')) {
                    $table->text('education')->nullable();
                }
                if (! Schema::hasColumn('tentors', 'experience_years')) {
                    $table->unsignedSmallInteger('experience_years')->nullable();
                }
                if (! Schema::hasColumn('tentors', 'experience')) {
                    $table->text('experience')->nullable();
                }
                if (! Schema::hasColumn('tentors', 'certifications')) {
                    $table->text('certifications')->nullable();
                }
                if (! Schema::hasColumn('tentors', 'teaching_method')) {
                    $table->text('teaching_method')->nullable();
                }
            });
        }

        if (! Schema::hasTable('tutor_reviews')) {
            Schema::create('tutor_reviews', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('schedule_booking_request_id')
                    ->constrained('schedule_booking_requests')
                    ->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('tentor_id')->constrained('tentors')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->text('comment')->nullable();
                $table->boolean('is_visible')->default(true);
                $table->timestamps();

                $table->unique(
                    'schedule_booking_request_id',
                    'tutor_review_booking_unique'
                );
                $table->index(
                    ['tentor_id', 'is_visible', 'created_at'],
                    'tutor_review_public_index'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_reviews');

        if (! Schema::hasTable('tentors')) {
            return;
        }

        Schema::table('tentors', function (Blueprint $table): void {
            foreach ([
                'profile_photo_path',
                'education',
                'experience_years',
                'experience',
                'certifications',
                'teaching_method',
            ] as $column) {
                if (Schema::hasColumn('tentors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
