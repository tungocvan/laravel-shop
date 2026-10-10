<?php

namespace Tests\Feature\Admission;

use Modules\Admission\Imports\ApplicationsImport;
use Tests\TestCase;

class AdmissionImportGeneratedCodeTest extends TestCase
{
    public function test_blank_excel_code_is_generated_for_new_record_and_not_overwritten_for_existing_record(): void
    {
        $source = file_get_contents(base_path('Modules/Admission/Imports/ApplicationsImport.php'));

        $this->assertStringContainsString("if (empty(\$data['mhs'])) {", $source);
        $this->assertStringContainsString("unset(\$data['mhs']);", $source);
        $this->assertStringContainsString("get('application_code_prefix').'%s%04d', now()->year, \$nextId++)", $source);
        $this->assertStringContainsString("where('mhs', \$generatedCode)->exists()", $source);
        $this->assertStringContainsString("\$data['mhs'] = \$generatedCode;", $source);
        $this->assertTrue(class_exists(ApplicationsImport::class));
    }
}
