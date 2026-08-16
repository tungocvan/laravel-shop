<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class PricingResult extends Model
{
    protected $table = 'muasamcong_pricing_results';

    protected $guarded = [];

    protected $casts = [
        'winning_code' => 'array',
        'winning_name' => 'array',
        'dia_diem' => 'array',
        'raw_payload' => 'array',
        'ngay_dang_tai_kqlcnt' => 'datetime',
        'ngay_ban_hanh_quyet_dinh' => 'datetime',
        'don_gia' => 'decimal:4',
        'so_luong' => 'decimal:4',
        'so_nha_thau_tham_du' => 'decimal:8',
        'synced_at' => 'datetime',
    ];
}
