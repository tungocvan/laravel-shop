<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminHeaderPresentationContractTest extends TestCase
{
    public function test_shell_presentation_exposes_header_runtime_values(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminShellPresentationService.php'));

        foreach (['header_style', 'header_padding_x', 'header_action_gap', 'header_mode', 'header_backdrop_blur'] as $key) {
            $this->assertStringContainsString("'{$key}'", $service);
        }

        $this->assertStringContainsString('--admin-header-background', $service);
        $this->assertStringContainsString('--admin-header-divider', $service);
        $this->assertStringContainsString('--admin-header-shadow', $service);
        $this->assertStringContainsString("header.presentation.padding_x", $service);
        $this->assertStringContainsString("header.presentation.action_gap", $service);
    }

    public function test_header_consumes_semantic_runtime_instead_of_fixed_spacing_and_surface(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header.blade.php'));

        $this->assertStringContainsString("header_padding_x", $view);
        $this->assertStringContainsString("header_action_gap", $view);
        $this->assertStringContainsString("header_backdrop_blur", $view);
        $this->assertStringContainsString('data-admin-header-mode', $view);
        $this->assertStringContainsString('var(--admin-header-background)', $view);
        $this->assertStringContainsString('var(--admin-header-shadow)', $view);
        $this->assertStringNotContainsString('sm:px-6 lg:px-8', $view);
        $this->assertStringNotContainsString('var(--admin-surface-raised) 94%', $view);
    }
}
