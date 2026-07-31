<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_not_found_requests_render_the_custom_error_page(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan');
    }

    public function test_too_many_requests_render_the_custom_error_page(): void
    {
        Route::get('/pengujian-terlalu-banyak-permintaan', fn () => abort(429));

        $this->get('/pengujian-terlalu-banyak-permintaan')
            ->assertStatus(429)
            ->assertSee('tunggu beberapa saat');
    }

    public function test_expired_sessions_redirect_to_login(): void
    {
        Route::get('/pengujian-sesi-berakhir', fn () => abort(419));

        $this->get('/pengujian-sesi-berakhir')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
    }
}
