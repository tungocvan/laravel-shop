<?php

namespace Modules\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    //protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'group_name',
        'type', // text, textarea, editor, json, image, boolean
        'label'
    ];

    // ✅ UPDATE: Tự động cast value dựa trên type nếu cần thiết,
    // nhưng đơn giản nhất là cast value khi cần hoặc luôn để raw text nếu không phức tạp.
    // Tuy nhiên, với Laravel hiện đại, ta nên định nghĩa cast attributes.
    protected function casts(): array
    {
        return [
            // Lưu ý: Nếu logic dynamic type quá phức tạp, ta xử lý ở Service.
            // Ở đây tạm thời giữ nguyên, xử lý decode ở ServiceLayer để tránh lỗi cast string sang array không mong muốn.
        ];
    }

    // Helper: Lấy giá trị (giữ nguyên logic cache của bạn)
    public static function getValue($key, $default = null)
    {
        return Cache::rememberForever('setting_' . $key, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function setValue($key, $value, $group = 'general')
    {
        // Xử lý array -> json trước khi lưu nếu cần
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group_name' => $group]
        );
        Cache::forget('setting_' . $key);
    }
}
