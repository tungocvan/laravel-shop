<?php

namespace App\Console\Commands;

use App\Services\MasothueLookupService;
use Illuminate\Console\Command;
use Throwable;

class MstLookup extends Command
{
    protected $signature = 'mst:lookup
        {query : Ten doanh nghiep, don vi hoac ma so thue can tra cuu}
        {--json : Xuat ket qua dang JSON}';

    protected $description = 'Tra cuu ma so thue va thong tin phap ly tu MaSoThue';

    public function handle(MasothueLookupService $lookupService): int
    {
        try {
            $result = $lookupService->lookup((string) $this->argument('query'));
        } catch (Throwable $exception) {
            $this->error('Tra cuu that bai: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->table(
            ['Truong', 'Gia tri'],
            [
                ['Tu khoa', $result['query']],
                ['Ten phap ly', $result['name']],
                ['Ma so thue', $result['tax_code']],
                ['Kieu khop', $result['match_type']],
                ['Tinh trang', $result['status'] ?? ''],
                ['Dia chi thue', $result['tax_address'] ?? ''],
                ['Nguoi dai dien', $result['representative'] ?? ''],
                ['Ngay hoat dong', $result['active_since'] ?? ''],
                ['Quan ly boi', $result['managed_by'] ?? ''],
                ['Loai hinh DN', $result['organization_type'] ?? ''],
                ['Canonical URL', $result['canonical_url']],
                ['Kiem tra luc', $result['source_checked_at']],
            ]
        );

        return self::SUCCESS;
    }
}
