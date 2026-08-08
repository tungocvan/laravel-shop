<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    protected $table = 'medicines';
    protected $guarded = [];

    protected $fillable = [
        'stt_thong_tu',
        'phan_nhom_thong_tu',
        'ten_hoat_chat',
        'nong_do_ham_luong',
        'ten_biet_duoc',
        'dang_bao_che',
        'duong_dung',
        'don_vi_tinh',
        'quy_cach_dong_goi',
        'giay_phep_luu_hanh',
        'han_dung',
        'co_so_san_xuat',
        'nuoc_san_xuat',
        'gia_ke_khai',
        'nha_phan_phoi',
        'don_gia',
        'gia_von',
        'link_hinh_anh',
        'link_hssp',
        'han_dung_visa',
        'han_dung_gmp',
    ];

    // 🔗 relationship
    public function tenders()
    {
        return $this->hasMany(Tender::class);
    }
}
