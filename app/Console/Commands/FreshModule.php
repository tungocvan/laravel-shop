<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class FreshModule extends Command
{
    protected $signature = 'module:fresh
        {name : Ten module}
        {--force : Bo qua confirm}
        {--dry-run : Chi xem, khong thuc thi}';

    protected $description = 'Fresh module (drop tables + migrate lai) an toan';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $modulePath = base_path("Modules/{$name}");

        if (! $this->files->exists($modulePath)) {
            $this->error("Module {$name} khong ton tai.");
            return self::INVALID;
        }

        $migrationPath = $this->getMigrationPath($modulePath);

        if ($migrationPath === null) {
            $this->warn("Module {$name} khong co migrations.");
            return self::SUCCESS;
        }

        $tables = $this->detectModuleTables($modulePath);

        $this->line('');
        $this->info("=== MODULE FRESH: {$name} ===");

        if (empty($tables)) {
            $this->warn("Khong tim thay table nao.");
        } else {
            $this->warn("Cac table se bi DROP:");
            foreach ($tables as $table) {
                $this->line(" - {$table}");
            }
        }

        // 👉 Dry run
        if ($this->option('dry-run')) {
            $this->info("Dry-run: khong co gi bi thay doi.");
            return self::SUCCESS;
        }

        // ❌ Chặn production
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error("Production mode. Can --force de thuc hien.");
            return self::INVALID;
        }

        // 👉 Confirm
        if (! $this->option('force')) {
            if (! $this->confirm("Ban CHAC CHAN muon fresh module {$name}?")) {
                $this->warn("Da huy.");
                return self::SUCCESS;
            }
        }

        // 🔥 Drop tables (disable FK)
        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
                $this->warn("Dropped: {$table}");
            }
        }

        Schema::enableForeignKeyConstraints();

        // 🔥 Migrate lại module
        $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $migrationPath);

        Artisan::call('migrate', [
            '--path' => $relativePath,
            '--force' => true,
        ]);

        $this->line(Artisan::output());

        $this->info("Module {$name} da fresh thanh cong.");

        return self::SUCCESS;
    }

    protected function detectModuleTables(string $modulePath): array
    {
        // 👉 Ưu tiên config module.php (best practice)
        $configPath = $modulePath.'/config/module.php';

        if ($this->files->exists($configPath)) {
            $config = require $configPath;

            if (! empty($config['tables']) && is_array($config['tables'])) {
                return $config['tables'];
            }
        }

        // 👉 Fallback: parse migration
        $migrationPath = $this->getMigrationPath($modulePath);

        if ($migrationPath === null) {
            return [];
        }

        $tables = [];

        foreach ($this->files->allFiles($migrationPath) as $file) {
            $content = $this->files->get($file->getPathname());

            preg_match_all("/Schema::create\\(['\"](.*?)['\"]/", $content, $matches);

            if (! empty($matches[1])) {
                $tables = array_merge($tables, $matches[1]);
            }
        }

        return array_unique($tables);
    }

    protected function getMigrationPath(string $modulePath): ?string
    {
        $paths = [
            'database/migrations',
            'Database/Migrations',
        ];

        foreach ($paths as $segment) {
            $path = $modulePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $segment);

            if ($this->files->exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
