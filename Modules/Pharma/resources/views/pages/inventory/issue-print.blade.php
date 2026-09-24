<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>In phiếu {{ $issue->number }}</title><style>@page{size:A4;margin:14mm}body{font-family:Arial,sans-serif;color:#111;font-size:12px}.toolbar{display:flex;justify-content:flex-end;gap:8px;margin:0 auto 16px;max-width:1000px}.toolbar button,.toolbar a{border:1px solid #cbd5e1;border-radius:8px;padding:9px 14px;background:white;text-decoration:none;color:#111;font-weight:600}.toolbar button{background:#4f46e5;color:white;border-color:#4f46e5}.sheet{max-width:1000px;margin:auto}.head{display:flex;justify-content:space-between}.title{text-align:center;margin:22px 0}.title h1{margin:0;font-size:26px}.meta{margin-bottom:14px}.meta td{border:0;padding:2px 0;vertical-align:top}.meta .label{width:160px;font-weight:bold;white-space:nowrap;padding-right:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #444;padding:7px 6px}th{background:#f1f5f9;font-size:10px}.r{text-align:right}.c{text-align:center}.sign{margin-top:28px}.sign td{border:0;text-align:center;font-weight:bold}.space{height:90px}.muted{color:#64748b;font-size:10px}@media print{.toolbar{display:none}.sheet{max-width:none}}</style></head><body>
<div class="toolbar"><a href="{{ route('admin.pharma.inventory.issues.show',$issue) }}">← Quay lại</a><a href="{{ route('admin.pharma.inventory.issues.pdf',$issue) }}">Tải PDF</a><button onclick="window.print()">In phiếu</button></div>
<div class="sheet">@php
$totalValue=$issue->items->sum(fn($i)=>(float)$i->quantity*(float)$i->unit_price);
$signatures=collect([
 ['show'=>$settings->show_issuer_signature,'label'=>$settings->issuer_label,'show_date'=>false],
 ['show'=>$settings->show_deliverer_signature,'label'=>$settings->deliverer_label,'show_date'=>false],
 ['show'=>$settings->show_receiver_signature,'label'=>$settings->receiver_label,'show_date'=>false],
 ['show'=>$settings->show_keeper_signature,'label'=>$settings->keeper_label,'show_date'=>true],
])->where('show',true)->values();
$signatureWidth=$signatures->count() > 0 ? (100 / $signatures->count()) : 100;
@endphp
<div class="head"><div><b>{{ $settings->organization_name ?: 'PHIẾU XUẤT KHO DƯỢC PHẨM' }}</b>@if($settings->organization_address)<br><span class="muted">{{ $settings->organization_address }}</span>@endif @if($settings->tax_code)<br><span class="muted">MST: {{ $settings->tax_code }}</span>@endif @if($settings->phone)<span class="muted"> · ĐT: {{ $settings->phone }}</span>@endif</div><div class="r"><b>{{ $issue->number }}</b><br><span class="muted">{{ $issue->issue_date->format('d/m/Y') }}</span></div></div>
<div class="title"><h1>{{ $settings->document_title }}</h1>@if($settings->document_subtitle)<div class="muted">{{ $settings->document_subtitle }}</div>@endif<b>Số: {{ $issue->number }}</b></div>
<table class="meta">
<tr><td class="label">Ngày xuất:</td><td>{{ $issue->issue_date->format('d/m/Y') }}</td></tr>
<tr><td class="label">Kho xuất:</td><td>{{ $settings->warehouse_name }}</td></tr>
<tr><td class="label">Khách hàng / Nơi nhận:</td><td>{{ $issue->recipient_name ?: '—' }}</td></tr>
<tr><td class="label">Người phụ trách:</td><td>{{ $issue->priceList?->manager?->name ?: '—' }}</td></tr>
@if($settings->show_price_list)
<tr><td class="label">Bảng giá áp dụng:</td><td>{{ $issue->priceList?->code ?: '—' }} {{ $issue->priceList?->name ? '('.$issue->priceList->name.')' : '' }}</td></tr>
@endif
</table>
<table><thead><tr><th>STT</th><th>Mã thuốc</th><th>Tên thuốc / Quy cách</th><th>ĐVT</th><th>Số lô</th><th>HSD</th><th>SL</th>@if($settings->show_unit_price)<th>Đơn giá</th>@endif @if($settings->show_total_value)<th>Thành tiền</th>@endif</tr></thead><tbody>
@foreach($issue->items as $item)<tr><td class="c">{{ $loop->iteration }}</td><td>{{ $item->medicine->medicine_code }}</td><td><b>{{ $item->medicine->name }}</b>@if($item->medicine->packaging_specification)<br><span class="muted">{{ $item->medicine->packaging_specification }}</span>@endif</td><td class="c">{{ $item->medicine->unit ?: '—' }}</td><td>{{ $item->batch_number ?: 'Chưa chọn lô' }}</td><td class="c">{{ $item->expiry_date?->format('d/m/Y') ?: '—' }}</td><td class="r">{{ number_format((float)$item->quantity,0,',','.') }}</td>@if($settings->show_unit_price)<td class="r">{{ number_format((float)$item->unit_price,0,',','.') }}</td>@endif @if($settings->show_total_value)<td class="r"><b>{{ number_format((float)$item->quantity*(float)$item->unit_price,0,',','.') }}</b></td>@endif</tr>@endforeach
<tr><td colspan="7" class="r"><b>Tổng cộng</b></td>@if($settings->show_unit_price)<td></td>@endif @if($settings->show_total_value)<td class="r"><b>{{ number_format($totalValue,0,',','.') }} đ</b></td>@endif</tr></tbody></table>
@if($settings->show_notes && filled($issue->notes))<p><b>Ghi chú:</b> {{ $issue->notes }}</p>@endif
@if($signatures->isNotEmpty())
<table class="sign"><tr>
@foreach($signatures as $signature)
<td style="width: {{ $signatureWidth }}%">{{ $signature['label'] }}@if($signature['show_date'])<br><span class="muted">Ngày ..... tháng ..... năm .....</span>@endif<br><span class="muted">(Ký, ghi rõ họ tên)</span><div class="space"></div></td>
@endforeach
</tr></table>
@endif
@if($settings->footer_note)<p class="c muted">{{ $settings->footer_note }}</p>@endif</div>
<script>window.addEventListener('load',()=>{ if(new URLSearchParams(location.search).get('auto')==='1') window.print(); });</script></body></html>