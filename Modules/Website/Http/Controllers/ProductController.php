<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    public function index()
    {
        //return view('Website::products.index');
        return view('Website::pages.shop');
    }

    public function show($slug)
    {
        return view('Website::products.show', compact('slug'));
    }
    public function detail($slug)
    {
        // Truy vấn sản phẩm kèm theo categories và gallery
        $product = WpProduct::with(['categories'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Lấy sản phẩm liên quan (cùng danh mục)
        $relatedProducts = WpProduct::whereHas('categories', function($q) use ($product) {
            $q->whereIn('categories.id', $product->categories->pluck('id'));
        })
        ->where('id', '!=', $product->id)
        ->limit(4)
        ->get();

        return view('Website::products.detail', compact('product', 'relatedProducts'));
    }
}
