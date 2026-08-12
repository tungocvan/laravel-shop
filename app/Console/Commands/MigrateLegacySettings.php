<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\System\Services\LegacySettingsMigrationService;

class MigrateLegacySettings extends Command
{
    protected $signature = 'settings:migrate-legacy
        {--apply : Thực sự ghi các key global chưa tồn tại vào bảng settings}';

    protected $description = 'Dry-run hoặc migrate idempotent global settings từ wp_settings';

    public function handle(LegacySettingsMigrationService $migrationService): int
    {
        $apply = (bool) $this->option('apply');
        $result = $migrationService->migrate($apply);

        $this->table(['Chỉ số', 'Giá trị'], collect($result)
            ->map(fn ($value, string $key): array => [$key, is_bool($value) ? ($value ? 'yes' : 'no') : $value])
            ->values()->all());

        if (! $apply) {
            $this->warn('DRY-RUN: không có dữ liệu nào bị thay đổi. Dùng --apply sau khi phê duyệt.');
        } else {
            $this->info('Đã migrate các key global đủ điều kiện. Các key home_* được giữ nguyên.');
        }

        return $result['conflicts'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
