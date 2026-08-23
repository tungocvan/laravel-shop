<?php

namespace Modules\Admin\Livewire\Partials;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Modules\Admin\Services\AdminHeaderUserMenuService;

class HeaderUser extends Component
{
    public $user;
    public array $userMenuContext = [];

    public function mount(AdminHeaderUserMenuService $userMenuService): void
    {
        $this->user = Auth::guard('admin')->user();
        $this->userMenuContext = $userMenuService->context($this->user);
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
