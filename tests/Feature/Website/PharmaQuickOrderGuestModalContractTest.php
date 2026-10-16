<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaQuickOrderGuestModalContractTest extends TestCase
{
    public function test_pharma_header_intercepts_guest_quick_order_links_with_login_modal(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));

        $this->assertStringContainsString('@click.capture=', $header);
        $this->assertStringContainsString("pathname.replace(/\\/$/, '') === '/quick-order'", $header);
        $this->assertStringContainsString("auth()->guest()", $header);
        $this->assertStringContainsString('quickOrderLoginPrompt = true', $header);
        $this->assertStringContainsString('aria-labelledby="pharma-quick-order-login-title"', $header);
        $this->assertStringContainsString('Vui lòng đăng nhập để đặt hàng nhanh', $header);
        $this->assertStringContainsString("route('login')", $header);
        $this->assertStringContainsString('Để sau', $header);
    }

    public function test_quick_order_get_and_post_remain_protected_by_auth_middleware(): void
    {
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));

        $this->assertStringContainsString("Route::middleware('auth')->get('/quick-order'", $routes);
        $this->assertStringContainsString("Route::middleware('auth')->post('/quick-order/requests'", $routes);
    }
}
