<?php

namespace Modules\Order\Services;

use App\Models\User;
use Modules\Order\Models\AffiliateScheme;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;

class AffiliateService
{
    public function calculateCommission(float $orderSubtotal): float
    {
        return $orderSubtotal * 0.10;
    }

    public function getStats($userId): array
    {
        $query = Order::query()->where('affiliate_id', $userId);

        return [
            'total_earnings' => $query->clone()
                ->where('commission_status', 'approved')
                ->sum('commission_amount'),
            'pending_earnings' => $query->clone()
                ->where('commission_status', 'pending')
                ->sum('commission_amount'),
            'total_orders' => $query->count(),
        ];
    }

    public function getCommissionHistory($userId, $status = 'all', $limit = 10)
    {
        return Order::query()
            ->where('affiliate_id', $userId)
            ->with('items')
            ->when($status !== 'all', fn ($query) => $query->where('commission_status', $status))
            ->select('id', 'order_code', 'customer_name', 'total', 'commission_amount', 'commission_status', 'rejection_reason', 'created_at')
            ->latest()
            ->paginate($limit);
    }

    public function getAffiliateOrderDetail($orderId, $affiliateId)
    {
        return Order::query()
            ->whereKey($orderId)
            ->where('affiliate_id', $affiliateId)
            ->with('items')
            ->firstOrFail();
    }

    public function calculateItemsCommission(array $cartItems): array
    {
        $defaultRate = 10;
        $processedItems = [];
        $totalOrderCommission = 0;

        foreach ($cartItems as $item) {
            $product = Product::find($item['product_id']);
            $rate = ($product && $product->affiliate_commission_rate !== null)
                ? (float) $product->affiliate_commission_rate
                : (float) $defaultRate;
            $itemTotal = (float) $item['price'] * (int) $item['quantity'];
            $commissionAmount = ($itemTotal * $rate) / 100;

            $processedItems[] = [
                'product_id' => $item['product_id'],
                'commission_rate' => $rate,
                'commission_amount' => $commissionAmount,
            ];

            $totalOrderCommission += $commissionAmount;
        }

        return [
            'items' => $processedItems,
            'total_commission' => $totalOrderCommission,
        ];
    }

    public function calculateHybridCommission(int $productId, int $affiliateId, float $price, int $qty): array
    {
        $affiliate = User::with('level')->find($affiliateId);
        $levelId = $affiliate?->affiliate_level_id;

        $scheme = AffiliateScheme::query()
            ->where('product_id', $productId)
            ->where(function ($query) use ($affiliateId, $levelId): void {
                $query->where('user_id', $affiliateId)
                    ->orWhere('level_id', $levelId);
            })
            ->where('is_active', true)
            ->orderByRaw('user_id DESC')
            ->first();

        $type = $scheme ? $scheme->commission_type : 'percentage';
        $percent = 0;
        $fixed = 0;

        if ($scheme) {
            $percent = (float) $scheme->percent_value;
            $fixed = (float) $scheme->fixed_value;
        } else {
            $product = Product::find($productId);
            $percent = $product->affiliate_commission_rate ?? 10;
        }

        $commissionFromPercent = ($price * $qty) * ($percent / 100);
        $commissionFromFixed = $fixed * $qty;

        return [
            'type' => $type,
            'rate' => $percent,
            'fixed_unit_amount' => $fixed,
            'total_amount' => $commissionFromPercent + $commissionFromFixed,
            'level_name' => $affiliate?->level?->name ?? 'Vãng lai',
        ];
    }
}
