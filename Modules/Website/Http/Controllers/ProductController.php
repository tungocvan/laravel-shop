<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Pharma\Models\SupplierTracking;
use Modules\System\Services\SettingsService;

class ProductController extends Controller
{
    public function index(Request $request, SettingsService $settings)
    {
        if ($settings->get('website.public_template', 'default') !== 'pharma') {
            return view('Website::pages.shop');
        }

        $authenticated = auth()->check();
        $search = trim((string) $request->query('search', ''));
        $category = (string) $request->query('category', '');
        $supplier = trim((string) $request->query('supplier', ''));
        $sort = (string) $request->query('sort', 'latest');
        $range = $authenticated ? (string) $request->query('price_range', '') : '';

        $categories = Category::query()->ofType('medicine')->active()->sorted()->get();
        $suppliers = SupplierTracking::query()
            ->whereNotNull('supplier_name')->where('supplier_name', '!=', '')
            ->whereHas('medicine', fn ($medicine) => $medicine->whereHas('categories', fn ($cat) => $cat->where('type', 'medicine')))
            ->distinct()->orderBy('supplier_name')->pluck('supplier_name');

        $query = Product::query()->active()->whereHas('pharmaLink')
            ->whereHas('categories', fn ($cat) => $cat->where('type', 'medicine'))
            ->with(['categories' => fn ($cat) => $cat->where('type', 'medicine')]);
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')
                ->orWhere('short_description', 'like', '%'.$search.'%'));
        }
        if ($category !== '') {
            $query->whereHas('categories', fn ($cat) => $cat->where('type', 'medicine')->where('slug', $category));
        }
        if ($supplier !== '') {
            $query->whereHas('pharmaLink.medicine.supplierTrackings', fn ($tracking) => $tracking->where('supplier_name', $supplier));
        }
        if ($authenticated && in_array($range, ['0-100000', '100000-300000', '300000-500000', '500000-99999999'], true)) {
            [$min, $max] = array_map('intval', explode('-', $range));
            $query->whereRaw('COALESCE(NULLIF(sale_price, 0), regular_price) BETWEEN ? AND ?', [$min, $max]);
        }
        if ($authenticated && $sort === 'price_asc') {
            $query->orderByRaw('COALESCE(NULLIF(sale_price, 0), regular_price) ASC');
        } elseif ($authenticated && $sort === 'price_desc') {
            $query->orderByRaw('COALESCE(NULLIF(sale_price, 0), regular_price) DESC');
        } elseif ($sort === 'name_asc') {
            $query->orderBy('title');
        } else {
            $query->latest('id');
        }
        if (! $authenticated) {
            $query->select(['id', 'title', 'slug', 'image', 'short_description', 'created_at']);
        }
        return view('Website::pages.shop-pharma', [
            'products' => $query->paginate(12)->withQueryString(),
            'categories' => $categories,
            'suppliers' => $suppliers,
            'authenticated' => $authenticated,
            'search' => $search,
            'category' => $category,
            'supplier' => $supplier,
            'sort' => $sort,
            'range' => $range,
        ]);
    }

    public function show($slug, SettingsService $settings)
    {
        if ($settings->get('website.public_template', 'default') !== 'pharma') {
            return view('Website::products.show', compact('slug'));
        }

        $authenticated = auth()->check();
        $query = Product::query()->active()
            ->where('slug', $slug)
            ->whereHas('pharmaLink')
            ->whereHas('categories', fn ($cat) => $cat->where('type', 'medicine'))
            ->with(['categories' => fn ($cat) => $cat->where('type', 'medicine'),
                'pharmaLink.medicine']);
        if (! $authenticated) {
            $query->select(['id', 'title', 'slug', 'image', 'short_description', 'description', 'created_at']);
        }
        $product = $query->firstOrFail();

        $relatedQuery = Product::query()->active()->whereKeyNot($product->id)
            ->whereHas('pharmaLink')
            ->whereHas('categories', fn ($cat) => $cat->where('type', 'medicine')
                ->whereIn('categories.id', $product->categories->pluck('id')))
            ->with(['categories' => fn ($cat) => $cat->where('type', 'medicine')]);
        if (! $authenticated) {
            $relatedQuery->select(['id', 'title', 'slug', 'image', 'created_at']);
        }
        return view('Website::products.show-pharma', [
            'product' => $product,
            'related' => $relatedQuery->limit(4)->get(),
            'authenticated' => $authenticated,
        ]);
    }
}
