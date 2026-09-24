<!doctype html><html lang="vi"><head><meta charset="utf-8"><style>
@page{margin:16mm 12mm}*{box-sizing:border-box}body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#111}h1{font-size:21px;text-align:center;margin:16px 0 2px}.center{text-align:center}.right{text-align:right}.muted{color:#555}.header{width:100%;margin-bottom:12px}.header td{vertical-align:top}.meta{width:100%;border-collapse:collapse;margin:14px 0;line-height:1.5}.meta td{border:0;padding:1px 0;vertical-align:top}.meta .label{width:128px;font-weight:bold;white-space:nowrap;padding-right:10px}table.items{width:100%;border-collapse:collapse;margin-top:12px}table.items th,table.items td{border:1px solid #444;padding:6px 5px}table.items th{font-size:8px;background:#f3f4f6;text-transform:uppercase}.name{font-weight:bold}.small{font-size:8px}.totals{margin-top:12px;line-height:1.7}.sign{width:100%;margin-top:28px;text-align:center}.sign td{width:33.33%;vertical-align:top;font-weight:bold}.sign .space{height:70px}.note{margin-top:8px}.title-no{font-size:12px;font-weight:bold}.footer{position:fixed;bottom:-8mm;left:0;right:0;text-align:center;font-size:8px;color:#777}
</style></head><body>
@php
$totalValue=$issue->items->sum(fn($i)=>(float)$i->quantity*(float)$i->unit_price);
$signatures=collect([
    ['show'=>$settings->show_issuer_signature,'label'=>$settings->issuer_label,'show_date'=>false],
    ['show'=>$settings->show_deliverer_signature,'label'=>$settings->deliverer_label,'show_date'=>false],
    ['show'=>$settings->show_receiver_signature,'label'=>$settings->receiver_label,'show_date'=>false],
    ['show'=>$settings->show_keeper_signature,'label'=>$settings->keeper_label,'show_date'=>true],
])->where('show',true)->values();
$signatureWidth=$signatures->count() > 0 ? (100 / $signatures->count()) : 100;
@endphp
<table class="header"><tr><td><b>{{ $settings->organization_name ?: 'PHIẾU XUẤT KHO DƯỢC PHẨM' }}</b>@if($settings->organization_address)
<br><span class="muted">{{ $settings->organization_address }}</span>
@endif
@if($settings->tax_code)
<br><span class="muted">MST: {{ $settings->tax_code }}</span>
@endif
@if($settings->phone)
<span class="muted"> · ĐT: {{ $settings->phone }}</span>
@endif</td><td class="right"><b>Mẫu chứng từ nội bộ</b><br><span class="muted">Ngày lập: {{ $issue->issue_date->format('d/m/Y') }}</span></td></tr></table>
<h1>{{ $settings->document_title }}</h1>
@if($settings->document_subtitle)
<div class="center muted">{{ $settings->document_subtitle }}</div>
@endif
<div class="center title-no">Số: {{ $issue->number }}</div>
<table class="meta">
<tr><td class="label">Ngày xuất:</td><td>{{ $issue->issue_date->format('d/m/Y') }}</td></tr>
<tr><td class="label">Xuất tại kho:</td><td>{{ $settings->warehouse_name }}</td></tr>
<tr><td class="label">Khách hàng / Nơi nhận:</td><td>{{ $issue->recipient_name ?: '—' }}</td></tr>
<tr><td class="label">Người phụ trách:</td><td>{{ $issue->priceList?->manager?->name ?: '—' }}</td></tr>
@if($settings->show_price_list)
<tr><td class="label">Bảng giá áp dụng:</td><td>{{ $issue->priceList?->code ?: '—' }} {{ $issue->priceList?->name ? '('.$issue->priceList->name.')' : '' }}</td></tr>
@endif
</table>
<table class="items"><thead><tr><th>STT</th><th>Mã thuốc</th><th>Tên thuốc / Quy cách</th><th>ĐVT</th><th>Số lô</th><th>Hạn dùng</th><th>SL</th>
@if($settings->show_unit_price)
<th>Đơn giá</th>
@endif
@if($settings->show_total_value)
<th>Thành tiền</th>
@endif</tr></thead><tbody>
@foreach($issue->items as $item)<tr><td class="center">{{ $loop->iteration }}</td><td>{{ $item->medicine->medicine_code }}</td><td><span class="name">{{ $item->medicine->name }}</span>@if($item->medicine->packaging_specification)<br><span class="small">{{ $item->medicine->packaging_specification }}</span>@endif</td><td class="center">{{ $item->medicine->unit ?: '—' }}</td><td>{{ $item->batch_number }}</td><td class="center">{{ $item->expiry_date->format('d/m/Y') }}</td><td class="right">{{ number_format((float)$item->quantity,0,',','.') }}</td>
@if($settings->show_unit_price)
<td class="right">{{ number_format((float)$item->unit_price,0,',','.') }}</td>
@endif
@if($settings->show_total_value)
<td class="right"><b>{{ number_format((float)$item->quantity*(float)$item->unit_price,0,',','.') }}</b></td>
@endif
</tr>
@endforeach
<tr>
<td colspan="7" class="right"><b>Tổng cộng</b></td>
@if($settings->show_unit_price)
<td></td>
@endif
@if($settings->show_total_value)
<td class="right"><b>{{ number_format($totalValue,0,',','.') }} đ</b></td>
@endif
</tr></tbody></table>
<div class="totals">
<b>Tổng số mặt hàng:</b> {{ $issue->items->count() }}
@if($settings->show_total_value)
<br><b>Tổng giá trị:</b> {{ number_format($totalValue,0,',','.') }} đồng
@endif
</div>
@if($settings->show_notes && filled($issue->notes))<div class="note"><b>Ghi chú:</b> {{ $issue->notes }}</div>@endif
@if($signatures->isNotEmpty())
<table class="sign"><tr>
@foreach($signatures as $signature)
<td style="width: {{ $signatureWidth }}%">{{ $signature['label'] }}@if($signature['show_date'])<br><span class="muted small">Ngày ..... tháng ..... năm .....</span>@endif<br><span class="muted small">(Ký, ghi rõ họ tên)</span><div class="space"></div></td>
@endforeach
</tr></table>
@endif
@if($settings->footer_note)<div class="note center muted">{{ $settings->footer_note }}</div>@endif
<div class="footer">Phiếu {{ $issue->number }} · Trạng thái: {{ $issue->status==='posted'?'Đã ghi sổ':'Nháp' }}</div>
</body></html>