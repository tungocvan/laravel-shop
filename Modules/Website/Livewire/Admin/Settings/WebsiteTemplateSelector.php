<?php
namespace Modules\Website\Livewire\Admin\Settings;

use Livewire\Component;
use Modules\System\Services\SettingsService;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Services\WebsiteTemplateService;

class WebsiteTemplateSelector extends Component
{
    use AuthorizesAdminPermissions;

    public string $template = 'default';

    public function mount(WebsiteTemplateService $templates): void
    {
        $this->template = $templates->active();
    }

    public function save(SettingsService $settings): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        $this->validate(['template' => 'required|in:default,pharma']);
        $settings->set('website.template', $this->template, 'website');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã lưu template Website.']);
    }

    public function render()
    {
        return view('Website::livewire.admin.settings.website-template-selector');
    }
}
