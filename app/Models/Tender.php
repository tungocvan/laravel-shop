<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tender extends Model
{
    protected $table = 'tenders';
    protected $guarded = [];
    protected $fillable = [
        'medicine_id',
        'trang_thai_trung_thau',
        'gia_trung_thau',
        'soluong_trung_thau',
        'noi_trung_thau',
        'congty_trung_thau',
        'so_qdtt',
        'tg_trung_thau',
        'nhom_thuoc_tt',
    ];

    // 🔗 relationship
    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }
}
