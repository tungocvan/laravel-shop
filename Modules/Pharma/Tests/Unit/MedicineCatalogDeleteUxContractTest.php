<?php

namespace Modules\Pharma\Tests\Unit;

use Tests\TestCase;

class MedicineCatalogDeleteUxContractTest extends TestCase
{
    public function test_catalog_exposes_group_and_protects_hssp_delete_with_centered_modals(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Index.php'));
        $index = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));
        $form = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/form.blade.php'));

        $this->assertIsString($component);
        $this->assertIsString($index);
        $this->assertIsString($form);
        $this->assertStringContainsString('>Nhóm thuốc</th>', $index);
        $this->assertStringContainsString('{{ $medicine->circular_group', $index);
        $this->assertStringContainsString('$medicine->profiles_count > 0', $index);
        $this->assertStringContainsString('disabled title="Không thể xóa vì thuốc đã có HSSP"', $index);
        $this->assertStringContainsString('wire:click="confirmDelete(', $index);
        $this->assertStringContainsString('wire:click="deleteConfirmed"', $index);
        $this->assertStringContainsString('fixed inset-0 z-50 flex items-center justify-center', $index);
        $this->assertStringContainsString('Đã xóa thuốc khỏi Medicine Master thành công.', $component);
        $this->assertStringContainsString('Không thể xóa thuốc vì đã có Hồ sơ sản phẩm (HSSP).', $component);
        $this->assertStringContainsString('← Quay về Danh mục thuốc chuẩn', $form);
        $this->assertStringNotContainsString('Variant / SKU', $index);
        $this->assertStringContainsString('min-w-64 px-4 py-4 text-sm leading-6', $index);
        $this->assertStringContainsString('@if($this->hasActiveSelectFilters())', $index);
        $this->assertStringContainsString('public function hasActiveSelectFilters(): bool', $component);
        $this->assertStringContainsString("$this->perPage !== 10", $component);
    }
}
