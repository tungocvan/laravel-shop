<?php

namespace Modules\Website\Livewire\Account;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Product\Services\WishlistService;

class WishlistPage extends Component
{
    use WithPagination;

    public function remove($productId)
    {
        $service = App::make(WishlistService::class);
        $service->toggle(Auth::id(), $productId);

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Đã xóa khỏi yêu thích']);
        $this->dispatch('wishlist-updated');
    }

    public function render()
    {
        $service = App::make(WishlistService::class);
        $products = $service->getWishlistItems(Auth::id(), 12);
        $wishlistIds = $products->pluck('id')->toArray();

        return view('Website::livewire.account.wishlist-page', [
            'products' => $products,
            'wishlistIds' => $wishlistIds,
        ]);
    }
}
