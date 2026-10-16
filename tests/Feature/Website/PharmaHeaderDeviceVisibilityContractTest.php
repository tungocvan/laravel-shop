<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\HeaderComponentRegistry;
use Modules\Website\Services\HeaderLayoutService;
use Tests\TestCase;

final class PharmaHeaderDeviceVisibilityContractTest extends TestCase
{
    public function test_legacy_layout_defaults_remain_device_scoped(): void
    {
        $service = new HeaderLayoutService(new HeaderComponentRegistry());
        $layout = $service->resolvedLayout([
            'desktop' => ['main' => ['left' => [['type' => 'brand', 'enabled' => true]]]],
            'mobile' => ['drawer' => [['type' => 'mobile-menu', 'enabled' => true]]],
        ]);

        $this->assertSame('desktop', $layout['desktop.main.left'][0]['visibility']);
        $this->assertSame('mobile', $layout['mobile.drawer'][0]['visibility']);
    }

    public function test_four_visibility_states_are_resolved_and_available_in_admin(): void
    {
        $service = new HeaderLayoutService(new HeaderComponentRegistry());
        $layout = $service->resolvedLayout([
            'desktop' => ['main' => ['left' => [
                ['type' => 'brand', 'enabled' => true, 'config' => ['visibility' => 'both']],
                ['type' => 'brand', 'enabled' => true, 'config' => ['visibility' => 'mobile']],
                ['type' => 'brand', 'enabled' => true, 'config' => ['visibility' => 'hidden']],
            ]]],
        ]);
        $this->assertSame(['both', 'mobile'], array_column($layout['desktop.main.left'], 'visibility'));

        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));
        $this->assertStringContainsString('Desktop + Mobile', $view);
        $this->assertStringContainsString('Chỉ Desktop', $view);
        $this->assertStringContainsString('Chỉ Mobile', $view);
        $this->assertStringContainsString('Ẩn hoàn toàn', $view);
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $this->assertStringContainsString('visibilityClass', $header);
        $this->assertStringContainsString('drawerVisibility', $header);
        $this->assertStringContainsString("mobile.search", $header);
        $this->assertStringContainsString('topbarVisibility', $header);
        $this->assertStringContainsString('topbarFallbackVisibility', $header);
    }
}
