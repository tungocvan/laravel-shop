<?php

namespace Modules\Pharma\Data;

use DateTimeInterface;

final readonly class DrugAwardProjectionData
{
    public function __construct(
        public string $sourceSystem,
        public string $sourceRecordType,
        public string $sourceRecordKey,
        public string $medicineName,
        public ?string $notifyNo = null,
        public ?string $lotNo = null,
        public ?string $lotName = null,
        public ?string $medicineCode = null,
        public ?string $activeIngredient = null,
        public ?string $concentration = null,
        public ?string $route = null,
        public ?string $dosageForm = null,
        public ?string $unit = null,
        public ?string $drugGroup = null,
        public ?string $packagingSpec = null,
        public ?int $shelfLifeMonths = null,
        public ?string $registrationOrImportLicense = null,
        public ?string $manufacturer = null,
        public ?string $country = null,
        public string|int|float|null $quantity = null,
        public string|int|float|null $pricePlan = null,
        public string|int|float|null $winningPrice = null,
        public string|int|float|null $amount = null,
        public ?string $contractorCode = null,
        public ?string $contractorName = null,
        public ?string $investorCode = null,
        public ?string $investorName = null,
        public ?string $decisionNo = null,
        public DateTimeInterface|string|null $decisionDate = null,
        public DateTimeInterface|string|null $publishedAt = null,
        public ?string $contractNo = null,
        public ?int $contractPeriod = null,
        public ?string $contractPeriodUnit = null,
        public ?string $contractPeriodText = null,
        public ?string $effectFramePeriod = null,
        public ?string $sourceReference = null,
        public ?string $sourceChannel = null,
        public ?string $syncSource = null,
        public ?string $payloadHash = null,
        public DateTimeInterface|string|null $observedAt = null,
        public DateTimeInterface|string|null $lastVerifiedAt = null,
        public bool $isActive = true,
    ) {}
}
