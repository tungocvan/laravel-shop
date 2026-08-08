<?php

namespace Modules\Invoices\Livewire;

use Livewire\Component;
use Modules\Invoices\Services\GdtInvoiceService;

class GdtInvoice extends Component
{
    protected GdtInvoiceService $service;

    public $fromDate;

    public $toDate;

    public array $invoices = [];

    public int $total = 0;

    public string $invoiceType = 'sold';

    public function boot(GdtInvoiceService $service): void
    {
        $this->service = $service;
    }

    public function searchInvoices(): void
    {
        $validated = $this->validate([
            'fromDate' => ['required', 'date'],
            'toDate' => ['required', 'date', 'after_or_equal:fromDate'],
            'invoiceType' => ['required', 'in:sold,purchase'],
        ], [
            'fromDate.required' => 'Vui lòng chọn ngày bắt đầu.',
            'toDate.required' => 'Vui lòng chọn ngày kết thúc.',
            'toDate.after_or_equal' => 'Ngày kết thúc phải từ ngày bắt đầu trở đi.',
        ]);

        try {
            $result = $this->service->search(
                $validated['fromDate'],
                $validated['toDate'],
                $validated['invoiceType']
            );
        } catch (\RuntimeException $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }

        $this->invoices = $result['items'];
        $this->total = $result['total'];
    }

    public function render()
    {
        return view('Invoices::livewire.gdt-invoice');
    }
}
