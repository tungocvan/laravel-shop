<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $voucher->direction === 'receipt' ? 'Phiếu thu' : 'Phiếu chi' }} — {{ $voucher->number }}</title>
    <style>
        @page { size: {{ $printSettings['paper'] }} {{ $printSettings['orientation'] }}; margin: {{ $printSettings['margin_mm'] }}mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef1f5; color: #111827; font: {{ $printSettings['font_size'] }}pt/1.45 'DejaVu Sans', sans-serif; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; max-width: {{ $printSettings['paper'] === 'A5' ? '148mm' : '210mm' }}; margin: 12px auto; gap: 12px; }
        .toolbar button, .toolbar a { border: 1px solid #cbd5e1; background: white; color: #111827; border-radius: 6px; padding: 9px 14px; font: inherit; text-decoration: none; cursor: pointer; }
        .paper { position: relative; width: {{ $printSettings['paper'] === 'A5' ? '148mm' : '210mm' }}; min-height: {{ $printSettings['paper'] === 'A5' ? '210mm' : '297mm' }}; padding: {{ $printSettings['margin_mm'] }}mm; margin: auto; background: #fff; box-shadow: 0 4px 25px #0001; }
        .head { display: flex; justify-content: space-between; gap: 10px; }
        .head small { display: block; color: #475569; }
        h1 { text-align: center; font-size: 19px; letter-spacing: .06em; margin: 17px 0 2px; }
        .center { text-align: center; }
        .muted { color: #64748b; }
        .meta { text-align: right; font-size: 10px; }
        .line { display: flex; gap: 7px; margin: 9px 0; }
        .line .label { flex: 0 0 auto; color: #475569; }
        .line .value { flex: 1; min-width: 0; overflow-wrap: anywhere; border-bottom: 1px dotted #cbd5e1; font-weight: 600; }
        .amount { border: 1px solid #cbd5e1; padding: 9px; margin: 15px 0 8px; border-radius: 5px; }
        .amount strong { font-size: 16px; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-top: 26px; text-align: center; }
        .signatures strong { display: block; }
        .signatures small { display: block; color: #64748b; }
        .signature-space { height: 45px; }
        .stamp { position: absolute; top: 36%; left: 12%; right: 12%; text-align: center; transform: rotate(-25deg); font-size: 35px; font-weight: 800; letter-spacing: 2px; color: rgba(160, 55, 55, .15); pointer-events: none; }
        .footer { margin-top: 18px; font-size: 9px; color: #64748b; }
        @media print {
            html, body { width: auto; background: white; margin: 0; }
            .toolbar { display: none !important; }
            .paper { width: auto; min-height: 0; padding: 0; margin: 0; box-shadow: none; }
        }
        @media screen and (max-width: 560px) {
            .toolbar { padding: 0 12px; }
            .paper { width: 100%; min-height: 0; padding: 20px 15px; }
        }
    </style>
</head>
<body>
    <nav class="toolbar" aria-label="Thao tác in">
        <a href="{{ route('admin.finance.index') }}">← Sổ chứng từ</a>
        <div><a href="{{ route('admin.finance.vouchers.pdf', ['voucher' => $voucher->id]) }}">Tải PDF</a> <button type="button" onclick="window.print()">In phiếu {{ $printSettings['paper'] }}</button></div>
    </nav>
    <main class="paper">
        @if($voucher->status === 'draft')
            <div class="stamp">BẢN NHÁP</div>
        @elseif($voucher->status === 'reversed')
            <div class="stamp">ĐÃ HOÀN TÁC</div>
        @elseif($voucher->status === 'cancelled')
            <div class="stamp">ĐÃ HỦY</div>
        @endif
        <div class="head">
            <div>
                <strong>{{ $printSettings['organization'] }}</strong>
                <small>{{ $printSettings['subtitle'] }}</small>
            </div>
            <div class="meta">
                <strong>Số: {{ $voucher->number }}</strong><br>
                Trạng thái: {{ match($voucher->status) { 'posted' => 'Đã ghi sổ', 'draft' => 'Bản nháp', 'reversed' => 'Đã hoàn tác', 'cancelled' => 'Đã hủy', default => 'Không xác định' } }}
            </div>
        </div>
        <h1>{{ $voucher->direction === 'receipt' ? 'PHIẾU THU' : 'PHIẾU CHI' }}</h1>
        <div class="center muted">Ngày {{ \Illuminate\Support\Carbon::parse($voucher->voucher_date)->format('d/m/Y') }}</div>
        <div style="margin-top: 22px">
            <div class="line"><span class="label">{{ $voucher->direction === 'receipt' ? 'Người nộp tiền:' : 'Người nhận tiền:' }}</span><span class="value">{{ $voucher->origin === 'manual' ? $voucher->counterparty : $voucher->source_counterparty }}</span></div>
            <div class="line"><span class="label">Nội dung:</span><span class="value">{{ $voucher->origin === 'manual' ? $voucher->description : 'Thanh toán chứng từ '.$voucher->source_document_code }}</span></div>
            @if($voucher->origin === 'manual')
                <div class="line"><span class="label">Nghiệp vụ:</span><span class="value">{{ $voucher->category_name ?: $voucher->manual_category }}</span></div>
            @endif
            <div class="line"><span class="label">Tài khoản:</span><span class="value">{{ $voucher->account_name }} ({{ $voucher->account_code }})</span></div>
            <div class="line"><span class="label">Hình thức:</span><span class="value">{{ $voucher->account_kind === 'bank' ? 'Chuyển khoản' : 'Tiền mặt' }}</span></div>
            @if($printSettings['show_bank'] && $voucher->account_kind === 'bank')
                <div class="line"><span class="label">Ngân hàng:</span><span class="value">{{ $voucher->bank_name ?: '—' }}{{ $voucher->bank_account_number ? ' · '.$voucher->bank_account_number : '' }}</span></div>
            @endif
            @if($printSettings['show_reference'] && ($voucher->reference_number || $voucher->source_document_code))
                <div class="line"><span class="label">Tham chiếu:</span><span class="value">{{ $voucher->reference_number ?: $voucher->source_document_code }}</span></div>
            @endif
        </div>
        <div class="amount">
            <div>Số tiền: <strong>{{ number_format((float) $voucher->amount, 0, ',', '.') }} {{ $voucher->currency }}</strong></div>
            <div style="margin-top: 5px">Bằng chữ: <strong>{{ $amountInWords }}</strong></div>
        </div>
        @if($printSettings['show_signatures'])
        <div class="signatures">
            <div><strong>{{ $printSettings['signature_creator'] }}</strong><small>(Ký, ghi rõ họ tên)</small><div class="signature-space"></div></div>
            <div><strong>{{ $voucher->direction === 'receipt' ? 'Người nộp tiền' : 'Người nhận tiền' }}</strong><small>(Ký, ghi rõ họ tên)</small><div class="signature-space"></div></div>
            <div><strong>{{ $voucher->account_kind === 'cash' ? 'Thủ quỹ' : $printSettings['signature_approver'] }}</strong><small>(Ký, ghi rõ họ tên)</small><div class="signature-space"></div></div>
        </div>
        @endif
        <p class="footer">Mẫu chứng từ nội bộ · {{ $printSettings['paper'] }} {{ $printSettings['orientation'] === 'portrait' ? 'dọc' : 'ngang' }} · Việc in không làm thay đổi trạng thái hoặc số dư kế toán.</p>
    </main>
</body>
</html>
