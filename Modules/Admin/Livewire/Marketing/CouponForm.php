<?php

namespace Modules\Admin\Livewire\Marketing;

use Livewire\Component;
use Modules\Website\Models\Coupon;

class CouponForm extends Component
{
    public $couponId;
    public $isEdit = false;

    public $code, $description, $type = 'fixed', $value = 0;
    public $min_order_value = 0, $usage_limit = null;
    public $starts_at, $expires_at;
    public $is_active = true;

    public function mount($id = null)
    {
        if ($id) {
            $this->isEdit = true;
            $this->couponId = $id;
            $c = Coupon::findOrFail($id);
            
            $this->code = $c->code;
            $this->description = $c->description;
            $this->type = $c->type;
            $this->value = $c->value; // Livewire tự convert decimal
            $this->min_order_value = $c->min_order_value;
            $this->usage_limit = $c->usage_limit;
            $this->starts_at = $c->starts_at ? $c->starts_at->format('Y-m-d\TH:i') : null;
            $this->expires_at = $c->expires_at ? $c->expires_at->format('Y-m-d\TH:i') : null;
            $this->is_active = (bool)$c->is_active;
        }
    }

    public function save()
    {
        $rules = [
            'code' => 'required|alpha_dash|unique:coupons,code,' . $this->couponId,
            'value' => 'required|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
        ];

        if ($this->type === 'percent') {
            $rules['value'] = 'required|numeric|min:0|max:100';
        }

        $this->validate($rules);

        $data = [
            'code' => strtoupper($this->code),
            'description' => $this->description,
            'type' => $this->type,
            'value' => $this->value,
            'min_order_value' => $this->min_order_value ?? 0,
            'usage_limit' => $this->usage_limit,
            'starts_at' => $this->starts_at ?: null,
            'expires_at' => $this->expires_at ?: null,
            'is_active' => $this->is_active,
        ];

        Coupon::updateOrCreate(['id' => $this->couponId], $data);

        session()->flash('success', $this->isEdit ? 'Cập nhật mã thành công' : 'Tạo mã mới thành công');
        return redirect()->route('admin.coupons.index');
    }

    public function render()
    {
        return view('Admin::livewire.marketing.coupon-form');
    }
}