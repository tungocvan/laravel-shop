<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemAdvancedConfigTest extends TestCase
{
    public function test_advanced_config_security_contract(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/AdvancedConfig.php'));
        $service = file_get_contents(base_path('Modules/System/Services/Env/AdvancedConfigService.php'));
        $node = file_get_contents(base_path('Modules/System/Services/Env/SystemConfigService.php'));

        $this->assertStringContainsString('AuthorizesSystemActions', $source);
        $this->assertGreaterThanOrEqual(4, substr_count($source, "authorizePermission('system.env.update')"));
        $this->assertStringContainsString("'BRIDGE_SECRET_KEY' => ''", $service);
        $this->assertStringContainsString('in:sync,database,redis', $source);
        $this->assertStringContainsString('queuePollAttempts > 15', $source);
        $this->assertStringContainsString('connectTimeout(2)', $node);
        $this->assertStringNotContainsString('response->body()', $node);
        $this->assertStringNotContainsString('$e->getMessage()', $node);
    }
}
