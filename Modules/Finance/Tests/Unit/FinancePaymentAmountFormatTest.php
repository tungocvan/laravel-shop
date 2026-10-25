<?php

namespace Modules\Finance\Tests\Unit;

use Illuminate\Validation\ValidationException;
use Modules\Finance\Livewire\DebtWorkspace;
use Tests\TestCase;

final class FinancePaymentAmountFormatTest extends TestCase
{
    public function test_grouped_vnd_amounts_are_normalized_to_exact_decimal_strings(): void
    {
        $component = new DebtWorkspace;
        $normalize = new \ReflectionMethod($component, 'normalizePaymentInput');
        $cents = new \ReflectionMethod($component, 'toPaymentCents');
        $format = new \ReflectionMethod($component, 'formatPaymentInput');

        foreach (['40.824.000' => '40824000', '40.000.000' => '40000000', '540.000.000' => '540000000',
            '1000000' => '1000000', '1.500,50' => '1500.50'] as $input => $expected) {
            self::assertSame($expected, $normalize->invoke($component, $input));
        }
        self::assertSame(4082400000, $cents->invoke($component, $normalize->invoke($component, '40.824.000')));
        self::assertSame('40.824.000', $format->invoke($component, '40824000.00'));
        self::assertSame('1.500,5', $format->invoke($component, '1500.50'));
    }

    public function test_amount_is_formatted_after_field_update(): void
    {
        $component = new DebtWorkspace;
        $component->paymentAmount = '40824000';
        $component->updatedPaymentAmount();
        self::assertSame('40.824.000', $component->paymentAmount);
        self::assertFalse($component->paymentPreviewed);
    }

    public function test_payment_field_sanitizes_input_and_formats_thousands_in_browser(): void
    {
        $view = file_get_contents(dirname(__DIR__, 4).'/Modules/Finance/resources/views/livewire/debt-workspace.blade.php');
        self::assertStringContainsString('inputmode="numeric" wire:model.blur="paymentAmount"', $view);
        self::assertStringContainsString("replace(/[^0-9]/g, '')", $view);
        self::assertStringContainsString('slice(0, 15)', $view);
        self::assertStringContainsString('replace(/\\B(?=(\\d{3})+(?!\\d))/g', $view);
    }

    public function test_invalid_grouping_is_rejected(): void
    {
        $component = new DebtWorkspace;
        $normalize = new \ReflectionMethod($component, 'normalizePaymentInput');
        $this->expectException(ValidationException::class);
        $normalize->invoke($component, '40.82.400');
    }
}
