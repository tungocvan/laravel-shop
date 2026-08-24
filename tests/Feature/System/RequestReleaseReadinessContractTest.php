<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class RequestReleaseReadinessContractTest extends TestCase
{
    public function test_request_release_readiness_contract_is_locked(): void
    {
        $manifest = require base_path('Modules/Request/config/module.php');
        $files = require base_path('Modules/Request/config/files.php');
        $exports = require base_path('Modules/Request/config/exports.php');
        $notifications = require base_path('Modules/Request/config/notifications.php');
        $checker = file_get_contents(base_path('app/Modules/RequestReleaseReadinessChecker.php'));
        $command = file_get_contents(base_path('app/Console/Commands/CheckRequestReleaseReadiness.php'));

        $this->assertSame('Request', $manifest['name']);
        $this->assertFalse($manifest['default_enabled']);
        $this->assertCount(31, $manifest['permissions']);
        $this->assertCount(18, $manifest['tables']);

        $this->assertNotSame('public', $files['disk']);
        $this->assertSame('request-outbox', $notifications['outbox_queue']);
        $this->assertSame('request-notifications', $notifications['queue']);
        $this->assertSame('request-exports', $exports['queue']);

        $this->assertStringContainsString("'module_enabled'", $checker);
        $this->assertStringContainsString("'migration_ready'", $checker);
        $this->assertStringContainsString("'permissions_synced'", $checker);
        $this->assertStringContainsString("'super_admin_permissions'", $checker);
        $this->assertStringContainsString("'private_storage'", $checker);
        $this->assertStringContainsString("'queue_contract'", $checker);
        $this->assertStringContainsString('request:release-readiness', $command);
    }
}
