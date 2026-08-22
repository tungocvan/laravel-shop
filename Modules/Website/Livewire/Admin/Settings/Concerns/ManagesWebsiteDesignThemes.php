<?php

namespace Modules\Website\Livewire\Admin\Settings\Concerns;

use InvalidArgumentException;
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

    public function updatedSelectedTheme(string $slug): void
    {
        $this->resetValidation('themeName');
        if ($slug === '') {
            $this->themeName = '';
            return;
        }

        $theme = app(WebsiteDesignThemeService::class)->all()[$slug] ?? null;
        $this->themeName = is_array($theme) ? (string) ($theme['name'] ?? '') : '';
    }

    public function saveDesignTheme(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');
        $this->validate(['themeName' => 'required|string|min:1|max:80'], [
            'themeName.required' => 'Vui lòng nhập tên theme.',
            'themeName.max' => 'Tên theme không được vượt quá 80 ký tự.',
        ]);
        $slug = app(WebsiteDesignThemeService::class)->save($this->themeName, $this->design);
        $this->selectedTheme = $slug;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã lưu Website design theme.']);
    }

    public function applyDesignTheme(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');
        $this->design = app(WebsiteDesignThemeService::class)->apply($this->selectedTheme);
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã nạp theme vào form. Bấm Lưu thay đổi để publish.']);
    }

    public function updateDesignTheme(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');
        app(WebsiteDesignThemeService::class)->update($this->selectedTheme, $this->design);
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã cập nhật Website design theme.']);
    }

    public function renameDesignTheme(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');
        $this->validate(['themeName' => 'required|string|min:1|max:80'], [
            'themeName.required' => 'Vui lòng nhập tên theme mới.',
            'themeName.max' => 'Tên theme không được vượt quá 80 ký tự.',
        ]);
        app(WebsiteDesignThemeService::class)->rename($this->selectedTheme, $this->themeName);
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã đổi tên Website design theme.']);
    }

    public function deleteDesignTheme(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');
        app(WebsiteDesignThemeService::class)->delete($this->selectedTheme);
        $this->selectedTheme = '';
        $this->themeName = '';
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã xóa Website design theme.']);
    }

    public function restoreDefaultDesignThemes(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');
        app(WebsiteDesignThemeService::class)->restoreDefaultThemes();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã khôi phục 03 Website design themes mặc định. Custom themes được giữ nguyên.']);
    }

    public function exportDesignTheme(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');
        $this->themeJson = app(WebsiteDesignThemeService::class)->export($this->selectedTheme);
    }

    public function importDesignTheme(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');
        $this->validate([
            'themeJson' => 'required|string|min:2',
        ], [
            'themeJson.required' => 'Vui lòng dán dữ liệu JSON theme trước khi import.',
            'themeJson.min' => 'Dữ liệu JSON theme không hợp lệ.',
        ]);

        try {
            $slug = app(WebsiteDesignThemeService::class)->import(
                $this->themeJson,
                filled($this->themeName) ? $this->themeName : null
            );
        } catch (InvalidArgumentException $exception) {
            $this->addError('themeJson', $exception->getMessage());
            return;
        }

        $this->selectedTheme = $slug;
        $this->updatedSelectedTheme($slug);
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã import Website design theme.']);
    }
}
