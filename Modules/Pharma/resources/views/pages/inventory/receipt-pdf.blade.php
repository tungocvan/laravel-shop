<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:14mm}body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#111}.header,.meta,.items,.sign{width:100%;border-collapse:collapse}.header td{border:0;vertical-align:top}.right{text-align:right}.center{text-align:center}.muted{color:#666}.small{font-size:9px}h1{font-size:19px;margin:12px 0 3px}.meta{margin:14px 0;line-height:1.5}.meta td{border:0;padding:1px 0;vertical-align:top}.meta .label{width:128px;font-weight:bold;white-space:nowrap;padding-right:10px}.items th,.items td{border:1px solid #555;padding:5px 4px}.items th{background:#eee}.num{text-align:right;white-space:nowrap}.note{margin-top:12px}.sign{margin-top:26px}.sign td{border:0;text-align:center;font-weight:bold;vertical-align:top}.space{height:55px}
</style></head><body>
@php
$totalValue=$receipt->items->sum(fn($i)=>(float)$i->quantity*(float)$i->unit_price_ex_vat);
$signatures=collect([
 ['show'=>$settings->show_issuer_signature,'label'=>$settings->issuer_label],
 ['show'=>$settings->show_deliverer_signature,'label'=>$settings->deliverer_label],
 ['show'=>$settings->show_keeper_signature,'label'=>$settings->keeper_label],
 ['show'=>$settings->show_manager_signature,'label'=>$settings->manager_label],
])->where('show',true)->values();
$signatureWidth=$signatures->count()>0 ? 100/$signatures->count() : 100;
@endphp
<table class="header"><tr><td><b>{{ $settings->organization_name ?: 'PHIẾU NHẬP KHO DƯỢC PHẨM' }}</b>@if($settings->organization_address)<br>{{ $settings->organization_address }}@endif @if($settings->tax_code)<br>MST: {{ $settings->tax_code }}@endif @if($settings->phone)<br>ĐT: {{ $settings->phone }}@endif</td><td class="right muted">Ngày lập: {{ now()->format('d/m/Y') }}</td></tr></table>
<div class="center"><h1>{{ $settings->document_title }}</h1>@if($settings->document_subtitle)<div>{{ $settings->document_subtitle }}</div>@endif<div class="muted">Số: {{ $receipt->number }}</div></div>
<table class="meta"><tr><td class="label">Ngày nhập:</td><td>{{ $receipt->receipt_date->format('d/m/Y') }}</td></tr><tr><td class="label">Nhập tại kho:</td><td>{{ $settings->warehouse_name }}</td></tr><tr><td class="label">Nhà cung cấp:</td><td>{{ $receipt->supplier_name ?: '—' }}</td></tr>@if($settings->show_invoice)<tr><td class="label">Hóa đơn:</td><td>{{ $receipt->invoice_number ?: '—' }}@if($receipt->invoice_date) — {{ $receipt->invoice_date->format('d/m/Y') }}@endif</td></tr>@endif</table>
<table class="items"><thead><tr><th>STT</th><th>Mã thuốc</th><th>Tên thuốc</th><th>ĐVT</th><th>Số lô</th><th>HSD</th><th>SL</th>@if($settings->show_unit_price)<th>Đơn giá</th>@endif@if($settings->show_total_value)<th>Thành tiền</th>@endif</tr></thead><tbody>
@foreach($receipt->items as $item)<tr><td class="center">{{ $loop->iteration }}</td><td>{{ $item->medicine?->medicine_code }}</td><td>{{ $item->medicine?->name }}</td><td class="center">{{ $item->medicine?->unit ?: '—' }}</td><td>{{ $item->batch_number }}</td><td class="center">{{ $item->expiry_date->format('d/m/Y') }}</td><td class="num">{{ number_format((float)$item->quantity,0,',','.') }}</td>@if($settings->show_unit_price)<td class="num">{{ number_format((float)$item->unit_price_ex_vat,0,',','.') }}</td>@endif@if($settings->show_total_value)<td class="num">{{ number_format((float)$item->quantity*(float)$item->unit_price_ex_vat,0,',','.') }}</td>@endif</tr>@endforeach
<tr><td colspan="7" class="right"><b>Tổng cộng</b></td>@if($settings->show_unit_price)<td></td>@endif@if($settings->show_total_value)<td class="num"><b>{{ number_format($totalValue,0,',','.') }}</b></td>@endif</tr></tbody></table>
@if($settings->show_total_value)<p class="right"><b>Tổng giá trị:</b> {{ number_format($totalValue,0,',','.') }} đ</p>@endif
@if($settings->show_notes && filled($receipt->notes))<div class="note"><b>Ghi chú:</b> {{ $receipt->notes }}</div>@endif
@if($signatures->isNotEmpty())<table class="sign"><tr>@foreach($signatures as $signature)<td style="width:{{ $signatureWidth }}%">{{ $signature['label'] }}<br><span class="muted small">(Ký, ghi rõ họ tên)</span><div class="space"></div></td>@endforeach</tr></table>@endif
@if($settings->footer_note)<div class="center muted small">{{ $settings->footer_note }}</div>@endif
</body></html>