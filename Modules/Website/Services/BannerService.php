<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Modules\Website\Models\Banner; // Hoặc Imagick nếu server có hỗ trợ

class BannerService
{
    protected $manager;

    public function __construct()
    {
        // Khởi tạo trình quản lý ảnh (Sử dụng GD Driver mặc định của PHP)
        $this->manager = new ImageManager(new Driver);
    }

    public function getAll()
    {
        return Banner::orderBy('order', 'asc')->orderBy('id', 'desc')->get();
    }

    public function save($data, $imageDesktop = null, $imageMobile = null)
    {
        $banner = ! empty($data['id']) ? Banner::findOrFail($data['id']) : null;
        $oldDesktop = $banner?->image_desktop;
        $oldMobile = $banner?->image_mobile;
        $newPaths = [];

        try {
            if ($imageDesktop) {
                $data['image_desktop'] = $newPaths[] = $this->processImage($imageDesktop, 1920, 600, 'banners');
            }
            if ($imageMobile) {
                $data['image_mobile'] = $newPaths[] = $this->processImage($imageMobile, 800, 1000, 'banners');
            }

            DB::transaction(function () use (&$banner, $data): void {
                if ($banner) {
                    $banner->update($data);
                } else {
                    $banner = Banner::create($data);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($newPaths as $path) {
                $this->deleteImage($path);
            }
            throw $exception;
        }

        if ($imageDesktop) {
            $this->deleteImage($oldDesktop);
        }
        if ($imageMobile) {
            $this->deleteImage($oldMobile);
        }

        return $banner;
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
        $filename = uniqid().'.webp';
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
