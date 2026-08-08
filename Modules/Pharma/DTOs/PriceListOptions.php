<?php

namespace Modules\Pharma\DTOs;

final readonly class PriceListOptions
{
    public function __construct(
        public array $productRows,
        public array $sourceColumns,
        public string $recipient,
        public string $signatureDate,
        public string $signatureTitle,
        public string $outputPath,
    ) {}
}
