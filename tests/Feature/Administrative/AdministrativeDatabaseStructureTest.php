<?php

namespace Tests\Feature\Administrative;

use App\Modules\Migration\Guards\PrefixGuard;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdministrativeDatabaseStructureTest extends TestCase
{
    public function test_administrative_tables_are_registered_by_the_module_manifest(): void
    {
        $manifest = require base_path('Modules/Administrative/config/module.php');

        $this->assertSame('Administrative', $manifest['name']);
        $this->assertSame('domain', $manifest['type']);
        $this->assertSame($this->expectedTables(), $manifest['tables']);
    }

    public function test_every_migration_obeys_the_module_table_prefix(): void
    {
        $guard = app(PrefixGuard::class);
        $migrations = File::files(base_path('Modules/Administrative/database/migrations'));

        $this->assertCount(7, $migrations);

        foreach ($migrations as $migration) {
            $guard->check('Administrative', $migration->getPathname());
            $this->addToAssertionCount(1);
        }
    }

    public function test_migrations_define_the_approved_tables_and_critical_columns(): void
    {
        $definitions = collect(File::files(base_path('Modules/Administrative/database/migrations')))
            ->mapWithKeys(fn ($file): array => [$file->getFilename() => File::get($file->getPathname())])
            ->implode("\n");

        foreach ($this->expectedTables() as $table) {
            $this->assertStringContainsString("Schema::create('{$table}'", $definitions);
        }

        foreach ([
            'lookup_token_hash',
            'wants_email_receipt',
            'revision_count',
            'supplement_reason',
            'deleted_at',
            'submission_code',
            'submitted_at',
            'processed_by',
            'version',
            'checksum',
            'from_status',
            'to_status',
            'actor_type',
        ] as $column) {
            $this->assertStringContainsString("'{$column}'", $definitions);
        }
    }

    private function expectedTables(): array
    {
        return [
            'administrative_procedures',
            'administrative_submissions',
            'administrative_files',
            'administrative_status_histories',
        ];
    }
}
