<?php

namespace Modules\Administrative\Livewire\Public;

use Livewire\Component;
use Modules\Administrative\Services\PublicBrandingService;

class PublicHeader extends Component
{
    public string $logo = '';

    public string $nameLine1 = '';

    public string $nameLine2 = '';

    public string $description = '';

    public function mount(PublicBrandingService $branding): void
    {
        $data = $branding->get();
        $this->logo = $data['logo'];
        $this->nameLine1 = $data['name_line_1'];
        $this->nameLine2 = $data['name_line_2'];
        $this->description = $data['description'];
    }

    public function render()
    {
        return view('Administrative::livewire.public.public-header');
    }
}
