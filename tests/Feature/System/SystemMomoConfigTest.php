<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemMomoConfigTest extends TestCase
{
    public function test_momo_config_security_contract(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/MomoConfig.php'));
        $service = file_get_contents(base_path('Modules/System/Services/Env/MomoConfigService.php'));

        $this->assertStringContainsString('AuthorizesSystemActions', $source);
        $this->assertGreaterThanOrEqual(2, substr_count($source, "authorizePermission('system.env.update')"));
        $this->assertStringContainsString("'MOMO_ACCESS_KEY' => ''", $service);
        $this->assertStringContainsString("'MOMO_SECRET_KEY' => ''", $service);
        $this->assertStringContainsString("str_ends_with(\$host, '.momo.vn')", $service);
        $this->assertStringContainsString('connectTimeout(2)', $service);
        $this->assertStringContainsString("System::livewire.settings.momo-config", $source);
        $this->assertStringNotContainsString('Admin::livewire.settings.momo-config', $source);
        $this->assertStringNotContainsString('$e->getMessage()', $source);
    }
}
