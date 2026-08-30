<?php

namespace Modules\Pharma\Data;

use DateTimeInterface;

final readonly class DrugBidAwardSourceData
{
    public function __construct(
        public string $sourceType,
        public string $sourceId,
        public string $medicineName,
        public string $packagingSpecification,
        public int $quantity,
        public string $unitPrice,
        public string $biddingNoticeCode,
        public string $investorName,
        public string $decisionNumber,
        public DateTimeInterface|string $decisionDate,
        public int $contractDurationMonths,
        public string $winningCompanyName,
        public ?string $decisionDocumentUrl = null,
        public ?int $medicineId = null,
        public ?DateTimeInterface $sourceSyncedAt = null,
        public ?string $sourcePayloadHash = null,
    ) {}

    public function toAwardAttributes(): array
    {
        return [
            'medicine_id' => $this->medicineId,
            'medicine_name' => $this->medicineName,
            'packaging_specification' => $this->packagingSpecification,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'bidding_notice_code' => $this->biddingNoticeCode,
            'investor_name' => $this->investorName,
            'decision_number' => $this->decisionNumber,
            'decision_date' => $this->decisionDate,
            'contract_duration_months' => $this->contractDurationMonths,
            'winning_company_name' => $this->winningCompanyName,
            'decision_document_url' => $this->decisionDocumentUrl,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'source_synced_at' => $this->sourceSyncedAt,
            'source_payload_hash' => $this->sourcePayloadHash,
        ];
    }
}
