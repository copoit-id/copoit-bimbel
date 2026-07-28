<?php

namespace Tests\Unit;

use App\Models\ClassSchedule;
use App\Models\Package;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PackageScheduleRelationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('packages', function (Blueprint $table): void {
            $table->id('package_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('class_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('detail_packages', function (Blueprint $table): void {
            $table->id('detail_package_id');
            $table->foreignId('package_id');
            $table->string('detailable_type');
            $table->unsignedBigInteger('detailable_id');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->unique(['package_id', 'detailable_type', 'detailable_id']);
        });
    }

    public function test_schedule_can_be_linked_to_package_with_stable_morph_alias(): void
    {
        $package = Package::query()->create(['name' => 'Paket + Jadwal']);
        $schedule = ClassSchedule::query()->create(['title' => 'Senin Sore']);

        $schedule->detailPackages()->create([
            'package_id' => $package->package_id,
            'order' => 0,
        ]);

        $this->assertDatabaseHas('detail_packages', [
            'package_id' => $package->package_id,
            'detailable_type' => 'schedule',
            'detailable_id' => $schedule->id,
        ]);
        $this->assertTrue($schedule->fresh()->packages->contains('package_id', $package->package_id));
        $this->assertTrue($package->fresh()->schedules->contains('id', $schedule->id));

        $schedule->delete();

        $this->assertDatabaseMissing('detail_packages', [
            'detailable_type' => 'schedule',
            'detailable_id' => $schedule->id,
        ]);
    }
}
