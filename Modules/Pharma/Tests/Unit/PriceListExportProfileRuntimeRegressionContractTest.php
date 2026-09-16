<?php

namespace Modules\Pharma\Tests\Unit;

use Tests\TestCase;

class PriceListExportProfileRuntimeRegressionContractTest extends TestCase
{
    public function test_import_and_duplicate_names_are_collision_safe(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/PriceListExportProfileService.php'));

        $this->assertStringContainsString('private function uniqueName', $service);
        $this->assertStringContainsString("where('user_id',\$userId)->where('name',\$name)->exists()", $service);
        $this->assertStringContainsString("\$suffix=' ('.\$i++.')'", $service);
        $this->assertStringContainsString("'name'=>\$this->uniqueName(\$userId,\$baseName)", $service);
        $this->assertStringContainsString("\$c->name=\$this->uniqueName", $service);
    }

    public function test_confirmed_profile_delete_uses_captured_profile_id(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));

        $this->assertStringContainsString("requestDeleteProfile():void{if(\$this->profileId)\$this->confirm('Xóa profile?','delete-profile',(string)\$this->profileId);}", $component);
        $this->assertStringContainsString("\$action=\$this->confirmAction;\$value=\$this->confirmValue;", $component);
        $this->assertStringContainsString("if(\$action==='delete-profile'&&\$value!==''){\$profileId=(int)\$value;", $component);
        $this->assertStringContainsString('->delete((int)auth(\'admin\')->id(),$profileId)', $component);
    }

    public function test_confirmed_json_delete_uses_captured_filename(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));

        $this->assertStringContainsString("requestDeleteJson(string \$name):void{\$this->confirm('Xóa file JSON?','delete-json',\$name);}", $component);
        $this->assertStringContainsString("\$action=\$this->confirmAction;\$value=\$this->confirmValue;", $component);
        $this->assertStringContainsString("if(\$action==='delete-json'&&\$value!==''){\$jsonName=\$value;", $component);
        $this->assertStringContainsString('->delete((int)auth(\'admin\')->id(),$jsonName)', $component);
    }
}
