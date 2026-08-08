<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    // Đã cập nhật tên bảng theo yêu cầu
    protected $table = 'pharma_medicines';

    protected $fillable = [
        'circular_order_number',
        'circular_group',
        'active_ingredients',
        'concentration',
        'name',
        'dosage_form',
        'route_of_administration',
        'unit',
        'packaging_specification',
        'registration_number',
        'shelf_life',
        'registered_company',
        'manufacturing_company',
        'manufacturing_country',
        'visa_validity_date',
        'gmp_certification_date',
        'declared_price',
        'is_special_control',
        'profile_link',
        'notes',
    ];

    // Ép kiểu dữ liệu (Casting) để đảm bảo toàn vẹn dữ liệu
    protected $casts = [
        'visa_validity_date' => 'date',
        'gmp_certification_date' => 'date',
        'is_special_control' => 'boolean',
        'declared_price' => 'decimal:2',
    ];
}
