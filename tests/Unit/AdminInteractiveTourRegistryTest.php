<?php

namespace Tests\Unit;

use App\Support\AdminTours\AdminTourRegistry;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminInteractiveTourRegistryTest extends TestCase
{
    public function test_tryout_tour_uses_safe_steps_and_existing_routes(): void
    {
        $tour = app(AdminTourRegistry::class)->definitions()['admin.tryout.create'];

        $this->assertSame(3, $tour['version']);
        $this->assertSame(['admin', 'tutor'], $tour['portal']);
        $this->assertSame(['feature' => 'tryout', 'action' => 'create'], $tour['required_permission']);
        $this->assertSame(
            ['open_create', 'fill_name', 'set_schedule', 'complete'],
            array_column($tour['steps'], 'id')
        );

        foreach ($tour['steps'] as $step) {
            $this->assertTrue(Route::has($step['route']));
            $this->assertContains($step['allowed_action'], ['click', 'input', 'none']);
        }
    }

    public function test_tour_routes_are_registered_in_the_admin_portal(): void
    {
        foreach (['admin.tours.show', 'admin.tours.start', 'admin.tours.steps.store', 'admin.tours.complete'] as $routeName) {
            $this->assertTrue(Route::has($routeName));
        }
    }
}
