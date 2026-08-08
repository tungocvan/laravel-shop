<?php

namespace Modules\Website\Livewire\Account;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Modules\Website\Services\WishlistService;

class WishlistPage extends Component
{
    use WithPagination;

    public function remove($productId)
    {
        $service = App::make(WishlistService::class);
        $service->toggle(Auth::id(), $productId); // Toggle lần nữa sẽ thành xóa

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Đã xóa khỏi yêu thích']);
        $this->dispatch('wishlist-updated'); // Cập nhật icon trên header
    }

    public function render()
    {
        $service = App::make(WishlistService::class);
        $products = $service->getWishlistItems(Auth::id(), 12);

        // Lấy lại list ID để truyền vào Product Card (để trái tim luôn đỏ)
        $wishlistIds = $products->pluck('id')->toArray();

        return view('Website::livewire.account.wishlist-page', [
            'products' => $products,
            'wishlistIds' => $wishlistIds
        ]);
    }
}
