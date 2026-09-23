<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryIssueDocumentSetting extends Model
{
    protected $table='pharma_inventory_issue_document_settings';

    protected $fillable=[
        'organization_name','organization_address','tax_code','phone','document_title','document_subtitle',
        'warehouse_name','issuer_label','deliverer_label','receiver_label','footer_note',
        'show_price_list','show_unit_price','show_total_value','show_notes',
    ];

    protected $casts=[
        'show_price_list'=>'boolean','show_unit_price'=>'boolean','show_total_value'=>'boolean','show_notes'=>'boolean',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([],[
            'document_title'=>'PHIẾU XUẤT KHO','warehouse_name'=>'Kho chính',
            'issuer_label'=>'Người lập phiếu','deliverer_label'=>'Người giao hàng','receiver_label'=>'Người nhận hàng',
            'show_price_list'=>true,'show_unit_price'=>true,'show_total_value'=>true,'show_notes'=>true,
        ]);
    }
}
