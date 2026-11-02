<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PharmaIssuePdfBladeCompilationTest extends TestCase
{
    public function test_deferred_issue_pdf_blade_compiles_to_valid_php(): void
    {
        $path = base_path('Modules/Pharma/resources/views/pages/inventory/issue-pdf.blade.php');
        $template = file_get_contents($path);

        $this->assertIsString($template);
        $this->assertStringContainsString('@if($deferredItems->isNotEmpty())', $template);
        $this->assertStringContainsString('@php'."\n".'    $deferred =', $template);

        $compiled = Blade::compileString($template);
        $this->assertNotSame('', $compiled);

        $file = tempnam(sys_get_temp_dir(), 'pharma-issue-pdf-');
        try {
            file_put_contents($file, $compiled);
            exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1', $output, $exitCode);
            $this->assertSame(0, $exitCode, implode("\n", $output));
        } finally {
            @unlink($file);
        }
    }
}
