<?php

namespace Tests\Unit;

use App\Http\Controllers\superadmin\SuperAdminController;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class SuperAdminDemoManagementTest extends TestCase
{
    public function test_admin_index_return_query_keeps_the_last_page_and_allowed_filters(): void
    {
        $method = new ReflectionMethod(SuperAdminController::class, 'indexReturnQuery');
        $request = Request::create('/', 'POST', [
            'return_page' => 3,
            'return_status' => 'active',
            'return_sort' => 'name_asc',
            'return_search' => 'Admin Demo',
        ]);

        $this->assertSame([
            'page' => 3,
            'status' => 'active',
            'sort' => 'name_asc',
            'search' => 'Admin Demo',
        ], $method->invoke(app(SuperAdminController::class), $request));
    }

    public function test_demo_note_removes_unsafe_markup_but_keeps_allowed_formatting(): void
    {
        $method = new ReflectionMethod(SuperAdminController::class, 'sanitizeDemoNote');

        $note = $method->invoke(
            app(SuperAdminController::class),
            '<p>Catatan <strong>penting</strong><script>alert(1)</script><a href="javascript:alert(1)">bahaya</a><a href="https://example.test" onclick="alert(1)">aman</a></p>'
        );

        $this->assertSame(
            '<p>Catatan <strong>penting</strong>bahaya<a href="https://example.test" rel="noopener noreferrer">aman</a></p>',
            $note
        );
    }

    public function test_demo_deal_statuses_include_the_defined_follow_up_stages(): void
    {
        $reflection = new \ReflectionClass(SuperAdminController::class);

        $this->assertSame([
            'baru' => 'Baru',
            'potensial' => 'Potensial',
            'menunggu_keputusan' => 'Menunggu Keputusan',
            'deal' => 'Deal',
            'tidak_jadi' => 'Tidak Jadi',
        ], $reflection->getConstant('DEMO_DEAL_STATUSES'));
    }
}
