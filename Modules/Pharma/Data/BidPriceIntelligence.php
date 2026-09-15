<?php

namespace Modules\Pharma\Data;

use Illuminate\Support\Collection;

final readonly class BidPriceIntelligence
{
    public function __construct(
        public ?string $latest,
        public ?string $min,
        public ?string $max,
        public ?string $average,
        public ?string $median,
        public int $count,
        public ?string $latestAwardDate,
        public Collection $recentAwards,
    ) {}
}
