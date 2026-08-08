<?php

namespace Modules\Admin\Services;

use Modules\Admin\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;

class SettingsService
{
    /** 
     * Lấy giá trị setting (có Cache)
     */
    public function get(string $key, $default = null)
    {
        // Cache key ngắn gọn: wp_opt_{key}
        return Cache::rememberForever("wp_opt_{$key}", function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();

            // Xử lý decode JSON nếu type là json
            if ($setting && $setting->type === 'json') {
                return json_decode($setting->value, true);
            }

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Lưu một setting (Xóa cache ngay lập tức)
     */
    public function set(string $key, $value, string $group = 'general', string $type = 'text'): void
    {
        // Nếu value là array, auto chuyển sang json
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            $type = 'json';
        }

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group_name' => $group,
                'type' => $type
            ]
        );

        Cache::forget("wp_opt_{$key}");
      //  Cache::tags(['settings'])->flush(); // Nếu dùng Redis tags
    }

    /**
     * Lưu hàng loạt settings từ Form (Admin)
     * Input: ['header_hotline' => '...', 'header_email' => '...']
     */
    public function updateMany(array $data, string $group = 'general'): bool
    {
        DB::beginTransaction();
        try {
            foreach ($data as $key => $value) {
                if (in_array($key, ['_token', '_method'])) continue;
                $this->set($key, $value, $group);
            }
            DB::commit();
            return true;
        } catch (\Exception $e) { // Thêm backslash trước Exception để chắc chắn đúng class
            DB::rollBack();

            // --- DEBUG CODE (Xóa sau khi fix xong) ---
            dd($e->getMessage());
            // ----------------------------------------

            return false;
        }
    }
}
