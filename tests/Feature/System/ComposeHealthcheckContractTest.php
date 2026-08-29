<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class ComposeHealthcheckContractTest extends TestCase
{
    public function test_worker_healthchecks_do_not_embed_nul_in_shell_arguments(): void
    {
        $compose = file_get_contents(base_path('compose.yaml'));

        $this->assertIsString($compose);
        $this->assertStringNotContainsString("tr '\\0'", $compose);
        $this->assertStringContainsString('str_contains($$c,\"queue:work\")', $compose);
        $this->assertStringContainsString('str_contains($$c,\"admission-documents\")', $compose);
        $this->assertStringContainsString('str_contains($$c,\"schedule:work\")', $compose);
    }
}
