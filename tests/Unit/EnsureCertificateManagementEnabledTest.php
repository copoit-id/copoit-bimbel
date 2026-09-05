<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureCertificateManagementEnabled;
use App\Services\PlanModuleService;
use Illuminate\Http\Request;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureCertificateManagementEnabledTest extends TestCase
{
    public function test_it_blocks_certificate_routes_when_the_plan_disables_certificates(): void
    {
        config(['client.branding.certificate_management_enabled' => true]);
        $planModules = Mockery::mock(PlanModuleService::class);
        $planModules->shouldReceive('allows')->once()->with('certificate')->andReturn(false);

        try {
            (new EnsureCertificateManagementEnabled($planModules))->handle(
                Request::create('/sertifikat/validasi'),
                fn () => response('ok'),
            );
            $this->fail('Certificate route was not blocked.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }
}
