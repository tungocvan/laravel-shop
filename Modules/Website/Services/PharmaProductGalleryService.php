<?php

namespace Modules\Website\Services;

use Modules\Product\Models\Product;

final class PharmaProductGalleryService
{
    /**
     * Product's wp_products.image/gallery are the single source of truth.
     * Keep original ordering, omit duplicates, and do not fabricate placeholders.
     *
     * @return array<int, string>
     */
    public function images(Product $product): array
    {
        $paths = [];
        if (is_string($product->image) && trim($product->image) !== '') {
            $paths[] = $product->image;
        }

        foreach ((array) ($product->gallery ?? []) as $path) {
            if (is_string($path) && trim($path) !== '') {
                $paths[] = $path;
            }
        }

        $seen = [];
        $images = [];
        foreach ($paths as $path) {
            $path = trim($path);
            if (isset($seen[$path])) {
                continue;
            }
            if (preg_match('~^https?://~i', $path)) {
                $url = $path;
            } elseif (! str_starts_with($path, '/') && ! str_contains($path, '..') && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                $url = asset('storage/'.$path);
            } else {
                continue;
            }
            $seen[$path] = true;
            $images[] = $url;
        }

        return $images;
    }
}
