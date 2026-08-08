<?php

namespace Modules\Website\Services;

use Modules\Website\Models\FooterColumn;
use Modules\Website\Models\FooterLink;
use Modules\Website\Models\SocialLink;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FooterService
{
    /* ================= SOCIAL LINKS ================= */


    public function updateSocialLinks(array $data): void
    {
        // Logic create/update social links
        // ... (Chi tiết khi vào implement)
        Cache::forget('social_links');
    }

    /* ================= FOOTER COLUMNS & LINKS ================= */

    // public function getFooterColumns()
    // {
    //     // Cache key: footer_columns_full
    //     return Cache::remember('footer_columns_full', 3600, function () {
    //         return FooterColumn::query()
    //             ->where('is_active', true)
    //             ->orderBy('sort_order')
    //             ->with(['links' => function ($q) {
    //                 $q->where('is_active', true)->orderBy('sort_order');
    //             }])
    //             ->get();
    //     });
    // }

    public function createColumn(array $data): FooterColumn
    {
        $col = FooterColumn::create($data);
        $this->clearCache(); // ✅ Xóa cache ngay
        return $col;
    }

    // 👇 BỔ SUNG FUNCTION DELETE COLUMN VÀO SERVICE
    public function deleteColumn(int $id): bool
    {
        $col = FooterColumn::find($id);
        if ($col) {
            $col->delete();
            $this->clearCache(); // ✅ Xóa cache ngay
            return true;
        }
        return false;
    }

    public function addLinkToColumn(int $columnId, array $data): FooterLink
    {
        $data['footer_column_id'] = $columnId;
        $link = FooterLink::create($data);
        $this->clearCache(); // ✅ Xóa cache ngay
        return $link;
    }

    public function deleteLink(int $linkId): bool
    {
        $link = FooterLink::findOrFail($linkId);
        $link->delete();
        $this->clearCache(); // ✅ Xóa cache ngay
        return true;
    }

    // Helper để xóa cache gọn gàng
    private function clearCache()
    {
        //Cache::forget('footer_columns_full');
        Cache::forget('footer_columns_admin');
        Cache::forget('footer_columns_frontend');
    }
    // Cập nhật thông tin Link
    public function updateLink(int $id, array $data): bool
    {
        $link = FooterLink::find($id);
        if ($link) {
            $link->update($data);
            $this->clearCache(); // Xóa cache
            return true;
        }
        return false;
    }

    // Cập nhật thứ tự sắp xếp (Nhận vào mảng ID đã sắp xếp)
    public function updateLinkOrder(array $orderedIds): void
    {
        // Duyệt qua mảng ID và update lại index
        foreach ($orderedIds as $index => $id) {
            FooterLink::where('id', $id)->update(['sort_order' => $index + 1]);
        }
        $this->clearCache();
    }
    // Cập nhật thứ tự Cột
    public function updateColumnOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            FooterColumn::where('id', $id)->update(['sort_order' => $index + 1]);
        }
        $this->clearCache();
    }

    // Ẩn/Hiện Cột
    public function toggleColumnStatus(int $id): bool
    {
        $col = FooterColumn::find($id);
        if ($col) {
            $col->update(['is_active' => !$col->is_active]); // Đảo ngược trạng thái
            $this->clearCache();
            return true;
        }
        return false;
    }
    /**
     * Dành cho ADMIN: Lấy tất cả cột và link (kể cả ẩn)
     */
    public function getColumnsForAdmin()
    {
        // Cache key riêng cho Admin
        return Cache::remember('footer_columns_admin', 3600, function () {
            return FooterColumn::query()
                ->orderBy('sort_order')
                ->with(['links' => function ($q) {
                    $q->orderBy('sort_order'); // Admin cần thấy cả link ẩn
                }])
                ->get();
        });
    }

    /**
     * Dành cho FRONTEND: Chỉ lấy cột và link đang hiện
     */
    public function getColumnsForFrontend()
    {
        return Cache::remember('footer_columns_frontend', 3600, function () {
            return FooterColumn::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->with(['links' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order');
                }])
                ->get();
        });
    }
    public function updateColumn(int $id, array $data): bool
    {
        $col = FooterColumn::find($id);
        if ($col) {
            $col->update($data);
            $this->clearCache(); // Xóa cache admin & frontend
            return true;
        }
        return false;
    }
    // ... (Các method cũ giữ nguyên)

    // --- SOCIAL LINK ACTIONS ---

    public function updateSocialLink(int $id, array $data): bool
    {
        $link = SocialLink::find($id);
        if ($link) {
            $link->update($data);
            Cache::forget('social_links'); // Xóa cache
            return true;
        }
        return false;
    }

    public function updateSocialLinkOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            SocialLink::where('id', $id)->update(['sort_order' => $index + 1]);
        }
        Cache::forget('social_links');
    }


    /* ================= SOCIAL LINKS ================= */

    public function getSocialLinks()
    {
        return Cache::remember('social_links', 86400, function () {
            return SocialLink::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });
    }

    // ✅ THÊM: Tạo mới và xóa Cache
    public function createSocialLink(array $data): void
    {
        SocialLink::create($data);
        Cache::forget('social_links');
    }

    // ✅ THÊM: Xóa và xóa Cache
    public function deleteSocialLink(int $id): void
    {
        SocialLink::destroy($id);
        Cache::forget('social_links');
    }

    // ... (Các method update giữ nguyên) ...
}
