<?php

namespace Modules\Partner\Data;

class ExternalPartnerData
{
    public function __construct(
        public readonly string $source,
        public readonly string $externalId,
        public readonly ?string $name = null,
        public readonly ?string $taxCode = null,
        public readonly ?string $address = null,
        public readonly ?string $representative = null,
        public readonly ?string $activeSince = null,
        public readonly ?string $managedBy = null,
        public readonly ?string $organizationType = null,
        public readonly ?string $externalStatus = null,
        public readonly ?string $sourceUrl = null,
        public readonly ?string $checkedAt = null,
        public readonly ?string $matchType = null,
    ) {}

    public static function fromRegistry(
        array $candidate,
        array $detail,
        ?string $checkedAt = null,
        string $source = 'doanhnghiep_vn'
    ): self {
        return new self(
            source: $source,
            externalId: (string) $candidate['tax_code'],
            name: $candidate['name'] ?? null,
            taxCode: $candidate['tax_code'] ?? null,
            address: $detail['tax_address'] ?? $candidate['address'] ?? null,
            representative: $detail['representative'] ?? $candidate['representative'] ?? null,
            activeSince: $detail['active_since'] ?? null,
            managedBy: $detail['managed_by'] ?? null,
            organizationType: $detail['organization_type'] ?? null,
            externalStatus: $detail['status'] ?? $candidate['status'] ?? null,
            sourceUrl: $detail['source_url'] ?? $candidate['canonical_url'] ?? null,
            checkedAt: $checkedAt ?? now()->toIso8601String(),
            matchType: $candidate['match_type'] ?? null,
        );
    }

    public static function fromMasothue(array $candidate, array $detail, ?string $checkedAt = null): self
    {
        return self::fromRegistry($candidate, $detail, $checkedAt, 'masothue');
    }

    public function snapshot(): array
    {
        return [
            'tax_code' => $this->taxCode,
            'name' => $this->name,
            'tax_address' => $this->address,
            'representative' => $this->representative,
            'active_since' => $this->activeSince,
            'managed_by' => $this->managedBy,
            'organization_type' => $this->organizationType,
            'status' => $this->externalStatus,
        ];
    }
}
