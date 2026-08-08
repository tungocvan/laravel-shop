<?php

namespace Modules\Admin\Services;

use Modules\Admin\Models\FlashSale;
use Modules\Admin\Models\FlashSaleItem;
use Illuminate\Support\Facades\DB;

class FlashSaleService
{
    // 4. SERVICE_FLASH_SALE
    public function getAll()
    {
        return FlashSale::withCount('items')->orderBy('id', 'desc')->paginate(10);
    }

    public function createFlashSale($data, $items)
    {
        return DB::transaction(function () use ($data, $items) {
            $flashSale = FlashSale::create($data);

            foreach ($items as $item) {
                $flashSale->items()->create([
                    'product_id' => $item['product_id'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'sold' => 0
                ]);
            }
            return $flashSale;
        });
    }

    public function updateFlashSale($id, $data, $items)
    {
        return DB::transaction(function () use ($id, $data, $items) {
            $flashSale = FlashSale::findOrFail($id);
            $flashSale->update($data);

            // Sync Items: Xóa cũ, tạo mới (Cách đơn giản nhất để tránh logic diff phức tạp)
            $flashSale->items()->delete();

            foreach ($items as $item) {
                $flashSale->items()->create([
                    'product_id' => $item['product_id'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    // Giữ lại 'sold' nếu cần logic phức tạp hơn, ở đây reset về 0 hoặc map lại từ db cũ nếu muốn
                    'sold' => $item['sold'] ?? 0
                ]);
            }
            return $flashSale;
        });
    }

    public function delete($id)
    {
        FlashSale::destroy($id);
    }
    // End 4.
}
