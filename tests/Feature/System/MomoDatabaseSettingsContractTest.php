<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class MomoDatabaseSettingsContractTest extends TestCase
{
    public function test_momo_settings_use_database_and_safe_missing_value_defaults(): void
    {
        $service = file_get_contents(base_path('Modules/System/Services/Env/MomoConfigService.php'));

        $this->assertStringContainsString('SettingsService $settings', $service);
        $this->assertStringContainsString("self::DEFAULT_ENDPOINT", $service);
        $this->assertStringContainsString('return $default;', $service);
        $this->assertStringContainsString("Log::warning('MoMo setting unavailable.'", $service);
        $this->assertStringContainsString("updateMany(\$values, 'momo')", $service);
        $this->assertStringContainsString('Crypt::encryptString($secret)', $service);
        $this->assertStringContainsString('Crypt::decryptString(', $service);
        $this->assertStringNotContainsString('EnvManagerService', $service);
        $this->assertStringNotContainsString('env(', $service);
    }

    public function test_momo_ui_does_not_offer_to_update_env_file(): void
    {
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/momo-config.blade.php'));
        $this->assertStringContainsString('Lưu cấu hình MoMo vào cơ sở dữ liệu?', $view);
        $this->assertStringNotContainsString('vào .env?', $view);
    }
}
