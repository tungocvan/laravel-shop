<?php

namespace Modules\Website\Livewire\Home;

use Livewire\Component;
use Modules\Website\Models\WpProduct;
use Modules\Admin\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

class BestSellers extends Component
{
    /**
     * Placeholder: Hiển thị Skeleton (Khung xương) khi đang tải Lazy Load
     */
    public function placeholder()
    {
        return <<<'blade'
        <div class="py-12 container mx-auto px-4 mb-16">
            <div class="flex flex-col items-center mb-10 gap-2">
                <div class="h-8 bg-gray-200 rounded w-64 animate-pulse"></div>
                <div class="h-4 bg-gray-200 rounded w-40 animate-pulse"></div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach(range(1, 8) as $i)
                    <div class="bg-white rounded-xl border border-gray-100 p-3 h-[350px] animate-pulse">
                        <div class="bg-gray-200 rounded-lg aspect-square mb-4"></div>
                        <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                        <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                        <div class="h-2 bg-gray-200 rounded-full w-full mt-auto"></div>
                    </div>
                @endforeach
            </div>
        </div>
        blade;
    }

    public function render()
    {
        // 1. Lấy số lượng hiển thị từ Admin (Mặc định 8)
        $limit = Setting::where('key', 'home_best_sellers_count')->value('value');
        $limit = $limit ? (int)$limit : 8;

        // 2. Query sản phẩm bán chạy (sold_count DESC)
        $products = WpProduct::where('is_active', true)
            ->orderBy('sold_count', 'desc')
            ->take($limit)
            ->get(); // Không cần with('categories') nếu không hiển thị tên danh mục để tối ưu

        return view('Website::livewire.home.best-sellers', [
            'products' => $products
        ]);
    }
}
