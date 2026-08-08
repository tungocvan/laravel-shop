<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MergeMigrationsCommand extends Command
{
    protected $signature = 'migrate:merge-schema';
    protected $description = 'Merge all migrations into a single schema migration file';

    protected string $outputFile = 'database/migrations/0000_00_00_000000_merged_schema.php';

    public function handle(): int
    {
        $migrationFiles = $this->getAllMigrationFiles();

        if (empty($migrationFiles)) {
            $this->error('No migration files found.');
            return Command::FAILURE;
        }

        $schemaBlocks = [];

        foreach ($migrationFiles as $file) {
            $content = File::get($file);
            $schemaBlocks = array_merge(
                $schemaBlocks,
                $this->extractSchema($content, $file)
            );
        }

        $merged = $this->buildMigrationFile(array_filter($schemaBlocks));

        File::put(base_path($this->outputFile), $merged);

        $this->info('✅ Merged migration created: ' . $this->outputFile);

        // 🔥 MOVE MIGRATIONS TO _backup
        $this->moveMigrationsToBackup($migrationFiles);

        $this->info('📦 Original migrations moved to database/migrations/_backup');

        return Command::SUCCESS;
    }


    /**
     * Get all migration files from core + modules
     */
    protected function getAllMigrationFiles(): array
    {
        $paths = [
            database_path('migrations'),
        ];

        // Modules/*/database/migrations
        if (is_dir(base_path('Modules'))) {
            foreach (File::directories(base_path('Modules')) as $module) {
                $moduleMigration = $module . '/database/migrations';
                if (is_dir($moduleMigration)) {
                    $paths[] = $moduleMigration;
                }
            }
        }

        $files = [];
        foreach ($paths as $path) {
            $files = array_merge(
                $files,
                File::glob($path . '/*.php') ?: []
            );
        }

        sort($files);

        return $files;
    }

    /**
     * Extract Schema::create & Schema::table blocks
     */

     protected function extractSchema(string $content, string $file): array
    {
        $tokens = token_get_all($content);

        $schemas = [];
        $capturing = false;
        $buffer = '';
        $braceLevel = 0;

        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            $token = $tokens[$i];

            // Detect Schema::create OR Schema::table
            if (
                is_array($token)
                && $token[0] === T_STRING
                && $token[1] === 'Schema'
                && isset($tokens[$i + 1], $tokens[$i + 2])
                && is_array($tokens[$i + 1])
                && $tokens[$i + 1][0] === T_DOUBLE_COLON
                && is_array($tokens[$i + 2])
                && in_array($tokens[$i + 2][1], ['create', 'table'], true)
            ) {
                $capturing = true;
                $buffer = '';
                $braceLevel = 0;
            }

            if ($capturing) {
                $buffer .= is_array($token) ? $token[1] : $token;

                if ($token === '{') {
                    $braceLevel++;
                }

                if ($token === '}') {
                    $braceLevel--;
                }

                // kết thúc Schema block khi closure đóng xong và gặp ;
                if ($braceLevel === 0 && $token === ';') {
                    $schemas[] = $buffer;
                    $capturing = false;
                }
            }
        }

        if (empty($schemas)) {
            return [];
        }

        return array_map(
            fn ($schema) => "// Source: {$file}\n{$schema}",
            $schemas
        );
    }


    /**
     * Build final migration file
     */
    protected function buildMigrationFile(array $schemas): string
    {
        $body = implode("\n\n", $schemas);

        return <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
{$this->indent($body, 2)}
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
PHP;
    }

    protected function indent(string $text, int $level = 1): string
    {
        $indent = str_repeat('    ', $level);
        return implode("\n", array_map(
            fn ($line) => $indent . $line,
            explode("\n", $text)
        ));
    }

    protected function moveMigrationsToBackup(array $files): void
    {
        $backupDir = database_path('migrations/_backup');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        foreach ($files as $file) {
            // bỏ qua file merged
            if (str_contains($file, 'merged_schema.php')) {
                continue;
            }

            // bỏ qua file đã ở _backup
            if (str_contains($file, '_backup')) {
                continue;
            }

            $target = $backupDir . '/' . basename($file);

            if (! file_exists($target)) {
                rename($file, $target);
            }
        }
    }

}
