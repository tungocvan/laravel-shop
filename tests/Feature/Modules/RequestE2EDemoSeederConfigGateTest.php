<?php

namespace Tests\Feature\Modules;

use Tests\TestCase;

class RequestE2EDemoSeederConfigGateTest extends TestCase
{
    public function test_production_demo_gate_reads_cached_request_config_instead_of_env_directly(): void
    {
        $path = base_path('database/seeders/RequestE2EDemoSeeder.php');
        $contents = file_get_contents($path);

        $this->assertIsString($contents);
        $this->assertStringContainsString(
            "config('request.settings.demo_seeders_enabled', false)",
            $contents,
        );
        $this->assertStringNotContainsString(
            "env('REQUEST_ENV'",
            $contents,
        );
    }
}
