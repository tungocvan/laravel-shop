<?php

namespace Modules\Website\Livewire\Admin\Affiliate;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Models\AffiliateLevel;
use Modules\Website\Services\AdminAffiliateService;

class CommissionList extends Component
{
    use WithPagination, AuthorizesAdminPermissions;

    #[Url]
    public $statusFilter = 'all';

    #[Url]
    public $levelFilter = 'all';

    #[Url]
    public $search = '';

    public $selectedOrder = null;
    public $isModalOpen = false;
    public $showRejectForm = false;
    public $rejectionReason = '';

    public function approve($orderId, AdminAffiliateService $service)
    {
        $this->authorizeAdminPermission('affiliate.manage');

        try {
            $service->approve($orderId);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Đã duyệt hoa hồng & cập nhật hạng đối tác!']);

            if ($this->isModalOpen && $this->selectedOrder->id == $orderId) {
                $this->selectedOrder = $service->getOrderDetail($orderId);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function reject(AdminAffiliateService $service)
    {
        $this->authorizeAdminPermission('affiliate.manage');

        $this->validate([
            'rejectionReason' => 'required|min:5',
        ]);

        try {
            $service->reject($this->selectedOrder->id, $this->rejectionReason);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Đã từ chối chi trả hoa hồng.']);
            $this->showRejectForm = false;
            $this->selectedOrder = $service->getOrderDetail($this->selectedOrder->id);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function openDetail($orderId, AdminAffiliateService $service)
    {
        $this->selectedOrder = $service->getOrderDetail($orderId);
        $this->isModalOpen = true;
        $this->showRejectForm = false;
        $this->rejectionReason = '';
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->selectedOrder = null;
    }

    public function render(AdminAffiliateService $service)
    {
        $filters = [
            'status' => $this->statusFilter,
            'level' => $this->levelFilter,
            'search' => $this->search,
        ];

        return view('Website::livewire.admin.affiliate.commission-list', [
            'commissions' => $service->getCommissions($filters),
            'levels' => AffiliateLevel::all(),
        ]);
    }
}
