<?php

namespace Modules\LoanManagement\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class LoanInterestCalculator
{
    /**
     * Simple interest for a period, with no compounding.
     * Annual rate is a percentage, e.g. 12.5 means 12.5%.
     * Monetary results are rounded to whole VND, half up.
     */
    public function calculate(
        string $outstandingPrincipal,
        string $annualRatePercent,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $dayBasis = 365
    ): string {
        if (! in_array($dayBasis, [360, 365, 366], true)) {
            throw new InvalidArgumentException('Unsupported day-count basis.');
        }

        if (! is_numeric($outstandingPrincipal) || ! is_numeric($annualRatePercent)
            || (float) $outstandingPrincipal < 0 || (float) $annualRatePercent < 0) {
            throw new InvalidArgumentException('Principal and annual rate must be non-negative numbers.');
        }

        $days = $from->diffInDays($to, false);
        if ($days < 0) {
            throw new InvalidArgumentException('Period end must not precede period start.');
        }

        // Require BCMath: avoid binary floating point errors for money.
        if (! function_exists('bcmul')) {
            throw new \RuntimeException('BCMath extension is required for loan interest calculation.');
        }

        $numerator = bcmul(bcmul($outstandingPrincipal, $annualRatePercent, 8), (string) $days, 8);
        $interest = bcdiv($numerator, bcmul('100', (string) $dayBasis, 8), 8);

        return bcadd($interest, '0.5', 0) ;
    }
}
