<?php

namespace App\Modules;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ModuleLifecycleManager
{
    public function databaseStatus(array $module): array
    {
        $tables = $this->expectedTables($module);
        $missing = array_values(array_filter($tables, fn (string $table): bool => ! Schema::hasTable($table)));

        return [
            'tables' => $tables,
            'missing_tables' => $missing,
            'ready' => $missing === [],
        ];
    }

    public function migrateIfNeeded(array $module): array
    {
        $before = $this->databaseStatus($module);
        $migrationPath = $this->migrationPath($module['path']);

        if ($migrationPath === null || ($before['tables'] !== [] && $before['ready'])) {
            return $before + ['migrated' => false, 'output' => ''];
        }

        $relativePath = str_replace('\\', '/', ltrim(str_replace(base_path(), '', $migrationPath), '/\\'));
        $exitCode = Artisan::call('migrate', [
            '--path' => $relativePath,
            '--force' => true,
            '--no-interaction' => true,
        ]);

        if ($exitCode !== 0) {
            throw new \RuntimeException(trim(Artisan::output()) ?: "Migration của module {$module['name']} thất bại.");
        }

        $after = $this->databaseStatus($module);
        if ($after['missing_tables'] !== []) {
            throw new \RuntimeException(
                'Migration hoàn tất nhưng vẫn thiếu bảng: ' . implode(', ', $after['missing_tables']) . '.'
            );
        }

        return $after + ['migrated' => true, 'output' => trim(Artisan::output())];
    }

    public function archive(array $module, array $registry): string
    {
        if ($module['required'] ?? false) {
            throw new \LogicException("{$module['name']} là Shell Module và không thể xóa.");
        }

        if ($module['enabled'] ?? false) {
            throw new \LogicException("Hãy tắt module {$module['name']} trước khi xóa.");
        }

        $dependents = collect($registry)
            ->filter(fn (array $candidate): bool => in_array($module['name'], $candidate['depends'] ?? [], true))
            ->keys()
            ->values();

        if ($dependents->isNotEmpty()) {
            throw new \LogicException(
                "Không thể xóa module {$module['name']} vì còn được khai báo phụ thuộc bởi: {$dependents->join(', ')}."
            );
        }

        $source = realpath($module['path']);
        $modulesRoot = realpath(base_path('Modules'));
        if ($source === false || $modulesRoot === false || ! str_starts_with($source, $modulesRoot . DIRECTORY_SEPARATOR)) {
            throw new \RuntimeException('Đường dẫn module không hợp lệ.');
        }

        $trashRoot = storage_path('app/module-trash');
        File::ensureDirectoryExists($trashRoot);
        $destination = $trashRoot . DIRECTORY_SEPARATOR . $module['name'] . '-' . now()->format('Ymd-His');

        if (! File::moveDirectory($source, $destination)) {
            throw new \RuntimeException("Không thể chuyển module {$module['name']} vào thư mục lưu trữ.");
        }

        return $destination;
    }

    private function expectedTables(array $module): array
    {
        $manifestPath = collect([
            $module['path'] . '/config/module.php',
            $module['path'] . '/Config/module.php',
        ])->first(fn (string $path): bool => is_file($path));
        $manifest = $manifestPath ? require $manifestPath : [];
        $tables = is_array($manifest['tables'] ?? null) ? $manifest['tables'] : [];

        if ($tables === []) {
            $path = $this->migrationPath($module['path']);
            foreach ($path ? File::files($path) : [] as $file) {
                preg_match_all("/Schema::create\\(\\s*['\"]([^'\"]+)['\"]/", File::get($file->getPathname()), $matches);
                $tables = array_merge($tables, $matches[1] ?? []);
            }
        }

        return array_values(array_unique(array_filter(array_map('strval', $tables))));
    }

    private function migrationPath(string $modulePath): ?string
    {
        foreach (['database/migrations', 'Database/Migrations'] as $relative) {
            $path = $modulePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (is_dir($path)) {
                return $path;
            }
        }

        return null;
    }
}
