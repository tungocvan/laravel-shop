<?php
namespace Modules\Admin\Livewire\Settings;

use Livewire\Component;
use Modules\Admin\Services\Env\EnvManagerService;
use Illuminate\Support\Facades\Http;

class StorageConfig extends Component
{
   
    public function render()
    {
        return view('Admin::livewire.settings.storage-config');
    }
}