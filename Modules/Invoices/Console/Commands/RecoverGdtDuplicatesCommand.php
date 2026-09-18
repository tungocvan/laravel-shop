<?php

namespace Modules\Invoices\Console\Commands;

use Illuminate\Console\Command;
use Modules\Invoices\Services\GdtDuplicateRecoveryService;

final class RecoverGdtDuplicatesCommand extends Command
{
    protected $signature = 'invoices:recover-gdt-duplicates
        {--year=2026 : Năm hóa đơn cần kiểm tra}
        {--type=sold : sold hoặc purchase}
        {--apply : Thực hiện recovery; mặc định chỉ dry-run}';

    protected $description = 'Dry-run hoặc recovery duplicate GDT đã được xác minh bằng canonical identity và RAW hash';

    public function handle(GdtDuplicateRecoveryService $service): int
    {
        $year = (int) $this->option('year');
        $type = (string) $this->option('type');
        if (! in_array($type, ['sold', 'purchase'], true)) {
            $this->error('--type chỉ nhận sold hoặc purchase.');

            return self::FAILURE;
        }

        $stats = $service->recover($year, $type, (bool) $this->option('apply'));
        $this->table(['Chỉ tiêu', 'Giá trị'], collect($stats)
            ->except('blocked_reasons')
            ->map(fn ($value, $key) => [$key, is_scalar($value) ? (string) $value : json_encode($value)])
            ->values()
            ->all());

        foreach ($stats['blocked_reasons'] as $reason) {
            $this->warn($reason);
        }

        return $stats['blocked'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
