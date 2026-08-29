<?php

namespace Modules\Pharma\Livewire\Medicine;

use Exception;
use Livewire\Component;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Services\MedicineService;

class Form extends Component
{
    use AuthorizesPharmaActions;

    public ?int $medicineId = null;
    public bool $isEditMode = false;

    public $circular_order_number;
    public $circular_group;
    public $active_ingredients;
    public $concentration;
    public $name;
    public $dosage_form;
    public $route_of_administration;
    public $unit;
    public $packaging_specification;
    public $registration_number;
    public $shelf_life;
    public $registered_company;
    public $manufacturing_company;
    public $manufacturing_country;
    public $visa_validity_date;
    public $gmp_certification_date;
    public $declared_price;
    public $is_special_control = false;
    public $profile_link;
    public $notes;

    protected function rules()
    {
        return [
            'active_ingredients' => 'required|string|max:255',
            'concentration' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'dosage_form' => 'required|string|max:255',
            'route_of_administration' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
            'packaging_specification' => 'required|string|max:255',
            'registration_number' => 'required|string|max:255',
            'shelf_life' => 'required|string|max:255',
            'registered_company' => 'required|string|max:255',
            'manufacturing_company' => 'required|string|max:255',
            'manufacturing_country' => 'required|string|max:255',
            'circular_order_number' => 'nullable|string|max:255',
            'circular_group' => 'nullable|string|max:255',
            'visa_validity_date' => 'nullable|date',
            'gmp_certification_date' => 'nullable|date',
            'declared_price' => 'nullable|numeric|min:0',
            'is_special_control' => 'boolean',
            'profile_link' => 'nullable|url',
            'notes' => 'nullable|string',
        ];
    }

    public function mount(MedicineService $medicineService, ?int $id = null)
    {
        $id ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();

        if ($id) {
            $this->medicineId = $id;
            $this->isEditMode = true;
            $medicine = $medicineService->findOrFail($id);
            $this->fill($medicine->toArray());

            if ($this->visa_validity_date) {
                $this->visa_validity_date = date('Y-m-d', strtotime($this->visa_validity_date));
            }
            if ($this->gmp_certification_date) {
                $this->gmp_certification_date = date('Y-m-d', strtotime($this->gmp_certification_date));
            }
        }
    }

    public function save(MedicineService $medicineService)
    {
        $this->isEditMode ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();
        $validatedData = $this->validate();

        try {
            if ($this->isEditMode) {
                $medicineService->update($this->medicineId, $validatedData);
                session()->flash('success', 'Cập nhật hồ sơ thuốc thành công.');
            } else {
                $medicineService->store($validatedData);
                session()->flash('success', 'Thêm mới hồ sơ thuốc thành công.');
            }

            return redirect()->route('admin.pharma.hssp.index');
        } catch (Exception $e) {
            report($e);
            session()->flash('error', 'Không thể lưu hồ sơ thuốc. Vui lòng thử lại hoặc kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('Pharma::livewire.medicine.form');
    }
}
