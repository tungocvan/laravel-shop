<?php

namespace Tests\Feature\Inventory;

use Modules\Inventory\Services\InvoiceLineStockClassifier;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryBatchC2ExceptionReviewContractTest extends TestCase
{
    #[Test]
    public function inbox_supports_bulk_exception_review_without_bypassing_canonical_matching(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));

        $this->assertStringContainsString('public array $selectedLineIds = []', $component);
        $this->assertStringContainsString('public ?int $bulkItemId = null', $component);
        $this->assertStringContainsString('selectAllUnresolved', $component);
        $this->assertStringContainsString('bulkAssignSelected', $component);
        $this->assertStringContainsString('bulkMarkNonStock', $component);
        $this->assertStringContainsString('InventoryItemMatchingService::class', $component);
        $this->assertStringContainsString('->assign($line, $item', $component);
        $this->assertStringContainsString('->markNonStock($line)', $component);
    }

    #[Test]
    public function selected_lines_are_scoped_to_the_current_inbox(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));

        $this->assertStringContainsString("->where('inbox_id', \$this->selectedInboxId)", $component);
        $this->assertStringContainsString("throw new DomainException('Có dòng đã chọn không thuộc hóa đơn hiện tại.')", $component);
        $this->assertStringContainsString("throw new DomainException('Chọn ít nhất một dòng cần xử lý.')", $component);
    }

    #[Test]
    public function exception_review_ui_has_bulk_mapping_and_non_stock_actions(): void
    {
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString('Chọn tất cả UNRESOLVED', $view);
        $this->assertStringContainsString('Mapping hàng loạt', $view);
        $this->assertStringContainsString('Đánh dấu NON_STOCK', $view);
        $this->assertStringContainsString('wire:model="selectedLineIds"', $view);
        $this->assertStringContainsString('wire:model="bulkItemId"', $view);
        $this->assertStringContainsString('trạng thái tự chuyển READY', $view);
        $this->assertStringNotContainsString('ReceiptPostingService', $view);
    }

    #[Test]
    public function deterministic_classifier_uses_gdt_goods_category_as_stock_evidence(): void
    {
        $result = app(InvoiceLineStockClassifier::class)->classify(
            'Cefuroxime 125mg/5ml',
            'Lọ',
            [
                'raw_gdt_line' => [
                    'InventoryItemCategoryCode' => 'HH',
                    'InventoryItemCategoryName' => 'Hàng hóa',
                ],
            ],
        );

        $this->assertSame('STOCK', $result['classification']);
        $this->assertSame('gdt_goods_category', $result['reason']);
        $this->assertSame('deterministic-v1', $result['classifier']);
    }

    #[Test]
    public function deterministic_classifier_rejects_service_and_interest_lines_from_stock(): void
    {
        $classifier = app(InvoiceLineStockClassifier::class);

        $service = $classifier->classify(
            'Dịch vụ kiểm định',
            null,
            [
                'raw_gdt_line' => [
                    'InventoryItemCategoryCode' => 'DV',
                    'InventoryItemCategoryName' => 'Dịch vụ',
                ],
            ],
        );

        $interest = $classifier->classify('Thanh toan lai', null, []);

        $this->assertSame('NON_STOCK', $service['classification']);
        $this->assertSame('gdt_service_category', $service['reason']);
        $this->assertSame('NON_STOCK', $interest['classification']);
        $this->assertSame('interest_payment', $interest['reason']);
    }

    #[Test]
    public function deterministic_classifier_fails_safe_when_evidence_is_insufficient(): void
    {
        $result = app(InvoiceLineStockClassifier::class)->classify(
            'Khoản chi khác',
            null,
            [],
        );

        $this->assertSame('UNRESOLVED', $result['classification']);
        $this->assertSame('insufficient_deterministic_evidence', $result['reason']);
    }

    #[Test]
    public function stock_without_inventory_item_remains_review_required_by_contract(): void
    {
        $matching = file_get_contents(base_path('Modules/Inventory/Services/InventoryItemMatchingService.php'));
        $integration = file_get_contents(base_path('Modules/Inventory/Services/InventoryInvoiceIntegrationService.php'));

        $this->assertStringContainsString("->where('classification', 'STOCK')", $matching);
        $this->assertStringContainsString("->whereNull('inventory_item_id')", $matching);
        $this->assertStringContainsString("'processing_status' => \$reviewRequired ? 'REVIEW_REQUIRED' : 'READY'", $matching);
        $this->assertStringContainsString("'stock_classification'", $integration);
        $this->assertStringContainsString("'match_reason' => 'classifier:'", $integration);
    }
}
