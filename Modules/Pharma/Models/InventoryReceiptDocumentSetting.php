<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReceiptDocumentSetting extends Model
{
    protected $table='pharma_inventory_receipt_document_settings';

    protected $fillable=[
        'organization_name','organization_address','tax_code','phone','document_title','document_subtitle',
        'warehouse_name','issuer_label','deliverer_label','receiver_label','keeper_label','footer_note',
        'show_unit_price','show_invoice_unit_price','show_vat','show_total_value','show_notes',
        'show_issuer_signature','show_deliverer_signature','show_receiver_signature','show_keeper_signature',
    ];

    protected $casts=[
        'show_unit_price'=>'boolean','show_invoice_unit_price'=>'boolean','show_vat'=>'boolean',
        'show_total_value'=>'boolean','show_notes'=>'boolean','show_issuer_signature'=>'boolean',
        'show_deliverer_signature'=>'boolean','show_receiver_signature'=>'boolean','show_keeper_signature'=>'boolean',
    ];

    public static function current(): self
    {
        if ($current=static::query()->first()) return $current;

        $issue=InventoryIssueDocumentSetting::current();

        return static::query()->create([
            'organization_name'=>$issue->organization_name,'organization_address'=>$issue->organization_address,
            'tax_code'=>$issue->tax_code,'phone'=>$issue->phone,'document_title'=>'PHIẾU NHẬP KHO',
            'document_subtitle'=>$issue->document_subtitle,'warehouse_name'=>$issue->warehouse_name ?: 'Kho chính',
            'issuer_label'=>'Người lập phiếu','deliverer_label'=>'Người giao hàng',
            'receiver_label'=>'Người nhận hàng','keeper_label'=>$issue->keeper_label ?: 'Thủ kho',
            'footer_note'=>$issue->footer_note,'show_unit_price'=>true,'show_invoice_unit_price'=>true,'show_vat'=>true,
            'show_total_value'=>true,'show_notes'=>true,'show_issuer_signature'=>true,'show_deliverer_signature'=>true,
            'show_receiver_signature'=>true,'show_keeper_signature'=>true,
        ]);
    }
}
