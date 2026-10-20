<?php

namespace Tests\Feature\Muasamcong;

use Modules\Muasamcong\Console\Commands\AuditEnvCleanup;
use Tests\TestCase;

class AuditEnvCleanupTest extends TestCase
{
    public function test_read_only_cleanup_audit_is_registered(): void
    {
        $command = $this->app->make(AuditEnvCleanup::class);

        $this->assertSame('muasamcong:audit-env-cleanup', $command->getName());
        $this->assertSame('muasamcong:audit-env-cleanup', \Illuminate\Support\Facades\Artisan::all()['muasamcong:audit-env-cleanup']->getName());
        $this->assertFalse($command->getDefinition()->hasOption('force'));
    }
}
