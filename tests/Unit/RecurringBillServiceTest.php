<?php

namespace Tests\Unit;

use App\Models\BillInvoice;
use App\Models\Package;
use App\Models\RecurringBill;
use App\Models\User;
use App\Models\UserPackageAcces;
use App\Services\RecurringBillService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RecurringBillServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role')->default('user');
            $table->timestamps();
        });

        Schema::create('packages', function (Blueprint $table): void {
            $table->id('package_id');
            $table->string('name');
            $table->string('status')->default('active');
            $table->unsignedInteger('price')->default(0);
            $table->timestamps();
        });

        Schema::create('user_package_access', function (Blueprint $table): void {
            $table->id('user_package_access_id');
            $table->foreignId('user_id');
            $table->foreignId('package_id');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('recurring_bills', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('amount');
            $table->string('frequency');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('due_day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('recurring_bill_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recurring_bill_id');
            $table->foreignId('user_id')->nullable();
            $table->foreignId('class_id')->nullable();
            $table->foreignId('package_id')->nullable();
            $table->foreignId('study_group_id')->nullable();
            $table->timestamps();
        });

        Schema::create('bill_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recurring_bill_id')->nullable();
            $table->foreignId('user_id');
            $table->string('invoice_number')->unique();
            $table->string('title');
            $table->unsignedInteger('amount');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('due_date');
            $table->string('status')->default('unpaid');
            $table->timestamps();
        });
    }

    public function test_package_targets_follow_membership_for_each_billing_period(): void
    {
        $package = Package::query()->create(['name' => 'Paket Intensif']);
        $existingUser = $this->user('existing');
        $newUser = $this->user('new');
        $formerUser = $this->user('former');

        $this->grantPackageAccess($existingUser, $package, '2026-05-01');
        $this->grantPackageAccess($newUser, $package, '2026-07-15');
        $this->grantPackageAccess($formerUser, $package, '2026-05-01', '2026-06-10');

        $bill = RecurringBill::query()->create([
            'name' => 'SPP Intensif',
            'amount' => 100000,
            'frequency' => 'monthly',
            'start_date' => '2026-06-01',
        ]);
        $bill->targets()->create(['package_id' => $package->package_id]);

        $created = app(RecurringBillService::class)->generateInvoices(
            $bill,
            Carbon::parse('2026-07-01'),
        );

        $this->assertSame(4, $created);
        $this->assertDatabaseCount('bill_invoices', 4);
        $this->assertTrue($this->hasInvoice($existingUser, '2026-06-01'));
        $this->assertTrue($this->hasInvoice($existingUser, '2026-07-01'));
        $this->assertTrue($this->hasInvoice($formerUser, '2026-06-01'));
        $this->assertFalse($this->hasInvoice($formerUser, '2026-07-01'));
        $this->assertFalse($this->hasInvoice($newUser, '2026-06-01'));
        $this->assertTrue($this->hasInvoice($newUser, '2026-07-01'));

        $this->assertSame(0, app(RecurringBillService::class)->generateInvoices(
            $bill,
            Carbon::parse('2026-07-01'),
        ));
    }

    private function user(string $suffix): User
    {
        return User::query()->create([
            'name' => "User {$suffix}",
            'email' => "{$suffix}@example.test",
            'role' => 'user',
        ]);
    }

    private function grantPackageAccess(User $user, Package $package, string $startDate, ?string $endDate = null): void
    {
        UserPackageAcces::query()->create([
            'user_id' => $user->id,
            'package_id' => $package->package_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active',
        ]);
    }

    private function hasInvoice(User $user, string $periodStart): bool
    {
        return BillInvoice::query()
            ->where('user_id', $user->id)
            ->whereDate('period_start', $periodStart)
            ->exists();
    }
}
