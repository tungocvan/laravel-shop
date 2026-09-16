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

        $this->assertStringContainsString('publicfunctionrequestDeleteProfile():void', $component);
        $this->assertStringContainsString("\$this->confirm('Xóaprofile?','delete-profile',(string)\$this->profileId);", $component);
        $this->assertStringContainsString($this->compact("\$action=\$this->confirmAction;\$value=\$this->confirmValue;"), $component);
        $this->assertStringContainsString($this->compact("if(\$action==='delete-profile'&&\$value!=='')"), $component);
        $this->assertStringContainsString($this->compact("\$profileId=(int)\$value;"), $component);
        $this->assertStringContainsString($this->compact("->delete((int)auth('admin')->id(),\$profileId)"), $component);
    }

    public function test_confirmed_json_delete_uses_captured_filename(): void
    {
        $component = $this->compact(file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php')));

        $this->assertStringContainsString('publicfunctionrequestDeleteJson(string$name):void', $component);
        $this->assertStringContainsString("\$this->confirm('XóafileJSON?','delete-json',\$name);", $component);
        $this->assertStringContainsString($this->compact("\$action=\$this->confirmAction;\$value=\$this->confirmValue;"), $component);
        $this->assertStringContainsString($this->compact("if(\$action==='delete-json'&&\$value!=='')"), $component);
        $this->assertStringContainsString($this->compact("\$jsonName=\$value;"), $component);
        $this->assertStringContainsString($this->compact("->delete((int)auth('admin')->id(),\$jsonName)"), $component);
    }

    public function test_designer_v32_owns_interaction_safe_json_and_confirmation_overlays(): void
    {
        $legacyView = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator-v31.blade.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator-v32.blade.php'));

        $this->assertStringContainsString('wire:click="requestDeleteProfile"', $legacyView);
        $this->assertStringContainsString('wire:key="excel-designer-json-library-v32"', $view);
        $this->assertStringContainsString('wire:model.live="selectedJsonFile"', $view);
        $this->assertStringContainsString('value="{{ $file[\'name\'] }}"', $view);
        $this->assertStringContainsString('$wire.requestDeleteJson($el.value)', $view);
        $this->assertStringContainsString('wire:key="excel-designer-confirm-v32"', $view);
        $this->assertStringContainsString("\$wire.\$call('confirmAction')", $view);
        $this->assertStringNotContainsString('selectJsonFile(@js(', $view);
        $this->assertStringNotContainsString('requestDeleteJson(@js(', $view);
    }

    private function compact(string $source): string
    {
        return preg_replace('/\s+/', '', $source) ?? $source;
    }
}
