<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinancePaymentPreparationContractTest extends TestCase
{
    public function test_payment_preparation_is_not_a_posting_action(): void
    {
        $root = dirname(__DIR__, 4);
        $component = file_get_contents($root.'/Modules/Finance/Livewire/DebtWorkspace.php');
        $view = file_get_contents($root.'/Modules/Finance/resources/views/livewire/debt-workspace.blade.php');

        self::assertStringContainsString('public function previewPayment(): void', $component);
        self::assertStringContainsString('private function toPaymentCents(string $value): int', $component);
        self::assertStringContainsString('paymentAccountId', $view);
        self::assertStringContainsString('paymentReference', $view);
        self::assertStringContainsString('wire:click="previewPayment"', $view);
        self::assertStringContainsString('Xác nhận thanh toán', $view);
        self::assertStringContainsString('disabled class=', $view);
        self::assertStringContainsString('public function confirmPayment(', $component);
    }
}
