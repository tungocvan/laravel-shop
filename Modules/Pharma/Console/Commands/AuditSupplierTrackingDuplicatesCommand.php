<?php

namespace Modules\Pharma\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Pharma\Models\SupplierTracking;

class AuditSupplierTrackingDuplicatesCommand extends Command
{
    protected $signature = 'audit:pharma-supplier-tracking-duplicates';

    protected $description = 'Audit duplicate Supplier Tracking business keys without mutating data';

    public function handle(): int
    {
        $groups = [];

        SupplierTracking::query()
            ->select('id', 'medicine_id', 'supplier_name', 'working_date')
            ->whereNotNull('working_date')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$groups): void {
                foreach ($rows as $row) {
                    $supplier = Str::of((string) $row->supplier_name)->trim()->squish()->lower()->toString();
                    $date = $row->working_date?->format('Y-m-d') ?? (string) $row->working_date;
                    $key = $row->medicine_id.'|'.$supplier.'|'.$date;

                    $groups[$key] ??= [
                        'medicine_id' => $row->medicine_id,
                        'supplier' => $supplier,
                        'working_date' => $date,
                        'ids' => [],
                    ];
                    $groups[$key]['ids'][] = $row->id;
                }
            });

        $duplicates = collect($groups)->filter(fn (array $group): bool => count($group['ids']) > 1)->values();

        if ($duplicates->isEmpty()) {
            $this->info('PASS: Không phát hiện duplicate Supplier Tracking business key.');

            return self::SUCCESS;
        }

        $this->error('BLOCKED: Phát hiện duplicate Medicine + Supplier(normalized) + Working Date.');
        $this->table(
            ['Medicine ID', 'Supplier normalized', 'Working date', 'Record IDs'],
            $duplicates->map(fn (array $group): array => [
                $group['medicine_id'],
                $group['supplier'],
                $group['working_date'],
                implode(', ', $group['ids']),
            ])->all()
        );
        $this->warn('Không có dữ liệu nào bị thay đổi. Hãy xử lý duplicate có chủ đích trước khi chạy migration unique.');

        return self::FAILURE;
    }
}
