<?php

namespace Tests\Unit;

use App\Models\BillInvoice;
use App\Models\BookingCohort;
use App\Models\Package;
use App\Models\PackageBookingRule;
use App\Models\User;
use App\Services\GroupBookingService;
use App\Services\RecurringBillService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GroupBookingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
    }

    public function test_every_member_is_billed_separately_and_group_is_created_after_all_pay(): void
    {
        $organizer = $this->user('organizer');
        $member = $this->user('member');
        $package = Package::query()->create([
            'name' => 'Bimbel Offline',
            'is_active' => true,
            'is_displayed' => true,
            'access_duration_unit' => 'month',
            'access_duration_value' => 1,
        ]);
        $rule = PackageBookingRule::query()->create([
            'package_id' => $package->package_id,
            'is_enabled' => true,
            'learning_mode' => 'group',
            'min_participants' => 2,
            'max_participants' => 2,
            'payment_deadline_hours' => 48,
        ]);
        $rule->priceTiers()->create([
            'participant_count' => 2,
            'price_per_person' => 150000,
        ]);
        $service = app(GroupBookingService::class);

        $cohort = $service->create($package, $organizer, 2);
        $service->join($member, $cohort->invite_code);
        $cohort = $cohort->fresh('participants.invoice');

        $this->assertSame(BookingCohort::STATUS_FORMING, $cohort->status);
        $this->assertCount(2, $cohort->participants);
        $this->assertSame(2, $cohort->participants->pluck('bill_invoice_id')->unique()->count());
        $this->assertSame(
            [150000],
            $cohort->participants->pluck('unit_price_snapshot')->unique()->values()->all()
        );

        $billing = app(RecurringBillService::class);
        foreach ($cohort->participants as $participant) {
            $billing->recordPayment(
                BillInvoice::query()->findOrFail($participant->bill_invoice_id),
                150000,
                'cash',
                null
            );
        }
        $cohort = $cohort->fresh(['participants', 'studyGroup.users']);

        $this->assertSame(BookingCohort::STATUS_READY, $cohort->status);
        $this->assertNotNull($cohort->study_group_id);
        $this->assertCount(2, $cohort->studyGroup->users);
        $this->assertSame(2, $cohort->participants->where('status', 'paid')->count());
        $this->assertDatabaseCount('user_package_access', 2);
    }

    public function test_join_is_rejected_after_target_capacity_is_reached(): void
    {
        $organizer = $this->user('organizer');
        $member = $this->user('member');
        $third = $this->user('third');
        $package = Package::query()->create([
            'name' => 'Kelompok Kecil',
            'is_active' => true,
            'is_displayed' => true,
        ]);
        $rule = PackageBookingRule::query()->create([
            'package_id' => $package->package_id,
            'is_enabled' => true,
            'learning_mode' => 'group',
            'min_participants' => 2,
            'max_participants' => 2,
            'payment_deadline_hours' => 48,
        ]);
        $rule->priceTiers()->create([
            'participant_count' => 2,
            'price_per_person' => 100000,
        ]);
        $service = app(GroupBookingService::class);
        $cohort = $service->create($package, $organizer, 2);
        $service->join($member, $cohort->invite_code);

        $this->expectException(ValidationException::class);
        $service->join($third, $cohort->invite_code);
    }

    private function user(string $name): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => "{$name}@example.test",
            'role' => 'user',
        ]);
    }

    private function createTables(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role');
            $table->string('password')->nullable();
            $table->timestamps();
        });
        Schema::create('packages', function (Blueprint $table): void {
            $table->id('package_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_displayed')->default(true);
            $table->string('access_duration_unit')->default('forever');
            $table->unsignedInteger('access_duration_value')->nullable();
            $table->timestamps();
        });
        Schema::create('package_booking_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('package_id')->unique();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedSmallInteger('session_quota')->default(1);
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->unsignedSmallInteger('min_notice_hours')->default(1);
            $table->unsignedSmallInteger('max_advance_days')->default(30);
            $table->unsignedSmallInteger('cancellation_hours')->default(6);
            $table->boolean('allow_custom_time')->default(true);
            $table->boolean('allow_all_tutors')->default(true);
            $table->string('delivery_mode')->default('offline');
            $table->string('learning_mode')->default('personal');
            $table->unsignedSmallInteger('min_participants')->default(1);
            $table->unsignedSmallInteger('max_participants')->default(1);
            $table->string('default_location')->nullable();
            $table->unsignedSmallInteger('payment_deadline_hours')->default(48);
            $table->timestamps();
        });
        Schema::create('package_booking_price_tiers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('package_booking_rule_id');
            $table->unsignedSmallInteger('participant_count');
            $table->unsignedBigInteger('price_per_person');
            $table->timestamps();
        });
        Schema::create('study_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('tentor_id')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('study_group_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('study_group_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['study_group_id', 'user_id']);
        });
        Schema::create('bill_invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('recurring_bill_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('invoice_number')->unique();
            $table->string('title');
            $table->unsignedBigInteger('amount');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('due_date');
            $table->string('status')->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('bill_invoice_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('bill_invoice_id');
            $table->string('receipt_number')->unique();
            $table->unsignedBigInteger('amount');
            $table->string('payment_method');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at');
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamps();
        });
        Schema::create('user_package_access', function (Blueprint $table): void {
            $table->id('user_package_access_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('payment_amount')->nullable();
            $table->string('payment_status')->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('requirement_status')->default('none');
            $table->timestamps();
            $table->unique(['user_id', 'package_id']);
        });
        Schema::create('booking_cohorts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('package_booking_rule_id');
            $table->unsignedBigInteger('package_booking_price_tier_id')->nullable();
            $table->unsignedBigInteger('organizer_user_id');
            $table->unsignedBigInteger('study_group_id')->nullable();
            $table->string('invite_code')->unique();
            $table->unsignedSmallInteger('target_participants');
            $table->unsignedBigInteger('unit_price_snapshot');
            $table->string('status');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('booking_cohort_participants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('booking_cohort_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('bill_invoice_id')->nullable();
            $table->unsignedBigInteger('user_package_access_id')->nullable();
            $table->string('role');
            $table->string('status');
            $table->unsignedBigInteger('unit_price_snapshot');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['booking_cohort_id', 'user_id']);
        });
    }
}
