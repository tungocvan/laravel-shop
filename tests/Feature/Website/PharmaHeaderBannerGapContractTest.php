<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\HeaderPresentationService;
use Tests\TestCase;

final class PharmaHeaderBannerGapContractTest extends TestCase
{
    public function test_responsive_banner_gap_defaults_clamps_and_preserves_saved_values(): void
    {
        $service = new HeaderPresentationService;
        $this->assertSame(['desktop' => 0, 'mobile' => 0], $service->resolve([])['banner_gap']);
        $this->assertSame(['desktop' => 120, 'mobile' => 0], $service->resolve(['banner_gap' => ['desktop' => 999, 'mobile' => -15]])['banner_gap']);
        $this->assertSame(['desktop' => 32, 'mobile' => 12], $service->resolve(['banner_gap' => ['desktop' => 32, 'mobile' => 12]])['banner_gap']);
        $this->assertSame(['desktop' => 32, 'mobile' => 12], $service->resolve($service->resolve(['banner_gap' => ['desktop' => 32, 'mobile' => 12]]))['banner_gap']);
    }

    public function test_builder_controls_theme_snapshot_and_frontend_spacing_are_connected(): void
    {
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));
        $editor = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));

        $this->assertStringContainsString('presentation.banner_gap.desktop', $editor);
        $this->assertStringContainsString('presentation.banner_gap.mobile', $editor);
        $this->assertStringContainsString('wire:click="saveBuilder"', $editor);
        $this->assertStringContainsString("'presentation' => \$presentationService->resolve(\$this->presentation)", $hub);
        $this->assertStringContainsString('pharma-header-banner-gap', $header);
        $this->assertStringContainsString("['banner_gap']['desktop']", $header);
        $this->assertStringContainsString("['banner_gap']['mobile']", $header);
        $this->assertStringContainsString('@media (min-width: 1024px)', $header);
    }
}
