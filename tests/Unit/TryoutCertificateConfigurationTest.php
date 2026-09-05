<?php

namespace Tests\Unit;

use App\Http\Controllers\admin\TryoutController;
use App\Services\PlanModuleService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class TryoutCertificateConfigurationTest extends TestCase
{
    public function test_disabled_certificate_feature_forces_tryout_certificate_configuration_off(): void
    {
        $planModules = Mockery::mock(PlanModuleService::class);
        $planModules->shouldReceive('allows')->once()->with('certificate')->andReturn(false);
        $controller = new TryoutController($planModules);
        config(['client.branding.certificate_management_enabled' => true]);

        $this->assertFalse($this->invoke($controller, 'certificateManagementEnabled'));

        $configuration = $this->invoke(
            $controller,
            'certificateConfiguration',
            [Request::create('/admin/tryout', 'POST', [
                'is_certification' => '1',
                'certificate_template_id' => 99,
            ]), false],
        );

        $this->assertSame([
            'is_certification' => false,
            'certificate_template_id' => null,
        ], $configuration);
    }

    public function test_enabled_certificate_feature_keeps_the_selected_template(): void
    {
        $planModules = Mockery::mock(PlanModuleService::class);
        $controller = new TryoutController($planModules);

        $configuration = $this->invoke(
            $controller,
            'certificateConfiguration',
            [Request::create('/admin/tryout', 'POST', [
                'is_certification' => '1',
                'certificate_template_id' => 12,
            ]), true],
        );

        $this->assertSame([
            'is_certification' => true,
            'certificate_template_id' => 12,
        ], $configuration);
    }

    private function invoke(TryoutController $controller, string $method, array $arguments = []): mixed
    {
        $reflection = new \ReflectionMethod($controller, $method);

        return $reflection->invokeArgs($controller, $arguments);
    }
}
