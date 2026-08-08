<?php

namespace Modules\Facebook\Livewire\Dashboard;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Modules\Facebook\Services\FacebookDashboardService;

class Index extends Component
{
    private FacebookDashboardService $dashboard;

    public function boot(FacebookDashboardService $dashboard): void
    {
        $this->dashboard = $dashboard;
    }

    public function render(): View
    {
        $this->authorizePermission('facebook.view');

        return view('Facebook::livewire.dashboard.index', [
            'summary' => $this->dashboard->summary(),
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user, 403);
        Gate::forUser($user)->authorize($permission);
    }
}
