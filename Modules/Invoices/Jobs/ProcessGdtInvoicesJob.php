<?php

namespace Modules\Invoices\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Invoices\Services\GdtInvoiceService;

class ProcessGdtInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public string $start,
        public string $end,
        public bool $vatIn = false,
    ) {}

    public function handle(GdtInvoiceService $service): void
    {
        Log::info('[GDT JOB] Bắt đầu xử lý hóa đơn.', [
            'start' => $this->start,
            'end' => $this->end,
            'type' => $this->vatIn ? 'purchase' : 'sold',
        ]);

        $service->processRange($this->start, $this->end, null, $this->vatIn);

        Log::info('[GDT JOB] Hoàn tất xử lý hóa đơn.');
    }
}
