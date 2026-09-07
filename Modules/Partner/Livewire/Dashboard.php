<?php

namespace Modules\Partner\Livewire;

use Livewire\Component;
use Modules\Partner\Services\PartnerDashboardService;

class Dashboard extends Component
{
    public function render(PartnerDashboardService $dashboard)
    {
        return view('partner::livewire.dashboard', [
            'metrics' => $dashboard->metrics(),
            'topProvinces' => $dashboard->topProvinces(),
        ]);
    }
}
