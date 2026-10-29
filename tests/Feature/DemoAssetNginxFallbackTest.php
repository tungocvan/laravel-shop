<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class DemoAssetNginxFallbackTest extends TestCase
{
    public function test_demo_assets_fall_back_to_laravel_before_static_file_regex(): void
    {
        $config = file_get_contents(__DIR__.'/../../docker/nginx/default.conf');

        $this->assertIsString($config);
        $this->assertMatchesRegularExpression(
            '#location\s+\^~\s+/demo-assets/\s*\{\s*try_files\s+\$uri\s+\$uri/\s+/index\.php\?\$query_string;\s*\}#',
            $config
        );

        $this->assertLessThan(
            strpos($config, 'location ~* \\.'),
            strpos($config, 'location ^~ /demo-assets/')
        );
    }

    public function test_generic_static_assets_and_php_upstream_are_unchanged(): void
    {
        $config = file_get_contents(__DIR__.'/../../docker/nginx/default.conf');

        $this->assertStringContainsString('fastcgi_pass app:9000;', $config);
        $this->assertStringContainsString('location ~* \\.(?:css|js|jpg|jpeg|gif|png|svg|ico|woff2?)$', $config);
        $this->assertStringContainsString('location ^~ /livewire/', $config);
    }
}
