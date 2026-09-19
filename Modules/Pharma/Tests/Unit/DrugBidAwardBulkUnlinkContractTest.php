<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DrugBidAwardBulkUnlinkContractTest extends TestCase
{
    #[Test]
    public function linked_review_supports_guarded_bulk_unlink(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/ReviewWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/review-workspace.blade.php'));

        foreach ([
            'requestBulkUnlink',
            'bulkUnlinkSelected',
            "where('review_status', DrugBidAwardMatch::REVIEW_CONFIRMED)",
            "review_reason' => 'bulk_manual_unlink_requires_review'",
            'DB::transaction',
            'lockForUpdate',
            "'medicine_id' => null",
            "'medicine_code' => null",
            "'medicine_match_status' => DrugBidAward::MATCH_UNRESOLVED",
        ] as $text) {
            $this->assertStringContainsString($text, $component);
        }

        foreach ([
            'wire:model.live="selectedAwardIds"',
            'Hủy liên kết hàng loạt',
            "confirmationAction === 'bulk_unlink'",
            'Dữ liệu kết quả trúng thầu không bị xóa',
            'wire:click="bulkUnlinkSelected"',
        ] as $text) {
            $this->assertStringContainsString($text, $view);
        }
    }
}
