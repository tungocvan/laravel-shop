<?php

namespace Modules\Admin\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminShellViewportScrollContractTest extends TestCase
{
    public function test_workspace_is_height_constrained_and_main_remains_vertical_scroll_owner(): void
    {
        $root = dirname(__DIR__, 4);
        $shell = file_get_contents($root.'/Modules/Admin/resources/views/layouts/partials/shell.blade.php');
        $content = file_get_contents($root.'/Modules/Admin/resources/views/layouts/partials/content.blade.php');
        $master = file_get_contents($root.'/Modules/Admin/resources/views/layouts/master.blade.php');

        self::assertStringContainsString('height: 100dvh', $shell);
        self::assertStringContainsString('class="grid h-full min-h-0 min-w-0 overflow-hidden"', $shell);
        self::assertStringContainsString('grid-template-rows: auto minmax(0, 1fr) auto;', $shell);
        self::assertStringContainsString('id="admin-main"', $content);
        self::assertStringContainsString('min-h-0 flex-1 overflow-y-auto', $content);
        self::assertStringContainsString('class="h-full overflow-hidden"', $master);
    }
}
