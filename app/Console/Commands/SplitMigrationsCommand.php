<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SplitMigrationsCommand extends Command
{
    protected $signature = 'split:migrations {file}';

    protected $description = 'Split merged migration into ordered single-table migrations (safe migrate order)';

    public function handle(): int
    {
        $fileName = $this->argument('file');

        $paths = [
            database_path('migrations'),
            base_path('Modules'),
        ];

        $migrationPath = $this->findMigrationFile($fileName, $paths);

        if (! $migrationPath) {
            $this->error("Migration file not found: {$fileName}");
            return self::FAILURE;
        }

        $content = file_get_contents($migrationPath);

        // Lấy Schema::create theo ĐÚNG THỨ TỰ xuất hiện
        preg_match_all(
            "/Schema::create\\(\\s*['\"]([^'\"]+)['\"]\\s*,\\s*function\\s*\\([^)]*\\)\\s*\\{([\\s\\S]*?)\\n\\s*\\}\\s*\\);/m",
            $content,
            $matches,
            PREG_SET_ORDER
        );

        if (count($matches) < 2) {
            $this->info('Migration already compliant. No split needed.');
            return self::SUCCESS;
        }

        $this->info('Splitting migration (preserve merge order): ' . basename($migrationPath));

        $baseTimestamp = $this->extractRawTimestamp(basename($migrationPath));
        $directory     = dirname($migrationPath);

        foreach ($matches as $order => $match) {
            $table      = $match[1];
            $schemaBody = rtrim($match[2]);

            // tăng timestamp theo thứ tự xuất hiện (KHÔNG dùng strtotime)
            $timestamp = $this->incrementTimestamp($baseTimestamp, $order);

            $fileName = "{$timestamp}_create_{$table}_table.php";
            $path     = $directory . DIRECTORY_SEPARATOR . $fileName;

            $stub = <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
{$schemaBody}
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};
PHP;

            file_put_contents($path, $stub);
            $this->line("  → {$fileName}");
        }

        $this->moveToBackup($migrationPath);

        $this->info('Split completed. Order preserved. Safe to migrate ✅');

        return self::SUCCESS;
    }

    /**
     * Trả về raw timestamp dạng: 2024_01_01_000001
     */
    private function extractRawTimestamp(string $file): string
    {
        return substr($file, 0, 17);
    }

    /**
     * Tăng timestamp theo thứ tự: +1 giây mỗi bảng
     */
    private function incrementTimestamp(string $base, int $offset): string
    {
        [$y, $m, $d, $his] = explode('_', $base);

        $time = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            "{$y}-{$m}-{$d} " . substr($his, 0, 2) . ':' . substr($his, 2, 2) . ':' . substr($his, 4, 2)
        );

        $time->modify("+{$offset} seconds");

        return $time->format('Y_m_d_His');
    }

    private function moveToBackup(string $migrationPath): void
    {
        $backupDir = database_path('migrations/_backup');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backupPath = $backupDir . '/' . basename($migrationPath) . '.backup';

        if (! file_exists($backupPath)) {
            rename($migrationPath, $backupPath);
        }

        $this->info('Original migration moved to _backup');
    }

    private function findMigrationFile(string $input, array $paths): ?string
    {
        if (file_exists(base_path($input))) {
            return realpath(base_path($input));
        }

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path)
            );

            foreach ($it as $file) {
                if (
                    $file->isFile() &&
                    $file->getFilename() === $input &&
                    ! str_contains($file->getPathname(), '_backup')
                ) {
                    return $file->getRealPath();
                }
            }
        }

        return null;
    }
}
