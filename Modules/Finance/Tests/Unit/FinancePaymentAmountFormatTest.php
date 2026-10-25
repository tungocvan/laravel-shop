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

        foreach (['40.824.000' => '40824000', '540.000.000' => '540000000',
            '1000000' => '1000000', '1.500,50' => '1500.50'] as $input => $expected) {
            self::assertSame($expected, $normalize->invoke($component, $input));
        }
        self::assertSame(4082400000, $cents->invoke($component, $normalize->invoke($component, '40.824.000')));
        self::assertSame('40.824.000', $format->invoke($component, '40824000.00'));
        self::assertSame('1.500,5', $format->invoke($component, '1500.50'));
    }

    public function test_invalid_grouping_is_rejected(): void
    {
        $component = new DebtWorkspace;
        $normalize = new \ReflectionMethod($component, 'normalizePaymentInput');
        $this->expectException(ValidationException::class);
        $normalize->invoke($component, '40.82.400');
    }
}
