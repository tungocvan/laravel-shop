<?php

namespace Modules\Website\Livewire\Home;

use Livewire\Component;
use Modules\Admin\Models\FlashSale as FlashSaleModel;
use Carbon\Carbon;

class FlashSale extends Component
{
    public $flashSale; // Chứa thông tin chương trình Sale (Title, EndTime...)
    public $products = []; // Danh sách sản phẩm Sale
    public $isActive = false;
    public $endTimeJs = 0; // Timestamp cho JS đếm ngược

    public function mount()
    {

        $now = now();
        // DEBUG 1: Kiểm tra giờ server hiện tại

        // 1. Tìm chương trình Flash Sale đang chạy
        $this->flashSale = FlashSaleModel::where('is_active', true)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->with(['items.product' => function ($q) {
                // Eager Load sản phẩm để lấy ảnh, tên
                $q->select('id', 'title', 'slug', 'image', 'regular_price', 'sale_price');
            }])
            ->first();

        if ($this->flashSale) {
            $this->isActive = true;

            // 2. Chuyển đổi EndTime sang Timestamp JS (milliseconds)

            //$this->endTimeJs = Carbon::parse($this->flashSale->end_time)->timestamp * 1000;
            $this->endTimeJs = $this->flashSale->end_time->timestamp * 1000;
            // dd(
            //     $this->flashSale->end_time->toDateTimeString(),
            //     $this->flashSale->end_time->timezoneName,
            //     now()->toDateTimeString(),
            //     now()->timezoneName
            // );

            //dd($this->endTimeJs);
            //Test: Ép thời gian kết thúc là ngày mai
            //$this->endTimeJs = now()->addDays(30)->timestamp * 1000;
            // 3. Format lại danh sách sản phẩm để View dễ dùng
            // Lấy tối đa 6-12 sản phẩm tùy layout
            $this->products = $this->flashSale->items->take(12)->map(function ($item) {
                $product = $item->product;
                if (!$product) return null;

                // Tính % giảm giá dựa trên giá gốc và giá Flash Sale
                $discountPercent = 0;
                if ($product->regular_price > 0) {
                    $discountPercent = round((($product->regular_price - $item->price) / $product->regular_price) * 100);
                }

                // Tính % đã bán (Progress bar)
                $soldPercent = 0;
                if ($item->quantity > 0) {
                    $soldPercent = ($item->sold / $item->quantity) * 100;
                    // Giới hạn max 100%
                    if ($soldPercent > 100) $soldPercent = 100;
                }

                return (object) [
                    'id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'image_url' => (function() use ($product) {
                        if (!$product->image) return 'https://placehold.co/300';

                        // Nếu là link online (bắt đầu bằng http) thì giữ nguyên
                        if (str_starts_with($product->image, 'http')) {
                            return $product->image;
                        }

                        // Nếu là ảnh upload thì thêm storage/
                        return asset('storage/' . $product->image);
                    })(),
                    'regular_price' => $product->regular_price,
                    'sale_price' => $item->price, // Giá Flash Sale lấy từ bảng pivot
                    'discount_percent' => $discountPercent,
                    'sold' => $item->sold,
                    'quantity' => $item->quantity,
                    'sold_percent' => $soldPercent,
                ];
            })->filter(); // Loại bỏ null
        }
    }

    public function placeholder()
    {
        // Giữ nguyên Skeleton cũ của bạn
        return <<<'blade'
        <div class="bg-white rounded-xl shadow-sm border border-red-100 p-6 mb-8 animate-pulse">
            <div class="flex items-center justify-between mb-6">
                <div class="h-8 bg-gray-200 rounded w-1/3"></div>
                <div class="h-4 bg-gray-200 rounded w-20"></div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                @foreach(range(1, 6) as $i)
                    <div class="bg-gray-100 rounded-lg aspect-square"></div>
                @endforeach
            </div>
        </div>
        blade;
    }

    public function render()
    {
        // Nếu không có chương trình nào active -> Ẩn
        if (!$this->isActive || $this->products->isEmpty()) {
            return <<<'blade'
                <div></div>
            blade;
        }

        return view('Website::livewire.home.flash-sale');
    }
}
