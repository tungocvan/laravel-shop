<?php

namespace Tests\Feature\Admin;

use Modules\Admin\Services\AdminDesignService;
use Tests\TestCase;

class AdminDesignContractTest extends TestCase
{
    public function test_admin_design_config_defines_semantic_token_groups(): void
    {
        $design = config('admin.admin.design');

        $this->assertIsArray($design);
        $this->assertArrayHasKey('typography', $design);
        $this->assertArrayHasKey('colors', $design);
        $this->assertArrayHasKey('spacing', $design);
        $this->assertArrayHasKey('radius', $design);

        $this->assertSame('slate-50', data_get($design, 'colors.surface_base'));
        $this->assertSame('white', data_get($design, 'colors.surface_raised'));
        $this->assertSame('indigo-600', data_get($design, 'colors.accent'));
    }

    public function test_design_service_sanitizes_unknown_values_and_drops_unknown_keys(): void
    {
        $service = new AdminDesignService();

        $tokens = $service->sanitize([
            'typography' => [
                'font_family' => 'javascript:alert(1)',
                'body_size' => 'sm',
            ],
            'colors' => [
                'surface_base' => 'url(javascript:alert(1))',
                'accent' => 'indigo-600',
            ],
            'spacing' => [
                'content' => '999',
            ],
            'radius' => [
                'control' => '999px',
            ],
            'unexpected' => ['raw' => '<script>alert(1)</script>'],
        ]);

        $this->assertSame('sans', data_get($tokens, 'typography.font_family'));
        $this->assertSame('sm', data_get($tokens, 'typography.body_size'));
        $this->assertSame('slate-50', data_get($tokens, 'colors.surface_base'));
        $this->assertSame('indigo-600', data_get($tokens, 'colors.accent'));
        $this->assertSame('4', data_get($tokens, 'spacing.content'));
        $this->assertSame('lg', data_get($tokens, 'radius.control'));
        $this->assertArrayNotHasKey('unexpected', $tokens);
    }

    public function test_css_variables_are_resolved_from_whitelisted_values(): void
    {
        $service = new AdminDesignService();
        $variables = $service->cssVariables([
            'colors' => [
                'surface_base' => 'slate-50',
                'surface_raised' => 'white',
                'text_primary' => 'slate-900',
                'text_secondary' => 'slate-700',
                'text_muted' => 'slate-500',
                'border_subtle' => 'slate-200',
                'accent' => 'indigo-600',
                'focus_ring' => 'indigo-500',
                'success' => 'emerald-600',
                'warning' => 'amber-500',
                'danger' => 'rose-600',
                'info' => 'sky-600',
            ],
        ]);

        $this->assertSame('#f8fafc', $variables['--admin-surface-base']);
        $this->assertSame('#ffffff', $variables['--admin-surface-raised']);
        $this->assertSame('#0f172a', $variables['--admin-text-primary']);
        $this->assertSame('#4f46e5', $variables['--admin-accent']);
        $this->assertSame('#6366f1', $variables['--admin-focus-ring']);
        $this->assertSame('0.5rem', $variables['--admin-radius-control']);
        $this->assertSame('1rem', $variables['--admin-space-content']);

        foreach ($variables as $name => $value) {
            $this->assertStringStartsWith('--admin-', $name);
            $this->assertStringNotContainsString('javascript:', $value);
            $this->assertStringNotContainsString('<script', $value);
        }
    }

    public function test_head_composes_design_tokens_without_replacing_existing_asset_contracts(): void
    {
        $head = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/head.blade.php'));
        $presentation = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/presentation-styles.blade.php'));

        $this->assertStringContainsString("@include('Admin::layouts.partials.presentation-styles')", $head);
        $this->assertStringContainsString("@vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])", $head);
        $this->assertStringContainsString('@livewireStyles', $head);
        $this->assertStringContainsString('AdminDesignService::class', $presentation);
        $this->assertStringContainsString('id="admin-design-tokens"', $presentation);
        $this->assertStringContainsString(':root', $presentation);
    }
}
