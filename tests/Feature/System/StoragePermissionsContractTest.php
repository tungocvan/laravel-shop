<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class StoragePermissionsContractTest extends TestCase
{
    public function test_local_filesystem_uses_group_safe_private_permissions(): void
    {
        $local = config('filesystems.disks.local');

        $this->assertSame(0660, $local['permissions']['file']['private']);
        $this->assertSame(0770, $local['permissions']['dir']['private']);
        $this->assertSame('private', $local['directory_visibility']);
    }

    public function test_public_filesystem_keeps_public_files_readable_without_opening_private_files(): void
    {
        $public = config('filesystems.disks.public');

        $this->assertSame(0664, $public['permissions']['file']['public']);
        $this->assertSame(0775, $public['permissions']['dir']['public']);
        $this->assertSame('public', $public['directory_visibility']);
    }

    public function test_docker_build_and_entrypoint_normalize_the_complete_storage_app_tree(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $entrypoint = file_get_contents(base_path('docker/entrypoint.sh'));

        foreach ([$dockerfile, $entrypoint] as $source) {
            $this->assertStringContainsString('find storage/app -type d -exec chmod 2770', $source);
            $this->assertStringContainsString('find storage/app -type f -exec chmod 0660', $source);
        }

        $this->assertStringContainsString('chown -R www-data:www-data storage bootstrap/cache', $entrypoint);
    }

    public function test_storage_permission_runbook_warns_against_world_writable_storage(): void
    {
        $runbook = file_get_contents(base_path('docs/STORAGE_PERMISSIONS.md'));

        $this->assertStringContainsString('storage/app', $runbook);
        $this->assertStringContainsString('namei -l', $runbook);
        $this->assertStringContainsString('chmod 777', $runbook);
        $this->assertStringContainsString('Docker / Compose', $runbook);
        $this->assertStringContainsString('VPS without Docker', $runbook);
    }
}
