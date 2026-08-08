<?php

namespace Tests\Feature\Admin;

use Modules\Admin\Services\MenuImportExportService;
use Tests\TestCase;

class MenuImportExportServiceTest extends TestCase
{
    public function test_it_reports_invalid_menu_json(): void
    {
        $report = app(MenuImportExportService::class)->importFromJson('{bad json');

        $this->assertFalse($report['success']);
        $this->assertSame(1, $report['error_rows']);
        $this->assertNotEmpty($report['errors']);
    }

    public function test_it_reports_invalid_menu_tree_before_persisting(): void
    {
        $payload = json_encode([
            [
                'name' => '',
                'url' => '/admin',
                'children' => 'invalid',
            ],
        ]);

        $report = app(MenuImportExportService::class)->importFromJson($payload);

        $this->assertFalse($report['success']);
        $this->assertSame(1, $report['total_rows']);
        $this->assertSame(2, $report['error_rows']);
        $this->assertSame('name', $report['errors'][0]['column']);
        $this->assertSame('children', $report['errors'][1]['column']);
    }
}
