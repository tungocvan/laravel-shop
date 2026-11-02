<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class PharmaInventoryFastExcelImportContractTest extends TestCase
{
    public function test_inventory_controller_imports_installed_fast_excel_class(): void
    {
        $controller = file_get_contents(__DIR__.'/../../Modules/Pharma/Http/Controllers/InventoryController.php');
        $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('rap2hpoutre/fast-excel', $composer['require']);
        self::assertStringContainsString('use Rap2hpoutre\\FastExcel\\FastExcel;', $controller);
        self::assertStringContainsString('use Symfony\\Component\\HttpFoundation\\StreamedResponse;', $controller);
        self::assertSame(5, substr_count($controller, '): StreamedResponse'));
        self::assertStringContainsString("new FastExcel(\$rows)", $controller);
        self::assertStringContainsString("pharma-ton-dau-ky-mau.xlsx", $controller);
    }
}
