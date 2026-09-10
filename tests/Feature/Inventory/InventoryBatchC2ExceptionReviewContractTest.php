<?php

namespace Tests\Feature\Inventory;

use Modules\Inventory\Services\InventoryReferenceCandidateService;
use Modules\Inventory\Services\InvoiceLineStockClassifier;
use Modules\Pharma\Models\Medicine;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
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
    public function exception_review_keeps_bulk_capabilities_in_component_and_business_actions_in_ui(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString('selectAllUnresolved', $component);
        $this->assertStringContainsString('bulkAssignSelected', $component);
        $this->assertStringContainsString('bulkMarkNonStock', $component);
        $this->assertStringContainsString('wire:change="assignLine(', $view);
        $this->assertStringContainsString('wire:click="markNonStock(', $view);
        $this->assertStringContainsString('wire:click="createDraftReceipt"', $view);
        $this->assertStringContainsString('Tạo phiếu nhập nháp', $view);
        $this->assertStringContainsString('Phiếu nháp chưa làm thay đổi tồn kho', $view);
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
        $this->assertSame('deterministic-v2', $result['classifier']);
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
    public function admin_goods_annotation_is_stock_evidence_but_does_not_assign_inventory_item(): void
    {
        $classifier = app(InvoiceLineStockClassifier::class);
        $result = $classifier->classify('Vật tư chuyên môn', null, [
            'source_business_classification' => 'GOODS',
            'source_business_note' => 'Nhà cung cấp hàng hóa.',
        ]);

        $this->assertSame('STOCK', $result['classification']);
        $this->assertSame('admin_source_goods', $result['reason']);
        $this->assertSame('deterministic-v2', $result['classifier']);

        $integration = file_get_contents(base_path('Modules/Inventory/Services/InventoryInvoiceIntegrationService.php'));
        $this->assertStringContainsString("'inventory_item_id' => null", $integration);
    }

    #[Test]
    public function admin_service_expense_annotation_marks_weak_lines_non_stock(): void
    {
        $result = app(InvoiceLineStockClassifier::class)->classify(
            'Chi phí tư vấn tháng 01',
            null,
            ['source_business_classification' => 'SERVICE_EXPENSE'],
        );

        $this->assertSame('NON_STOCK', $result['classification']);
        $this->assertSame('admin_source_service_expense', $result['reason']);
    }

    #[Test]
    public function admin_service_expense_annotation_conflicting_with_physical_goods_fails_safe(): void
    {
        $result = app(InvoiceLineStockClassifier::class)->classify(
            'Cefuroxime 125mg/5ml',
            'Lọ',
            ['source_business_classification' => 'SERVICE_EXPENSE'],
        );

        $this->assertSame('UNRESOLVED', $result['classification']);
        $this->assertSame('source_annotation_conflicts_with_stock_evidence', $result['reason']);
    }

    #[Test]
    public function mixed_annotation_does_not_override_line_level_evidence(): void
    {
        $classifier = app(InvoiceLineStockClassifier::class);
        $goods = $classifier->classify('Thuốc Cefuroxime', 'Lọ', [
            'source_business_classification' => 'MIXED',
        ]);
        $fee = $classifier->classify('Phí vận chuyển', null, [
            'source_business_classification' => 'MIXED',
        ]);

        $this->assertSame('STOCK', $goods['classification']);
        $this->assertSame('NON_STOCK', $fee['classification']);
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

    #[Test]
    public function Product_and_Pharma_are_reference_candidates_only_and_never_assign_inventory_item(): void
    {
        $candidateService = file_get_contents(base_path('Modules/Inventory/Services/InventoryReferenceCandidateService.php'));
        $matching = file_get_contents(base_path('Modules/Inventory/Services/InventoryItemMatchingService.php'));

        $this->assertStringContainsString("'source' => 'Product'", $candidateService);
        $this->assertStringContainsString("'source' => 'Pharma'", $candidateService);
        $this->assertStringContainsString("'auto_match_eligible' => false", $candidateService);
        $this->assertStringContainsString('reference_only_no_inventory_item_link', $candidateService);
        $this->assertStringNotContainsString("'inventory_item_id' =>", $candidateService);
        $this->assertStringContainsString("'reference_candidates'", $matching);
    }

    #[Test]
    public function unverified_and_demo_Pharma_records_are_explicitly_blocked_from_auto_match(): void
    {
        $service = app(InventoryReferenceCandidateService::class);
        $method = new ReflectionMethod($service, 'pharmaBlockedReasons');
        $method->setAccessible(true);

        $medicine = new Medicine([
            'identity_status' => Medicine::IDENTITY_UNVERIFIED,
            'registration_number' => 'DEMO-PHARMA-HSSP-003',
            'notes' => 'demo reference record',
        ]);

        $blocked = $method->invoke($service, $medicine);

        $this->assertContains('identity_status_unverified', $blocked);
        $this->assertContains('demo_registration', $blocked);
        $this->assertContains('demo_record', $blocked);
    }

    #[Test]
    public function pharma_candidate_anchor_does_not_reduce_identity_to_strength_or_lot_data(): void
    {
        $service = app(InventoryReferenceCandidateService::class);
        $method = new ReflectionMethod($service, 'pharmaIdentityAnchor');
        $method->setAccessible(true);

        $anchor = $method->invoke(
            $service,
            'Cefuroxime 125mg/5ml ( H/1C.TT/60,8g BPHDU), Lô: 26001CN, HD: 28/03/2029',
        );

        $this->assertSame('cefuroxime', $anchor);
    }
}
