<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsurePlanFeatureEnabled;
use App\Models\Plan;
use App\Services\PlanModuleService;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PlanModuleServiceTest extends TestCase
{
    public function test_legacy_plans_default_to_full_feature_access(): void
    {
        $service = app(PlanModuleService::class);
        $plan = new Plan(['features_json' => null]);

        $access = $service->accessForPlan($plan);

        $this->assertNotEmpty($access);
        $this->assertNotContains(false, $access, true);
    }

    public function test_it_builds_the_expected_module_presets(): void
    {
        $service = app(PlanModuleService::class);
        $packageOnly = $service->presetAccess('package_only');
        $packageSchedule = $service->presetAccess('package_schedule');
        $cbt = $service->presetAccess('cbt_only');
        $administration = $service->presetAccess('administration_only');
        $standard = $service->presetAccess('standard');
        $full = $service->presetAccess('full');

        $this->assertTrue($packageOnly['package']);
        $this->assertFalse($packageOnly['schedule']);
        $this->assertFalse($packageOnly['class']);
        $this->assertTrue($packageSchedule['package']);
        $this->assertTrue($packageSchedule['schedule']);
        $this->assertTrue($packageSchedule['booking']);
        $this->assertFalse($packageSchedule['class']);
        $this->assertFalse($packageSchedule['tryout']);
        $this->assertTrue($cbt['tryout']);
        $this->assertTrue($cbt['package']);
        $this->assertFalse($cbt['finance']);
        $this->assertTrue($administration['finance']);
        $this->assertTrue($administration['package']);
        $this->assertTrue($administration['schedule']);
        $this->assertFalse($administration['tryout']);
        $this->assertTrue($standard['tryout']);
        $this->assertTrue($standard['finance']);
        $this->assertNotContains(false, $full, true);
    }

    public function test_it_uses_the_most_specific_route_prefix(): void
    {
        $service = app(PlanModuleService::class);

        $this->assertSame('tryout', $service->featureForRoute('user.package.tryout.list'));
        $this->assertSame('tryout', $service->featureForRoute('admin.package.tryout.index'));
        $this->assertSame('material', $service->featureForRoute('admin.package.material.index'));
        $this->assertSame('class', $service->featureForRoute('admin.package.class.index'));
        $this->assertSame('schedule', $service->featureForRoute('admin.class-schedules.index'));
        $this->assertSame('schedule', $service->featureForRoute('user.class-schedule.index'));
        $this->assertSame('schedule', $service->featureForRoute('tutor.schedule.index'));
        $this->assertSame('profile', $service->featureForRoute('tutor.profile.edit'));
        $this->assertSame('booking', $service->featureForRoute('admin.package-booking.edit'));
        $this->assertSame('booking', $service->featureForRoute('user.booking.index'));
        $this->assertSame('booking', $service->featureForRoute('tutor.booking.index'));
        $this->assertSame('tes_koran', $service->featureForRoute('admin.package.tes-koran.index'));
        $this->assertSame('certification', $service->featureForRoute('user.package.sertifikasi'));
        $this->assertSame('pembayaran', $service->featureForRoute('user.billing.index'));
        $this->assertSame('discussion', $service->featureForRoute('user.chat.messages'));
        $this->assertSame('discussion', $service->featureForRoute('tutor.chat.messages'));
        $this->assertSame('class', $service->featureForRoute('tutor.attendance.index'));
        $this->assertSame('laporan', $service->featureForRoute('laporan.live-score.public'));
        $this->assertSame('artikel', $service->featureForRoute('general.articles.index'));
        $this->assertSame('general_page', $service->featureForRoute('general.statistics'));
        $this->assertSame('package', $service->featureForRoute('user.package.index'));
        $this->assertSame(
            'ai_question_generator',
            $service->featureForRoute('admin.question-bank.questions.ai-generator')
        );
        $this->assertSame(
            'ai_question_generator',
            $service->featureForRoute('admin.question.ai-generator')
        );
        $this->assertNull($service->featureForRoute('super-admin.plans.index'));
    }

    public function test_saved_overrides_and_preset_defaults_are_both_honored(): void
    {
        $service = app(PlanModuleService::class);
        $plan = new Plan([
            'features_json' => [
                'module_access' => [
                    'preset' => 'cbt_only',
                    'features' => [
                        'tryout' => false,
                    ],
                ],
            ],
        ]);

        $access = $service->accessForPlan($plan);

        $this->assertFalse($access['tryout']);
        $this->assertTrue($access['question_bank']);
        $this->assertFalse($access['finance']);
    }

    public function test_middleware_rejects_a_disabled_route_feature(): void
    {
        $service = \Mockery::mock(PlanModuleService::class);
        $service->shouldReceive('featureForRoute')
            ->once()
            ->with('admin.finance.income.index')
            ->andReturn('finance');
        $service->shouldReceive('allows')
            ->once()
            ->with('finance')
            ->andReturnFalse();

        $request = Request::create('/admin/keuangan/pemasukan', 'GET');
        $route = new Route(['GET'], '/admin/keuangan/pemasukan', fn () => null);
        $route->name('admin.finance.income.index');
        $request->setRouteResolver(fn (): Route => $route);

        $middleware = new EnsurePlanFeatureEnabled($service);

        try {
            $middleware->handle($request, fn () => response('allowed'));
            $this->fail('Disabled module route was not rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_web_route_pipeline_rejects_a_disabled_module(): void
    {
        RouteFacade::middleware('web')
            ->get('/_module-access-test', fn () => response('allowed'))
            ->name('admin.finance.module-test');

        $service = \Mockery::mock(PlanModuleService::class);
        $service->shouldReceive('featureForRoute')
            ->once()
            ->with('admin.finance.module-test')
            ->andReturn('finance');
        $service->shouldReceive('allows')
            ->once()
            ->with('finance')
            ->andReturnFalse();
        $this->app->instance(PlanModuleService::class, $service);

        $this->get('/_module-access-test')->assertForbidden();
    }
}
