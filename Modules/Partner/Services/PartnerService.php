<?php

namespace Modules\Partner\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Partner\Models\Partner;

class PartnerService
{
    public function paginate(array $filters = [], int|string $perPage = 10)
    {
        $query = Partner::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('tax_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%");
                });
            })
            ->when($filters['legal_type'] ?? null, function (Builder $query, string $legalType): void {
                $query->where('legal_type', $legalType);
            })
            ->when($filters['partner_type'] ?? null, function (Builder $query, string $partnerType): void {
                $query->whereJsonContains('partner_types', $partnerType);
            })
            ->when($filters['source'] ?? null, function (Builder $query, string $source): void {
                $query->where('source', $source);
            })
            ->when($filters['status'] ?? null, function (Builder $query, string $status): void {
                $query->where('status', $status);
            })
            ->latest('id');

        if ($perPage === 'All') {
            return $query->get();
        }

        return $query->paginate((int) $perPage);
    }

    public function create(array $data): Partner
    {
        return Partner::create($this->normalizeData($data));
    }

    public function update(Partner $partner, array $data): Partner
    {
        $partner->update($this->normalizeData($data));

        return $partner->refresh();
    }

    public function delete(Partner $partner): bool
    {
        return (bool) $partner->delete();
    }

    public function find(int $id): ?Partner
    {
        return Partner::query()->find($id);
    }

    public function findOrFail(int $id): Partner
    {
        return Partner::query()->findOrFail($id);
    }

    public function options(): array
    {
        return [
            'legal_types' => Partner::LEGAL_TYPES,
            'partner_types' => Partner::PARTNER_TYPES,
            'sources' => Partner::SOURCES,
            'statuses' => Partner::STATUSES,
        ];
    }

    private function normalizeData(array $data): array
    {
        return [
            'tax_code' => $this->nullableString($data['tax_code'] ?? null),
            'name' => trim((string) ($data['name'] ?? '')),
            'legal_type' => $data['legal_type'] ?? 'company',
            'partner_types' => array_values($data['partner_types'] ?? []),
            'phone' => $this->nullableString($data['phone'] ?? null),
            'email' => $this->nullableString($data['email'] ?? null),
            'contact_person' => $this->nullableString($data['contact_person'] ?? null),
            'address' => $this->nullableString($data['address'] ?? null),
            'source' => $data['source'] ?? 'manual',
            'status' => $data['status'] ?? 'active',
            'note' => $this->nullableString($data['note'] ?? null),
        ];
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
