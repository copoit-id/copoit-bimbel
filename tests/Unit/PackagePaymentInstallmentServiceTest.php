<?php

namespace Tests\Unit;

use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Services\PackagePaymentInstallmentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PackagePaymentInstallmentServiceTest extends TestCase
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
            $table->string('access_duration_unit')->default('forever');
            $table->unsignedInteger('access_duration_value')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id('payment_id');
            $table->string('transaction_id')->unique();
            $table->foreignId('user_id');
            $table->foreignId('package_id');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('admin_fee')->default(0);
            $table->unsignedInteger('total_amount');
            $table->string('status')->default('pending');
            $table->string('payment_method');
            $table->text('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_installments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id');
            $table->string('receipt_number')->unique();
            $table->unsignedInteger('amount');
            $table->string('payment_method');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('paid_by')->nullable();
            $table->timestamps();
        });

        Schema::create('user_package_access', function (Blueprint $table): void {
            $table->id('user_package_access_id');
            $table->foreignId('user_id');
            $table->foreignId('package_id');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('payment_amount')->nullable();
            $table->string('payment_status')->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'package_id']);
        });
    }

    public function test_package_access_is_granted_only_after_the_final_installment(): void
    {
        $admin = $this->user('admin', 'admin');
        $student = $this->user('student');
        $package = Package::query()->create(['name' => 'Paket Intensif']);
        $payment = Payment::query()->create([
            'transaction_id' => 'MANUAL-CICIL-001',
            'user_id' => $student->id,
            'package_id' => $package->package_id,
            'amount' => 100000,
            'total_amount' => 100000,
            'status' => Payment::STATUS_PENDING,
            'payment_method' => 'manual',
            'payment_details' => json_encode(['manual' => true]),
        ]);

        $service = app(PackagePaymentInstallmentService::class);
        $payment = $service->record($payment, 40000, 'cash', 'DP', $admin);

        $this->assertSame(Payment::STATUS_PARTIAL, $payment->status);
        $this->assertSame(40000, $payment->paid_amount);
        $this->assertSame(60000, $payment->remaining_amount);
        $this->assertDatabaseMissing('user_package_access', ['user_id' => $student->id]);

        $payment = $service->record($payment, 60000, 'transfer', 'Pelunasan', $admin);

        $this->assertSame(Payment::STATUS_SUCCESS, $payment->status);
        $this->assertSame(100000, $payment->paid_amount);
        $this->assertSame(0, $payment->remaining_amount);
        $this->assertDatabaseCount('payment_installments', 2);
        $this->assertDatabaseHas('user_package_access', [
            'user_id' => $student->id,
            'package_id' => $package->package_id,
            'status' => 'active',
            'payment_status' => 'paid',
        ]);
    }

    private function user(string $suffix, string $role = 'user'): User
    {
        return User::query()->create([
            'name' => "User {$suffix}",
            'email' => "{$suffix}@example.test",
            'role' => $role,
        ]);
    }
}
