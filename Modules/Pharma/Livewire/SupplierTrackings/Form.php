<?php

namespace Modules\Pharma\Livewire\SupplierTrackings;

use Livewire\Component;
use Modules\Pharma\Services\SupplierTrackingService;

class Form extends Component
{
    public ?int $trackingId = null;

    public ?int $medicine_id = null;

    public array $form = [
        'working_date' => '',
        'supplier_name' => '',
        'supplier_representative' => '',
        'area' => '',

        'import_price' => 0,
        'selling_price' => 0,
        'invoice_price' => 0,

        'invoice_difference_amount' => 0,
        'invoice_difference_percent' => 0,
        'invoice_difference_fee' => 0,

        'cost_price' => 0,
        'gross_profit_percent' => 0,

        'committed_quantity' => '',
        'unit' => '',
        'deposit_amount' => '',

        'start_date' => '',
        'end_date' => '',
        'contract_url' => '',
        'status' => 'active',
        'note' => '',
    ];

    public function mount(SupplierTrackingService $service, $id = null): void
    {
        $this->trackingId = $id ? (int) $id : null;

        if ($this->trackingId) {
            $tracking = $service->find($this->trackingId);

            $this->medicine_id = $tracking->medicine_id;

            $this->form = array_merge(
                $this->form,
                $tracking->only(array_keys($this->form))
            );

            $this->form['working_date'] = optional($tracking->working_date)->format('Y-m-d');
            $this->form['start_date'] = optional($tracking->start_date)->format('Y-m-d');
            $this->form['end_date'] = optional($tracking->end_date)->format('Y-m-d');
        }

        $this->recalculate();
    }

    public function updatedForm(): void
    {
        $this->recalculate();
    }

    public function recalculate(): void
    {
        $calculated = app(SupplierTrackingService::class)
            ->previewCalculate($this->form);

        $this->form['invoice_difference_fee'] = $calculated['invoice_difference_fee'];
        $this->form['cost_price'] = $calculated['cost_price'];
        $this->form['gross_profit_percent'] = $calculated['gross_profit_percent'];
    }

    public function save(SupplierTrackingService $service)
    {
        $this->form['medicine_id'] = $this->medicine_id;

        $data = $this->validate([
            'form.medicine_id' => ['required', 'exists:pharma_medicines,id'],
            'form.working_date' => ['nullable', 'date'],

            'form.supplier_name' => ['required', 'string', 'max:255'],
            'form.supplier_representative' => ['nullable', 'string', 'max:255'],
            'form.area' => ['nullable', 'string', 'max:255'],

            'form.import_price' => ['nullable', 'numeric', 'min:0'],
            'form.selling_price' => ['nullable', 'numeric', 'min:0'],
            'form.invoice_price' => ['nullable', 'numeric', 'min:0'],

            'form.invoice_difference_percent' => ['nullable', 'numeric', 'min:0'],

            'form.committed_quantity' => ['nullable', 'numeric', 'min:0'],
            'form.unit' => ['nullable', 'string', 'max:50'],
            'form.deposit_amount' => ['nullable', 'numeric', 'min:0'],

            'form.start_date' => ['nullable', 'date'],
            'form.end_date' => ['nullable', 'date'],
            'form.contract_url' => ['nullable', 'url'],
            'form.status' => ['required', 'in:active,completed,paused,cancelled'],
            'form.note' => ['nullable', 'string'],
        ])['form'];

        if ($this->trackingId) {
            $service->update($this->trackingId, $data);
            session()->flash('success', 'Đã cập nhật theo dõi nhà cung cấp.');
        } else {
            $service->create($data);
            session()->flash('success', 'Đã thêm theo dõi nhà cung cấp.');
        }

        return redirect()->route('admin.pharma.supplier-trackings.index');
    }

    public function money($value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        return number_format((float) $value, 0, ',', '.');
    }

    public function percent($value): string
    {
        if ($value === null || $value === '') {
            return '0%';
        }

        return number_format((float) $value, 2, ',', '.').'%';
    }

    public function render(SupplierTrackingService $service)
    {
        return view('Pharma::livewire.supplier-trackings.form', [
            'medicines' => $service->medicinesForSelect(),
        ]);
    }
}
