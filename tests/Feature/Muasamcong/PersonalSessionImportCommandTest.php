<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PersonalSessionImportCommandTest extends TestCase
{
    public function test_personal_session_import_command_is_registered(): void
    {
        $this->assertArrayHasKey('msc:import-personal-session', Artisan::all());
    }
}
