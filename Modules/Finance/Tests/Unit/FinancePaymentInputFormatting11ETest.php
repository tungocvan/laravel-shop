<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinancePaymentInputFormatting11ETest extends TestCase
{
    public function test_payment_input_formats_while_livewire_syncs_and_confirmation_requires_preview(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/debt-workspace.blade.php');
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/DebtWorkspace.php');

        self::assertStringContainsString('wire:model.live.debounce.300ms="paymentAmount"', $view);
        self::assertStringContainsString('x-on:input=', $view);
        self::assertStringContainsString("replace(/[^0-9]/g, '')", $view);
        self::assertStringContainsString("replace(/\\B(?=(\\d{3})+(?!\\d))/g, '.')", $view);
        self::assertStringContainsString('wire:click="previewPayment"', $view);
        self::assertStringContainsString('@disabled(! $paymentPreviewed)', $view);
        self::assertStringContainsString('public function updatedPaymentAmount()', $component);
        self::assertStringContainsString('$this->paymentPreviewed = true;', $component);
    }
}
