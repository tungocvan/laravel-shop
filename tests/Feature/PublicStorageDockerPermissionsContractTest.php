<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicStorageDockerPermissionsContractTest extends TestCase
{
    #[Test]
    public function docker_build_keeps_public_storage_readable_while_private_storage_stays_restricted(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));

        $this->assertStringContainsString('find storage/app -type d -exec chmod 2770 {} \\;', $dockerfile);
        $this->assertStringContainsString('find storage/app -type f -exec chmod 0660 {} \\;', $dockerfile);
        $this->assertStringContainsString('find storage/app/public -type d -exec chmod 2775 {} \\;', $dockerfile);
        $this->assertStringContainsString('find storage/app/public -type f -exec chmod 0664 {} \\;', $dockerfile);
    }

    #[Test]
    public function container_entrypoint_repairs_public_storage_after_private_defaults_are_applied(): void
    {
        $entrypoint = file_get_contents(base_path('docker/entrypoint.sh'));

        $privateDirectoryRule = 'find storage/app -type d -exec chmod 2770 {} \\;';
        $privateFileRule = 'find storage/app -type f -exec chmod 0660 {} \\;';
        $publicDirectoryRule = 'find storage/app/public -type d -exec chmod 2775 {} \\;';
        $publicFileRule = 'find storage/app/public -type f -exec chmod 0664 {} \\;';

        $this->assertStringContainsString($privateDirectoryRule, $entrypoint);
        $this->assertStringContainsString($privateFileRule, $entrypoint);
        $this->assertStringContainsString($publicDirectoryRule, $entrypoint);
        $this->assertStringContainsString($publicFileRule, $entrypoint);

        $this->assertLessThan(
            strpos($entrypoint, $publicDirectoryRule),
            strpos($entrypoint, $privateDirectoryRule),
            'Public directory permissions must be applied after the private storage defaults.'
        );
        $this->assertLessThan(
            strpos($entrypoint, $publicFileRule),
            strpos($entrypoint, $privateFileRule),
            'Public file permissions must be applied after the private storage defaults.'
        );
    }
}
