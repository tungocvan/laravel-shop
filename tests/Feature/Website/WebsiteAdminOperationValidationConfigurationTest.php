<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteAdminOperationValidationConfigurationTest extends TestCase
{
    public function test_theme_export_requires_selection_and_operations_emit_feedback(): void
    {
        $concern = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/Concerns/ManagesWebsiteDesignThemes.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/design-themes.blade.php'));

        $this->assertStringContainsString("requireSelectedTheme('export')", $concern);
        $this->assertStringContainsString("addError('selectedTheme'", $concern);
        $this->assertStringContainsString("'Export JSON thành công'", $concern);
        $this->assertStringContainsString("'Không thể Export JSON'", $concern);
        $this->assertStringContainsString("'Import JSON thành công'", $concern);
        $this->assertStringContainsString("'Không thể Import JSON'", $concern);
        $this->assertStringContainsString("dispatch('operation-feedback'", $concern);
        $this->assertStringContainsString("@error('selectedTheme')", $view);
        $this->assertStringContainsString('wire:click="exportDesignTheme"', $view);
    }

    public function test_website_save_uses_confirmation_and_success_failure_feedback(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/WebsiteSettings.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/website-settings.blade.php'));
        $confirm = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/save-confirm.blade.php'));
        $feedback = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/operation-feedback.blade.php'));

        $this->assertStringContainsString("'Không thể lưu thay đổi'", $component);
        $this->assertStringContainsString("'Lưu thay đổi thất bại'", $component);
        $this->assertStringContainsString("'Lưu thay đổi thành công'", $component);
        $this->assertStringContainsString("report(\$exception)", $component);
        $this->assertStringContainsString("@click=\"\$dispatch('website-save-confirm')\"", $view);
        $this->assertStringContainsString('partials.save-confirm', $view);
        $this->assertStringContainsString('partials.operation-feedback', $view);
        $this->assertStringContainsString('@website-save-confirm.window', $confirm);
        $this->assertStringContainsString('$wire.save()', $confirm);
        $this->assertStringContainsString('@operation-feedback.window', $feedback);
    }

    public function test_operation_validation_standard_is_documented(): void
    {
        $docs = file_get_contents(base_path('docs/modules/Website/ADMIN_OPERATION_VALIDATION_STANDARD.md'));

        foreach (['Save / Update', 'Export', 'Import', 'Confirmation vs feedback modal', 'operation-feedback'] as $contract) {
            $this->assertStringContainsString($contract, $docs);
        }
    }
}
