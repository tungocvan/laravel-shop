<?php

namespace App\Console\Commands;

use App\Modules\ModuleLifecycleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RecoverModuleMigrations extends Command
{
    protected $signature = 'module:migration-recover {module : Tên module trong thư mục Modules}';

    protected $description = 'Lập kế hoạch phục hồi migration ledger an toàn; mặc định chỉ dry-run';

    public function handle(ModuleLifecycleManager $manager): int
    {
        $module = $this->resolveModule((string) $this->argument('module'));
        if ($module === null) {
            $this->error('Không tìm thấy module hợp lệ.');

            return self::FAILURE;
        }

        $diagnosis = $manager->migrationDiagnosis($module);

        $this->components->info("Dry-run migration recovery: {$module['name']}");
        $this->line('Không có thay đổi nào được ghi vào database.');
        $this->newLine();

        if ($diagnosis->isFresh()) {
            $this->info('FRESH: Không cần recovery ledger. Hãy bật module để chạy migration bình thường.');

            return self::SUCCESS;
        }

        if ($diagnosis->isReady() && $diagnosis->missingMigrationRecords === []) {
            $this->info('READY: Schema và migration ledger đã đồng bộ.');

            return self::SUCCESS;
        }

        $this->table(
            ['Hạng mục', 'Giá trị'],
            [
                ['Bảng hiện có', implode(', ', $diagnosis->existingTables) ?: '(không có)'],
                ['Bảng còn thiếu', implode(', ', $diagnosis->missingTables) ?: '(không có)'],
                ['Ledger còn thiếu', implode(', ', $diagnosis->missingMigrationRecords) ?: '(không có)'],
            ]
        );

        $this->newLine();
        $this->warn('Recovery tự động đang bị khóa theo nguyên tắc fail-safe.');
        $this->line('Không migration record nào được tạo chỉ dựa trên việc một vài bảng đang tồn tại.');
        $this->line('Cần xác minh schema do từng migration tạo ra trước khi cho phép ghi ledger.');

        return self::FAILURE;
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
}
