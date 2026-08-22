<?php

namespace Modules\Website\Livewire\Admin\Settings\Concerns;

use InvalidArgumentException;
use Illuminate\Validation\ValidationException;
use Modules\Website\Services\WebsiteDesignThemeService;
use Throwable;

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
        $this->resetValidation(['themeName', 'selectedTheme']);
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

        try {
            $this->validateThemeName('Vui lòng nhập tên theme.');
            $slug = app(WebsiteDesignThemeService::class)->save($this->themeName, $this->design);
            $this->selectedTheme = $slug;
            $this->updatedSelectedTheme($slug);
            $this->themeFeedback('success', 'Lưu theme thành công', 'Website design theme đã được lưu.');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
            $this->themeFeedback('error', 'Không thể lưu theme', 'Vui lòng kiểm tra lại Tên theme.');
        } catch (Throwable $exception) {
            report($exception);
            $this->themeFeedback('error', 'Không thể lưu theme', 'Có lỗi khi lưu Website design theme. Vui lòng thử lại.');
        }
    }

    public function applyDesignTheme(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');
        if (! $this->requireSelectedTheme('áp dụng')) {
            return;
        }

        try {
            $this->design = app(WebsiteDesignThemeService::class)->apply($this->selectedTheme);
            $this->themeFeedback('success', 'Áp dụng theme thành công', 'Theme đã được nạp vào form. Bấm Lưu thay đổi để publish ra storefront.');
        } catch (Throwable $exception) {
            report($exception);
            $this->themeFeedback('error', 'Không thể áp dụng theme', $this->safeThemeError($exception));
        }
    }

    public function updateDesignTheme(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');
        if (! $this->requireSelectedTheme('cập nhật')) {
            return;
        }

        try {
            app(WebsiteDesignThemeService::class)->update($this->selectedTheme, $this->design);
            $this->themeFeedback('success', 'Cập nhật theme thành công', 'Theme đã chọn đã được cập nhật bằng cấu hình thiết kế hiện tại.');
        } catch (Throwable $exception) {
            report($exception);
            $this->themeFeedback('error', 'Không thể cập nhật theme', $this->safeThemeError($exception));
        }
    }

    public function renameDesignTheme(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');
        if (! $this->requireSelectedTheme('đổi tên')) {
            return;
        }

        try {
            $this->validateThemeName('Vui lòng nhập tên theme mới.');
            app(WebsiteDesignThemeService::class)->rename($this->selectedTheme, $this->themeName);
            $this->themeFeedback('success', 'Đổi tên theme thành công', 'Tên Website design theme đã được cập nhật.');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
            $this->themeFeedback('error', 'Không thể đổi tên theme', 'Vui lòng kiểm tra lại Tên theme.');
        } catch (Throwable $exception) {
            report($exception);
            $this->themeFeedback('error', 'Không thể đổi tên theme', $this->safeThemeError($exception));
        }
    }

    public function deleteDesignTheme(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');
        if (! $this->requireSelectedTheme('xóa')) {
            return;
        }

        try {
            app(WebsiteDesignThemeService::class)->delete($this->selectedTheme);
            $this->selectedTheme = '';
            $this->themeName = '';
            $this->themeFeedback('success', 'Xóa theme thành công', 'Website design theme đã được xóa.');
        } catch (Throwable $exception) {
            report($exception);
            $this->themeFeedback('error', 'Không thể xóa theme', $this->safeThemeError($exception));
        }
    }

    public function restoreDefaultDesignThemes(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');

        try {
            app(WebsiteDesignThemeService::class)->restoreDefaultThemes();
            $this->themeFeedback('success', 'Khôi phục themes thành công', 'Đã khôi phục 03 Website design themes mặc định. Custom themes được giữ nguyên.');
        } catch (Throwable $exception) {
            report($exception);
            $this->themeFeedback('error', 'Không thể khôi phục themes', 'Có lỗi khi khôi phục themes mặc định. Vui lòng thử lại.');
        }
    }

    public function exportDesignTheme(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');
        if (! $this->requireSelectedTheme('export')) {
            return;
        }

        try {
            $this->themeJson = app(WebsiteDesignThemeService::class)->export($this->selectedTheme);
            $this->themeFeedback('success', 'Export JSON thành công', 'JSON của theme đã chọn đã được đưa vào vùng Export / Import.');
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('selectedTheme', $this->safeThemeError($exception));
            $this->themeFeedback('error', 'Không thể Export JSON', $this->safeThemeError($exception));
        }
    }

    public function importDesignTheme(): void
    {
        $this->resetValidation();
        $this->authorizeAdminPermission('website.settings.manage');

        try {
            $this->validate([
                'themeJson' => 'required|string|min:2',
            ], [
                'themeJson.required' => 'Vui lòng dán dữ liệu JSON theme trước khi import.',
                'themeJson.min' => 'Dữ liệu JSON theme không hợp lệ.',
            ]);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
            $this->themeFeedback('error', 'Không thể Import JSON', 'Vui lòng nhập dữ liệu JSON theme hợp lệ trước khi import.');
            return;
        }

        try {
            $slug = app(WebsiteDesignThemeService::class)->import(
                $this->themeJson,
                filled($this->themeName) ? $this->themeName : null
            );
        } catch (InvalidArgumentException $exception) {
            $this->addError('themeJson', $exception->getMessage());
            $this->themeFeedback('error', 'Không thể Import JSON', $exception->getMessage());
            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('themeJson', 'Có lỗi khi import Website design theme.');
            $this->themeFeedback('error', 'Không thể Import JSON', 'Có lỗi khi import Website design theme. Vui lòng thử lại.');
            return;
        }

        $this->selectedTheme = $slug;
        $this->updatedSelectedTheme($slug);
        $this->themeFeedback('success', 'Import JSON thành công', 'Website design theme đã được kiểm tra và import thành công.');
    }

    private function validateThemeName(string $requiredMessage): void
    {
        $this->validate(['themeName' => 'required|string|min:1|max:80'], [
            'themeName.required' => $requiredMessage,
            'themeName.max' => 'Tên theme không được vượt quá 80 ký tự.',
        ]);
    }

    private function requireSelectedTheme(string $action): bool
    {
        if ($this->selectedTheme !== '') {
            return true;
        }

        $message = 'Vui lòng chọn một theme đã lưu trước khi '.$action.'.';
        $this->addError('selectedTheme', $message);
        $this->themeFeedback('error', 'Chưa chọn theme', $message);
        return false;
    }

    private function safeThemeError(Throwable $exception): string
    {
        return $exception instanceof InvalidArgumentException
            ? $exception->getMessage()
            : 'Có lỗi khi xử lý Website design theme. Vui lòng thử lại.';
    }

    private function themeFeedback(string $type, string $title, string $message): void
    {
        $this->dispatch('operation-feedback', type: $type, title: $title, message: $message);
    }
}
