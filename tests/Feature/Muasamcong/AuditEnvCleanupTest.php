<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Support\Facades\Artisan;
use Modules\Muasamcong\Console\Commands\AuditEnvCleanup;
use Tests\TestCase;

class AuditEnvCleanupTest extends TestCase
{
    public function test_read_only_cleanup_audit_is_registered(): void
    {
        $command = $this->app->make(AuditEnvCleanup::class);

        $this->assertSame('muasamcong:audit-env-cleanup', $command->getName());
        $this->assertTrue(Artisan::has('muasamcong:audit-env-cleanup'));
        $this->assertFalse($command->getDefinition()->hasOption('force'));
    }
}
