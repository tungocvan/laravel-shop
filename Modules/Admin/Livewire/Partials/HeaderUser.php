<?php

namespace Modules\Admin\Livewire\Partials;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Modules\Admin\Services\HeaderMenuService;

class HeaderUser extends Component
{
    public $user;
    public $adminMenuItems = [];

    public function mount(HeaderMenuService $headerMenuService)
    {
        $this->user = Auth::guard('admin')->user();

        $menuDefault = [[
            'id' => 0,
            'title' => 'Profile',
            'url' => route('admin.profile'),
            'icon' => 'fa-solid fa-user',
            'children' => []
        ]];

        $this->adminMenuItems =
            $headerMenuService->getMenuTreeByLocation('admin') ?? $menuDefault;
    }

    public function logout()
    {
        Auth::guard('admin')->logout();

        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function render()
    {
        return view('Admin::livewire.partials.header-user');
    }
}