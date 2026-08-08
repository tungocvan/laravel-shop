<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    // 4. KHOI_TAO_MODEL_SETTING
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'group_name', // homepage, general, custom
        'type',       // text, json, image...
        'label',
    ];

    // Helper: Lấy giá trị nhanh
    public static function getValue($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) return $default;

        return $setting->value;
    }

    // Helper: Lưu giá trị nhanh
    public static function setValue($key, $value, $group = 'general', $type = 'text')
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group_name' => $group,
                'type' => $type
            ]
        );
    }
    // End 4.
}
