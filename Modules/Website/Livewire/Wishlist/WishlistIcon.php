<?php

namespace Modules\Website\Livewire\Wishlist;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Product\Services\WishlistService;

class WishlistIcon extends Component
{
    public $count = 0;

    public function mount()
    {
        $this->updateCount();
    }

    #[On('wishlist-updated')]
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
