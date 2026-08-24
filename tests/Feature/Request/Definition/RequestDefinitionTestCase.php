<?php

namespace Tests\Feature\Request\Definition;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

abstract class RequestDefinitionTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--path' => 'Modules/Request/database/migrations', '--force' => true]);
    }
}
