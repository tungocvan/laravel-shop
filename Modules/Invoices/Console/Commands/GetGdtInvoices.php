<?php

namespace Modules\Invoices\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Invoices\Jobs\ProcessGdtInvoicesJob;
use Modules\Invoices\Services\GdtInvoiceService;

class GetGdtInvoices extends Command
{
    protected $signature = 'gdt:invoices
                            {start_date : Ngày bắt đầu (d/m/Y hoặc Y-m-d)}
                            {end_date   : Ngày kết thúc (d/m/Y hoặc Y-m-d)}
                            {--queue    : Đưa việc xử lý vào queue thay vì chạy trực tiếp}
                            {--vatIn     : nếu không có tham số là hóa đơn bán ra  và có  là mua vào}';

    protected $description = 'Lấy hóa đơn GDT trực tiếp hoặc đưa vào queue nếu dùng --queue';

    /**
     * Parse date với nhiều định dạng
     */
    private function parseDateFlexible($date)
    {
        $formats = ['d/m/Y', 'Y-m-d'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date);
            } catch (\Exception $e) {
                // continue
            }
        }

        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Handle chính của command
     */
    public function handle()
    {
        $startInput = $this->argument('start_date');
        $endInput = $this->argument('end_date');

        $start = $this->parseDateFlexible($startInput);
        $end = $this->parseDateFlexible($endInput);

        if (! $start || ! $end) {
            $this->error('❌ Sai định dạng ngày! Hãy nhập: d/m/Y hoặc Y-m-d');

            return Command::FAILURE;
        }

        if ($end->lt($start)) {
            $this->error('❌ end_date phải lớn hơn hoặc bằng start_date!');

            return Command::FAILURE;
        }

        if ($this->option('vatIn')) {
            $vatIn = true;
            $type = 'Hóa đơn mua hàng';
        } else {
            $vatIn = false;
            $type = 'Hóa đơn bán ra';
        }
        // ============================
        // 1️⃣ XỬ LÝ QUEUE
        // ============================
        if ($this->option('queue')) {

            $this->info('📦 Đưa job vào queue...');
            $this->info("📅 Từ {$start->format('d/m/Y')} → {$end->format('d/m/Y')}");

            ProcessGdtInvoicesJob::dispatch(
                $start->toDateString(),
                $end->toDateString(),
                $vatIn
            );

            $this->info('✅ Job đã được đưa vào queue!');
            $this->info('➡ Chạy queue worker: php artisan queue:work --timeout=180');

            return Command::SUCCESS;
        }

        // ----------------------------
        // NGƯỢC LẠI → Chạy trực tiếp không queue
        // ----------------------------
        $this->info('⚡ Chạy trực tiếp không dùng queue...');
        $this->info("⚡ Bạn đang xuất $type ....");
        $this->info("📅 Khoảng thời gian: {$start->format('d/m/Y')} → {$end->format('d/m/Y')}");

        $service = new GdtInvoiceService;
        $service->processRange($start->format('Y-m-d'), $end->format('Y-m-d'), function ($msg) {
            $this->info($msg); // sẽ hiển thị trực tiếp trên CLI
        }, $vatIn);

        $this->info('✅ Hoàn tất xử lý trực tiếp!');

        return Command::SUCCESS;

    }
}
