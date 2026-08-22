<?php

namespace Modules\Website\Livewire\Admin\Home\Concerns;

use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Modules\Website\Services\HomepageLayoutThemeService;

trait ManagesHomepageLayoutThemes
{
    public string $selectedTheme = '';
    public string $themeName = '';
    public string $themeJson = '';

    #[Computed]
    public function layoutThemes(): array
    {
        return app(HomepageLayoutThemeService::class)->all();
    }

    public function updatedSelectedTheme(string $slug): void
    {
        if ($slug === '') {
            $this->themeName = '';
            $this->themeJson = '';
            $this->resetErrorBag('theme');
            return;
        }

        $this->selectTheme($slug);
    }

    public function selectTheme(string $slug): void
    {
        $themes = app(HomepageLayoutThemeService::class)->all();
        if (! is_array($themes[$slug] ?? null)) {
            return;
        }

        $this->selectedTheme = $slug;
        $this->themeName = (string) ($themes[$slug]['name'] ?? '');
        $this->themeJson = '';
        $this->resetErrorBag('theme');
    }

    public function saveTheme(HomepageLayoutThemeService $themes): void
    {
        $this->authorizeAdminPermission('website.home.manage');

        try {
            [$slug] = $themes->saveNew(
                $this->themeName,
                $this->sectionOrder,
                $this->layout,
                $this->sectionTypes,
                $this->presentation
            );
            $this->selectedTheme = $slug;
            unset($this->layoutThemes);
            $this->themeToast('Đã lưu Homepage theme mới.');
        } catch (InvalidArgumentException $exception) {
            $this->addError('theme', $exception->getMessage());
        }
    }

    public function applyTheme(HomepageLayoutThemeService $themes): void
    {
        $this->authorizeAdminPermission('website.home.manage');
        $theme = $this->selectedThemeData($themes);
        if ($theme === null) {
            return;
        }

        try {
            $state = $themes->apply($theme);
            $this->sectionOrder = $state['section_order'];
            $this->layout = $state['visibility'];
            $this->sectionTypes = $state['section_types'];
            $this->presentation = $state['presentation'];
            $this->themeName = $state['name'];
            $this->themeToast('Đã nạp theme vào Builder. Kiểm tra Preview rồi bấm Lưu thay đổi để publish.');
        } catch (InvalidArgumentException $exception) {
            $this->addError('theme', $exception->getMessage());
        }
    }

    public function updateTheme(HomepageLayoutThemeService $themes): void
    {
        $this->authorizeAdminPermission('website.home.manage');
        if ($this->selectedThemeData($themes) === null) {
            return;
        }

        try {
            $themes->update(
                $this->selectedTheme,
                $this->themeName,
                $this->sectionOrder,
                $this->layout,
                $this->sectionTypes,
                $this->presentation
            );
            unset($this->layoutThemes);
            $this->themeToast('Đã cập nhật Homepage theme.');
        } catch (InvalidArgumentException $exception) {
            $this->addError('theme', $exception->getMessage());
        }
    }

    public function renameTheme(HomepageLayoutThemeService $themes): void
    {
        $this->authorizeAdminPermission('website.home.manage');

        try {
            $themes->rename($this->selectedTheme, $this->themeName);
            unset($this->layoutThemes);
            $this->themeToast('Đã đổi tên Homepage theme.');
        } catch (InvalidArgumentException $exception) {
            $this->addError('theme', $exception->getMessage());
        }
    }

    public function deleteTheme(HomepageLayoutThemeService $themes): void
    {
        $this->authorizeAdminPermission('website.home.manage');

        try {
            $themes->delete($this->selectedTheme);
            $this->selectedTheme = '';
            $this->themeName = '';
            $this->themeJson = '';
            unset($this->layoutThemes);
            $this->themeToast('Đã xóa Homepage theme.');
        } catch (InvalidArgumentException $exception) {
            $this->addError('theme', $exception->getMessage());
        }
    }

    public function exportTheme(HomepageLayoutThemeService $themes): void
    {
        $this->authorizeAdminPermission('website.home.manage');
        $theme = $this->selectedThemeData($themes);
        if ($theme === null) {
            return;
        }

        try {
            $this->themeJson = $themes->export($theme);
            $this->resetErrorBag('theme');
            $this->themeToast('Đã tạo JSON export cho Homepage theme.');
        } catch (InvalidArgumentException $exception) {
            $this->addError('theme', $exception->getMessage());
        }
    }

    public function importTheme(HomepageLayoutThemeService $themes): void
    {
        $this->authorizeAdminPermission('website.home.manage');

        try {
            [$slug] = $themes->import($this->themeJson);
            $this->selectedTheme = $slug;
            $saved = $themes->all();
            $this->themeName = (string) ($saved[$slug]['name'] ?? '');
            unset($this->layoutThemes);
            $this->themeToast('Đã import Homepage theme. Chọn Áp dụng để nạp vào Builder.');
        } catch (InvalidArgumentException $exception) {
            $this->addError('theme', $exception->getMessage());
        }
    }

    private function selectedThemeData(HomepageLayoutThemeService $themes): ?array
    {
        $all = $themes->all();
        if ($this->selectedTheme === '' || ! is_array($all[$this->selectedTheme] ?? null)) {
            $this->addError('theme', 'Hãy chọn Homepage theme trước.');
            return null;
        }

        return $all[$this->selectedTheme];
    }

    private function themeToast(string $message): void
    {
        $this->resetErrorBag('theme');
        $this->dispatch('alert', ['type' => 'success', 'message' => $message]);
    }
}
