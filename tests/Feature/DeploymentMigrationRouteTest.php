<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeploymentMigrationRouteTest extends TestCase
{
    public function test_it_hides_the_route_when_deployment_migrations_are_disabled(): void
    {
        config([
            'app.deploy_migration_enabled' => false,
            'app.deploy_migration_token' => 'test-token',
        ]);

        $this->get('/_deploy/migrate/test-token')->assertNotFound();
    }

    public function test_it_runs_migrations_for_the_configured_token(): void
    {
        config([
            'app.deploy_migration_enabled' => true,
            'app.deploy_migration_token' => 'test-token',
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate', ['--force' => true])
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Nothing to migrate.');

        $this->get('/_deploy/migrate/test-token')
            ->assertOk()
            ->assertSeeText('Nothing to migrate.');
    }

    public function test_it_hides_the_route_for_an_invalid_token(): void
    {
        config([
            'app.deploy_migration_enabled' => true,
            'app.deploy_migration_token' => 'test-token',
        ]);

        $this->get('/_deploy/migrate/wrong-token')->assertNotFound();
    }
}
