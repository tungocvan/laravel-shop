<?php

namespace Modules\Website\Livewire\Admin\Dashboard;

use Livewire\Component;
use Modules\Website\Services\WebsiteAdminDashboardService;

class WebsiteDashboard extends Component
{
    public function render(WebsiteAdminDashboardService $dashboard)
    {
        return view('Website::livewire.admin.dashboard.website-dashboard', [
            'summary' => $dashboard->summary(),
            'checks' => $dashboard->checks(),
        ]);
    }
}
