<?php
namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionLocation extends Model
{
    protected $table = 'admission_locations';

    protected $fillable = [
        'province_code',
        'province_name',
        'ward_code',
        'ward_name',
    ];

    // Helper để lấy danh sách Tỉnh/TP duy nhất cho dropdown
    public static function getProvinces()
    {
        return self::select('province_code', 'province_name')
            ->distinct()
            ->orderBy('province_name')
            ->get();
    }

    // Helper để lấy danh sách Phường/Xã theo mã Tỉnh
    public static function getWardsByProvince($provinceCode)
    {
        return self::where('province_code', $provinceCode)
            ->orderBy('ward_name')
            ->get();
    }
}