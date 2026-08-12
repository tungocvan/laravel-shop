<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Services\ProductService;

class ProductController extends Controller
{
    public function index()
    {
        // return view('Website::products.index');
        return view('Website::pages.shop');
    }

    public function show($slug)
    {
        return view('Website::products.show', compact('slug'));
    }

    public function detail(string $slug, ProductService $productService)
    {
        $product = $productService->findActiveBySlugWithCategories($slug);
        $relatedProducts = $productService->relatedActive($product);

        return view('Website::products.detail', compact('product', 'relatedProducts'));
    }
}
