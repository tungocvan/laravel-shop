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
        $this->assertStringContainsString($this->compact('$c->name=$this->uniqueName'), $service);
    }

    public function test_confirmed_profile_delete_uses_collision_free_captured_profile_id(): void
    {
        $component = $this->compact(file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php')));

        $this->assertStringContainsString('publicfunctionrequestDeleteProfile():void', $component);
        $this->assertStringContainsString("\$this->openConfirmation('Xóaprofile?','delete-profile',(string)\$this->profileId);", $component);
        $this->assertStringContainsString('publicfunctionexecuteConfirmedAction():void', $component);
        $this->assertStringContainsString($this->compact('$action=$this->pendingConfirmAction;$value=$this->pendingConfirmValue;'), $component);
        $this->assertStringContainsString($this->compact("if(\$action==='delete-profile'&&\$value!=='')"), $component);
        $this->assertStringContainsString($this->compact('$profileId=(int)$value;'), $component);
        $this->assertStringContainsString($this->compact("->delete((int)auth('admin')->id(),\$profileId)"), $component);
    }

    public function test_confirmed_json_delete_uses_collision_free_captured_filename(): void
    {
        $component = $this->compact(file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php')));

        $this->assertStringContainsString('publicfunctionrequestDeleteJson(string$name):void', $component);
        $this->assertStringContainsString("\$this->openConfirmation('XóafileJSON?','delete-json',\$name);", $component);
        $this->assertStringContainsString($this->compact('$action=$this->pendingConfirmAction;$value=$this->pendingConfirmValue;'), $component);
        $this->assertStringContainsString($this->compact("if(\$action==='delete-json'&&\$value!=='')"), $component);
        $this->assertStringContainsString($this->compact('$jsonName=$value;'), $component);
        $this->assertStringContainsString($this->compact("->delete((int)auth('admin')->id(),\$jsonName)"), $component);
    }

    public function test_confirmation_state_and_action_names_do_not_collide(): void
    {
        $component = $this->compact(file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php')));

        $this->assertStringContainsString("\$pendingConfirmAction=''", $component);
        $this->assertStringContainsString("\$pendingConfirmValue=''", $component);
        $this->assertStringContainsString('privatefunctionopenConfirmation(', $component);
        $this->assertStringContainsString('publicfunctionexecuteConfirmedAction():void', $component);
        $this->assertStringNotContainsString("\$confirmAction=''", $component);
        $this->assertStringNotContainsString('publicfunctionconfirmAction():void', $component);
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
        $this->assertStringContainsString('wire:click="executeConfirmedAction"', $view);
        $this->assertStringContainsString('wire:target="executeConfirmedAction"', $view);
        $this->assertStringNotContainsString('wire:click="confirmAction"', $view);
        $this->assertStringNotContainsString('selectJsonFile(@js(', $view);
        $this->assertStringNotContainsString('requestDeleteJson(@js(', $view);
    }

    private function compact(string $source): string
    {
        return preg_replace('/\s+/', '', $source) ?? $source;
    }
}
