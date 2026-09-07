<?php

namespace Modules\Partner\Livewire;

use Livewire\Component;
use Modules\Partner\Services\PartnerDashboardService;

class Dashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can('view_partner'), 403);
    }

    public function render(PartnerDashboardService $dashboard)
    {
        return view('partner::livewire.dashboard', [
            'metrics' => $dashboard->metrics(),
            'topProvinces' => $dashboard->topProvinces(),
        ]);
    }
}
