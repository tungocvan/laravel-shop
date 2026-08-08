<?php

namespace Modules\Website\Livewire\Wishlist;

use Livewire\Component;
use Livewire\Attributes\On; // Dùng để lắng nghe sự kiện
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Modules\Website\Services\WishlistService;

class WishlistIcon extends Component
{
    public $count = 0;

    public function mount()
    {
        $this->updateCount();
    }

    #[On('wishlist-updated')] // Lắng nghe sự kiện từ Product Card
    public function updateCount()
    {
        if (Auth::check()) {
            $service = App::make(WishlistService::class);
            $this->count = $service->count(Auth::id());
        } else {
            $this->count = 0;
        }
    }

    public function render()
    {
        return view('Website::livewire.wishlist.wishlist-icon');
    }
}
