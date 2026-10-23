<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceReverseToDraftContractTest extends TestCase
{
    public function test_reversal_preserves_history_and_creates_a_new_draft(): void
    {
        $service = file_get_contents(dirname(__DIR__, 2).'/Services/VoucherPostingService.php');
        $this->assertStringContainsString('public function reverseToDraft(', $service);
        $this->assertStringContainsString('$this->reverse($voucherId, $actorId);', $service);
        $this->assertStringContainsString("'status' => 'draft'", $service);
        $this->assertStringContainsString("'voucher_id' => $newId", $service);
        $this->assertStringContainsString("DB::transaction(function () use (\$voucherId, \$actorId): int", $service);
    }

    public function test_reverse_to_draft_requires_both_permissions(): void
    {
        $workspace = file_get_contents(dirname(__DIR__, 2).'/Livewire/Workspace.php');
        $this->assertStringContainsString("can('finance.voucher.reverse')", $workspace);
        $this->assertStringContainsString("can('finance.voucher.create')", $workspace);
        $this->assertStringContainsString('reverseToDraft($voucherId', $workspace);
    }
}
