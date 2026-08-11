<?php

namespace Modules\Website\Livewire\Admin\Coupon;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Services\CouponService;

class CouponTable extends Component
{
    use AuthorizesAdminPermissions, WithFileUploads, WithPagination;

    public $search = '';

    public $perPage = 10;

    public $selected = [];

    public $selectAll = false;

    public $showImportModal = false;

    public $importFile;

    public function updatedSearch()
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatingPage()
    {
        $this->resetSelection();
    }

    public function resetSelection()
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value, CouponService $coupons)
    {
        if ($value) {
            $this->selected = $coupons->ids($this->search);
        } else {
            $this->selected = [];
        }
    }

    public function toggleStatus($id, CouponService $coupons)
    {
        $this->authorizeAdminPermission('marketing.coupon.manage');

        $coupons->toggle($id);
        $this->dispatch('notify', content: 'Đã thay đổi trạng thái.', type: 'success');
    }

    public function deleteSelected(CouponService $coupons)
    {
        $this->authorizeAdminPermission('marketing.coupon.manage');
        $coupons->deleteMany($this->selected);
        $this->resetSelection();
        $this->dispatch('notify', content: 'Đã xóa các mã đã chọn.', type: 'success');
    }

    public function delete($id, CouponService $coupons)
    {
        $this->authorizeAdminPermission('marketing.coupon.manage');
        $coupons->delete($id);
        $this->dispatch('notify', content: 'Đã xóa mã giảm giá.', type: 'success');
    }

    public function export(CouponService $coupons)
    {
        $data = $coupons->all($this->search)->map(function ($item) {
            return [
                'code' => $item->code,
                'description' => $item->description,
                'type' => $item->type,
                'value' => (float) $item->value,
                'min_order_value' => (float) $item->min_order_value,
                'usage_limit' => $item->usage_limit,
                'starts_at' => $item->starts_at ? $item->starts_at->toDateTimeString() : null,
                'expires_at' => $item->expires_at ? $item->expires_at->toDateTimeString() : null,
                'is_active' => (bool) $item->is_active,
            ];
        });

        $fileName = 'coupons-export-'.date('Y-m-d-His').'.json';

        return response()->streamDownload(function () use ($data) {
            echo $data->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $fileName);
    }

    public function import(CouponService $coupons)
    {
        $this->authorizeAdminPermission('marketing.coupon.manage');
        $this->validate(['importFile' => 'required|mimes:json,txt|max:2048']);

        try {
            $json = json_decode(file_get_contents($this->importFile->getRealPath()), true);
            if (! is_array($json)) {
                throw new \Exception('File JSON không hợp lệ.');
            }

            $count = $coupons->import($json);

            $this->showImportModal = false;
            $this->importFile = null;
            $this->dispatch('notify', content: "Đã import thành công {$count} mã.", type: 'success');
        } catch (\Exception $e) {
            $this->addError('importFile', 'Lỗi: '.$e->getMessage());
        }
    }

    public function render(CouponService $coupons)
    {
        return view('Website::livewire.admin.coupon.coupon-table', [
            'coupons' => $coupons->paginate($this->search, $this->perPage),
        ]);
    }
}
