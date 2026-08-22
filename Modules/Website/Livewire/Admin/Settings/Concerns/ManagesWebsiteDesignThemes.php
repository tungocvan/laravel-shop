<?php

namespace Modules\Website\Livewire\Admin\Settings\Concerns;

use Modules\Website\Services\WebsiteDesignThemeService;

trait ManagesWebsiteDesignThemes
{
    public string $themeName = '';
    public string $selectedTheme = '';
    public string $themeJson = '';

    public function getDesignThemesProperty(): array
    {
        return app(WebsiteDesignThemeService::class)->all();
    }

    public function saveDesignTheme(): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        $slug = app(WebsiteDesignThemeService::class)->save($this->themeName, $this->design);
        $this->selectedTheme = $slug;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã lưu Website design theme.']);
    }

    public function applyDesignTheme(): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        $this->design = app(WebsiteDesignThemeService::class)->apply($this->selectedTheme);
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã nạp theme vào form. Bấm Lưu thay đổi để publish.']);
    }

    public function updateDesignTheme(): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        app(WebsiteDesignThemeService::class)->update($this->selectedTheme, $this->design);
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã cập nhật Website design theme.']);
    }

    public function renameDesignTheme(): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        app(WebsiteDesignThemeService::class)->rename($this->selectedTheme, $this->themeName);
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã đổi tên Website design theme.']);
    }

    public function deleteDesignTheme(): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        app(WebsiteDesignThemeService::class)->delete($this->selectedTheme);
        $this->selectedTheme = '';
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã xóa Website design theme.']);
    }

    public function exportDesignTheme(): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        $this->themeJson = app(WebsiteDesignThemeService::class)->export($this->selectedTheme);
    }

    public function importDesignTheme(): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        $slug = app(WebsiteDesignThemeService::class)->import($this->themeJson, filled($this->themeName) ? $this->themeName : null);
        $this->selectedTheme = $slug;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã import Website design theme.']);
    }
}
