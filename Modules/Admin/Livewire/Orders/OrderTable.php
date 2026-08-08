<?php

namespace Modules\Admin\Livewire\Orders;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Website\Models\Order;

class OrderTable extends Component
{
    use WithPagination;

    public $search = '';

    public $perPage = 10;

    // --- MỚI: QUẢN LÝ CHECKBOX ---
    public $selected = []; // Chứa danh sách ID được chọn
    public $selectAll = false; // Trạng thái checkbox "Chọn tất cả"
    // 1. THÊM BIẾN STATUS
    public $status = 'all'; // Mặc định hiển thị tất cả


    // 2. ĐÂY LÀ HÀM BẠN ĐANG THIẾU (Thêm vào class này)
    public function setStatus($status)
    {
        $this->status = $status;
        $this->resetPage(); // Reset về trang 1

        // Reset luôn các dòng đang chọn (để tránh xóa nhầm)
        $this->selected = [];
        $this->selectAll = false;
    }
    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'all'],
    ];

    // Reset lựa chọn khi chuyển trang hoặc lọc
    public function updatedSearch() { $this->resetPage(); $this->selected = []; $this->selectAll = false; }
    public function updatedStatus() { $this->resetPage(); $this->selected = []; $this->selectAll = false; }
    public function updatedPage()   { $this->selected = []; $this->selectAll = false; }

    // Xử lý nút "Chọn tất cả" trên trang hiện tại
    public function updatedSelectAll($value)
    {
        if ($value) {
            // Lấy ID của các đơn hàng trong trang hiện tại (theo bộ lọc)
            $this->selected = $this->getOrdersQuery()->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    // Hàm lấy Query (Tách ra để tái sử dụng)
    private function getOrdersQuery()
    {
        $query = Order::query()->orderBy('created_at', 'desc');

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('order_code', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $this->search . '%');
            });
        }
        return $query;
    }



    // Hàm 1: Xóa hàng loạt (như đã viết ở trên)
    public function deleteSelected()
    {
        // Lấy danh sách ID và xóa các đơn hợp lệ (Pending/Cancelled)
        $orders = Order::whereIn('id', $this->selected)
                    ->whereIn('status', ['pending', 'cancelled'])
                    ->get();

        foreach ($orders as $order) {
            $order->forceDelete();
        }

        $this->selected = []; // Reset checkbox
        session()->flash('success', 'Đã xóa các đơn hàng đã chọn.');
    }

    // Hàm 2: Xóa 1 dòng (Nên thêm hàm này để nút thùng rác ở từng dòng hoạt động)
    public function delete($id)
    {
        $order = Order::find($id);

        if ($order && in_array($order->status, ['pending', 'cancelled'])) {
            $order->forceDelete();
            session()->flash('success', 'Đã xóa đơn hàng #' . $order->order_code);
        } else {
            session()->flash('error', 'Đơn hàng này không thể xóa.');
        }
    }

    public function render()
    {
        // Lưu ý: Cần paginate sau khi getQuery
        return view('Admin::livewire.orders.order-table', [
            'orders' => $this->getOrdersQuery()->paginate($this->perPage)
        ]);
    }
}
