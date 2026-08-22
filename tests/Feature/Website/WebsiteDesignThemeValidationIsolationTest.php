<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteDesignThemeValidationIsolationTest extends TestCase
{
    public function test_theme_actions_clear_unrelated_validation_errors(): void
    {
        $concern = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/Concerns/ManagesWebsiteDesignThemes.php'));

        foreach ([
            'saveDesignTheme',
            'applyDesignTheme',
            'updateDesignTheme',
            'renameDesignTheme',
            'deleteDesignTheme',
            'exportDesignTheme',
            'importDesignTheme',
        ] as $method) {
            $offset = strpos($concern, "function {$method}");
            $this->assertNotFalse($offset, "Missing {$method}");
            $slice = substr($concern, $offset, 220);
            $this->assertStringContainsString('$this->resetValidation();', $slice, "{$method} must clear unrelated validation errors.");
        }
    }

    public function test_legacy_robots_value_is_normalized_on_mount(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/WebsiteSettings.php'));

        $this->assertStringContainsString("strtolower(str_replace(' ', '',", $component);
        $this->assertStringContainsString("'index,follow'", $component);
        $this->assertStringContainsString("'index,nofollow'", $component);
        $this->assertStringContainsString("'noindex,follow'", $component);
        $this->assertStringContainsString("'noindex,nofollow'", $component);
        $this->assertStringContainsString("? \$savedRobots : 'index,follow'", $component);
    }
}
