<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteRobotsValidationConfigurationTest extends TestCase
{
    public function test_robots_validation_uses_rule_in_for_comma_containing_values(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/WebsiteSettings.php'));

        $this->assertStringContainsString('use Illuminate\\Validation\\Rule;', $component);
        $this->assertStringContainsString("'robots' => ['required', Rule::in(\$this->allowedRobots())]", $component);
        $this->assertStringContainsString("'index,follow'", $component);
        $this->assertStringContainsString("'index,nofollow'", $component);
        $this->assertStringContainsString("'noindex,follow'", $component);
        $this->assertStringContainsString("'noindex,nofollow'", $component);
        $this->assertStringNotContainsString("'robots' => 'required|in:index,follow", $component);
    }
}
