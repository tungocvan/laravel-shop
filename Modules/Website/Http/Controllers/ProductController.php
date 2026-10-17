<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Pharma\Models\SupplierTracking;
use Modules\Pharma\Models\Medicine;
use Modules\System\Services\SettingsService;

class ProductController extends Controller
{
    public function index(Request $request, SettingsService $settings)
    {
        if ($settings->get('website.public_template', 'default') !== 'pharma') {
            return view('Website::pages.shop');
        }

        $catalogCardDesign = app(\Modules\Website\Services\PharmaProductCardStyle::class)->normalize((array) $settings->get('website.pharma_catalog.card_design', []));
        $filterVisibility = array_replace(['category' => true, 'supplier' => true, 'product_type' => true, 'circular_group' => true], (array) $settings->get('website.pharma_catalog.filters', []));
        $authenticated = auth()->check();
        $search = trim((string) $request->query('search', ''));
        $category = (string) $request->query('category', '');
        $supplier = trim((string) $request->query('supplier', ''));
        $sort = (string) $request->query('sort', 'latest');
        $productType = (string) $request->query('product_type', '');
        $circularGroup = (string) $request->query('circular_group', '');

        $categories = Category::query()->ofType('medicine')->active()->sorted()->get();
        // Use the canonical Product -> PharmaProductLink -> Medicine identity.
        // Medicine categories are not guaranteed to be populated even when the
        // published product has a category_product medicine classification.
        $publishedMedicineIds = Product::query()->active()
            ->whereHas('pharmaLink')
            ->whereHas('categories', fn ($cat) => $cat->where('type', 'medicine'))
            ->select('wp_products.id');

        $linkedMedicineIds = \Modules\Pharma\Models\PharmaProductLink::query()
            ->whereIn('product_id', $publishedMedicineIds)
            ->select('medicine_id');

        $suppliers = SupplierTracking::query()
            ->whereIn('medicine_id', $linkedMedicineIds)
            ->whereNotNull('supplier_name')->where('supplier_name', '!=', '')
            ->distinct()->orderBy('supplier_name')->pluck('supplier_name');

        $productTypes = Medicine::productTypeOptions();
        $circularGroups = Medicine::query()
            ->whereIn('id', $linkedMedicineIds)
            ->whereNotNull('circular_group')->where('circular_group', '!=', '')
            ->distinct()->orderBy('circular_group')->pluck('circular_group');

        $query = Product::query()->active()->whereHas('pharmaLink')
            ->whereHas('categories', fn ($cat) => $cat->where('type', 'medicine'))
            ->with(['categories' => fn ($cat) => $cat->where('type', 'medicine')]);
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')
                ->orWhere('short_description', 'like', '%'.$search.'%'));
        }
        if (! empty($filterVisibility['category']) && $category !== '') {
            $query->whereHas('categories', fn ($cat) => $cat->where('type', 'medicine')->where('slug', $category));
        }
        if (! empty($filterVisibility['supplier']) && $supplier !== '') {
            $query->whereHas('pharmaLink.medicine.supplierTrackings', fn ($tracking) => $tracking->where('supplier_name', $supplier));
        }
        if ($productType !== '' && array_key_exists($productType, $productTypes)) {
            $query->whereHas('pharmaLink.medicine', fn ($medicine) => $medicine->where('product_type', $productType));
        }
        if (! empty($filterVisibility['circular_group']) && $circularGroup !== '') {
            $query->whereHas('pharmaLink.medicine', fn ($medicine) => $medicine->where('circular_group', $circularGroup));
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
            'filterVisibility' => $filterVisibility,
            'catalogCardDesign' => $catalogCardDesign,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'authenticated' => $authenticated,
            'search' => $search,
            'category' => $category,
            'supplier' => $supplier,
            'sort' => $sort,
            'productType' => $productType,
            'productTypes' => $productTypes,
            'circularGroup' => $circularGroup,
            'circularGroups' => $circularGroups,
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
            $query->select(['id', 'title', 'slug', 'image', 'gallery', 'short_description', 'description', 'created_at']);
        }
        $product = $query->firstOrFail();
        $galleryImages = app(\Modules\Website\Services\PharmaProductGalleryService::class)->images($product);

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
            'galleryImages' => $galleryImages,
            'related' => $relatedQuery->limit(4)->get(),
            'authenticated' => $authenticated,
        ]);
    }
}
