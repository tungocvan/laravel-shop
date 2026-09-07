<?php

namespace Modules\Partner\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Partner\Models\Partner;

class PartnerService
{
    public function __construct(private readonly PartnerQueryService $queryService) {}

    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        return $this->queryService->query($filters)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function filtered(array $filters = []): Collection
    {
        return $this->queryService->query($filters)->latest('id')->get();
    }

    public function create(array $data): Partner
    {
        return Partner::create($this->normalizeData($data));
    }

    public function update(Partner $partner, array $data): Partner
    {
        $partner->update($this->normalizeData($data));

        return $partner->fresh();
    }

    public function delete(Partner $partner): bool
    {
        return (bool) $partner->delete();
    }

    public function find(int $id): ?Partner
    {
        return Partner::find($id);
    }

    public function findOrFail(int $id): Partner
    {
        return Partner::findOrFail($id);
    }

    public function legalTypeOptions(): array
    {
        return Partner::LEGAL_TYPES;
    }

    public function partnerTypeOptions(): array
    {
        return Partner::PARTNER_TYPES;
    }

    public function sourceOptions(): array
    {
        return Partner::SOURCES;
    }

    public function statusOptions(): array
    {
        return Partner::STATUSES;
    }

    private function normalizeData(array $data): array
    {
        return [
            'tax_code' => $data['tax_code'] ?: null,
            'name' => trim($data['name']),
            'legal_type' => $data['legal_type'],
            'partner_types' => array_values($data['partner_types'] ?? []),
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'contact_person' => $data['contact_person'] ?: null,
            'address' => $data['address'] ?: null,
            'province_code' => $data['province_code'] ?? null,
            'source' => $data['source'],
            'status' => $data['status'],
            'note' => $data['note'] ?: null,
        ];
    }
}
