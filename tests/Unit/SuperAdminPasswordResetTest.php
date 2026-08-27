<?php

namespace Tests\Unit;

use App\Http\Controllers\superadmin\SuperAdminController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SuperAdminPasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('role');
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });
    }

    public function test_demo_admin_password_is_reset_to_the_default_password(): void
    {
        $admin = User::create([
            'name' => 'Admin Demo',
            'role' => 'admin_demo',
            'password' => Hash::make('password-lama'),
            'remember_token' => 'existing-token',
        ]);

        app(SuperAdminController::class)->resetPassword($admin);

        $admin->refresh();

        $this->assertTrue(Hash::check('password123', $admin->password));
        $this->assertNull($admin->remember_token);
    }
}
