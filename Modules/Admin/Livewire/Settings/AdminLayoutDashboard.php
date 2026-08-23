<?php

namespace Modules\Admin\Livewire\Settings;

use Livewire\Component;
use Modules\Admin\Services\AdminLayoutDashboardService;

class AdminLayoutDashboard extends Component
{
    public function render(AdminLayoutDashboardService $dashboard)
    {
        return view('Admin::livewire.settings.admin-layout-dashboard', [
            'cards' => $dashboard->cards(),
        ]);
    }
}
