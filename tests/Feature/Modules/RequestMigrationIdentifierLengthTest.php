<?php

namespace Tests\Feature\Modules;

use Tests\TestCase;

class RequestMigrationIdentifierLengthTest extends TestCase
{
    public function test_request_stage_definition_unique_names_fit_mysql_identifier_limit(): void
    {
        $path = base_path('Modules/Request/database/migrations/2026_09_01_000001_create_request_definition_tables.php');
        $contents = file_get_contents($path);

        $this->assertIsString($contents);

        $names = [
            'request_stage_version_key_unique',
            'request_stage_version_position_unique',
        ];

        foreach ($names as $name) {
            $this->assertLessThanOrEqual(64, strlen($name));
            $this->assertStringContainsString("'{$name}'", $contents);
        }

        $this->assertStringNotContainsString(
            '$table->unique([\'request_type_version_id\', \'stage_key\']);',
            $contents,
        );
        $this->assertStringNotContainsString(
            '$table->unique([\'request_type_version_id\', \'position\']);',
            $contents,
        );
    }
}
