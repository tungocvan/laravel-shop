<?php

namespace Modules\Admin\Livewire\Footer;

use Livewire\Component;
use Modules\Website\Models\SocialLink;
use Modules\Website\Services\FooterService; // Nhớ use Service

class SocialLinks extends Component
{
    // Form Create
    public $platform, $url, $icon_class, $sort_order = 0;

    // Form Edit
    public $editingId = null;
    public $edit_platform, $edit_url, $edit_icon_class;

    // Danh sách Icon mặc định (FontAwesome Brands)
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

    public function render(FooterService $service) // Inject Service vào render để lấy data mới nhất
    {
        return view('Admin::livewire.footer.social-links', [
            'links' => $service->getSocialLinks() // Đảm bảo Service có hàm getSocialLinks()
        ]);
    }

    // --- CREATE ---
    public function save(FooterService $service) // ✅ Inject Service
    {
        $this->validate(['platform' => 'required', 'url' => 'required']);

        // Gọi Service để tạo + xóa cache
        $service->createSocialLink([
            'platform' => $this->platform,
            'name' => $this->platform,
            'url' => $this->url,
            'icon_class' => $this->icon_class ?? 'fas fa-link',
            'sort_order' => (int)$this->sort_order,
            'is_active' => true
        ]);

        $this->reset(['platform', 'url', 'icon_class', 'sort_order']);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã thêm Social Link']);
    }

    // --- DELETE ---
    public function delete($id, FooterService $service) // ✅ Inject Service
    {
        // Gọi Service để xóa + xóa cache
        $service->deleteSocialLink($id);

        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã xóa Social Link']);
    }
    // --- EDIT INLINE ---
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
        $this->validate([
            'edit_platform' => 'required',
            'edit_url' => 'required'
        ]);

        $service->updateSocialLink($this->editingId, [
            'platform' => $this->edit_platform,
            'name' => $this->edit_platform,
            'url' => $this->edit_url,
            'icon_class' => $this->edit_icon_class
        ]);

        $this->cancelEdit();
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã cập nhật']);
    }

    // --- SORTING ---
    public function updateOrder($orderedIds, FooterService $service)
    {
        $service->updateSocialLinkOrder($orderedIds);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã cập nhật thứ tự']);
    }
}
