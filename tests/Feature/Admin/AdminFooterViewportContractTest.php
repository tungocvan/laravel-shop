<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminFooterViewportContractTest extends TestCase
{
    public function test_admin_shell_owns_viewport_and_only_content_scrolls(): void
    {
        $master = file_get_contents(base_path('Modules/Admin/resources/views/layouts/master.blade.php'));
        $shell = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/shell.blade.php'));
        $content = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/content.blade.php'));
        $footer = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/footer.blade.php'));

        $this->assertStringContainsString('<html lang="{{ $adminConfig[\'locale\'] ?? \'vi\' }}" class="h-full overflow-hidden">', $master);
        $this->assertStringContainsString('class="h-full overflow-hidden bg-slate-50"', $master);
        $this->assertStringContainsString('height: 100dvh;', $shell);
        $this->assertStringContainsString('class="grid min-h-0 min-w-0 overflow-hidden"', $shell);
        $this->assertStringContainsString('grid-template-rows: auto minmax(0, 1fr) auto;', $shell);
        $this->assertStringContainsString('class="min-h-0 flex-1 overflow-y-auto focus:outline-none"', $content);
        $this->assertStringContainsString('class="shrink-0 text-xs', $footer);
        $this->assertStringNotContainsString('position: fixed', $footer);
    }
}
