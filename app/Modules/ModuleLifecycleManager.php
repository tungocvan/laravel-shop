<?php

namespace App\Modules;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

    public function migrationDiagnosis(array $module): ModuleMigrationDiagnosis
    {
        $expectedTables = $this->expectedTables($module);
        $existingTables = array_values(array_filter($expectedTables, fn (string $table): bool => Schema::hasTable($table)));
        $missingTables = array_values(array_diff($expectedTables, $existingTables));
        $migrationFiles = $this->migrationNames($module['path']);
        $recordedMigrations = [];

        if (Schema::hasTable('migrations') && $migrationFiles !== []) {
            $recordedMigrations = DB::table('migrations')
                ->whereIn('migration', $migrationFiles)
                ->orderBy('migration')
                ->pluck('migration')
                ->map('strval')
                ->all();
        }

        $missingMigrationRecords = array_values(array_diff($migrationFiles, $recordedMigrations));

        return new ModuleMigrationDiagnosis(
            expectedTables: $expectedTables,
            existingTables: $existingTables,
            missingTables: $missingTables,
            migrationFiles: $migrationFiles,
            recordedMigrations: $recordedMigrations,
            missingMigrationRecords: $missingMigrationRecords,
            resumable: $this->isResumableState(
                $module['path'],
                $expectedTables,
                $existingTables,
                $migrationFiles,
                $recordedMigrations,
                $missingMigrationRecords,
            ),
        );
    }

    public function migrateIfNeeded(array $module): array
    {
        $before = $this->databaseStatus($module);
        $migrationPath = $this->migrationPath($module['path']);

        if ($migrationPath === null) {
            return $before + ['migrated' => false, 'output' => ''];
        }

        $diagnosis = $this->migrationDiagnosis($module);
        if ($diagnosis->isReady()) {
            return $before + ['migrated' => false, 'output' => ''];
        }

        if ($diagnosis->needsRecovery()) {
            throw new \RuntimeException(
                "Cơ sở dữ liệu module {$module['name']} đang ở trạng thái migration không đồng bộ: "
                .'đã có bảng '.implode(', ', $diagnosis->existingTables)
                .'; còn thiếu '.implode(', ', $diagnosis->missingTables)
                .'; migration ledger còn thiếu '.implode(', ', $diagnosis->missingMigrationRecords)
                .'. Không tự động chạy lại migration vì có thể ghi đè hoặc xung đột dữ liệu. '
                .'Hãy chạy chẩn đoán migration của module, đối chiếu schema với từng migration và chỉ phục hồi ledger sau khi đã xác minh.'
            );
        }

        $relativePath = str_replace('\\', '/', ltrim(str_replace(base_path(), '', $migrationPath), '/\\'));
        $exitCode = Artisan::call('migrate', [
            '--path' => $relativePath,
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new \RuntimeException(trim(Artisan::output()) ?: "Migration của module {$module['name']} thất bại.");
        }

        $after = $this->databaseStatus($module);
        if ($after['missing_tables'] !== []) {
            throw new \RuntimeException(
                'Migration hoàn tất nhưng vẫn thiếu bảng: '.implode(', ', $after['missing_tables']).'.'
            );
        }

        return $after + ['migrated' => true, 'output' => trim(Artisan::output())];
    }

    private function expectedTables(array $module): array
    {
        $manifestPath = collect([
            $module['path'].'/config/module.php',
            $module['path'].'/Config/module.php',
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

    private function migrationNames(string $modulePath): array
    {
        $path = $this->migrationPath($modulePath);
        if ($path === null) {
            return [];
        }

        return collect(File::files($path))
            ->map(fn ($file): string => pathinfo($file->getFilename(), PATHINFO_FILENAME))
            ->sort()
            ->values()
            ->all();
    }

    private function isResumableState(
        string $modulePath,
        array $expectedTables,
        array $existingTables,
        array $migrationFiles,
        array $recordedMigrations,
        array $missingMigrationRecords,
    ): bool {
        if ($recordedMigrations === [] || $missingMigrationRecords === []) {
            return false;
        }

        if ($recordedMigrations !== array_slice($migrationFiles, 0, count($recordedMigrations))) {
            return false;
        }

        $createdTablesByMigration = $this->createdTablesByMigration($modulePath);
        $recordedTables = [];
        $pendingTables = [];

        foreach ($recordedMigrations as $migration) {
            $recordedTables = array_merge($recordedTables, $createdTablesByMigration[$migration] ?? []);
        }

        foreach ($missingMigrationRecords as $migration) {
            $pendingTables = array_merge($pendingTables, $createdTablesByMigration[$migration] ?? []);
        }

        $recordedExpectedTables = array_values(array_intersect($expectedTables, array_unique($recordedTables)));
        $pendingExpectedTables = array_values(array_intersect($expectedTables, array_unique($pendingTables)));

        sort($recordedExpectedTables);
        sort($pendingExpectedTables);
        sort($existingTables);
        $missingTables = array_values(array_diff($expectedTables, $existingTables));
        sort($missingTables);

        return $existingTables === $recordedExpectedTables
            && $missingTables === $pendingExpectedTables;
    }

    private function createdTablesByMigration(string $modulePath): array
    {
        $path = $this->migrationPath($modulePath);
        if ($path === null) {
            return [];
        }

        $ownership = [];

        foreach (File::files($path) as $file) {
            $migration = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            preg_match_all("/Schema::create\\(\\s*['\"]([^'\"]+)['\"]/", File::get($file->getPathname()), $matches);
            $ownership[$migration] = array_values(array_unique(array_map('strval', $matches[1] ?? [])));
        }

        return $ownership;
    }

    private function migrationPath(string $modulePath): ?string
    {
        foreach (['database/migrations', 'Database/Migrations'] as $relative) {
            $path = $modulePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (is_dir($path)) {
                return $path;
            }
        }

        return null;
    }
}
