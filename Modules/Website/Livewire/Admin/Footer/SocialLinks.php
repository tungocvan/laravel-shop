<?php

namespace Modules\Website\Livewire\Admin\Footer;

use Livewire\Component;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Models\SocialLink;
use Modules\Website\Services\FooterService;

class SocialLinks extends Component
{
    use AuthorizesAdminPermissions;

    public $platform, $url, $icon_class, $sort_order = 0;
    public $editingId = null;
    public $edit_platform, $edit_url, $edit_icon_class;

    public $defaultIcons = [
        'fab fa-facebook' => 'Facebook',
        'fab fa-instagram' => 'Instagram',
        'fab fa-youtube' => 'YouTube',
        'fab fa-tiktok' => 'TikTok',
        'fab fa-twitter' => 'Twitter / X',
        'fab fa-linkedin' => 'LinkedIn',
        'fab fa-pinterest' => 'Pinterest',
        'fab fa-telegram' => 'Telegram',
        'fas fa-envelope' => 'Email / Contact',
        'fas fa-phone' => 'Phone',
    ];

    public function render(FooterService $service)
    {
        return view('Website::livewire.admin.footer.social-links', [
            'links' => $service->getSocialLinks(),
        ]);
    }

    public function save(FooterService $service)
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $this->validate(['platform' => 'required', 'url' => 'required']);

        $service->createSocialLink([
            'platform' => $this->platform,
            'name' => $this->platform,
            'url' => $this->url,
            'icon_class' => $this->icon_class ?? 'fas fa-link',
            'sort_order' => (int) $this->sort_order,
            'is_active' => true,
        ]);

        $this->reset(['platform', 'url', 'icon_class', 'sort_order']);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã thêm Social Link']);
    }

    public function delete($id, FooterService $service)
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $service->deleteSocialLink($id);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã xóa Social Link']);
    }

    public function edit($id)
    {
        $link = SocialLink::find($id);
        if ($link) {
            $this->editingId = $id;
            $this->edit_platform = $link->platform;
            $this->edit_url = $link->url;
            $this->edit_icon_class = $link->icon_class;
        }
    }

    public function cancelEdit()
    {
        $this->reset(['editingId', 'edit_platform', 'edit_url', 'edit_icon_class']);
    }

    public function update(FooterService $service)
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $this->validate([
            'edit_platform' => 'required',
            'edit_url' => 'required',
        ]);

        $service->updateSocialLink($this->editingId, [
            'platform' => $this->edit_platform,
            'name' => $this->edit_platform,
            'url' => $this->edit_url,
            'icon_class' => $this->edit_icon_class,
        ]);

        $this->cancelEdit();
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã cập nhật']);
    }

    public function updateOrder($orderedIds, FooterService $service)
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $service->updateSocialLinkOrder($orderedIds);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã cập nhật thứ tự']);
    }
}
