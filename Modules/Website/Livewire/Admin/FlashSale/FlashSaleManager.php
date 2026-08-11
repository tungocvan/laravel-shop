<?php

namespace Modules\Website\Livewire\Admin\FlashSale;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Models\FlashSale;
use Modules\Website\Services\FlashSaleService;

class FlashSaleManager extends Component
{
    use WithPagination, AuthorizesAdminPermissions;

    public $isModalOpen = false;
    public $isEditMode = false;
    public $showProductPicker = false;
    public $productSearchQuery = '';
    public $saleId;
    public $title;
    public $start_time;
    public $end_time;
    public $is_active = true;
    public $items = [];

    public function render()
    {
        $sales = (new FlashSaleService())->getAll();
        $searchProducts = [];

        if ($this->showProductPicker) {
            $query = DB::table('wp_products')
                ->select('id', 'title', 'image', 'regular_price', 'sale_price')
                ->where('is_active', true);

            if ($this->productSearchQuery) {
                $query->where('title', 'like', '%' . $this->productSearchQuery . '%');
            }

            $searchProducts = $query->limit(10)->get();
        }

        return view('Website::livewire.admin.flash-sale.flash-sale-manager', [
            'sales' => $sales,
            'searchProducts' => $searchProducts,
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->isEditMode = true;
        $sale = FlashSale::with('items.product')->findOrFail($id);
        $this->saleId = $sale->id;
        $this->title = $sale->title;
        $this->start_time = $sale->start_time->format('Y-m-d\TH:i');
        $this->end_time = $sale->end_time->format('Y-m-d\TH:i');
        $this->is_active = $sale->is_active;

        foreach ($sale->items as $item) {
            if ($item->product) {
                $this->items[] = [
                    'product_id' => $item->product_id,
                    'name' => $item->product->title,
                    'image' => $item->product->image,
                    'original_price' => $item->product->regular_price,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'sold' => $item->sold,
                ];
            }
        }

        $this->isModalOpen = true;
    }

    public function save(FlashSaleService $service)
    {
        $this->authorizeAdminPermission('marketing.flash-sale.manage');

        $this->validate([
            'title' => 'required|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'items' => 'required|array|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $data = [
            'title' => $this->title,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditMode) {
            $service->updateFlashSale($this->saleId, $data, $this->items);
        } else {
            $service->createFlashSale($data, $this->items);
        }

        $this->isModalOpen = false;
        $this->resetForm();
        $this->dispatch('show-toast', type: 'success', message: 'Lưu chương trình Flash Sale thành công!');
    }

    public function delete($id, FlashSaleService $service)
    {
        $this->authorizeAdminPermission('marketing.flash-sale.manage');
        $service->delete($id);
        $this->dispatch('show-toast', type: 'success', message: 'Đã xóa Flash Sale.');
    }

    public function openPicker()
    {
        $this->showProductPicker = true;
        $this->productSearchQuery = '';
    }

    public function addProduct($productId)
    {
        foreach ($this->items as $item) {
            if ($item['product_id'] == $productId) {
                return;
            }
        }

        $prod = DB::table('wp_products')->find($productId);
        if ($prod) {
            $this->items[] = [
                'product_id' => $prod->id,
                'name' => $prod->title,
                'image' => $prod->image,
                'original_price' => $prod->regular_price,
                'price' => $prod->regular_price,
                'quantity' => 10,
                'sold' => 0,
            ];
        }
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function resetForm()
    {
        $this->reset(['saleId', 'title', 'start_time', 'end_time', 'is_active', 'items', 'isEditMode', 'isModalOpen']);
    }
}
