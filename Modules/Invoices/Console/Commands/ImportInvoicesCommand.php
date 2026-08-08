<?php

namespace Modules\Invoices\Console\Commands;

use Illuminate\Console\Command;
use Modules\Invoices\Services\InvoiceImportService;

// php artisan gdt:import-excel storage/app/gdt/vat_in/inafo_vat_out_t01_t10_2025.xlsx --type=sold
// php artisan gdt:import-excel storage/app/gdt/vat_in/inafo_vat_in_t01_t10_2025.xlsx --type=purchase
class ImportInvoicesCommand extends Command
{
    protected $signature = 'gdt:import-excel 
                            {file : Đường dẫn file Excel} 
                            {--type=sold : Loại hóa đơn (sold hoặc purchase)}';

    protected $description = 'Import hóa đơn từ file Excel vào database';

    public function handle(InvoiceImportService $service)
    {
        $file = $this->argument('file');
        $type = $this->option('type');

        if (! in_array($type, ['sold', 'purchase'])) {
            $this->error('❌ Loại hóa đơn không hợp lệ! Chỉ dùng sold hoặc purchase');

            return Command::FAILURE;
        }

        $this->info("📥 Bắt đầu import Excel: $file");

        try {
            $count = $service->import($file, $type, function ($msg) {
                $this->line($msg);
            });

            $this->info("🎉 Import thành công! Tổng: {$count} hóa đơn");

        } catch (\Exception $e) {
            $this->error('❌ Lỗi: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
