<?php

namespace Modules\Admin\Livewire\Affiliate;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Admin\Services\AdminAffiliateService;
use Modules\Admin\Models\AffiliateLevel;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

class CommissionList extends Component
{
    use WithPagination;

    #[Url]
    public $statusFilter = 'all'; 
    #[Url]
    public $levelFilter = 'all'; 
    #[Url]
    public $search = '';

    // Modal States
    public $selectedOrder = null;
    public $isModalOpen = false;
    public $showRejectForm = false;
    public $rejectionReason = '';

    /**
     * Duyệt hoa hồng & Kích hoạt thăng hạng tự động
     */
    public function approve($orderId, AdminAffiliateService $service)
    {
        try {
            $service->approve($orderId); 
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Đã duyệt hoa hồng & cập nhật hạng đối tác!']);
            
            // Refresh dữ liệu nếu modal đang mở
            if ($this->isModalOpen && $this->selectedOrder->id == $orderId) {
                $this->selectedOrder = $service->getOrderDetail($orderId);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Từ chối hoa hồng kèm lý do
     */
    public function reject(AdminAffiliateService $service)
    {
        $this->validate([
            'rejectionReason' => 'required|min:5'
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
            'level'  => $this->levelFilter,
            'search' => $this->search
        ];

        return view('Admin::livewire.affiliate.commission-list', [
            'commissions' => $service->getCommissions($filters),
            'levels' => AffiliateLevel::all()
        ]);
    }
}