<?php

namespace Modules\System\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\System\Services\SettingsService;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'group_name',
        'type', // text, textarea, editor, json, image, boolean
        'label',
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

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return app(SettingsService::class)->get($key, $default);
    }

    public static function setValue(
        string $key,
        mixed $value,
        string $group = 'general',
        string $type = 'text',
    ): void {
        app(SettingsService::class)->set($key, $value, $group, $type);
    }
}
