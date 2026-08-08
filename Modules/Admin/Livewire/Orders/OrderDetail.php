<?php

namespace Modules\Admin\Livewire\Orders;

use Livewire\Component;
use Modules\Website\Models\Order;

class OrderDetail extends Component
{
    public $orderId;
    public $order;

    // Biến tạm để cập nhật trạng thái
    public $newStatus;
    public $adminNote;

    public function mount($id)
    {
        $this->orderId = $id;
        // Load kèm items và product (để lấy ảnh hiển thị)
        $this->order = Order::with('items.product')->findOrFail($id);

        $this->newStatus = $this->order->status;
        $this->adminNote = $this->order->note; // Tạm dùng chung cột note, thực tế nên có admin_note riêng
    }

    // Hàm đổi trạng thái đơn hàng
    public function updateStatus()
    {
        // Validation cơ bản (Ví dụ: Không thể chuyển từ Cancelled về Pending)
        if ($this->order->status === 'cancelled' && $this->newStatus !== 'cancelled') {
            // Có thể thêm thông báo lỗi ở đây
            return;
        }

        $this->order->status = $this->newStatus;
        $oldStatus = $this->order->status;
        // Nếu chuyển sang hoàn thành -> set luôn đã thanh toán (nếu muốn logic tự động)
        if ($this->newStatus === 'completed') {
            // Logic trừ kho có thể viết ở đây
        }


        $this->order->save();

        // --- LOGIC MỚI: GHI LOG ---
        $this->order->histories()->create([
            'user_id' => auth()->id(), // Admin đang đăng nhập
            'action'  => 'Cập nhật trạng thái',
            'description' => "Đổi từ {$oldStatus} sang {$this->newStatus}",
        ]);

        // Refresh lại để timeline tự cập nhật
        $this->order->refresh();

        // Gửi thông báo (Flash message)
        session()->flash('success', 'Đã cập nhật trạng thái đơn hàng.');
    }



    public function deleteOrder()
    {
        // 1. Kiểm tra điều kiện (Chỉ xóa đơn Pending/Cancelled)
        if (!in_array($this->order->status, ['pending', 'cancelled'])) {
            session()->flash('error', 'Không thể xóa đơn hàng đang xử lý/giao hàng.');
            return;
        }

        // 2. Xóa vĩnh viễn
        $this->order->forceDelete();

        // 3. Quay về trang danh sách
        return redirect()->route('admin.orders.index')->with('success', 'Đã xóa đơn hàng.');
    }

    public function render()
    {
        return view('Admin::livewire.orders.order-detail');
    }
}
