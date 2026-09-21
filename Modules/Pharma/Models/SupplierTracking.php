<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Partner\Models\Partner;

class SupplierTracking extends Model
{
    protected $table = 'pharma_supplier_trackings';

    protected $fillable = [
        'medicine_id', 'partner_id', 'working_date', 'supplier_name', 'supplier_name_normalized',
        'supplier_representative', 'area', 'distribution_scope', 'distribution_regions', 'distribution_provinces',
        'import_price', 'selling_price', 'invoice_price', 'invoice_difference_amount',
        'invoice_difference_percent', 'invoice_difference_fee', 'cost_price', 'gross_profit_percent',
        'committed_quantity', 'unit', 'deposit_amount', 'start_date', 'end_date', 'contract_url',
        'contract_file_path', 'deposit_receipt_path', 'status', 'note',
    ];

    public array $exceptExport = ['supplier_name_normalized'];

    protected $casts = [
        'working_date' => 'date', 'start_date' => 'date', 'end_date' => 'date',
        'distribution_regions' => 'array', 'distribution_provinces' => 'array',
        'import_price' => 'decimal:2', 'selling_price' => 'decimal:2', 'invoice_price' => 'decimal:2',
        'invoice_difference_amount' => 'decimal:2', 'invoice_difference_percent' => 'decimal:2',
        'invoice_difference_fee' => 'decimal:2', 'cost_price' => 'decimal:2',
        'gross_profit_percent' => 'decimal:2', 'committed_quantity' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(
            OfficialSourceFacility::class,
            'pharma_supplier_tracking_facilities',
            'supplier_tracking_id',
            'official_facility_id'
        )->withTimestamps();
    }
}
