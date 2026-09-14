<?php

namespace Modules\Pharma\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Modules\Pharma\Contracts\PriceResolver;
use Modules\Pharma\DTOs\ResolvedPrice;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Models\PriceListItem;

class DatabasePriceResolver implements PriceResolver
{
    public function resolve(
        int $medicineVariantId,
        ?int $medicinePackageId = null,
        ?int $partnerId = null,
        DateTimeInterface|string|null $date = null,
    ): ?ResolvedPrice {
        $resolvedDate = $date instanceof DateTimeInterface
            ? CarbonImmutable::instance($date)
            : CarbonImmutable::parse($date ?? 'now');

        if ($partnerId !== null) {
            $customer = $this->findItem(
                PriceList::TYPE_CUSTOMER,
                $medicineVariantId,
                $medicinePackageId,
                $partnerId,
                $resolvedDate,
            );

            if ($customer !== null) {
                return $this->toDto($customer, $resolvedDate);
            }
        }

        $global = $this->findItem(
            PriceList::TYPE_GLOBAL,
            $medicineVariantId,
            $medicinePackageId,
            null,
            $resolvedDate,
        );

        return $global ? $this->toDto($global, $resolvedDate) : null;
    }

    private function findItem(
        string $type,
        int $variantId,
        ?int $packageId,
        ?int $partnerId,
        CarbonImmutable $date,
    ): ?PriceListItem {
        return PriceListItem::query()
            ->with('priceList')
            ->where('medicine_variant_id', $variantId)
            ->where('identity_key', PriceListItem::makeIdentityKey($variantId, $packageId))
            ->where('status', 'active')
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date->toDateString());
            })
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date->toDateString());
            })
            ->whereHas('priceList', function (Builder $query) use ($type, $partnerId, $date): void {
                $query->activeAt($date->toDateString())->where('type', $type);

                if ($type === PriceList::TYPE_CUSTOMER) {
                    $query->where('partner_id', $partnerId);
                } else {
                    $query->whereNull('partner_id');
                }
            })
            ->join('pharma_price_lists', 'pharma_price_lists.id', '=', 'pharma_price_list_items.price_list_id')
            ->orderByDesc('pharma_price_lists.priority')
            ->orderByDesc('pharma_price_lists.effective_from')
            ->orderByDesc('pharma_price_lists.id')
            ->select('pharma_price_list_items.*')
            ->first();
    }

    private function toDto(PriceListItem $item, CarbonImmutable $resolvedAt): ResolvedPrice
    {
        $list = $item->priceList;

        return new ResolvedPrice(
            priceListId: $list->id,
            priceListItemId: $item->id,
            sourceType: $list->type,
            partnerId: $list->partner_id,
            medicineId: $item->medicine_id,
            medicineVariantId: $item->medicine_variant_id,
            medicinePackageId: $item->medicine_package_id,
            declaredPrice: $item->declared_price_snapshot,
            companySalePrice: $item->company_sale_price,
            actualReceivablePrice: $item->actual_receivable_price,
            invoicePrice: $item->invoice_price,
            currency: $list->currency,
            effectiveFrom: $item->effective_from?->toDateString() ?? $list->effective_from?->toDateString(),
            effectiveTo: $item->effective_to?->toDateString() ?? $list->effective_to?->toDateString(),
            resolvedAt: $resolvedAt,
        );
    }
}
