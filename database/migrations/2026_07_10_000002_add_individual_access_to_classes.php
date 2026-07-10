<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                if (!Schema::hasColumn('classes', 'price')) {
                    $table->unsignedInteger('price')->default(0)->after('status');
                }

                if (!Schema::hasColumn('classes', 'is_for_sale')) {
                    $table->boolean('is_for_sale')->default(false)->after('price');
                }

                if (!Schema::hasColumn('classes', 'is_displayed')) {
                    $table->boolean('is_displayed')->default(true)->after('is_for_sale');
                }

                if (!Schema::hasColumn('classes', 'type_price')) {
                    $table->string('type_price', 30)->default('paid')->after('is_displayed');
                }

                if (!Schema::hasColumn('classes', 'conditional_requirement')) {
                    $table->text('conditional_requirement')->nullable()->after('type_price');
                }

                if (!Schema::hasColumn('classes', 'access_duration_value')) {
                    $table->unsignedInteger('access_duration_value')->nullable()->after('conditional_requirement');
                }

                if (!Schema::hasColumn('classes', 'access_duration_unit')) {
                    $table->string('access_duration_unit', 20)->default('forever')->after('access_duration_value');
                }
            });

            DB::table('classes')
                ->whereNull('type_price')
                ->orWhere('type_price', '')
                ->update(['type_price' => 'paid']);
        }

        if (!Schema::hasTable('user_class_access')) {
            Schema::create('user_class_access', function (Blueprint $table) {
                $table->id('user_class_access_id');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('class_id')->constrained('classes', 'class_id')->onDelete('cascade');
                $table->string('access_type', 30)->default('free');
                $table->string('access_source', 30)->default('direct');
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('status', 30)->default('active');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'class_id'], 'unique_user_class_access');
                $table->index(['class_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_class_access');

        if (!Schema::hasTable('classes')) {
            return;
        }

        Schema::table('classes', function (Blueprint $table) {
            foreach ([
                'access_duration_unit',
                'access_duration_value',
                'conditional_requirement',
                'type_price',
                'is_displayed',
                'is_for_sale',
                'price',
            ] as $column) {
                if (Schema::hasColumn('classes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
