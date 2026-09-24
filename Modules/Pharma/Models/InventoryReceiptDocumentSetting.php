<?php
namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReceiptDocumentSetting extends Model
{
    protected $table='pharma_inventory_receipt_document_settings';

    protected $fillable=[
        'organization_name','organization_address','tax_code','phone','document_title','document_subtitle',
        'warehouse_name','issuer_label','deliverer_label','keeper_label','manager_label','footer_note',
        'show_invoice','show_unit_price','show_total_value','show_notes',
        'show_issuer_signature','show_deliverer_signature','show_keeper_signature','show_manager_signature',
    ];

    protected $casts=[
        'show_invoice'=>'boolean','show_unit_price'=>'boolean','show_total_value'=>'boolean','show_notes'=>'boolean',
        'show_issuer_signature'=>'boolean','show_deliverer_signature'=>'boolean',
        'show_keeper_signature'=>'boolean','show_manager_signature'=>'boolean',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([],[
            'document_title'=>'PHIẾU NHẬP KHO','warehouse_name'=>'Kho chính',
            'issuer_label'=>'Người lập phiếu','deliverer_label'=>'Người giao hàng',
            'keeper_label'=>'Thủ kho','manager_label'=>'Người phụ trách',
            'show_invoice'=>true,'show_unit_price'=>true,'show_total_value'=>true,'show_notes'=>true,
            'show_issuer_signature'=>true,'show_deliverer_signature'=>true,
            'show_keeper_signature'=>true,'show_manager_signature'=>true,
        ]);
    }
}
