<?php

namespace Tests\Feature\Admin;

use Modules\Admin\Services\AdminShellPresentationService;
use Tests\TestCase;

class AdminShellPresentationContractTest extends TestCase
{
    public function test_shell_presentation_resolves_container_density_and_dimensions(): void
    {
        $context = app(AdminShellPresentationService::class)->context();

        $this->assertArrayHasKey('content_class', $context);
        $this->assertArrayHasKey('content_padding_class', $context);
        $this->assertSame('16rem', $context['sidebar_expanded_width']);
        $this->assertSame('5rem', $context['sidebar_collapsed_width']);
        $this->assertSame('4rem', $context['header_height']);
    }

    public function test_shell_consumes_semantic_design_variables_and_runtime_dimensions(): void
    {
        $shell = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/shell.blade.php'));
        $content = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/content.blade.php'));

        $this->assertStringContainsString('AdminShellPresentationService::class', $shell);
        $this->assertStringContainsString('var(--admin-surface-base)', $shell);
        $this->assertStringContainsString('var(--admin-text-primary)', $shell);
        $this->assertStringContainsString('var(--admin-surface-raised)', $shell);
        $this->assertStringNotContainsString('var(--admin-border-subtle)', $shell);
        $this->assertStringNotContainsString('border-r', $shell);
        $this->assertStringContainsString("sidebar_expanded_width", $shell);
        $this->assertStringContainsString("sidebar_collapsed_width", $shell);
        $this->assertStringContainsString('&& isDesktop', $shell);

        $this->assertStringContainsString("content_class", $content);
        $this->assertStringContainsString("content_padding_class", $content);
        $this->assertStringNotContainsString('px-4 py-5 sm:px-5 lg:px-6 lg:py-6', $content);
    }

    public function test_design_tokens_have_one_layout_configuration_source(): void
    {
        $designService = file_get_contents(base_path('Modules/Admin/Services/AdminDesignService.php'));
        $layoutManager = file_get_contents(base_path('Modules/Admin/Support/AdminLayoutManager.php'));

        $this->assertStringContainsString('AdminLayoutManager', $designService);
        $this->assertStringNotContainsString('admin_design_config', $designService);
        $this->assertStringContainsString("'design' =>", $layoutManager);
    }
}