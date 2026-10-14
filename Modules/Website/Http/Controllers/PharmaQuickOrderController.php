<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\Pharma\Models\PriceListItem;
use Modules\Product\Models\Product;
use Modules\System\Services\SettingsService;
use Modules\Website\Services\PharmaQuickOrderPriceListService;
use Modules\Website\Services\PharmaQuickOrderSettingsService;

final class PharmaQuickOrderController extends Controller
{
    public function index(
        Request $request,
        SettingsService $websiteSettings,
        PharmaQuickOrderSettingsService $quickOrderSettings,
        PharmaQuickOrderPriceListService $priceLists
    ): View|JsonResponse {
        abort_unless($websiteSettings->get('website.public_template', 'default') === 'pharma', 404);

        $configuration = $quickOrderSettings->get();
        abort_unless($configuration['enabled'], 404);

        $user = $request->user();
        abort_unless($user && $user->is_active, 403);

        // New Website customers may browse eligible globally scoped medicines.
        // Customer-specific price lists require a verified partner relationship.
        // Do not disclose negotiated prices or accept orders at this checkpoint.
        $globalListIds = $priceLists->eligibleForCustomer(
            0,
            (int) $user->id,
            $configuration['price_list_ids'],
            now()->toDateString()
        )->where('type', \Modules\Pharma\Models\PriceList::TYPE_GLOBAL)->pluck('id');

        $search = mb_substr(trim((string) $request->query('search', '')), 0, 120);
        $items = Product::query()->active()
            ->whereHas('pharmaLink', function ($link) use ($globalListIds): void {
                $link->whereIn('medicine_id', PriceListItem::query()
                    ->whereIn('price_list_id', $globalListIds)
                    ->where(function ($item): void {
                        $item->whereNull('effective_from')->orWhereDate('effective_from', '<=', now()->toDateString());
                    })
                    ->where(function ($item): void {
                        $item->whereNull('effective_to')->orWhereDate('effective_to', '>=', now()->toDateString());
                    })
                    ->select('medicine_id'));
            })
            ->whereHas('categories', fn ($category) => $category->where('type', 'medicine'))
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->with('pharmaLink')
            ->select(['id', 'title', 'slug', 'image', 'short_description'])
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();


        // Price must match the exact Pharma variant and package linked to the Website product.
        $links = $items->getCollection()->pluck('pharmaLink')->filter();
        $prices = PriceListItem::query()
            ->whereIn('price_list_id', $globalListIds)
            ->whereIn('medicine_variant_id', $links->pluck('medicine_variant_id')->filter()->unique())
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', now()->toDateString()))
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', now()->toDateString()))
            ->get(['medicine_variant_id', 'medicine_package_id', 'company_sale_price']);

        $productPayload = $items->getCollection()->map(function ($product) use ($prices): array {
            $link = $product->pharmaLink;
            $matches = $prices->filter(fn ($item) => $link
                && (int) $item->medicine_variant_id === (int) $link->medicine_variant_id
                && (int) ($item->medicine_package_id ?? 0) === (int) ($link->medicine_package_id ?? 0)
            )->pluck('company_sale_price')->filter(fn ($value) => $value !== null)->unique()->values();

            return [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'image' => $product->image,
                'company_sale_price' => $matches->count() === 1 ? (float) $matches->first() : null,
            ];
        });

        if ($request->expectsJson()) {
            return response()->json([
                'products' => $productPayload->values(),
                'next_page' => $items->hasMorePages() ? $items->currentPage() + 1 : null,
            ]);
        }

        return view('Website::pages.pharma-quick-order', [
            'items' => $items,
            'search' => $search,
            'quickOrderProducts' => $productPayload,
        ]);
    }
}
