<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use Livewire\Component;
use Modules\Pharma\Services\DrugBidAwardService;
use Modules\Pharma\Models\Medicine;
use Exception;

class Form extends Component
{
    public ?int $awardId = null;
    public bool $isEditMode = false;

    // Form fields
    public $medicine_id = null;
    public $medicine_name = '';
    public $packaging_specification = '';
    public $quantity = '';
    public $unit_price = '';
    public $bidding_notice_code = '';
    public $investor_name = '';
    public $decision_number = '';
    public $decision_date = '';
    public $contract_duration_months = '';
    public $winning_company_name = '';
    public $decision_document_url = '';

    public function mount(?int $id = null)
    {
        if ($id) {
            $this->awardId = $id;
            $this->isEditMode = true;
            $service = app(DrugBidAwardService::class);
            $award = $service->findOrFail($id);

            $this->medicine_id = $award->medicine_id;
            $this->medicine_name = $award->medicine_name;
            $this->packaging_specification = $award->packaging_specification;
            $this->quantity = $award->quantity;
            $this->unit_price = $award->unit_price;
            $this->bidding_notice_code = $award->bidding_notice_code;
            $this->investor_name = $award->investor_name;
            $this->decision_number = $award->decision_number;
            $this->decision_date = $award->decision_date?->format('Y-m-d');
            $this->contract_duration_months = $award->contract_duration_months;
            $this->winning_company_name = $award->winning_company_name;
            $this->decision_document_url = $award->decision_document_url;
        }
    }

    protected function rules(): array
    {
        return [
            'medicine_id'              => 'nullable|exists:pharma_medicines,id',
            'medicine_name'            => 'required|string|max:255',
            'packaging_specification'  => 'required|string|max:255',
            'quantity'                 => 'required|integer|min:1',
            'unit_price'               => 'required|numeric|min:0',
            'bidding_notice_code'      => 'required|string|max:100',
            'investor_name'            => 'required|string|max:255',
            'decision_number'          => 'required|string|max:100',
            'decision_date'            => 'required|date',
            'contract_duration_months' => 'required|integer|min:1',
            'winning_company_name'     => 'required|string|max:255',
            'decision_document_url'    => 'nullable|url|max:255',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'medicine_name'            => 'Tên thuốc thầu',
            'packaging_specification'  => 'Quy cách đóng gói',
            'quantity'                 => 'Số lượng',
            'unit_price'               => 'Đơn giá trúng thầu',
            'bidding_notice_code'      => 'Mã thông báo mời thầu',
            'investor_name'            => 'Tên chủ đầu tư',
            'decision_number'          => 'Số quyết định',
            'decision_date'            => 'Ngày ban hành',
            'contract_duration_months' => 'Thời hạn hiệu lực',
            'winning_company_name'     => 'Công ty trúng thầu',
        ];
    }

    public function save(DrugBidAwardService $service)
    {
        $data = $this->validate();

        try {
            if ($this->isEditMode) {
                $service->update($this->awardId, $data);
                session()->flash('success', 'Cập nhật thông tin trúng thầu thành công.');
            } else {
                $service->store($data);
                session()->flash('success', 'Thêm hồ sơ trúng thầu mới thành công.');
            }
            return redirect()->route('admin.pharma.drug-bid-awards.index');
        } catch (Exception $e) {
            session()->flash('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('Pharma::livewire.drug-bid-award.form', [
            'medicines' => Medicine::query()->latest()->get()
        ]);
    }
}
