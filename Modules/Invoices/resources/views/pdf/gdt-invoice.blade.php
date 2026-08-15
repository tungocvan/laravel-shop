<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Hóa đơn {{ $detail['shdon'] ?? $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 24px; }
        h1, h2, p { margin: 0; }
        .center { text-align: center; }
        .muted { color: #6b7280; }
        .mt-4 { margin-top: 16px; }
        .mt-2 { margin-top: 8px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { vertical-align: top; padding: 3px 0; }
        .label { width: 150px; font-weight: 700; }
        .box { border: 1px solid #d1d5db; padding: 10px; margin-top: 12px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.items th, table.items td { border: 1px solid #9ca3af; padding: 6px; }
        table.items th { background: #f3f4f6; font-size: 10px; }
        .right { text-align: right; }
        .total { width: 55%; margin-left: auto; margin-top: 12px; border-collapse: collapse; }
        .total td { padding: 4px 0; }
        .total .value { text-align: right; font-weight: 700; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #d1d5db; font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="center">
        <h1>HÓA ĐƠN GIÁ TRỊ GIA TĂNG</h1>
        <p class="mt-2">Bản thể hiện từ dữ liệu tra cứu của Tổng cục Thuế</p>
        <p class="muted mt-2">Ngày lập: {{ isset($detail['tdlap']) ? \Carbon\Carbon::parse($detail['tdlap'])->timezone(config('app.timezone'))->format('d/m/Y') : '-' }}</p>
    </div>

    <div class="box">
        <table class="grid">
            <tr><td class="label">Mẫu số</td><td>{{ $detail['khmshdon'] ?? '-' }}</td><td class="label">Ký hiệu</td><td>{{ $detail['khhdon'] ?? '-' }}</td></tr>
            <tr><td class="label">Số hóa đơn</td><td>{{ $detail['shdon'] ?? '-' }}</td><td class="label">Mã tra cứu</td><td>{{ $invoice->lookup_code ?: '-' }}</td></tr>
            <tr><td class="label">Loại hóa đơn</td><td colspan="3">{{ $detail['thdon'] ?? $detail['tlhdon'] ?? '-' }}</td></tr>
        </table>
    </div>

    <div class="box">
        <h2>Người bán</h2>
        <table class="grid mt-2">
            <tr><td class="label">Tên đơn vị</td><td>{{ $detail['nbten'] ?? '-' }}</td></tr>
            <tr><td class="label">Mã số thuế</td><td>{{ $detail['nbmst'] ?? '-' }}</td></tr>
            <tr><td class="label">Địa chỉ</td><td>{{ $detail['nbdchi'] ?? '-' }}</td></tr>
            <tr><td class="label">Tài khoản</td><td>{{ $detail['nbstkhoan'] ?? '-' }}</td></tr>
            <tr><td class="label">Ngân hàng</td><td>{{ $detail['nbtnhang'] ?? '-' }}</td></tr>
        </table>
    </div>

    <div class="box">
        <h2>Người mua</h2>
        <table class="grid mt-2">
            <tr><td class="label">Tên đơn vị</td><td>{{ $detail['nmten'] ?? '-' }}</td></tr>
            <tr><td class="label">Mã số thuế</td><td>{{ $detail['nmmst'] ?? '-' }}</td></tr>
            <tr><td class="label">Địa chỉ</td><td>{{ $detail['nmdchi'] ?? '-' }}</td></tr>
            <tr><td class="label">Hình thức thanh toán</td><td>{{ $detail['thtttoan'] ?? '-' }}</td></tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 32px;">STT</th>
                <th>Tên hàng hóa, dịch vụ</th>
                <th style="width: 65px;">ĐVT</th>
                <th style="width: 65px;">SL</th>
                <th style="width: 90px;">Đơn giá</th>
                <th style="width: 70px;">Thuế</th>
                <th style="width: 100px;">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($detail['hdhhdvu'] ?? []) as $item)
                <tr>
                    <td class="center">{{ $item['stt'] ?? $loop->iteration }}</td>
                    <td>{{ $item['ten'] ?? '-' }}</td>
                    <td class="center">{{ $item['dvtinh'] ?? '-' }}</td>
                    <td class="right">{{ isset($item['sluong']) ? number_format((float) $item['sluong'], 2, ',', '.') : '-' }}</td>
                    <td class="right">{{ isset($item['dgia']) ? number_format((float) $item['dgia'], 2, ',', '.') : '-' }}</td>
                    <td class="center">{{ $item['ltsuat'] ?? (isset($item['tsuat']) ? rtrim(rtrim(number_format((float) $item['tsuat'] * 100, 2, '.', ''), '0'), '.').'%' : '-') }}</td>
                    <td class="right">{{ isset($item['thtien']) ? number_format((float) $item['thtien'], 0, ',', '.') : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center muted">Không có chi tiết hàng hóa.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="total">
        <tr><td>Tổng tiền trước thuế</td><td class="value">{{ number_format((float) ($detail['tgtcthue'] ?? 0), 0, ',', '.') }} đ</td></tr>
        <tr><td>Tiền thuế GTGT</td><td class="value">{{ number_format((float) ($detail['tgtthue'] ?? 0), 0, ',', '.') }} đ</td></tr>
        <tr><td>Tổng thanh toán</td><td class="value">{{ number_format((float) ($detail['tgtttbso'] ?? 0), 0, ',', '.') }} đ</td></tr>
    </table>

    @if (! empty($detail['tgtttbchu']))
        <p class="mt-2"><strong>Số tiền bằng chữ:</strong> {{ $detail['tgtttbchu'] }}</p>
    @endif

    <div class="footer">
        <p>Mã dữ liệu hóa đơn: {{ $detail['mhdon'] ?? '-' }}</p>
        <p>Mã thông điệp đối chiếu: {{ $detail['mtdtchieu'] ?? '-' }}</p>
        <p>Thời điểm ký: {{ $detail['nky'] ?? '-' }}</p>
        <p class="mt-2">Tài liệu này được hệ thống tạo lại từ dữ liệu chi tiết trả về bởi API tra cứu hóa đơn GDT; không phải file PDF do GDT cung cấp trực tiếp.</p>
    </div>
</body>
</html>
