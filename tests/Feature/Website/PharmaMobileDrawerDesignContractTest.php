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

    public function test_drawer_navigation_has_no_chevrons_and_login_is_prominent(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $menuStart = strpos($header, '<nav aria-label="Điều hướng Pharma trên di động"');
        $menuEnd = strpos($header, '</nav>', $menuStart);
        $this->assertNotFalse($menuStart);
        $this->assertNotFalse($menuEnd);
        $menu = substr($header, $menuStart, $menuEnd - $menuStart);
        $this->assertStringNotContainsString('M5 12h14', $menu);
        $this->assertStringNotContainsString('m9 18 6-6-6-6', $menu);
        $this->assertStringContainsString('aria-current="page"', $menu);
        $this->assertStringContainsString('min-h-12 w-full items-center justify-center', $header);
        $this->assertStringContainsString('pb-[calc(20px+env(safe-area-inset-bottom,0px))]', $header);
        $this->assertStringContainsString('style="--pharma-header-accent: {{ $accent }};"', $header);
        $this->assertStringContainsString('style="background-color: {{ $accent }};"', $header);
        $this->assertStringContainsString('Chưa có tài khoản?', $header);
    }

    public function test_account_links_remain_conditional_and_compact(): void
    {
        $account = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/mobile-account-links.blade.php'));

        $this->assertStringContainsString('aria-label="Tài khoản của tôi"', $account);
        $this->assertStringContainsString('aria-label="Liên kết tài khoản"', $account);
        $this->assertStringContainsString("show_profile", $account);
        $this->assertStringContainsString("show_orders", $account);
        $this->assertStringContainsString("show_admin", $account);
        $this->assertStringContainsString('min-h-12', $account);
        $this->assertStringNotContainsString('aria-hidden="true">›', $account);
        $this->assertStringContainsString('border-t border-slate-100 px-3', $account);
        $this->assertStringContainsString('border-b border-slate-100 px-1 py-3', $account);
        $this->assertStringNotContainsString('divide-y divide-slate-200/70', $account);
        $this->assertStringNotContainsString('min-h-11 items-center gap-3 rounded-lg', $account);
    }
}
