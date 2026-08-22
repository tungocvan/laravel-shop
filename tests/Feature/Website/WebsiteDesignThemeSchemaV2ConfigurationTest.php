<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteDesignThemeSchemaV2ConfigurationTest extends TestCase
{
    public function test_schema_v2_contains_safe_visual_groups_and_supports_v1(): void
    {
        $service = file_get_contents(base_path('Modules/Website/Services/WebsiteDesignThemeService.php'));
        $concern = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/Concerns/ManagesWebsiteDesignThemes.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/design-themes.blade.php'));

        $this->assertStringContainsString('public const VERSION = 2', $service);
        $this->assertStringContainsString('public const LEGACY_VERSION = 1', $service);
        foreach (["'design'", "'layout'", "'appearance'", "'features'"] as $group) {
            $this->assertStringContainsString($group, $service);
        }
        $this->assertStringContainsString('WebsiteLayoutPresentationService $layoutService', $service);
        $this->assertStringContainsString('WebsiteAppearanceService $appearanceService', $service);
        $this->assertStringContainsString('if ($version === self::LEGACY_VERSION)', $service);
        $this->assertStringContainsString("if ((int) \$theme['version'] === self::LEGACY_VERSION)", $service);

        $this->assertStringContainsString("\$this->layoutPresentation", $concern);
        $this->assertStringContainsString("\$this->appearance", $concern);
        $this->assertStringContainsString('themeFeaturePayload()', $concern);
        $this->assertStringContainsString("if (isset(\$theme['layout']))", $concern);
        $this->assertStringContainsString("if (isset(\$theme['appearance']))", $concern);

        $this->assertStringContainsString('Schema v2', $view);
        $this->assertStringContainsString('Theme v1 cũ vẫn import/apply được', $view);
        $this->assertStringContainsString('Không chứa Logo/Favicon, SEO, Maintenance', $view);
    }

    public function test_v2_only_exports_safe_floating_widget_positions(): void
    {
        $service = file_get_contents(base_path('Modules/Website/Services/WebsiteDesignThemeService.php'));
        $concern = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/Concerns/ManagesWebsiteDesignThemes.php'));

        $this->assertStringContainsString("'chat_position'", $service);
        $this->assertStringContainsString("'back_to_top_position'", $service);
        $this->assertStringNotContainsString("'chat_widget' =>", $service);
        $this->assertStringNotContainsString("'back_to_top' =>", $service);
        $this->assertStringContainsString("'chat_position' => \$this->features['chat_position']", $concern);
        $this->assertStringContainsString("'back_to_top_position' => \$this->features['back_to_top_position']", $concern);
    }
}
