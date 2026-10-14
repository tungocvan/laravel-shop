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
            ->select(['id', 'title', 'slug', 'image', 'short_description'])
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'products' => $items->getCollection()->map(fn ($product) => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'image' => $product->image,
                ])->values(),
                'next_page' => $items->hasMorePages() ? $items->currentPage() + 1 : null,
            ]);
        }

        return view('Website::pages.pharma-quick-order', [
            'items' => $items,
            'search' => $search,
        ]);
    }
}
