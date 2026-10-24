<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtSettlementLifecycle;
use Tests\TestCase;

final class FinanceDebtSettlementLifecycleTest extends TestCase
{
    public function test_draft_to_posted_is_only_a_structural_candidate(): void
    {
        $result = (new FinanceDebtSettlementLifecycle)->inspectTransition('draft', 'posted');
        self::assertTrue($result['allowed']);
        self::assertSame('requires_atomic_posting_and_authoritative_mapping', $result['reason']);
    }

    public function test_posted_to_reversed_is_only_a_structural_candidate(): void
    {
        $result = (new FinanceDebtSettlementLifecycle)->inspectTransition('posted', 'reversed');
        self::assertTrue($result['allowed']);
        self::assertSame('requires_atomic_reversal_and_authoritative_mapping', $result['reason']);
    }

    public function test_duplicate_post_is_rejected(): void
    {
        $result = (new FinanceDebtSettlementLifecycle)->inspectTransition('posted', 'posted');
        self::assertFalse($result['allowed']);
        self::assertSame('duplicate_transition', $result['reason']);
    }

    public function test_reversed_cannot_be_reposted(): void
    {
        $result = (new FinanceDebtSettlementLifecycle)->inspectTransition('reversed', 'posted');
        self::assertFalse($result['allowed']);
        self::assertSame('invalid_lifecycle_transition', $result['reason']);
    }

    public function test_unknown_status_is_rejected(): void
    {
        self::assertSame('unknown_lifecycle_status', (new FinanceDebtSettlementLifecycle)->inspectTransition('unknown', 'posted')['reason']);
    }

    public function test_execution_remains_disabled_even_for_valid_transition(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementLifecycle)->assertCanExecute('draft', 'posted');
    }

    public function test_reversal_execution_remains_disabled(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementLifecycle)->assertCanExecute('posted', 'reversed');
    }
}
