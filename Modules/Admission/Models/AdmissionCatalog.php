<?php
namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionCatalog extends Model
{
    protected $table = 'admission_catalogs';
    protected $fillable = ['type', 'code', 'value', 'sort_order', 'is_active'];

    // Helper lấy nhanh danh mục theo loại
    public static function getByType($type)
    {
        return self::where('type', $type)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}