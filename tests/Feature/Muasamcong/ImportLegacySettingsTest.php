<?php

namespace Tests\Feature\Muasamcong;

use Modules\Muasamcong\Console\Commands\ImportLegacySettings;
use Modules\Muasamcong\Services\MuasamcongUrlValidator;
use Tests\TestCase;

class ImportLegacySettingsTest extends TestCase
{
    public function test_command_is_registered_and_supports_dry_run(): void
    {
        $command = $this->app->make(ImportLegacySettings::class);
        $this->assertSame('muasamcong:import-env-settings', $command->getName());
        $this->assertTrue($command->getDefinition()->hasOption('dry-run'));
        $this->assertTrue($command->getDefinition()->hasOption('force'));
    }

    public function test_import_validation_rejects_untrusted_urls_and_out_of_range_values(): void
    {
        $command = $this->app->make(ImportLegacySettings::class);
        $valid = new \ReflectionMethod($command, 'valid');
        $urls = $this->app->make(MuasamcongUrlValidator::class);

        $this->assertTrue($valid->invoke($command, 'contractor_endpoint', 'https://muasamcong.mof.gov.vn/o/egp-portal-contractor-selection-v2/services/smart/search', $urls));
        $this->assertFalse($valid->invoke($command, 'contractor_endpoint', 'https://example.com/steal', $urls));
        $this->assertFalse($valid->invoke($command, 'timeout', '121', $urls));
        $this->assertFalse($valid->invoke($command, 'page_size', '0', $urls));
        $this->assertTrue($valid->invoke($command, 'verify_ssl', 'true', $urls));
        $this->assertFalse($valid->invoke($command, 'session_cookie', "cookie\nInjected: x", $urls));
    }
}
