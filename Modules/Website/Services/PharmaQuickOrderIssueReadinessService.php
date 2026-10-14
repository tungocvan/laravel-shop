<?php

namespace Modules\Website\Services;

use Modules\Pharma\Models\PharmaProductLink;
use Modules\Website\Models\PharmaQuickOrderRequest;

/**
 * Read-only preflight before creating an InventoryIssue.
 * An accepted Website request is not itself an approved inventory issue.
 */
final class PharmaQuickOrderIssueReadinessService
{
    public function inspect(PharmaQuickOrderRequest $request): array
    {
        $items = $request->requested_items ?? [];
        $ids = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique();
        $links = PharmaProductLink::query()->whereIn('product_id', $ids)->get()->keyBy('product_id');
        $problems = [];
        $resolved = [];

        if ($request->status !== 'accepted') {
            $problems[] = 'Yêu cầu chưa được chấp nhận.';
        }
        if (! count($items)) {
            $problems[] = 'Yêu cầu không có sản phẩm.';
        }

        foreach ($items as $index => $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $link = $links->get($productId);

            if ($productId <= 0 || $quantity <= 0) {
                $problems[] = 'Dòng '.($index + 1).' có sản phẩm hoặc số lượng không hợp lệ.';
                continue;
            }
            if (! $link || ! $link->medicine_id) {
                $problems[] = 'Sản phẩm Website #'.$productId.' chưa liên kết thuốc Pharma.';
                continue;
            }
            $resolved[] = [
                'product_id' => $productId,
                'medicine_id' => (int) $link->medicine_id,
                'medicine_variant_id' => $link->medicine_variant_id,
                'medicine_package_id' => $link->medicine_package_id,
                'quantity' => $quantity,
            ];
        }

        // Readiness only validates identity mapping. Pricing, customer identity,
        // stock, permissions and warehouse must be validated by the issue authoring flow.
        return ['ready_for_authoring' => $problems === [], 'lines' => $resolved, 'problems' => $problems];
    }
}
