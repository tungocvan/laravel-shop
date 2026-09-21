<?php

namespace Modules\Pharma\Livewire\Medicine;

use Exception;
use Livewire\Component;
use LogicException;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Services\HsspMedicineValidityService;
use Modules\Pharma\Services\MedicineService;

class Form extends Component
{
    use AuthorizesPharmaActions;

    public ?int $medicineId = null;

    public bool $isEditMode = false;

    public ?string $profile_status = null;

    public ?string $last_verified_at = null;

    public $circular_order_number;

    public $circular_group;

    public $therapeutic_group;

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

    public $notes;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'active_ingredients' => 'nullable|string|max:255',
            'concentration' => 'nullable|string|max:255',
            'dosage_form' => 'nullable|string|max:255',
            'route_of_administration' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
            'packaging_specification' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'shelf_life' => 'nullable|string|max:255',
            'registered_company' => 'nullable|string|max:255',
            'manufacturing_company' => 'nullable|string|max:255',
            'manufacturing_country' => 'nullable|string|max:255',
            'circular_order_number' => 'nullable|string|max:255',
            'circular_group' => 'nullable|string|max:255',
            'therapeutic_group' => 'nullable|string|max:255',
            'visa_validity_date' => 'nullable|date',
            'gmp_certification_date' => 'nullable|date',
            'declared_price' => 'nullable|numeric|min:0',
            'is_special_control' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    public function mount(MedicineService $medicineService, HsspMedicineValidityService $hsspValidity, ?int $id = null)
    {
        $id ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();

        if ($id) {
            $this->medicineId = $id;
            $this->isEditMode = true;
            $medicine = $medicineService->findOrFail($id);
            $this->fill($medicine->toArray());

            $validity = $hsspValidity->forMedicine($id);
            $this->visa_validity_date = $validity['visa_validity_date'] ?? $this->visa_validity_date;
            $this->gmp_certification_date = $validity['gmp_certification_date'] ?? $this->gmp_certification_date;

            if ($this->visa_validity_date) {
                $this->visa_validity_date = date('Y-m-d', strtotime($this->visa_validity_date));
            }
            if ($this->gmp_certification_date) {
                $this->gmp_certification_date = date('Y-m-d', strtotime($this->gmp_certification_date));
            }
        }
    }

    public function verifyMaster(MedicineService $medicineService): void
    {
        $this->authorizePharmaEdit();

        if (! $this->isEditMode || ! $this->medicineId) {
            return;
        }

        try {
            $medicine = $medicineService->verifyMaster($this->medicineId);
            $this->profile_status = $medicine->profile_status;
            $this->last_verified_at = $medicine->last_verified_at?->format('Y-m-d H:i:s');
            session()->flash('success', 'Medicine Master đã được xác minh và có thể dùng để liên kết kết quả trúng thầu.');
        } catch (LogicException $e) {
            session()->flash('error', $e->getMessage());
        } catch (Exception $e) {
            report($e);
            session()->flash('error', 'Không thể xác minh Medicine Master. Vui lòng thử lại hoặc kiểm tra log hệ thống.');
        }
    }

    public function save(MedicineService $medicineService)
    {
        $this->isEditMode ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();
        $validatedData = $this->validate();

        try {
            if ($this->isEditMode) {
                $medicineService->update($this->medicineId, $validatedData);
                session()->flash('success', 'Cập nhật Medicine Master thành công.');
            } else {
                $medicineService->store($validatedData);
                session()->flash('success', 'Đã thêm thuốc vào Medicine Master. HSSP có thể được bổ sung sau.');
            }

            return redirect()->route('admin.pharma.medicines.index');
        } catch (LogicException $e) {
            session()->flash('error', $e->getMessage());
        } catch (Exception $e) {
            report($e);
            session()->flash('error', 'Không thể lưu thuốc. Vui lòng thử lại hoặc kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('Pharma::livewire.medicine.form');
    }
}
