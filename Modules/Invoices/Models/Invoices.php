<?php

namespace Modules\Invoices\Models;

use Illuminate\Database\Eloquent\Model;

class Invoices extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'lookup_code',
        'symbol',
        'invoice_number',
        'type',
        'issued_date',
        'tax_code',
        'name',
        'address',
        'email',
        'phone',
        'tax_rate',
        'vat_amount',
        'amount_before_vat',
        'total_amount',
        'invoice_type', // sold | purchase
    ];

    protected $casts = [
        'issued_date' => 'date',
        'tax_rate' => 'decimal:0',
        'vat_amount' => 'decimal:2',
        'amount_before_vat' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public static function headers()
    {
        return [
            'Mã tra cứu',
            'Ký hiệu',
            'Số HĐ',
            'Loại',
            'Ngày lập',
            'Mã số thuế',
            'Đơn vị',
            'Địa chỉ',
            'Email',
            'Số điện thoại',
            'Thuế suất',
            'VAT',
            'Trước VAT',
            'Thành tiền',
            'Loại hóa đơn',
        ];
    }
}
