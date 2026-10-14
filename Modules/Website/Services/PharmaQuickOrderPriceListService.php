<?php

namespace Modules\Website\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Pharma\Models\PriceList;

/**
 * Server-side price-list boundary for the Pharma Website quick-order feature.
 *
 * This service does not authorize the actor or establish the actor/customer
 * relationship. Callers MUST verify those separately before querying prices.
 */
final class PharmaQuickOrderPriceListService
{
    /**
     * @param list<int> $adminAllowedPriceListIds
     */
    public function eligibleForCustomer(
        int $customerPartnerId,
        int $actorUserId,
        array $adminAllowedPriceListIds,
        string $date
    ): Builder {
        // Fail closed when Admin has not explicitly selected any source.
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $adminAllowedPriceListIds),
            static fn (int $id): bool => $id > 0
        )));

        return PriceList::query()
            ->whereIn('id', $ids)
            ->activeAt($date)
            ->where(function (Builder $query) use ($customerPartnerId, $actorUserId): void {
                $query->where(function (Builder $global) use ($actorUserId): void {
                    $global->where('type', PriceList::TYPE_GLOBAL)
                        ->where(function (Builder $scope) use ($actorUserId): void {
                            $scope->whereDoesntHave('globalUsers')
                                ->orWhereHas('globalUsers', fn (Builder $users) => $users->whereKey($actorUserId));
                        });
                })->orWhere(function (Builder $customer) use ($customerPartnerId): void {
                    $customer->where('type', PriceList::TYPE_CUSTOMER)
                        ->where('customer_source', PriceList::CUSTOMER_SOURCE_PARTNER)
                        ->where('partner_id', $customerPartnerId);
                });
            });
    }
}
