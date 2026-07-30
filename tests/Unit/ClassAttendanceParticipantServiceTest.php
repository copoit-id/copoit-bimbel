<?php

namespace Tests\Unit;

use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Package;
use App\Services\ClassAttendanceParticipantService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClassAttendanceParticipantServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        foreach ([
            'class_schedule_destination_categories',
            'participant_destination_categories',
            'user_package_access',
            'user_class_access',
            'detail_packages',
            'class_sessions',
            'class_schedules',
            'classes',
            'packages',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_direct_and_package_students_share_the_same_package_schedule(): void
    {
        $directUserId = $this->insertUser('Siswa Langsung', 'langsung@example.com');
        $packageUserId = $this->insertUser('Siswa Paket', 'paket@example.com');
        $unrelatedUserId = $this->insertUser('Siswa Lain', 'lain@example.com');
        $package = Package::query()->create(['name' => 'Paket A']);
        $classId = DB::table('classes')->insertGetId([
            'title' => 'Matematika',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $class = ClassModel::query()->findOrFail($classId);
        $schedule = ClassSchedule::query()->create([
            'class_id' => $class->class_id,
            'title' => 'Senin Sore',
        ]);
        $schedule->detailPackages()->create([
            'package_id' => $package->package_id,
            'order' => 0,
        ]);
        $session = ClassSession::query()->create([
            'class_schedule_id' => $schedule->id,
            'class_id' => $class->class_id,
        ]);

        DB::table('user_class_access')->insert([
            'user_id' => $directUserId,
            'class_id' => $class->class_id,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_package_access')->insert([
            'user_id' => $packageUserId,
            'package_id' => $package->package_id,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $participantIds = app(ClassAttendanceParticipantService::class)
            ->participants($session)
            ->pluck('id');

        $this->assertTrue($participantIds->contains($directUserId));
        $this->assertTrue($participantIds->contains($packageUserId));
        $this->assertFalse($participantIds->contains($unrelatedUserId));
    }

    private function insertUser(string $name, string $email): int
    {
        return (int) DB::table('users')->insertGetId([
            'name' => $name,
            'email' => $email,
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTables(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('role')->default('user');
            $table->unsignedBigInteger('participant_destination_category_id')->nullable();
            $table->timestamps();
        });
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
        Schema::create('class_schedules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('study_group_id')->nullable();
            $table->string('title');
            $table->timestamps();
        });
        Schema::create('class_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_schedule_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('study_group_id')->nullable();
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
        Schema::create('user_class_access', function (Blueprint $table): void {
            $table->id('user_class_access_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('class_id');
            $table->string('status')->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('user_package_access', function (Blueprint $table): void {
            $table->id('user_package_access_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id');
            $table->string('status')->default('active');
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });
        Schema::create('participant_destination_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('class_schedule_destination_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_schedule_id');
            $table->unsignedBigInteger('participant_destination_category_id');
            $table->timestamps();
        });
    }
}
