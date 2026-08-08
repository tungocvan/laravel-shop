<?php

namespace Modules\Invoices\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Modules\Invoices\Jobs\ProcessGdtInvoicesJob;
use Modules\Invoices\Services\GdtApiService;
use Modules\Invoices\Services\GdtInvoiceService;
use Modules\Invoices\Services\InvoiceImportService;

class SearchHoadon extends Component
{
    protected GdtInvoiceService $invoiceService;

    protected InvoiceImportService $importService;

    protected GdtApiService $apiService;

    public $start_date;

    public $end_date;

    public $vatIn = false;     // false = bán ra, true = mua vào

    public $useQueue = false;  // xử lý qua queue hay không

    public $logs = [];

    protected $listeners = ['pollLogs'];

    public function boot(
        GdtInvoiceService $invoiceService,
        InvoiceImportService $importService,
        GdtApiService $apiService
    ): void {
        $this->invoiceService = $invoiceService;
        $this->importService = $importService;
        $this->apiService = $apiService;
    }

    public function mount(): void
    {
        Cache::forget('gdt_log'); // reset log trước khi chạy
        // mặc định lấy tháng hiện tại
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    private function log($msg)
    {
        $this->logs[] = '['.now()->format('H:i:s').'] '.$msg;
        $this->dispatch('scroll-bottom');
    }

    public function run()
    {
        $this->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'vatIn' => ['boolean'],
            'useQueue' => ['boolean'],
        ]);

        $this->logs = [];
        $this->log('Bắt đầu xử lý…');

        if ($this->useQueue) {

            ProcessGdtInvoicesJob::dispatch($this->start_date, $this->end_date, (bool) $this->vatIn);

            $this->log('Đã đưa vào queue thành công!');

            return;
        }

        // Chạy trực tiếp – không queue
        $this->invoiceService->processRange(
            $this->start_date,
            $this->end_date,
            function ($msg) {
                $this->log($msg);
                // $this->dispatch('scroll-bottom');
            },
            $this->vatIn
        );
        $this->log('Hoàn tất xử lý!');
        if (! $this->apiService->hasToken()) {
            session()->flash('status', 'Token đã hết hạn.');

            return $this->redirectRoute('admin.invoices.create-token');
        }

    }

    public function pollLogs()
    {
        $this->logs = Cache::get('gdt_log', []);

    }

    public function importExcel()
    {
        $this->logs = [];

        $this->log('Bắt đầu import Excel…');

        try {
            $this->importService->importExportedRange(
                $this->start_date,
                $this->end_date,
                (bool) $this->vatIn,
                fn ($message) => $this->log($message)
            );
        } catch (\Throwable $exception) {
            $this->log('❌ '.$exception->getMessage());

            return;
        }

        $this->log('🎯 Import hoàn tất!');
    }

    public function render()
    {
        return view('Invoices::livewire.search-hoadon');
    }
}
