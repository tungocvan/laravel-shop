<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class ClientPortalFileAvailabilityAndErrorRecoveryTest extends TestCase
{
    public function test_price_list_file_actions_are_gated_by_storage_availability(): void
    {
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Muasamcong/manifest.php'));
        $guard = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/muasamcong/partials/price-list-file-availability.blade.php'));

        $this->assertStringContainsString('price-list-file-availability', $manifest);
        $this->assertStringContainsString("cache: 'no-store'", $guard);
        $this->assertStringContainsString('data.file_available === true', $guard);
        $this->assertStringContainsString('data.pdf_available === true', $guard);
        $this->assertStringContainsString("style.setProperty('display', 'none', 'important')", $guard);
        $this->assertStringContainsString('Bản ghi đã hoàn thành nhưng file Excel không tồn tại trên storage', $guard);
        $this->assertStringContainsString('Không tạo được file Excel', $guard);
    }

    public function test_client_error_pages_recover_to_previous_page_or_my_apps(): void
    {
        $forbidden = file_get_contents(resource_path('views/errors/403.blade.php'));
        $notFound = file_get_contents(resource_path('views/errors/404.blade.php'));

        foreach ([$forbidden, $notFound] as $view) {
            $this->assertStringContainsString("request()->is('apps/*')", $view);
            $this->assertStringContainsString("Route::has('client.apps.index')", $view);
            $this->assertStringContainsString("route('client.apps.index')", $view);
            $this->assertStringContainsString('data-error-back', $view);
            $this->assertStringContainsString('history.back()', $view);
            $this->assertStringContainsString('document.referrer', $view);
        }
    }
}
