<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugBidAward extends Model
{
    /**
     * Tên bảng trong cơ sở dữ liệu.
     *
     * @var string
     */
    protected $table = 'pharma_drug_bid_awards';

    /**
     * Các thuộc tính có thể fillable hàng loạt.
     *
     * @var array
     */
    protected $fillable = [
        'medicine_id',
        'medicine_name',
        'packaging_specification',
        'quantity',
        'unit_price',
        'bidding_notice_code',
        'investor_name',
        'decision_number',
        'decision_date',
        'contract_duration_months',
        'winning_company_name',
        'decision_document_url'
    ];

    /**
     * Ép kiểu dữ liệu cho các trường đặc thù.
     *
     * @var array
     */
    protected $casts = [
        'medicine_id'              => 'integer',
        'quantity'                 => 'integer',
        'unit_price'               => 'decimal:2',
        'decision_date'            => 'date',
        'contract_duration_months' => 'integer',
    ];

    /**
     * Thiết lập mối quan hệ ngược về danh mục hồ sơ sản phẩm gốc.
     *
     * @return BelongsTo
     */
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }
}
