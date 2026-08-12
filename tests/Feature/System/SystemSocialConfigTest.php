<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemSocialConfigTest extends TestCase
{
    public function test_social_config_security_contract(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/SocialConfig.php'));
        $service = file_get_contents(base_path('Modules/System/Services/Env/SocialConfigService.php'));

        $this->assertStringContainsString('AuthorizesSystemActions', $source);
        $this->assertStringContainsString("authorizePermission('system.env.update')", $source);
        $this->assertStringContainsString("'GOOGLE_CLIENT_SECRET'", $service);
        $this->assertStringContainsString("'FACEBOOK_CLIENT_SECRET'", $service);
        $this->assertStringContainsString("'TINYMCE_API_KEY'", $service);
        $this->assertStringContainsString("\$result[\$key] = '';", $service);
        $this->assertStringContainsString('.apps.googleusercontent.com', $service);
        $this->assertStringContainsString('ctype_digit', $service);
        $this->assertStringContainsString('GOOGLE_ANALYTICS_ID', $source);
        $this->assertStringNotContainsString('$e->getMessage()', $source);
    }
}
