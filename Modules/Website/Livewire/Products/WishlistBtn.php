<?php

namespace Modules\Website\Livewire\Products;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Modules\Website\Services\WishlistService;

class WishlistBtn extends Component
{
    public $productId;
    public $isActive = false; // Trạng thái: Đỏ (True) hay Xám (False)

    public function mount($productId, $isActive = false)
    {
        $this->productId = $productId;
        $this->isActive = $isActive;
    }

    public function toggle()
    {
        if (!Auth::check()) {
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'Vui lòng đăng nhập để lưu sản phẩm yêu thích.']);
            return;
        }

        try {
            $service = App::make(WishlistService::class);
            $status = $service->toggle(Auth::id(), $this->productId);

            // Cập nhật trạng thái UI
            $this->isActive = ($status === 'added');

            // Thông báo
            $msg = $this->isActive ? 'Đã thêm vào yêu thích' : 'Đã bỏ yêu thích';
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);

            // Emit sự kiện để update Header Count (Nếu có component WishlistIcon)
            $this->dispatch('wishlist-updated');

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Lỗi hệ thống.']);
        }
    }

    public function render()
    {
        return view('Website::livewire.products.wishlist-btn');
    }
}
