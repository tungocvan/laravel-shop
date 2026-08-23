<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminLayoutContractTest extends TestCase
{
    public function test_master_layout_remains_a_thin_orchestration_shell(): void
    {
        $master = file_get_contents(base_path('Modules/Admin/resources/views/layouts/master.blade.php'));

        $this->assertStringContainsString("@include('Admin::layouts.partials.head')", $master);
        $this->assertStringContainsString('<x-admin::layout.skip-link />', $master);
        $this->assertStringContainsString("@include('Admin::layouts.partials.shell')", $master);
        $this->assertStringContainsString("@include('Admin::layouts.partials.stacks')", $master);
        $this->assertStringContainsString("@include('Admin::layouts.partials.scripts')", $master);

        $this->assertStringNotContainsString('<livewire:admin.partials.header', $master);
        $this->assertStringNotContainsString('<livewire:admin.partials.sidebar', $master);
        $this->assertStringNotContainsString('@yield(\'content\')', $master);
    }

    public function test_shell_and_content_preserve_existing_rendering_contracts(): void
    {
        $shell = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/shell.blade.php'));
        $content = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/content.blade.php'));

        $this->assertStringContainsString('<livewire:admin.partials.sidebar />', $shell);
        $this->assertStringContainsString('<livewire:admin.partials.header />', $shell);
        $this->assertStringContainsString("@include('Admin::layouts.partials.content')", $shell);
        $this->assertStringContainsString("@include('Admin::layouts.partials.footer')", $shell);

        $this->assertStringContainsString('@isset($slot)', $content);
        $this->assertStringContainsString('{{ $slot }}', $content);
        $this->assertStringContainsString("@yield('content')", $content);
        $this->assertStringContainsString('id="admin-main"', $content);
    }

    public function test_admin_layout_keeps_vite_and_page_asset_extension_points(): void
    {
        $head = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/head.blade.php'));
        $scripts = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/scripts.blade.php'));

        $this->assertStringContainsString("@vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])", $head);
        $this->assertStringContainsString("@yield('css')", $head);
        $this->assertStringContainsString("@stack('styles')", $head);
        $this->assertStringContainsString("@yield('js')", $scripts);
        $this->assertStringContainsString("@stack('scripts')", $scripts);
    }

    public function test_livewire_assets_have_exactly_one_source(): void
    {
        $head = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/head.blade.php'));
        $scripts = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/scripts.blade.php'));
        $livewireConfig = require base_path('config/livewire.php');

        $manualAssets = str_contains($head, '@livewireStyles') || str_contains($scripts, '@livewireScripts');
        $autoInject = (bool) ($livewireConfig['inject_assets'] ?? true);

        $this->assertNotSame(
            $autoInject,
            $manualAssets,
            'Admin layout must use exactly one Livewire asset source: auto injection or manual Blade directives.'
        );
    }
}
