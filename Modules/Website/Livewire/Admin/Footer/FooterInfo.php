<?php

namespace Modules\Website\Livewire\Admin\Footer;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\System\Services\SettingsService;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Throwable;

class FooterInfo extends Component
{
    use AuthorizesAdminPermissions, WithFileUploads;

    public $brand_name;
    public $brand_description;
    public $address;
    public $email;
    public $phone;
    public $app_title;
    public $app_description;
    public $app_button_title;
    public $app_button_subtitle;
    public $appstore_url;
    public $playstore_url;
    public $copyright;
    public array $legal_links = [];
    public array $trust_badges = [];
    public $brand_logo_upload;
    public $current_brand_logo;
    public $fallback_site_logo;
    public $fallback_site_name;

    public function mount(SettingsService $settingsService)
    {
        $this->fallback_site_name = $settingsService->get('site_name', 'FlexBiz');
        $this->brand_name = $settingsService->get('footer.brand_name', $this->fallback_site_name);
        $this->brand_description = $settingsService->get('footer.brand_description');
        $this->address = $settingsService->get('footer.address');
        $this->email = $settingsService->get('footer.email');
        $this->phone = $settingsService->get('footer.phone');
        $this->app_title = $settingsService->get('footer.app_title', 'Tải Ứng Dụng');
        $this->app_description = $settingsService->get('footer.app_description', 'Cài ứng dụng để truy cập nhanh trên iPhone, iPad, Android hoặc máy tính.');
        $this->app_button_title = $settingsService->get('footer.app_button_title', 'Cài ứng dụng '.$this->fallback_site_name);
        $this->app_button_subtitle = $settingsService->get('footer.app_button_subtitle', 'Truy cập nhanh từ màn hình chính · Không cần App Store');
        $this->appstore_url = $settingsService->get('footer.appstore_url');
        $this->playstore_url = $settingsService->get('footer.playstore_url');
        $this->copyright = $settingsService->get('footer.copyright', '© '.date('Y').' '.$this->fallback_site_name.'. All rights reserved.');

        $legalLinks = $settingsService->get('footer.legal_links', $this->defaultLegalLinks());
        $this->legal_links = is_array($legalLinks) ? array_values($legalLinks) : $this->defaultLegalLinks();

        $trustBadges = $settingsService->get('footer.trust_badges', $this->defaultTrustBadges());
        $this->trust_badges = is_array($trustBadges) ? array_values($trustBadges) : $this->defaultTrustBadges();

        $this->current_brand_logo = $settingsService->get('footer.brand_logo');
        $this->fallback_site_logo = $settingsService->get('site_logo');
    }

    public function addLegalLink(): void
    {
        $this->legal_links[] = ['label' => '', 'url' => '#', 'new_tab' => false, 'enabled' => true];
    }

    public function removeLegalLink(int $index): void
    {
        if (isset($this->legal_links[$index])) {
            unset($this->legal_links[$index]);
            $this->legal_links = array_values($this->legal_links);
        }
    }

    public function addTrustBadge(): void
    {
        $this->trust_badges[] = ['label' => '', 'image_url' => '', 'url' => '', 'enabled' => true];
    }

    public function removeTrustBadge(int $index): void
    {
        if (isset($this->trust_badges[$index])) {
            unset($this->trust_badges[$index]);
            $this->trust_badges = array_values($this->trust_badges);
        }
    }

    public function save(SettingsService $settingsService)
    {
        $this->authorizeAdminPermission('website.footer.manage');

        $this->validate([
            'brand_name' => ['nullable', 'string', 'max:120'],
            'brand_description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'app_title' => ['nullable', 'string', 'max:120'],
            'app_description' => ['nullable', 'string', 'max:500'],
            'app_button_title' => ['nullable', 'string', 'max:120'],
            'app_button_subtitle' => ['nullable', 'string', 'max:255'],
            'appstore_url' => ['nullable', 'string', 'max:500'],
            'playstore_url' => ['nullable', 'string', 'max:500'],
            'copyright' => ['nullable', 'string', 'max:255'],
            'legal_links' => ['array', 'max:12'],
            'legal_links.*.label' => ['nullable', 'string', 'max:100'],
            'legal_links.*.url' => ['nullable', 'string', 'max:500'],
            'legal_links.*.new_tab' => ['boolean'],
            'legal_links.*.enabled' => ['boolean'],
            'trust_badges' => ['array', 'max:12'],
            'trust_badges.*.label' => ['nullable', 'string', 'max:100'],
            'trust_badges.*.image_url' => ['nullable', 'string', 'max:1000'],
            'trust_badges.*.url' => ['nullable', 'string', 'max:500'],
            'trust_badges.*.enabled' => ['boolean'],
            'brand_logo_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $oldLogo = $this->current_brand_logo;
        $newLogo = null;

        if ($this->brand_logo_upload) {
            $newLogo = $this->brand_logo_upload->store('footer/brand', 'public');
        }

        try {
            $values = [
                'footer.brand_name' => filled($this->brand_name) ? trim($this->brand_name) : null,
                'footer.brand_description' => $this->brand_description,
                'footer.address' => $this->address,
                'footer.email' => $this->email,
                'footer.phone' => $this->phone,
                'footer.app_title' => $this->app_title,
                'footer.app_description' => $this->app_description,
                'footer.app_button_title' => $this->app_button_title,
                'footer.app_button_subtitle' => $this->app_button_subtitle,
                'footer.appstore_url' => $this->appstore_url,
                'footer.playstore_url' => $this->playstore_url,
                'footer.copyright' => $this->copyright,
                'footer.legal_links' => $this->sanitizeLegalLinks(),
                'footer.trust_badges' => $this->sanitizeTrustBadges(),
            ];

            if ($newLogo !== null) {
                $values['footer.brand_logo'] = $newLogo;
            }

            $settingsService->updateMany($values, 'footer');
        } catch (Throwable $exception) {
            if ($newLogo !== null) {
                Storage::disk('public')->delete($newLogo);
            }
            throw $exception;
        }

        if ($newLogo !== null) {
            $this->deleteOwnedBrandLogo($oldLogo);
            $this->current_brand_logo = $newLogo;
            $this->brand_logo_upload = null;
        }

        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã lưu thông tin Footer!']);
    }

    public function removeBrandLogo(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.footer.manage');

        $oldLogo = $this->current_brand_logo;
        $settingsService->set('footer.brand_logo', null, 'footer');
        $this->deleteOwnedBrandLogo($oldLogo);
        $this->current_brand_logo = null;
        $this->brand_logo_upload = null;

        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã xóa logo Footer. Footer sẽ dùng logo Website mặc định.']);
    }

    public function render()
    {
        return view('Website::livewire.admin.footer.footer-info');
    }

    private function sanitizeLegalLinks(): array
    {
        return collect($this->legal_links)
            ->filter(fn ($item) => is_array($item) && filled($item['label'] ?? null))
            ->map(fn ($item) => [
                'label' => trim((string) ($item['label'] ?? '')),
                'url' => trim((string) ($item['url'] ?? '#')) ?: '#',
                'new_tab' => (bool) ($item['new_tab'] ?? false),
                'enabled' => (bool) ($item['enabled'] ?? true),
            ])->values()->all();
    }

    private function sanitizeTrustBadges(): array
    {
        return collect($this->trust_badges)
            ->filter(fn ($item) => is_array($item) && filled($item['image_url'] ?? null))
            ->map(fn ($item) => [
                'label' => trim((string) ($item['label'] ?? '')),
                'image_url' => trim((string) ($item['image_url'] ?? '')),
                'url' => trim((string) ($item['url'] ?? '')),
                'enabled' => (bool) ($item['enabled'] ?? true),
            ])->values()->all();
    }

    private function defaultLegalLinks(): array
    {
        return [
            ['label' => 'Privacy Policy', 'url' => '#', 'new_tab' => false, 'enabled' => true],
            ['label' => 'Terms of Service', 'url' => '#', 'new_tab' => false, 'enabled' => true],
            ['label' => 'Cookie Settings', 'url' => '#', 'new_tab' => false, 'enabled' => true],
        ];
    }

    private function defaultTrustBadges(): array
    {
        return [
            ['label' => 'Visa', 'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg', 'url' => '', 'enabled' => true],
            ['label' => 'Mastercard', 'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg', 'url' => '', 'enabled' => true],
            ['label' => 'PayPal', 'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg', 'url' => '', 'enabled' => true],
            ['label' => 'Đã thông báo Bộ Công Thương', 'image_url' => 'https://webmedia.com.vn/images/2021/09/logo-da-thong-bao-bo-cong-thuong-mau-xanh.png', 'url' => '', 'enabled' => true],
        ];
    }

    private function deleteOwnedBrandLogo(?string $path): void
    {
        if (! is_string($path) || ! str_starts_with($path, 'footer/brand/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
