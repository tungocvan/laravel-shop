<?php

namespace Modules\Website\Livewire\Account\Affiliate;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url; // Để lưu filter lên URL
use Illuminate\Support\Facades\Auth;
use Modules\Website\Services\AffiliateService;

class AffiliateDashboard extends Component
{
    use WithPagination;

    public $referralCode;
    public $referralLink;

    // Filter
    #[Url]
    public $statusFilter = 'all';

    // Modal State
    public $isModalOpen = false;
    public $selectedOrder = null;

    public function mount()
    {
        $user = Auth::user();
        $this->referralCode = $user->id;
        $this->referralLink = route('home', ['ref' => $this->referralCode]);
    }

    // Reset phân trang khi đổi filter
    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    // Mở Modal
    public function openOrderModal($orderId, AffiliateService $service)
    {
        try {
            $this->selectedOrder = $service->getAffiliateOrderDetail($orderId, Auth::id());
            $this->isModalOpen = true;
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Không tìm thấy đơn hàng']);
        }
    }

    // Đóng Modal
    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->selectedOrder = null;
    }

    public function render(AffiliateService $service)
    {
        $userId = Auth::id();
        $stats = $service->getStats($userId);

        // Truyền filter vào service
        $commissions = $service->getCommissionHistory($userId, $this->statusFilter);

        return view('Website::livewire.account.affiliate.affiliate-dashboard', [
            'stats' => $stats,
            'commissions' => $commissions
        ]);
    }
}
