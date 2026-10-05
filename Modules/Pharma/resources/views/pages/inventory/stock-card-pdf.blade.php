<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><style>
@page{margin:12mm}*{box-sizing:border-box}body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#111}h1{text-align:center;font-size:20px;margin:4px 0}.center{text-align:center}.right{text-align:right}.muted{color:#666}.meta,.lots,.ledger{width:100%;border-collapse:collapse}.meta{margin:12px 0}.meta td{padding:3px 6px}.summary{width:100%;margin:10px 0;border-collapse:collapse}.summary td{border:1px solid #aaa;padding:8px;text-align:center}.summary b{display:block;font-size:14px;margin-top:3px}.lots th,.lots td,.ledger th,.ledger td{border:1px solid #777;padding:5px}.lots th,.ledger th{background:#f3f4f6;font-size:7px;text-transform:uppercase}.section{font-size:11px;font-weight:bold;margin:12px 0 5px}.footer{position:fixed;bottom:-6mm;left:0;right:0;text-align:center;font-size:7px;color:#777}
</style></head><body>
@php
$q=fn($v)=>rtrim(rtrim(number_format((float)$v,3,'.',''),'0'),'.');
$labels=['opening'=>'Tồn đầu kỳ','receipt'=>'Nhập kho','issue'=>'Xuất kho'];
@endphp
<div class="center muted">THẺ KHO DƯỢC PHẨM</div>
<h1>THẺ KHO · {{ $balance->medicine?->name }}</h1>
<div class="center">{{ $balance->medicine?->active_ingredients ?: '—' }} · {{ $balance->medicine?->packaging_specification ?: '—' }}</div>
<table class="summary"><tr><td>Tổng nhập<b>{{ number_format($total_received,0,',','.') }} {{ $balance->medicine?->unit }}</b></td><td>Tổng xuất<b>{{ number_format($total_issued,0,',','.') }} {{ $balance->medicine?->unit }}</b></td><td>Tồn hiện tại<b>{{ number_format($total_quantity_on_hand,0,',','.') }} {{ $balance->medicine?->unit }}</b></td><td>Số lô<b>{{ $balances->count() }}</b></td></tr></table>
<div class="section">Tồn theo lô / hạn dùng</div>
<table class="lots"><thead><tr><th>Số lô</th><th>Hạn dùng</th><th class="right">Tồn hiện tại</th></tr></thead><tbody>@foreach($balances as $lot)<tr><td>{{ $lot->batch_number }}</td><td class="center">{{ $lot->expiry_date?->format('d/m/Y') }}</td><td class="right">{{ $q($lot->quantity_on_hand) }} {{ $balance->medicine?->unit }}</td></tr>@endforeach</tbody></table>
<div class="section">Thẻ kho sản phẩm</div>
<table class="ledger"><thead><tr><th>Thời điểm</th><th>Chứng từ</th><th>Nghiệp vụ</th><th>Lô</th><th>HSD</th><th class="right">Nhập</th><th class="right">Xuất</th><th class="right">Tồn lô sau GD</th></tr></thead><tbody>
@foreach($movements as $movement) @php($in=$movement['quantity_delta']>0) @php($out=$movement['quantity_delta']<0)
<tr><td>{{ $movement['created_at']?->format('d/m/Y H:i') }}</td><td>{{ $movement['source']['number'] ?? '—' }}</td><td>{{ $labels[$movement['type']] ?? $movement['type'] }}</td><td>{{ $movement['batch_number'] }}</td><td>{{ $movement['expiry_date']?->format('d/m/Y') }}</td><td class="right">{{ $in ? $q($movement['quantity_delta']) : '—' }}</td><td class="right">{{ $out ? $q(abs($movement['quantity_delta'])) : '—' }}</td><td class="right">{{ $q($movement['balance_after']) }}</td></tr>
@endforeach
</tbody></table>
<div class="footer">Thẻ kho {{ $balance->medicine?->name }} · Xuất từ dữ liệu tồn kho hiệu lực</div>
</body></html>