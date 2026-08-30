<?php

namespace Tests\Feature\Pharma;

use Tests\TestCase;

class PharmaPriceListPipelineTest extends TestCase
{
    public function test_price_list_livewire_keeps_workbook_analysis_off_public_state(): void
    {
        $source = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Create.php'));

        $this->assertIsString($source);
        $this->assertStringNotContainsString('public array $analysis', $source);
        $this->assertStringNotContainsString('selectAllProducts', $source);
        $this->assertStringContainsString('private const PER_PAGE_OPTIONS = [10, 25, 50, 100];', $source);
        $this->assertStringContainsString('public bool $selectPage = false;', $source);
        $this->assertStringContainsString('private function analysisSummary', $source);
    }

    public function test_price_list_generation_uses_service_as_single_validation_boundary(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Create.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/PriceListService.php'));

        $this->assertIsString($component);
        $this->assertIsString($service);
        $this->assertStringNotContainsString("parseColumns($validated['columns']", $component);
        $this->assertStringContainsString('$analysis = $this->analyze(', $service);
        $this->assertStringContainsString('$columns = $this->parseColumns(', $service);
        $this->assertStringContainsString('$productRows = $this->resolveProductRows(', $service);
    }

    public function test_price_list_output_is_private_and_not_controlled_by_request_input(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/PriceListService.php'));

        $this->assertIsString($service);
        $this->assertStringContainsString("DEFAULT_EXPORT_DIRECTORY = 'app/private/exports/price-lists'", $service);
        $this->assertStringNotContainsString("$input['output_path']", $service);
        $this->assertStringContainsString('private function makeOutputPath(): string', $service);
        $this->assertStringContainsString('@unlink($outputPath)', $service);
    }

    public function test_price_list_workspace_is_bounded_and_page_scoped(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/create.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('wire:model.live="perPage"', $view);
        $this->assertStringContainsString('wire:model.live="selectPage"', $view);
        $this->assertStringContainsString('Chỉ chọn theo trang hiện tại', $view);
        $this->assertStringNotContainsString('Chọn tất cả', $view);
        $this->assertStringContainsString('wire:loading.attr="disabled"', $view);
    }
}
