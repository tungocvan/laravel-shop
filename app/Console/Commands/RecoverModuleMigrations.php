<?php

namespace App\Console\Commands;

use App\Modules\ModuleLifecycleManager;
use App\Modules\ModuleMigrationLedgerRepairer;
use App\Modules\ModuleMigrationRecoveryAssessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RecoverModuleMigrations extends Command
{
    protected $signature = 'module:migration-recover
        {module : Tên module trong thư mục Modules}
        {--apply : Ghi các migration ledger record đã được xác minh an toàn}';

    protected $description = 'Lập kế hoạch hoặc thực hiện phục hồi migration ledger đã được xác minh';

    public function handle(
        ModuleLifecycleManager $manager,
        ModuleMigrationRecoveryAssessor $assessor,
        ModuleMigrationLedgerRepairer $repairer,
    ): int {
        $module = $this->resolveModule((string) $this->argument('module'));
        if ($module === null) {
            $this->error('Không tìm thấy module hợp lệ.');

            return self::FAILURE;
        }

        $diagnosis = $manager->migrationDiagnosis($module);
        $assessment = $assessor->assess($module, $diagnosis);
        $apply = (bool) $this->option('apply');

        $this->components->info(($apply ? 'Apply migration recovery: ' : 'Dry-run migration recovery: ').$module['name']);

        if ($assessment->status === 'FRESH') {
            $this->info('FRESH: Không cần recovery ledger. Hãy bật module để chạy migration bình thường.');

            return self::SUCCESS;
        }

        if ($assessment->status === 'READY') {
            $this->info('READY: Schema và migration ledger đã đồng bộ.');

            return self::SUCCESS;
        }

        if ($assessment->isBlocked()) {
            $this->error('BLOCKED: Schema hoặc ownership contract chưa đủ điều kiện phục hồi ledger.');
            $this->line('Không có thay đổi nào được ghi vào database.');

            return self::FAILURE;
        }

        $this->warn('RECOVERABLE: Các migration sau có schema ownership đã được xác minh:');
        foreach ($assessment->recoverableMigrations as $migration) {
            $this->line("  - {$migration}");
        }

        if (! $apply) {
            $this->newLine();
            $this->line('DRY-RUN: Không có thay đổi nào được ghi vào database.');
            $this->line('Dùng --apply nếu muốn sửa migration ledger sau khi đã kiểm tra danh sách trên.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Xác nhận chỉ ghi các migration ledger record VERIFIED ở trên?', false)) {
            $this->warn('Đã hủy. Không có thay đổi nào được ghi vào database.');

            return self::SUCCESS;
        }

        $repaired = $repairer->repair($assessment);
        if ($repaired === []) {
            $this->info('Không còn migration ledger record nào cần sửa.');

            return self::SUCCESS;
        }

        $this->info('Đã phục hồi migration ledger an toàn:');
        foreach ($repaired as $migration) {
            $this->line("  - {$migration}");
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
}
