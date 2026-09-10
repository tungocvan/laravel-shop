<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtFileManagementContractTest extends TestCase
{
    #[Test]
    public function synced_files_use_checkbox_selection_and_bulk_delete(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SearchHoadon.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/search-hoadon.blade.php'));

        $this->assertStringContainsString('public array $selectedFiles = [];', $component);
        $this->assertStringContainsString('public function selectAllAvailableFiles(): void', $component);
        $this->assertStringContainsString('public function clearFileSelection(): void', $component);
        $this->assertStringContainsString('public function deleteSelectedFiles(): void', $component);
        $this->assertStringContainsString("'selectedFiles' => ['required', 'array', 'min:1', 'max:50']", $component);
        $this->assertStringContainsString('Dữ liệu hóa đơn và RAW canonical không bị xóa.', $component);

        $this->assertStringContainsString('type="checkbox" wire:model.live="selectedFiles"', $view);
        $this->assertStringContainsString('wire:click="selectAllAvailableFiles"', $view);
        $this->assertStringContainsString('wire:click="clearFileSelection"', $view);
        $this->assertStringContainsString('wire:click="deleteSelectedFiles"', $view);
        $this->assertStringNotContainsString('type="radio" wire:model.live="selectedFile"', $view);
    }

    #[Test]
    public function import_and_download_require_exactly_one_selected_file(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SearchHoadon.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/search-hoadon.blade.php'));

        $this->assertStringContainsString("'selectedFiles' => ['required', 'array', 'size:1']", $component);
        $this->assertStringContainsString('resolveSingleSelectedFile()', $component);
        $this->assertStringContainsString('@disabled(count($selectedFiles)!==1)', $view);
        $this->assertStringContainsString('không cần bấm Đồng bộ vào CSDL lần nữa', $view);
        $this->assertStringContainsString('file upload thủ công, Google Drive hoặc dữ liệu legacy', $view);
    }
}
