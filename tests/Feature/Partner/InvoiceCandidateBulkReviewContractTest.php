<?php

namespace Tests\Feature\Partner;

use Tests\TestCase;

class InvoiceCandidateBulkReviewContractTest extends TestCase
{
    public function test_invoice_candidate_workspace_follows_admin_filter_selection_and_pagination_contract(): void
    {
        $component = file_get_contents(base_path('Modules/Partner/Livewire/InvoiceCandidateReview.php'));
        $view = file_get_contents(base_path('Modules/Partner/resources/views/livewire/invoice-candidate-review.blade.php'));
        $pagination = file_get_contents(base_path('Modules/Partner/resources/views/vendor/pagination/admin-partner.blade.php'));
        $review = file_get_contents(base_path('Modules/Partner/Services/PartnerCandidateReviewService.php'));

        $this->assertStringContainsString("public string \$status = 'actionable'", $component);
        $this->assertStringContainsString('public string $role', $component);
        $this->assertStringContainsString('public array $selectedIds', $component);
        $this->assertStringContainsString('updatedSelectPage', $component);
        $this->assertStringContainsString('bulkCreate', $component);
        $this->assertStringContainsString("whereIn('status', ['pending', 'conflict'])", $component);
        $this->assertStringContainsString("whereJsonContains('partner_types', 'customer')", $component);

        $this->assertStringContainsString('Vai trò', $view);
        $this->assertStringContainsString('Tạo Partner đã chọn', $view);
        $this->assertStringContainsString('wire:model.live="selectPage"', $view);
        $this->assertStringContainsString("links('partner::vendor.pagination.admin-partner')", $view);

        $this->assertStringContainsString('bg-white', $pagination);
        $this->assertStringContainsString('bg-indigo-600', $pagination);
        $this->assertStringNotContainsString('bg-black', $pagination);

        $this->assertStringContainsString('function createPartners', $review);
        $this->assertStringContainsString("where('tax_code', \$candidate->tax_code)->exists()", $review);
        $this->assertStringContainsString('$this->confirmExisting($candidate, [])', $review);
    }
}
