<?php

namespace Modules\Website\Livewire\Admin\Coupon;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Models\Coupon;

class CouponTable extends Component
{
    use WithPagination, WithFileUploads, AuthorizesAdminPermissions;

    public $search = '';
    public $perPage = 10;
    public $selected = [];
    public $selectAll = false;
    public $showImportModal = false;
    public $importFile;

    public function updatedSearch() { $this->resetPage(); $this->resetSelection(); }
    public function updatingPage() { $this->resetSelection(); }

    public function resetSelection()
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->getQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function toggleStatus($id)
    {
        $this->authorizeAdminPermission('marketing.coupon.manage');

        $coupon = Coupon::find($id);
        if ($coupon) {
            $coupon->is_active = ! $coupon->is_active;
            $coupon->save();
            $this->dispatch('notify', content: 'Đã thay đổi trạng thái.', type: 'success');
        }
    }

    public function deleteSelected()
    {
        $this->authorizeAdminPermission('marketing.coupon.manage');
        Coupon::whereIn('id', $this->selected)->delete();
        $this->resetSelection();
        $this->dispatch('notify', content: 'Đã xóa các mã đã chọn.', type: 'success');
    }

    public function delete($id)
    {
        $this->authorizeAdminPermission('marketing.coupon.manage');
        Coupon::findOrFail($id)->delete();
        $this->dispatch('notify', content: 'Đã xóa mã giảm giá.', type: 'success');
    }

    public function export()
    {
        $data = $this->getQuery()->get()->map(function ($item) {
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

        $fileName = 'coupons-export-' . date('Y-m-d-His') . '.json';

        return response()->streamDownload(function () use ($data) {
            echo $data->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $fileName);
    }

    public function import()
    {
        $this->authorizeAdminPermission('marketing.coupon.manage');
        $this->validate(['importFile' => 'required|mimes:json,txt|max:2048']);

        try {
            $json = json_decode(file_get_contents($this->importFile->getRealPath()), true);
            if (! is_array($json)) {
                throw new \Exception('File JSON không hợp lệ.');
            }

            $count = 0;
            DB::transaction(function () use ($json, &$count) {
                foreach ($json as $item) {
                    Coupon::updateOrCreate(
                        ['code' => $item['code']],
                        [
                            'description' => $item['description'] ?? null,
                            'type' => $item['type'] ?? 'fixed',
                            'value' => $item['value'] ?? 0,
                            'min_order_value' => $item['min_order_value'] ?? 0,
                            'usage_limit' => $item['usage_limit'] ?? null,
                            'starts_at' => isset($item['starts_at']) ? Carbon::parse($item['starts_at']) : null,
                            'expires_at' => isset($item['expires_at']) ? Carbon::parse($item['expires_at']) : null,
                            'is_active' => $item['is_active'] ?? true,
                        ]
                    );
                    $count++;
                }
            });

            $this->showImportModal = false;
            $this->importFile = null;
            $this->dispatch('notify', content: "Đã import thành công {$count} mã.", type: 'success');
        } catch (\Exception $e) {
            $this->addError('importFile', 'Lỗi: ' . $e->getMessage());
        }
    }

    private function getQuery()
    {
        return Coupon::query()
            ->where('code', 'like', '%' . $this->search . '%')
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc');
    }

    public function render()
    {
        return view('Website::livewire.admin.coupon.coupon-table', [
            'coupons' => $this->getQuery()->paginate($this->perPage),
        ]);
    }
}
