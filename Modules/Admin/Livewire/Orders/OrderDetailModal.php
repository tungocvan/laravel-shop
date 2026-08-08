<?php

namespace Modules\Admin\Livewire\Orders;

use Livewire\Component;
use Livewire\Attributes\On;
use Modules\Admin\Services\AdminAffiliateService;

class OrderDetailModal extends Component
{
    public $order = null;
    public $isOpen = false;
    
    // State cho việc Từ chối
    public $isRejecting = false; 
    public $rejectReason = '';

    #[On('open-order-modal')] 
    public function open($orderId)
    {
        $service = app(AdminAffiliateService::class);
        $this->order = $service->getOrderDetail($orderId);
        $this->isOpen = true;
        
        // Reset form
        $this->isRejecting = false;
        $this->rejectReason = '';
    }

    public function close()
    {
        $this->isOpen = false;
        $this->reset(['order', 'isRejecting', 'rejectReason']);
    }

    // --- ACTION: DUYỆT ---
    public function approve()
    {
        try {
            app(AdminAffiliateService::class)->approve($this->order->id);
            
            $this->close();
            
            // Gửi thông báo & Refresh danh sách cha
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Đã duyệt hoa hồng thành công!']);
            $this->dispatch('refresh-commission-list'); 

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // --- ACTION: TỪ CHỐI (Bước 1: Mở form) ---
    public function startReject()
    {
        $this->isRejecting = true;
    }

    // --- ACTION: TỪ CHỐI (Bước 2: Hủy form) ---
    public function cancelReject()
    {
        $this->isRejecting = false;
        $this->rejectReason = '';
    }

    // --- ACTION: TỪ CHỐI (Bước 3: Submit) ---
    public function confirmReject()
    {
        $this->validate([
            'rejectReason' => 'required|min:5|max:255',
        ], [
            'rejectReason.required' => 'Vui lòng nhập lý do từ chối.',
            'rejectReason.min' => 'Lý do phải có ít nhất 5 ký tự.',
        ]);

        try {
            app(AdminAffiliateService::class)->reject($this->order->id, $this->rejectReason);
            
            $this->close();
            
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Đã từ chối hoa hồng.']);
            $this->dispatch('refresh-commission-list');

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('Admin::livewire.orders.order-detail-modal');
    }
}