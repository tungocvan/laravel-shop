<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaIphoneHeaderResponsiveContractTest extends TestCase
{
    public function test_pharma_header_can_wrap_without_expanding_mobile_viewport(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $account = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/account-dropdown.blade.php'));
        $meta = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/head-meta.blade.php'));

        $this->assertStringContainsString('flex-wrap items-center gap-3', $header);
        $this->assertStringContainsString('lg:flex-nowrap', $header);
        $this->assertStringContainsString('flex min-w-0 flex-1 items-center lg:flex-none', $header);
        $this->assertStringContainsString('min-w-0 flex-wrap items-center justify-end', $account);
        $this->assertStringContainsString('width=device-width, initial-scale=1', $meta);
    }
}
