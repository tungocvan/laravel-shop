<?php

namespace Modules\Pharma\Tests\Unit;

use Tests\TestCase;

class PriceListExportProfileRuntimeRegressionContractTest extends TestCase
{
    public function test_import_and_duplicate_names_are_collision_safe(): void
    {
        $service = $this->compact(file_get_contents(base_path('Modules/Pharma/Services/PriceListExportProfileService.php')));

        $this->assertStringContainsString($this->compact('private function uniqueName'), $service);
        $this->assertStringContainsString($this->compact("where('user_id',\$userId)->where('name',\$name)->exists()"), $service);
        $this->assertStringContainsString($this->compact("\$suffix=' ('.\$i++.')'"), $service);
        $this->assertStringContainsString($this->compact("'name'=>\$this->uniqueName(\$userId,\$baseName)"), $service);
        $this->assertStringContainsString($this->compact("\$c->name=\$this->uniqueName"), $service);
    }

    public function test_confirmed_profile_delete_uses_captured_profile_id(): void
    {
        $component = $this->compact(file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php')));

        $this->assertStringContainsString($this->compact("requestDeleteProfile():void{if(\$this->profileId)\$this->confirm('Xóa profile?','delete-profile',(string)\$this->profileId);}"), $component);
        $this->assertStringContainsString($this->compact("\$action=\$this->confirmAction;\$value=\$this->confirmValue;"), $component);
        $this->assertStringContainsString($this->compact("if(\$action==='delete-profile'&&\$value!==''){\$profileId=(int)\$value;"), $component);
        $this->assertStringContainsString($this->compact("->delete((int)auth('admin')->id(),\$profileId)"), $component);
    }

    public function test_confirmed_json_delete_uses_captured_filename(): void
    {
        $component = $this->compact(file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php')));

        $this->assertStringContainsString($this->compact("requestDeleteJson(string \$name):void{\$this->confirm('Xóa file JSON?','delete-json',\$name);}"), $component);
        $this->assertStringContainsString($this->compact("\$action=\$this->confirmAction;\$value=\$this->confirmValue;"), $component);
        $this->assertStringContainsString($this->compact("if(\$action==='delete-json'&&\$value!==''){\$jsonName=\$value;"), $component);
        $this->assertStringContainsString($this->compact("->delete((int)auth('admin')->id(),\$jsonName)"), $component);
    }

    public function test_designer_delete_and_json_selection_wiring_avoids_expression_parser_edges(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator-v31.blade.php'));

        $this->assertStringContainsString('wire:click="requestDeleteProfile"', $view);
        $this->assertStringContainsString('wire:click="selectJsonFile($event.currentTarget.value)"', $view);
        $this->assertStringContainsString('wire:click="requestDeleteJson($event.currentTarget.value)"', $view);
        $this->assertStringContainsString('wire:click="$call(\'confirmAction\')"', $view);
        $this->assertStringContainsString('wire:key="json-library-{{ $loop->index }}"', $view);
        $this->assertStringNotContainsString('selectJsonFile(@js(', $view);
        $this->assertStringNotContainsString('requestDeleteJson(@js(', $view);
    }

    private function compact(string $source): string
    {
        return preg_replace('/\s+/', '', $source) ?? $source;
    }
}
