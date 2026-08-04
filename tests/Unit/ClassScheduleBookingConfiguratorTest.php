<?php

namespace Tests\Unit;

use App\Models\ClassSchedule;
use App\Models\Package;
use App\Models\PackageBookingRule;
use App\Services\ClassScheduleBookingConfigurator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClassScheduleBookingConfiguratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
    }

    public function test_custom_class_schedule_generates_simple_internal_booking_rule(): void
    {
        $package = Package::query()->create(['name' => 'Paket Offline']);
        $schedule = ClassSchedule::query()->create([
            'class_id' => 1,
            'tentor_id' => 1,
            'title' => 'Kelas Rutin Senin',
            'schedule_type' => 'recurring',
            'start_time' => '18:30',
            'end_time' => '20:00',
            'start_date' => '2026-07-28',
            'location' => 'Ruang 1',
            'allow_custom_booking' => true,
            'booking_session_quota' => 8,
        ]);
        $schedule->detailPackages()->create([
            'package_id' => $package->package_id,
            'order' => 0,
        ]);

        app(ClassScheduleBookingConfigurator::class)->sync($schedule);
        $rule = PackageBookingRule::query()
            ->where('package_id', $package->package_id)
            ->firstOrFail();

        $this->assertTrue($rule->is_enabled);
        $this->assertSame(8, $rule->session_quota);
        $this->assertSame(90, $rule->duration_minutes);
        $this->assertSame('Ruang 1', $rule->default_location);
        $this->assertSame([1], $rule->tutors()->pluck('tentors.id')->all());
    }

    public function test_package_cannot_have_two_custom_booking_schedules(): void
    {
        $package = Package::query()->create(['name' => 'Paket Offline']);
        $schedule = ClassSchedule::query()->create([
            'class_id' => 1,
            'tentor_id' => 1,
            'title' => 'Kelas Pertama',
            'schedule_type' => 'single',
            'start_time' => '18:30',
            'end_time' => '20:00',
            'start_date' => '2026-07-28',
            'allow_custom_booking' => true,
            'booking_session_quota' => 1,
        ]);
        $schedule->detailPackages()->create([
            'package_id' => $package->package_id,
            'order' => 0,
        ]);

        $this->expectException(ValidationException::class);
        app(ClassScheduleBookingConfigurator::class)->ensurePackagesAvailable([
            $package->package_id,
        ]);
    }

    private function createTables(): void
    {
        Schema::create('packages', function (Blueprint $table): void {
            $table->id('package_id');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('classes', function (Blueprint $table): void {
            $table->id('class_id');
            $table->string('title');
            $table->timestamps();
        });
        Schema::create('tentors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('class_schedules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('study_group_id')->nullable();
            $table->unsignedBigInteger('tentor_id')->nullable();
            $table->string('title');
            $table->string('schedule_type');
            $table->string('frequency')->nullable();
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('meeting_url')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('allow_custom_booking')->default(false);
            $table->unsignedSmallInteger('booking_session_quota')->default(1);
            $table->timestamps();
        });
        Schema::create('detail_packages', function (Blueprint $table): void {
            $table->id('detail_package_id');
            $table->unsignedBigInteger('package_id');
            $table->string('detailable_type');
            $table->unsignedBigInteger('detailable_id');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->unique(['package_id', 'detailable_type', 'detailable_id']);
        });
        Schema::create('package_booking_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('package_id')->unique();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedSmallInteger('session_quota')->default(1);
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->unsignedSmallInteger('min_notice_hours')->default(0);
            $table->unsignedSmallInteger('max_advance_days')->default(365);
            $table->unsignedSmallInteger('cancellation_hours')->default(0);
            $table->boolean('allow_custom_time')->default(true);
            $table->boolean('allow_all_tutors')->default(false);
            $table->string('delivery_mode')->default('offline');
            $table->string('learning_mode')->default('personal');
            $table->unsignedSmallInteger('min_participants')->default(1);
            $table->unsignedSmallInteger('max_participants')->default(1);
            $table->string('default_location')->nullable();
            $table->unsignedSmallInteger('payment_deadline_hours')->default(48);
            $table->timestamps();
        });
        Schema::create('package_booking_rule_tentor', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('package_booking_rule_id');
            $table->unsignedBigInteger('tentor_id');
            $table->timestamps();
        });

        DB::table('classes')->insert([
            'class_id' => 1,
            'title' => 'Kelas Template',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tentors')->insert([
            'id' => 1,
            'name' => 'Tutor Satu',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
