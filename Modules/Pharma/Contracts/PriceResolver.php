<?php

namespace Modules\Pharma\Contracts;

use DateTimeInterface;
use Modules\Pharma\DTOs\ResolvedPrice;

interface PriceResolver
{
    public function resolve(
        int $medicineVariantId,
        ?int $medicinePackageId = null,
        ?int $partnerId = null,
        DateTimeInterface|string|null $date = null,
    ): ?ResolvedPrice;
}
