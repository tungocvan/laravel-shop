<?php

namespace Modules\Pharma\Livewire\SupplierTrackings;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Pharma\Exceptions\DuplicateSupplierTrackingException;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\SupplierTrackingService;

class Form extends Component
{
    use AuthorizesPharmaActions;
    use WithFileUploads;

    public ?int $trackingId = null;
    public ?int $medicine_id = null;
    public ?int $partner_id = null;
    public string $supplierSearch = '';
    public string $facilitySearch = '';
    public array $facility_ids = [];
    public $contractFile = null;
    public $depositReceipt = null;

    public array $form = [
        'working_date' => '', 'distribution_scope' => 'all', 'distribution_regions' => [],
        'import_price' => 0, 'invoice_price' => 0, 'committed_quantity' => '', 'unit' => '',
        'deposit_amount' => '', 'start_date' => '', 'end_date' => '', 'status' => 'active', 'note' => '',
        'contract_file_path' => null, 'deposit_receipt_path' => null,
    ];

    public function mount(SupplierTrackingService $service, $id = null, $medicineId = null): void
    {
        $id ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();
        $this->trackingId = $id ? (int) $id : null;

        if ($this->trackingId) {
            $tracking = $service->find($this->trackingId);
            $this->medicine_id = $tracking->medicine_id;
            $this->partner_id = $tracking->partner_id;
            $this->supplierSearch = $tracking->partner?->name ?? $tracking->supplier_name;
            $this->facility_ids = $tracking->facilities->pluck('id')->map(fn ($id) => (string) $id)->all();
            $this->form = array_merge($this->form, $tracking->only(array_keys($this->form)));
            $this->form['working_date'] = optional($tracking->working_date)->format('Y-m-d');
            $this->form['start_date'] = optional($tracking->start_date)->format('Y-m-d');
            $this->form['end_date'] = optional($tracking->end_date)->format('Y-m-d');
            $this->form['distribution_regions'] = $tracking->distribution_regions ?? [];
        } elseif ($medicineId && Medicine::query()->whereKey((int) $medicineId)->exists()) {
            $this->medicine_id = (int) $medicineId;
        }
    }

    public function updatedFormDistributionScope(): void
    {
        if ($this->form['distribution_scope'] !== 'regions') {
            $this->form['distribution_regions'] = [];
        }
        if ($this->form['distribution_scope'] !== 'facilities') {
            $this->facility_ids = [];
        }
    }

    public function save(SupplierTrackingService $service)
    {
        $this->trackingId ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();

        $payload = [
            'medicine_id' => $this->medicine_id,
            'partner_id' => $this->partner_id,
            'facility_ids' => $this->facility_ids,
        ] + $this->form;

        $data = $this->validate([
            'medicine_id' => ['required', 'exists:pharma_medicines,id'],
            'partner_id' => ['required', 'exists:partners,id'],
            'facility_ids' => ['array'],
            'facility_ids.*' => ['integer', 'exists:pharma_official_source_facilities,id'],
            'form.working_date' => ['nullable', 'date'],
            'form.distribution_scope' => ['required', 'in:all,regions,facilities'],
            'form.distribution_regions' => ['array'],
            'form.distribution_regions.*' => ['string', 'max:50'],
            'form.import_price' => ['required', 'numeric', 'min:0'],
            'form.invoice_price' => ['nullable', 'numeric', 'min:0'],
            'form.committed_quantity' => ['nullable', 'numeric', 'min:0'],
            'form.unit' => ['nullable', 'string', 'max:50'],
            'form.deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'form.start_date' => ['nullable', 'date'],
            'form.end_date' => ['nullable', 'date', 'after_or_equal:form.start_date'],
            'form.status' => ['required', 'in:active,completed,paused,cancelled'],
            'form.note' => ['nullable', 'string'],
            'contractFile' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'depositReceipt' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        if ($this->form['distribution_scope'] === 'regions' && empty($this->form['distribution_regions'])) {
            $this->addError('form.distribution_regions', 'Chọn ít nhất một vùng được phép bán.');
            return null;
        }
        if ($this->form['distribution_scope'] === 'facilities' && empty($this->facility_ids)) {
            $this->addError('facility_ids', 'Chọn ít nhất một cơ sở khám chữa bệnh.');
            return null;
        }

        if ($this->contractFile) {
            $this->form['contract_file_path'] = $this->contractFile->store('pharma/supplier-contracts', 'local');
        }
        if ($this->depositReceipt) {
            $this->form['deposit_receipt_path'] = $this->depositReceipt->store('pharma/supplier-deposits', 'local');
        }

        $payload = [
            'medicine_id' => $this->medicine_id, 'partner_id' => $this->partner_id,
            'facility_ids' => $this->facility_ids,
        ] + $this->form;

        try {
            $this->trackingId ? $service->update($this->trackingId, $payload) : $service->create($payload);
            session()->flash('success', $this->trackingId ? 'Đã cập nhật điều kiện thương mại nhà cung cấp.' : 'Đã tạo điều kiện thương mại nhà cung cấp.');
            return redirect()->route('admin.pharma.supplier-trackings.index');
        } catch (DuplicateSupplierTrackingException) {
            $this->addError('partner_id', 'Đã tồn tại bản ghi cho thuốc, nhà cung cấp và ngày làm việc này.');
            return null;
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Không thể lưu điều kiện thương mại. Vui lòng kiểm tra dữ liệu hoặc log hệ thống.');
            return null;
        }
    }

    public function render(SupplierTrackingService $service)
    {
        return view('Pharma::livewire.supplier-trackings.form', [
            'medicine' => $this->medicine_id ? Medicine::query()->find($this->medicine_id) : null,
            'suppliers' => $service->supplierCandidates($this->supplierSearch, $this->partner_id),
            'facilities' => $service->facilityCandidates($this->facilitySearch, $this->facility_ids),
            'regions' => $service->distributionRegions(),
        ]);
    }
}
