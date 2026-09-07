<?php

namespace Modules\Partner\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Partner\Models\Partner;

class PartnerQueryService
{
    public function query(array $filters = []): Builder
    {
        $query = Partner::query();

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('tax_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        foreach (['legal_type', 'source', 'status', 'province_code'] as $field) {
            if ($value = $filters[$field] ?? null) {
                $query->where($field, $value);
            }
        }

        if ($partnerType = $filters['partner_type'] ?? null) {
            $query->whereJsonContains('partner_types', $partnerType);
        }

        return $query;
    }
}
