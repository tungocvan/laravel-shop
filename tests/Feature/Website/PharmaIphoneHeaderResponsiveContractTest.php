<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaIphoneHeaderResponsiveContractTest extends TestCase
{
    public function test_pharma_header_stays_compact_without_expanding_mobile_viewport(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $account = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/account-dropdown.blade.php'));
        $meta = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/head-meta.blade.php'));

        $this->assertStringContainsString('flex w-full min-w-0 items-center gap-2', $header);
        $this->assertStringContainsString('flex min-w-0 flex-1 items-center lg:flex-none', $header);
        $this->assertStringContainsString('class="hidden text-xs font-medium sm:block"', $header);
        $this->assertStringContainsString('ml-auto hidden min-w-0 shrink-0 items-center justify-end lg:ml-0 lg:flex', $header);
        $this->assertStringContainsString('h-11 w-11 shrink-0 items-center justify-center', $header);
        $this->assertStringContainsString('min-w-0 flex-wrap items-center justify-end', $account);
        $this->assertStringContainsString('width=device-width, initial-scale=1', $meta);
    }

    public function test_mobile_navigation_uses_fixed_drawer_instead_of_expanding_header(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));

        $this->assertStringContainsString('x-teleport="body"', $header);
        $this->assertStringContainsString('fixed inset-0 z-[9999]', $header);
        $this->assertStringContainsString('fixed inset-0 z-[9999] lg:hidden', $header);
        $this->assertStringContainsString("headerLayout['mobile.drawer']", $header);
        $this->assertStringContainsString('w-[min(88vw,360px)]', $header);
        $this->assertStringContainsString('overflow-y-auto', $header);
        $this->assertStringContainsString('aria-controls="pharma-mobile-menu"', $header);
        $this->assertStringContainsString('aria-label="Đóng menu"', $header);
    }
}
