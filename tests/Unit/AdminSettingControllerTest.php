<?php

namespace Tests\Unit;

use App\Http\Controllers\admin\SettingController;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdminSettingControllerTest extends TestCase
{
    public function test_demo_admin_cannot_update_settings(): void
    {
        URL::defaults(['portal' => 'admin']);
        $request = Request::create('/admin/pengaturan', 'PUT');
        $request->setUserResolver(fn (): User => new User(['role' => 'admin_demo']));

        $response = app(SettingController::class)->update($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('admin.settings.index'), $response->getTargetUrl());
        $this->assertSame(
            'Akun demo hanya dapat melihat pengaturan dan tidak dapat mengubahnya.',
            session('error'),
        );
    }
}
