<?php

namespace Modules\Admin\Services;

use Modules\Admin\Models\Banner;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; // Hoặc Imagick nếu server có hỗ trợ

class BannerService
{
    protected $manager;

    public function __construct()
    {
        // Khởi tạo trình quản lý ảnh (Sử dụng GD Driver mặc định của PHP)
        $this->manager = new ImageManager(new Driver());
    }

    public function getAll()
    {
        return Banner::orderBy('order', 'asc')->orderBy('id', 'desc')->get();
    }

    public function save($data, $imageDesktop = null, $imageMobile = null)
    {
        
        // 1. XỬ LÝ ẢNH DESKTOP (Max 1920x600)
        if ($imageDesktop) {
            // Xóa ảnh cũ nếu đang update
            if (isset($data['id'])) {
                $this->deleteImage(Banner::find($data['id'])->image_desktop);
            }
            $data['image_desktop'] = $this->processImage($imageDesktop, 1920, 600, 'banners');
        }

        // 2. XỬ LÝ ẢNH MOBILE (Max 800x1000)
        if ($imageMobile) {
            // Xóa ảnh cũ nếu đang update
            if (isset($data['id'])) {
                $this->deleteImage(Banner::find($data['id'])->image_mobile);
            }
            $data['image_mobile'] = $this->processImage($imageMobile, 800, 1000, 'banners');
        }

        // 3. LƯU DATABASE
        if (isset($data['id']) && $data['id']) {
            $banner = Banner::find($data['id']);
            // Loại bỏ key ảnh nếu không có upload mới (để tránh ghi đè null)
            if (!$imageDesktop) unset($data['image_desktop']);
            if (!$imageMobile) unset($data['image_mobile']);

            $banner->update($data);
        } else {
            Banner::create($data);
        }
    }

    public function delete($id)
    {
        $banner = Banner::find($id);
        if ($banner) {
            $this->deleteImage($banner->image_desktop);
            $this->deleteImage($banner->image_mobile);
            $banner->delete();
        }
    }

    // --- HELPER FUNCTIONS ---

    /**
     * Hàm xử lý chung: Resize -> Convert WebP -> Save
     */
    private function processImage($file, $width, $height, $folder)
    {
        // Tạo tên file random
        $filename = uniqid() . '.webp';
        $path = "$folder/$filename";

        // Đọc ảnh
        $image = $this->manager->read($file);

        // LOGIC RESIZE:
        // Cách 1: cover (Cắt ảnh để lấp đầy khung - Đẹp layout, nhưng có thể mất chi tiết rìa ảnh)
        // Cách 2: scaleDown (Thu nhỏ để vừa khung - Giữ nguyên ảnh, nhưng có thể không lấp đầy chiều cao)

        // Ở đây tôi chọn 'cover' để Slider đồng bộ chiều cao tuyệt đối.
        $image->cover($width, $height);

        // Encode sang WebP chất lượng 80% (Siêu nhẹ)
        $encoded = $image->toWebp(80);

        // Lưu vào Storage (Public)
        Storage::disk('public')->put($path, (string) $encoded);

        return $path;
    }

    private function deleteImage($path)
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
