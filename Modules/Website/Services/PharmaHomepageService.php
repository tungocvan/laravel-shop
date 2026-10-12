<?php

namespace Modules\Website\Services;

use Illuminate\Support\Collection;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;

class PharmaHomepageService
{
    private const SUPPORTED = ['hero', 'categories', 'featured', 'new_arrivals'];

    public function __construct(private readonly HomepageContentService $homepage) {}

    public function sections(): array
    {
        $visibility = $this->homepage->visibility();
        $ordered = array_values(array_unique(array_map(
            fn (string $key): string => preg_replace('/_copy_\d+$/', '', $key),
            $this->homepage->order()
        )));

        // Only existing, approved Homepage Builder sections are rendered in Pharma.
        return collect($ordered)->filter(fn (string $key): bool =>
            in_array($key, self::SUPPORTED, true)
            && ! in_array($visibility['show_'.$key] ?? 'all', ['hidden', 'none'], true)
        )->map(fn (string $key): array => [
            'key' => $key,
            'visibility' => match ($visibility['show_'.$key] ?? 'all') {
                'desktop' => 'hidden md:block',
                'mobile' => 'block md:hidden',
                default => 'block',
            },
        ])->values()->all();
    }

    public function categories(): Collection
    {
        return Category::query()
            ->where('type', 'medicine')
            ->where('is_active', true)
            ->whereHas('products', fn ($query) => $query->where('is_active', true)->whereHas('pharmaLink'))
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'slug']);
    }

    public function products(string $section): Collection
    {
        $query = Product::query()
            ->where('is_active', true)
            ->whereHas('pharmaLink')
            ->with('categories');

        if ($section === 'featured') {
            $ids = $this->homepage->referenceIds('featured', 'product', 'home_featured_ids');
            if ($ids === []) {
                // No explicit featured selection: do not invent a featured badge.
                return collect();
            }
            return $query->whereIn('id', $ids)
                ->orderByRaw('CASE id '.collect($ids)->values()->map(fn ($id, $i) => 'WHEN '.(int) $id.' THEN '.$i)->implode(' ').' ELSE '.count($ids).' END')
                ->limit(8)->get();
        }

        return $query->latest('created_at')->limit(8)->get();
    }
}
