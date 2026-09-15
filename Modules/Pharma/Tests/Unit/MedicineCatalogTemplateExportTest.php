<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Exports\MedicineCatalogTemplateExport;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineCatalogTemplateExportTest extends TestCase
{
    #[Test]
    public function only_declared_price_is_numeric_in_the_template(): void
    {
        $export = new MedicineCatalogTemplateExport;
        $formats = $export->columnFormats();

        foreach (range('A', 'P') as $column) {
            $this->assertSame(NumberFormat::FORMAT_TEXT, $formats[$column], "Column {$column} must stay text");
        }

        $this->assertNotSame(NumberFormat::FORMAT_TEXT, $formats['Q']);
        $this->assertSame('#,##0.########', $formats['Q']);
    }

    #[Test]
    public function template_contains_therapeutic_group_and_special_control_columns(): void
    {
        $export = new MedicineCatalogTemplateExport;
        $headings = $export->headings();

        $this->assertContains('Nhóm thuốc điều trị', $headings);
        $this->assertContains('Thuốc KSĐB', $headings);
        $this->assertSame('Giá KK/ KKL', $headings[array_key_last($headings)]);
    }
}
