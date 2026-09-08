<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Invoices\Services\GdtInvoiceService;
use Tests\TestCase;

class InvoicesGdtEmptyExportTest extends TestCase
{
    public function test_empty_gdt_range_does_not_create_excel_file(): void
    {
        config([
            'invoices.gdt.base_url' => 'https://hoadondientu.gdt.gov.vn/api',
            'invoices.gdt.cache_key' => 'test-gdt-empty-token',
            'invoices.storage.export_directory' => 'gdt',
        ]);

        Cache::put('test-gdt-empty-token', 'server-only-token', 600);
        Http::fake([
            'https://hoadondientu.gdt.gov.vn/api/query/invoices/*' => Http::response([
                'datas' => [],
                'total' => 0,
            ]),
        ]);

        $service = app(GdtInvoiceService::class);
        $expectedFile = $service->expectedExportPath('2018-08-01', '2018-08-31', true);
        @unlink($expectedFile);

        $logs = [];
        $result = $service->processRange(
            '2018-08-01',
            '2018-08-31',
            function (string $message) use (&$logs): void {
                $logs[] = $message;
            },
            true,
        );

        $this->assertNull($result);
        $this->assertFileDoesNotExist($expectedFile);
        $this->assertTrue(collect($logs)->contains(
            fn (string $line): bool => str_contains($line, 'Không tạo file Excel')
        ));
    }
}
