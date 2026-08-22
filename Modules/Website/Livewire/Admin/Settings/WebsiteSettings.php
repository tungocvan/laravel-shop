<?php

namespace Modules\Website\Livewire\Admin\Settings;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\System\Services\SettingsService;
use Modules\Website\Livewire\Admin\Settings\Concerns\ManagesWebsiteDesignThemes;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Models\WebsitePage;
use Modules\Website\Services\WebsiteDesignService;
use Throwable;

class WebsiteSettings extends Component
{
    use AuthorizesAdminPermissions, ManagesWebsiteDesignThemes, WithFileUploads;

    public string $activeTab = 'seo';
    public string $siteName = '';
    public string $seoTitle = '';
    public string $seoDescription = '';
    public string $canonicalUrl = '';
    public string $robots = 'index,follow';
    public string $ogImage = '';
    public string $logo = '';
    public string $favicon = '';
    public string $analyticsCode = '';
    public string $headerScript = '';
    public array $design = [];
    public array $features = [
        'chat_widget' => true,
        'chat_position' => 'bottom-right',
        'back_to_top' => true,
        'back_to_top_position' => 'bottom-right',
    ];
    public $newLogo;
    public $newFavicon;

    public function mount(SettingsService $settings, WebsiteDesignService $designService): void
    {
        $page = WebsitePage::query()->where('slug', 'home')->first();
        $this->siteName = (string) $settings->get('site_name', 'FlexBiz');
        $this->logo = (string) $settings->get('site_logo', '');
        $this->favicon = (string) $settings->get('site_favicon', '');
        $this->canonicalUrl = (string) $settings->get('seo.canonical_url', url('/'));

        $savedRobots = strtolower(str_replace(' ', '', (string) $settings->get('seo.robots', 'index,follow')));
        $this->robots = in_array($savedRobots, $this->allowedRobots(), true) ? $savedRobots : 'index,follow';

        $this->analyticsCode = (string) $settings->get('analytics_code', '');
        $this->headerScript = (string) $settings->get('header_script', '');
        $this->seoTitle = (string) ($page?->seo_title ?: $this->siteName);
        $this->seoDescription = (string) ($page?->seo_description ?: '');
        $this->ogImage = (string) ($page?->seo_image ?: '');
        $savedDesign = $settings->get('website.design');
        $this->design = $designService->resolve(is_array($savedDesign) ? $savedDesign : null);

        $savedFeatures = $settings->get('website.features');
        if (is_array($savedFeatures)) {
            $this->features = [
                'chat_widget' => (bool) ($savedFeatures['chat_widget'] ?? true),
                'chat_position' => $this->widgetPosition($savedFeatures['chat_position'] ?? null, 'bottom-right'),
                'back_to_top' => (bool) ($savedFeatures['back_to_top'] ?? true),
                'back_to_top_position' => $this->widgetPosition($savedFeatures['back_to_top_position'] ?? null, 'bottom-right'),
            ];
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['seo', 'identity', 'design', 'themes', 'advanced'], true) ? $tab : 'seo';
    }

    public function resetDesign(WebsiteDesignService $designService): void
    {
        $this->resetValidation();
        $this->design = $designService->resolve();
    }

    public function save(SettingsService $settings, WebsiteDesignService $designService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        $this->resetValidation();

        try {
            $this->validate([
                'siteName' => 'required|string|max:120',
                'seoTitle' => 'required|string|max:70',
                'seoDescription' => 'nullable|string|max:170',
                'canonicalUrl' => 'nullable|url|max:255',
                'robots' => ['required', Rule::in($this->allowedRobots())],
                'newLogo' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:3072',
                'newFavicon' => 'nullable|mimes:png,ico,svg|max:1024',
                'analyticsCode' => 'nullable|string|max:10000',
                'headerScript' => 'nullable|string|max:20000',
                'design.typography.font_family_body' => 'required|string|max:240',
                'design.typography.font_family_heading' => 'required|string|max:240',
                'design.typography.base_font_size' => ['required', 'regex:/^\d+(?:\.\d+)?(?:px|rem)$/'],
                'design.colors.*' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'design.layout.default_container' => 'required|in:compact,standard,wide,full',
                'design.layout.container_width.compact' => ['required', 'regex:/^\d+(?:\.\d+)?(?:px|rem)$/'],
                'design.layout.container_width.standard' => ['required', 'regex:/^\d+(?:\.\d+)?(?:px|rem)$/'],
                'design.layout.container_width.wide' => ['required', 'regex:/^\d+(?:\.\d+)?(?:px|rem)$/'],
                'design.layout.radius.*' => ['required', 'regex:/^\d+(?:\.\d+)?(?:px|rem)$/'],
                'features.chat_widget' => 'required|boolean',
                'features.chat_position' => ['required', Rule::in($this->allowedWidgetPositions())],
                'features.back_to_top' => 'required|boolean',
                'features.back_to_top_position' => ['required', Rule::in($this->allowedWidgetPositions())],
            ]);
        } catch (ValidationException $exception) {
            $this->themeFeedback('error', 'Không thể lưu thay đổi', 'Một hoặc nhiều trường chưa hợp lệ. Vui lòng kiểm tra các thông báo trong form.');
            throw $exception;
        }

        $oldLogo = $this->logo;
        $oldFavicon = $this->favicon;
        $newLogoPath = $this->newLogo?->store('branding', 'public');
        $newFaviconPath = $this->newFavicon?->store('branding', 'public');
        $this->logo = $newLogoPath ?: $this->logo;
        $this->favicon = $newFaviconPath ?: $this->favicon;
        $this->design = $designService->resolve($this->design);

        try {
            $settings->updateMany([
                'site_name' => $this->siteName,
                'site_logo' => $this->logo,
                'site_favicon' => $this->favicon,
                'seo.canonical_url' => $this->canonicalUrl,
                'seo.robots' => $this->robots,
                'analytics_code' => $this->analyticsCode,
                'header_script' => $this->headerScript,
                'website.design' => $this->design,
                'website.features' => $this->features,
            ], 'website');
            WebsitePage::query()->updateOrCreate(['slug' => 'home'], [
                'title' => 'Trang chủ', 'status' => WebsitePage::STATUS_PUBLISHED, 'template' => 'homepage',
                'seo_title' => $this->seoTitle, 'seo_description' => $this->seoDescription, 'seo_image' => $this->ogImage ?: null,
            ]);
        } catch (Throwable $exception) {
            foreach (array_filter([$newLogoPath, $newFaviconPath]) as $path) {
                Storage::disk('public')->delete($path);
            }
            report($exception);
            $this->themeFeedback('error', 'Lưu thay đổi thất bại', 'Không thể lưu cấu hình Website. Dữ liệu cũ vẫn được giữ nguyên. Vui lòng thử lại.');
            return;
        }

        foreach ([[$newLogoPath, $oldLogo], [$newFaviconPath, $oldFavicon]] as [$newPath, $oldPath]) {
            if ($newPath && $oldPath && ! str_starts_with($oldPath, 'http')) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $this->reset(['newLogo', 'newFavicon']);
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã lưu cấu hình Website.']);
        $this->themeFeedback('success', 'Lưu thay đổi thành công', 'Cấu hình Website đã được lưu và áp dụng cho storefront.');
    }

    private function allowedRobots(): array
    {
        return ['index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'];
    }

    private function allowedWidgetPositions(): array
    {
        return ['bottom-left', 'bottom-right', 'right-middle'];
    }

    private function widgetPosition(mixed $value, string $default): string
    {
        return is_string($value) && in_array($value, $this->allowedWidgetPositions(), true) ? $value : $default;
    }

    public function render()
    {
        return view('Website::livewire.admin.settings.website-settings');
    }
}
