<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Models\Product;
use Modules\Website\Services\WebsiteTemplateService;

class ProductController extends Controller
{
    public function index()
    {
        return view('Website::pages.shop');
    }

    public function show(string $slug, WebsiteTemplateService $templates)
    {
        if ($templates->isPharma()) {
            $product = Product::query()
                ->with('categories')
                ->where('slug', $slug)
                ->where('is_active', true)
                ->whereHas('pharmaLink')
                ->firstOrFail();

            return view('Website::products.show', compact('slug', 'product'));
        }

        return view('Website::products.show', compact('slug'));
    }
}
