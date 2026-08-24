<?php

namespace Tests\Unit;

use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\DetailPackage;
use App\Models\Package;
use App\Models\Tentor;
use App\Models\TutorAttendance;
use App\Services\TutorPayrollService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TutorPayrollPackageRateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'tutor_payroll_items',
            'tutor_payrolls',
            'tutor_package_rates',
            'tutor_attendances',
            'class_sessions',
            'detail_packages',
            'class_schedules',
            'classes',
            'packages',
            'tentors',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tentors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('honor_per_attendance', 12, 0)->default(0);
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
            $table->string('title');
            $table->timestamps();
        });
        Schema::create('detail_packages', function (Blueprint $table): void {
            $table->id('detail_package_id');
            $table->unsignedBigInteger('package_id');
            $table->string('detailable_type');
            $table->unsignedBigInteger('detailable_id');
            $table->timestamps();
        });
        Schema::create('class_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_schedule_id')->nullable();
            $table->unsignedBigInteger('class_id');
            $table->date('session_date');
            $table->timestamps();
        });
        Schema::create('tutor_attendances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_session_id');
            $table->unsignedBigInteger('tentor_id');
            $table->string('status');
            $table->string('approval_status');
            $table->timestamps();
        });
        Schema::create('tutor_package_rates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tentor_id');
            $table->unsignedBigInteger('package_id');
            $table->decimal('amount', 12, 0);
            $table->timestamps();
            $table->unique(['tentor_id', 'package_id']);
        });
        Schema::create('tutor_payrolls', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tentor_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('rate_per_attendance', 12, 0)->default(0);
            $table->decimal('gross_amount', 12, 0)->default(0);
            $table->decimal('adjustment_amount', 12, 0)->default(0);
            $table->decimal('net_amount', 12, 0)->default(0);
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tentor_id', 'period_start', 'period_end']);
        });
        Schema::create('tutor_payroll_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tutor_payroll_id');
            $table->unsignedBigInteger('tutor_attendance_id')->nullable();
            $table->unsignedBigInteger('class_session_id')->nullable();
            $table->unsignedBigInteger('package_id')->nullable();
            $table->date('session_date')->nullable();
            $table->string('description');
            $table->decimal('amount', 12, 0)->default(0);
            $table->timestamps();
            $table->unique(['tutor_payroll_id', 'tutor_attendance_id']);
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'tutor_payroll_items',
            'tutor_payrolls',
            'tutor_package_rates',
            'tutor_attendances',
            'class_sessions',
            'detail_packages',
            'class_schedules',
            'classes',
            'packages',
            'tentors',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_it_uses_the_tutor_package_rate_for_an_approved_attendance(): void
    {
        $tentor = Tentor::query()->create([
            'name' => 'Tutor SD',
            'honor_per_attendance' => 100000,
        ]);
        $package = Package::query()->create(['name' => 'Paket SD']);
        $class = ClassModel::query()->create(['title' => 'Matematika SD']);
        $schedule = ClassSchedule::query()->create([
            'class_id' => $class->class_id,
            'title' => 'Kelas SD Reguler',
        ]);
        DetailPackage::query()->create([
            'package_id' => $package->package_id,
            'detailable_type' => 'schedule',
            'detailable_id' => $schedule->id,
        ]);
        $session = ClassSession::query()->create([
            'class_schedule_id' => $schedule->id,
            'class_id' => $class->class_id,
            'session_date' => '2026-08-20',
        ]);
        $attendance = TutorAttendance::query()->create([
            'class_session_id' => $session->id,
            'tentor_id' => $tentor->id,
            'status' => 'present',
            'approval_status' => 'approved',
        ]);
        $tentor->packageRates()->create([
            'package_id' => $package->package_id,
            'amount' => 150000,
        ]);

        $payroll = app(TutorPayrollService::class)->generate(
            $tentor,
            Carbon::parse('2026-08-01')->startOfDay(),
            Carbon::parse('2026-08-31')->endOfDay(),
        );

        $item = $payroll->items()->firstOrFail();

        $this->assertSame((int) $package->package_id, (int) $item->package_id);
        $this->assertSame(150000, (int) $item->amount);
        $this->assertSame(150000, (int) $payroll->gross_amount);
        $this->assertStringContainsString('Paket SD', $item->description);
        $this->assertDatabaseHas('tutor_attendances', ['id' => $attendance->id]);
    }
}
