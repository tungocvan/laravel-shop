<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaTopbarSharedLinksContractTest extends TestCase
{
    public function test_topbar_uses_shared_help_and_order_tracking_links(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $admin = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/GeneralSettings.php'));

        foreach (['header.topbar.help_url', 'header.topbar.order_tracking_url'] as $key) {
            $this->assertStringContainsString($key, $header);
            $this->assertStringContainsString($key, $admin);
        }
        $this->assertStringContainsString('Theo dõi đơn hàng', $header);
        $this->assertStringContainsString('Trợ giúp', $header);
        $this->assertStringNotContainsString('Thông tin không thay thế tư vấn chuyên môn y tế', $header);
        $this->assertStringContainsString('safeTopbarLink', $header);
    }

    public function test_topbar_device_dropdown_is_wired_to_existing_visibility_settings(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));
        $this->assertStringContainsString('setTopbarDeviceVisibility($event.target.value)', $view);
        $this->assertStringContainsString('Hiển thị cả 2', $view);
        $this->assertStringContainsString('Hiển thị Desktop', $view);
        $this->assertStringContainsString('Hiển thị Mobile', $view);
        $this->assertStringContainsString('public function setTopbarDeviceVisibility', $component);
        $this->assertStringContainsString("\$this->topbarSettings['show_mobile']", $component);
    }
}
