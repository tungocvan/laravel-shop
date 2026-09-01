<?php

namespace Modules\Website\Livewire\Account\Affiliate;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Order\Services\AffiliateService;

class AffiliateDashboard extends Component
{
    use WithPagination;

    public $referralCode;

    public $referralLink;

    #[Url]
    public $statusFilter = 'all';

    public $isModalOpen = false;

    public $selectedOrder = null;

    public function mount()
    {
        $user = Auth::user();
        $this->referralCode = $user->id;
        $this->referralLink = route('home', ['ref' => $this->referralCode]);
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function openOrderModal($orderId, AffiliateService $service)
    {
        try {
            $this->selectedOrder = $service->getAffiliateOrderDetail($orderId, Auth::id());
            $this->isModalOpen = true;
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Không tìm thấy đơn hàng']);
        }
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->selectedOrder = null;
    }

    public function render(AffiliateService $service)
    {
        $userId = Auth::id();
        $stats = $service->getStats($userId);
        $commissions = $service->getCommissionHistory($userId, $this->statusFilter);

        return view('Website::livewire.account.affiliate.affiliate-dashboard', [
            'stats' => $stats,
            'commissions' => $commissions,
        ]);
    }
}
