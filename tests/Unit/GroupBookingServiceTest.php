<?php

namespace Tests\Unit;

use App\Models\BillInvoice;
use App\Models\Package;
use App\Models\PackageBookingRule;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\GroupBookingService;
use App\Services\RecurringBillService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

    public function test_admin_approval_creates_a_separate_invoice_for_each_member_and_activates_rombel_after_all_pay(): void
    {
        $organizer = $this->user('organizer');
        $member = $this->user('member');
        $package = $this->package('Bimbel Offline', 150000);
        $rule = $this->groupRule($package, 2, 2);
        $rule->priceTiers()->create([
            'participant_count' => 2,
            'price_per_person' => 150000,
        ]);
        $service = app(GroupBookingService::class);

        $group = $service->create($package, $organizer, 2);
        $service->join($member, $group->invite_code);
        $group = $group->fresh('members.invoice');

        $this->assertSame(StudyGroup::STATUS_PENDING_APPROVAL, $group->status);
        $this->assertCount(2, $group->members);
        $this->assertSame(0, $group->members->whereNotNull('bill_invoice_id')->count());

        $service->approve($group);
        $group = $group->fresh('members.invoice');

        $this->assertSame(StudyGroup::STATUS_PENDING_PAYMENT, $group->status);
        $this->assertSame(2, $group->members->pluck('bill_invoice_id')->filter()->unique()->count());
        $this->assertSame(
            [150000],
            $group->members->pluck('unit_price_snapshot')->unique()->values()->all()
        );

        $billing = app(RecurringBillService::class);
        foreach ($group->members as $groupMember) {
            $billing->recordPayment(
                BillInvoice::query()->findOrFail($groupMember->bill_invoice_id),
                150000,
                'cash',
                null
            );
        }
        $group = $group->fresh('members');

        $this->assertSame(StudyGroup::STATUS_ACTIVE, $group->status);
        $this->assertTrue($group->is_active);
        $this->assertSame(2, $group->members->where('status', 'paid')->count());
        $this->assertDatabaseCount('user_package_access', 2);
    }

    public function test_same_price_mode_uses_the_package_price_when_tiers_have_not_been_configured(): void
    {
        $organizer = $this->user('organizer');
        $package = $this->package('Paket Sama Harga', 125000);
        $this->groupRule($package, 2, 3, 'same');

        $group = app(GroupBookingService::class)->create($package, $organizer, 3);

        $this->assertSame(125000, $group->unit_price_snapshot);
        $this->assertDatabaseHas('package_booking_price_tiers', [
            'package_booking_rule_id' => $group->package_booking_rule_id,
            'participant_count' => 3,
            'price_per_person' => 125000,
        ]);
    }

    public function test_active_package_scope_uses_the_status_column(): void
    {
        $activePackage = $this->package('Paket Aktif', 100000);
        $inactivePackage = $this->package('Paket Nonaktif', 100000);
        $inactivePackage->update(['status' => 'inactive']);

        $this->assertSame(
            [$activePackage->package_id],
            Package::query()->active()->pluck('package_id')->all()
        );
    }

    public function test_join_is_rejected_after_target_capacity_is_reached(): void
    {
        $organizer = $this->user('organizer');
        $member = $this->user('member');
        $third = $this->user('third');
        $package = $this->package('Kelompok Kecil', 100000);
        $rule = $this->groupRule($package, 2, 2);
        $rule->priceTiers()->create([
            'participant_count' => 2,
            'price_per_person' => 100000,
        ]);
        $service = app(GroupBookingService::class);
        $group = $service->create($package, $organizer, 2);
        $service->join($member, $group->invite_code);

        $this->expectException(ValidationException::class);
        $service->join($third, $group->invite_code);
    }

    private function package(string $name, int $price): Package
    {
        $package = Package::query()->create([
            'name' => $name,
            'price' => $price,
            'status' => 'active',
            'is_displayed' => true,
            'access_duration_unit' => 'month',
            'access_duration_value' => 1,
        ]);

        DB::table('packages')
            ->where('package_id', $package->package_id)
            ->update(['price' => $price]);

        return $package->fresh();
    }

    private function groupRule(
        Package $package,
        int $minParticipants,
        int $maxParticipants,
        string $pricingMode = 'tiered'
    ): PackageBookingRule {
        return PackageBookingRule::query()->create([
            'package_id' => $package->package_id,
            'is_enabled' => true,
            'learning_mode' => 'group',
            'min_participants' => $minParticipants,
            'max_participants' => $maxParticipants,
            'payment_deadline_hours' => 48,
            'group_pricing_mode' => $pricingMode,
        ]);
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
            $table->unsignedBigInteger('price')->default(0);
            $table->string('status')->default('active');
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
            $table->string('group_pricing_mode')->default('same');
            $table->timestamps();
        });
        Schema::create('package_booking_price_tiers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('package_booking_rule_id');
            $table->unsignedSmallInteger('participant_count');
            $table->unsignedBigInteger('price_per_person');
            $table->timestamps();
            $table->unique(['package_booking_rule_id', 'participant_count']);
        });
        Schema::create('study_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('tentor_id')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('package_id')->nullable();
            $table->unsignedBigInteger('package_booking_rule_id')->nullable();
            $table->unsignedBigInteger('package_booking_price_tier_id')->nullable();
            $table->unsignedBigInteger('organizer_user_id')->nullable();
            $table->string('invite_code', 12)->nullable()->unique();
            $table->unsignedSmallInteger('target_participants')->nullable();
            $table->unsignedBigInteger('unit_price_snapshot')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('study_group_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('study_group_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('bill_invoice_id')->nullable()->unique();
            $table->unsignedBigInteger('user_package_access_id')->nullable();
            $table->string('role')->default('member');
            $table->string('status')->default('paid');
            $table->unsignedBigInteger('unit_price_snapshot')->nullable();
            $table->timestamp('paid_at')->nullable();
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
    }
}
