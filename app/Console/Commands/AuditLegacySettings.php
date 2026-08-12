<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\System\Services\LegacySettingsAuditService;

class AuditLegacySettings extends Command
{
    protected $signature = 'settings:audit-legacy {--json : Xuất báo cáo JSON}';

    protected $description = 'Audit read-only dữ liệu settings và wp_settings trước khi hợp nhất';

    public function handle(LegacySettingsAuditService $auditService): int
    {
        $report = $auditService->audit();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $this->table(['Phân loại', 'Số lượng'], collect($report['summary'])
            ->map(fn (int $count, string $status): array => [$status, $count])->values()->all());

        $attention = collect($report['details'])
            ->whereIn('status', ['conflict', 'legacy_only'])
            ->map(fn (array $row): array => [
                $row['key'], $row['status'], $row['destination'],
                $row['canonical_type'] ?? '-', $row['legacy_type'] ?? '-',
            ])->all();

        if ($attention) {
            $this->table(['Key', 'Trạng thái', 'Đích', 'Type mới', 'Type cũ'], $attention);
        }

        $this->info('Audit hoàn tất. Không có dữ liệu nào bị thay đổi.');

        return self::SUCCESS;
    }
}
