<?php

namespace Modules\Admin\Livewire\Partials;

use Livewire\Component;
use Modules\Admin\Services\AdminHeaderService;
use Modules\Admin\Support\AdminLayoutManager;

class Header extends Component
{
    public array $headerContext = [];

    public bool $sidebarEnabled = true;

    public function mount(AdminHeaderService $headerService, AdminLayoutManager $layoutManager): void
    {
        $this->headerContext = $headerService->context();
        $this->sidebarEnabled = (bool) data_get($layoutManager->config(), 'sidebar.enabled', true);
    }

    public function render()
    {
        return view('Admin::livewire.partials.header');
    }
}
