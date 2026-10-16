<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaMobileDrawerDesignContractTest extends TestCase
{
    public function test_drawer_has_accessible_fixed_regions_and_scrollable_navigation(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));

        $this->assertStringContainsString('x-teleport="body"', $header);
        $this->assertStringContainsString('aria-modal="true"', $header);
        $this->assertStringContainsString('aria-label="Đóng menu"', $header);
        $this->assertStringContainsString('overflow-y-auto overscroll-contain', $header);
        $this->assertStringContainsString('safe-area-inset-bottom', $header);
        $this->assertStringContainsString('aria-current="page"', $header);
        $this->assertStringContainsString('active:scale-[0.985]', $header);
        $this->assertStringContainsString('line-clamp-2 break-words', $header);
    }

    public function test_account_links_remain_conditional_and_compact(): void
    {
        $account = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/mobile-account-links.blade.php'));

        $this->assertStringContainsString('aria-label="Tài khoản của tôi"', $account);
        $this->assertStringContainsString('aria-label="Liên kết tài khoản"', $account);
        $this->assertStringContainsString("show_profile", $account);
        $this->assertStringContainsString("show_orders", $account);
        $this->assertStringContainsString("show_admin", $account);
        $this->assertStringContainsString('min-h-11', $account);
    }
}
