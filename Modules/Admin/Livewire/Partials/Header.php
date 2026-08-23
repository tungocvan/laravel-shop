<?php

namespace Modules\Admin\Livewire\Partials;

use Livewire\Component;
use Modules\Admin\Services\AdminHeaderService;

class Header extends Component
{
    public array $headerContext = [];

    public function mount(AdminHeaderService $headerService): void
    {
        $this->headerContext = $headerService->context();
    }

    public function render()
    {
        return view('Admin::livewire.partials.header');
    }
}
