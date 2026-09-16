<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListExportProfileV2ContractTest extends TestCase
{
    #[Test]
    public function export_configurator_supports_profiles_media_and_portable_json_library(): void
    {
        $component=file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));
        $service=file_get_contents(base_path('Modules/Pharma/Services/PriceListExportProfileService.php'));
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator.blade.php'));
        $compact=str_replace(' ','',$component);

        foreach(['WithFileUploads','logoUpload','signatureUpload','openJsonSave','openJsonLibrary','importJson'] as $needle)$this->assertStringContainsString($needle,$component);
        $this->assertStringContainsString('pharma.price-list-export-profile.v1',$service);
        foreach(['Lưu JSON','Thư viện JSON','Tải JSON từ máy','temporaryUrl()','Thương hiệu & nội dung','Cột dữ liệu','Trang in'] as $needle)$this->assertStringContainsString($needle,$view);
        $this->assertStringContainsString('$this->removeLogoRequested=true',$compact);
        $this->assertStringContainsString('$this->removeSignatureRequested=true',$compact);
        $this->assertStringNotContainsString('removeLogo():void{$this->deleteMedia(',$compact);
    }

    #[Test]
    public function column_designer_keeps_selected_order_as_excel_order(): void
    {
        $component=file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator.blade.php'));
        $compact=str_replace(' ','',$component);

        foreach(['function addColumn','function removeColumn','function moveColumn','function resetSelectedOrder','function replaceSelectedOrder'] as $needle)$this->assertStringContainsString($needle,$component);
        $this->assertStringContainsString("'column_order'=>\$this->columnOrder",$compact);
        foreach(['1. Kho dữ liệu','2. Cột sẽ xuất Excel','3. Xem trước header Excel','A/B/C là vị trí thật trong file.','wire:click="resetSelectedOrder"'] as $needle)$this->assertStringContainsString($needle,$view);
        $this->assertStringNotContainsString('draggable="true"',$view);
    }

    #[Test]
    public function designer_v3_exposes_table_style_json_library_and_feedback_modals(): void
    {
        $component=file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator.blade.php'));

        foreach(['pageSetup.table_border','pageSetup.header_background','pageSetup.header_text_color','Excel Designer v3','Times New Roman','Thư viện cấu hình JSON','Lưu cấu hình JSON'] as $needle)$this->assertStringContainsString($needle,$view.$component);
        foreach(['noticeOpen','confirmOpen','confirmAction','saveJsonToServer','importSelectedJson'] as $needle)$this->assertStringContainsString($needle,$component);
    }

    #[Test]
    public function excel_export_applies_saved_profile_media_page_setup_and_cell_types(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));
        $compact=str_replace(' ','',$controller);

        foreach(['PriceListExportProfileService $profiles',"'export_profile_id'",'new Drawing','$profile[\'logo_path\']','$profile[\'signature_path\']','setPaperSize','setFitToWidth','writeConfiguredCell(','DataType::TYPE_STRING','Date::PHPToExcel',"setFormatCode('dd/mm/yyyy')",'getNumberFormat()->setFormatCode'] as $needle)$this->assertStringContainsString($needle,$controller);
        $this->assertStringContainsString('catch(Throwable)',$compact);
    }
}
