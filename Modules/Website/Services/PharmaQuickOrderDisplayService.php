<?php

namespace Modules\Website\Services;

use Modules\Product\Models\Product;
use Modules\Website\Models\PharmaQuickOrderRequest;

final class PharmaQuickOrderDisplayService
{
    public function lines(PharmaQuickOrderRequest $request): array
    {
        $raw = $request->requested_items ?? [];
        $ids = collect($raw)->pluck('product_id')->filter()->unique()->values();
        $products = Product::query()->whereIn('id', $ids)
            ->with('pharmaLink')->get(['id', 'title', 'image', 'slug'])->keyBy('id');

        return collect($raw)->map(function (array $line) use ($products): array {
            $id = (int) ($line['product_id'] ?? 0);
            $product = $products->get($id);

            return [
                'product_id' => $id,
                'title' => $product?->title ?? "Sản phẩm #{$id} không còn tồn tại",
                'image' => $product?->image,
                'slug' => $product?->slug,
                'quantity' => (int) ($line['quantity'] ?? 0),
                'missing' => ! $product,
                'has_pharma_link' => (bool) $product?->pharmaLink,
            ];
        })->all();
    }
}
