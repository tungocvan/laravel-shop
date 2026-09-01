<?php

namespace App\Console\Commands;

use App\Modules\ModuleLifecycleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DiagnoseModuleMigrations extends Command
{
    protected $signature = 'module:migration-status {module : Tên module trong thư mục Modules}';

    protected $description = 'Chẩn đoán schema và migration ledger của module mà không thay đổi dữ liệu';

    public function handle(ModuleLifecycleManager $manager): int
    {
        $module = $this->resolveModule((string) $this->argument('module'));
        if ($module === null) {
            $this->error('Không tìm thấy module hợp lệ.');

            return self::FAILURE;
        }

        $diagnosis = $manager->migrationDiagnosis($module);

        $this->components->info("Migration status: {$module['name']}");
        $this->line('Trạng thái: '.$this->status(
            $diagnosis->isFresh(),
            $diagnosis->isReady(),
            $diagnosis->isResumable(),
            $diagnosis->needsRecovery(),
        ));
        $this->line('Bảng: '.count($diagnosis->existingTables).'/'.count($diagnosis->expectedTables).' hiện có');
        $this->line('Ledger: '.count($diagnosis->recordedMigrations).'/'.count($diagnosis->migrationFiles).' migration đã ghi nhận');

        if ($diagnosis->missingTables !== []) {
            $this->newLine();
            $this->warn('Bảng còn thiếu:');
            foreach ($diagnosis->missingTables as $table) {
                $this->line("  - {$table}");
            }
        }

        if ($diagnosis->missingMigrationRecords !== []) {
            $this->newLine();
            $this->warn('Migration ledger còn thiếu:');
            foreach ($diagnosis->missingMigrationRecords as $migration) {
                $this->line("  - {$migration}");
            }
        }

        if ($diagnosis->isResumable()) {
            $this->newLine();
            $this->info('RESUMABLE: Schema và migration ledger đang khớp theo thứ tự; có thể tiếp tục module:migrate an toàn.');
        }

        if ($diagnosis->needsRecovery()) {
            $this->newLine();
            $this->error('NEEDS_RECOVERY: Không chạy migrate tự động và không tự ghi migration ledger.');
            $this->line("Chạy 'php artisan module:migration-recover {$module['name']}' để xem dry-run recovery plan.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function resolveModule(string $name): ?array
    {
        if (! preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $name)) {
            return null;
        }

        $modulesRoot = realpath(base_path('Modules'));
        $path = realpath(base_path('Modules/'.$name));
        if ($modulesRoot === false || $path === false || ! is_dir($path)) {
            return null;
        }

        if (! str_starts_with($path, $modulesRoot.DIRECTORY_SEPARATOR)) {
            return null;
        }

        $manifestPath = collect([$path.'/config/module.php', $path.'/Config/module.php'])
            ->first(fn (string $candidate): bool => File::isFile($candidate));
        $manifest = $manifestPath ? require $manifestPath : [];

        return [
            'name' => (string) ($manifest['name'] ?? $name),
            'path' => $path,
        ];
    }

    private function status(bool $fresh, bool $ready, bool $resumable, bool $needsRecovery): string
    {
        return match (true) {
            $needsRecovery => 'NEEDS_RECOVERY',
            $ready => 'READY',
            $resumable => 'RESUMABLE',
            $fresh => 'FRESH',
            default => 'INCOMPLETE',
        };
    }
}
