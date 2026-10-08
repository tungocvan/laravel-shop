<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;

class MedicineExcelProfile extends Model
{
    protected $table = 'pharma_medicine_excel_profiles';
    protected $guarded = [];
    protected $casts = [
        'is_default' => 'boolean',
        'columns' => 'array',
        'headers' => 'array',
        'widths' => 'array',
        'alignments' => 'array',
        'settings' => 'array',
    ];
}
