<?php

namespace Modules\Admin\Livewire\Partials;

use Livewire\Component;

class HeaderNotifications extends Component
{
    public string $icon = 'fa-regular fa-bell';

    public function render()
    {
        return view('Admin::livewire.partials.header-notifications');
    }
}
