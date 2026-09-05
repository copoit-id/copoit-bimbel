<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeploymentSuperAdminRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignId('role_id');
            $table->foreignId('user_id');
            $table->primary(['role_id', 'user_id']);
        });
    }

    public function test_it_creates_a_super_admin_from_existing_super_admin_configuration(): void
    {
        config([
            'app.deploy_migration_enabled' => true,
            'app.deploy_migration_token' => 'test-token',
            'seeders.super_admin.username' => 'owner',
            'seeders.super_admin.email' => 'owner@example.com',
            'seeders.super_admin.password' => 'password-rahasia-minimal-16',
        ]);
        DB::table('roles')->insert(['name' => 'Super Admin', 'slug' => 'super_admin']);

        $this->get('/_deploy/super-admin/test-token')->assertOk();

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->assertSame('super_admin', $user->role);
        $this->assertTrue($user->roles()->where('slug', 'super_admin')->exists());
        $this->assertTrue(Hash::check('password-rahasia-minimal-16', $user->password));
    }

    public function test_it_hides_the_route_when_the_token_is_invalid(): void
    {
        config([
            'app.deploy_migration_enabled' => true,
            'app.deploy_migration_token' => 'test-token',
        ]);

        $this->get('/_deploy/super-admin/wrong-token')->assertNotFound();
    }
}
