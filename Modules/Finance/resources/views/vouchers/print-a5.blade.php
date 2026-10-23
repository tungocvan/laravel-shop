<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $voucher->direction === 'receipt' ? 'Phiếu thu' : 'Phiếu chi' }} — {{ $voucher->number }}</title>
    <style>
        @php
            $landscape = $printSettings['orientation'] === 'landscape';
            $paperWidth = $printSettings['paper'] === 'A5' ? ($landscape ? 210 : 148) : ($landscape ? 297 : 210);
            $paperHeight = $printSettings['paper'] === 'A5' ? ($landscape ? 148 : 210) : ($landscape ? 210 : 297);
        @endphp
        @page { size: {{ $printSettings['paper'] }} {{ $printSettings['orientation'] }}; margin: {{ $printSettings['margin_mm'] }}mm; }
        * { box-sizing: border-box; }
        html, body, div, span, strong, small, h1, p, main, table, td { font-family: 'DejaVu Sans', 'DejaVuSans', sans-serif !important; }
        strong, b, h1 { font-weight: 700; }
        html, body { margin: 0; padding: 0; }
        body { background: #eef1f5; color: #111827; font-size: {{ $printSettings['font_size'] }}pt; line-height: 1.35; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; max-width: {{ $paperWidth }}mm; margin: 12px auto; gap: 12px; }
        .toolbar button, .toolbar a { border: 1px solid #cbd5e1; background: white; color: #111827; border-radius: 6px; padding: 9px 14px; font: inherit; text-decoration: none; cursor: pointer; }
        .paper { position: relative; width: {{ $paperWidth }}mm; min-height: {{ $paperHeight }}mm; padding: {{ $printSettings['margin_mm'] }}mm; margin: auto; background: #fff; box-shadow: 0 4px 25px #0001; }
        .head { display: table; width: 100%; table-layout: fixed; }
        .head > div { display: table-cell; width: 50%; vertical-align: top; }
        .head small { display: block; color: #475569; font-size: 8pt; }
        .meta { text-align: right; font-size: 8pt; overflow-wrap: anywhere; }
        h1 { text-align: center; font-size: 17pt; letter-spacing: .03em; margin: 13px 0 3px; }
        .center { text-align: center; }
        .muted { color: #64748b; }
        .details { margin-top: 13px; }
        .line { display: table; table-layout: fixed; width: 100%; margin: 0 0 7px; }
        .line .label { display: table-cell; width: 32%; vertical-align: top; color: #475569; }
        .line .value { display: table-cell; width: 68%; vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; border-bottom: 1px dotted #cbd5e1; font-weight: 600; }
        .amount { border: 1px solid #cbd5e1; padding: 9px; margin: 12px 0 8px; border-radius: 4px; page-break-inside: avoid; }
        .amount strong { font-size: 13pt; }
        .signatures { display: table; table-layout: fixed; width: 100%; margin-top: 16px; text-align: center; page-break-inside: avoid; }
        .signatures > div { display: table-cell; width: 33.33%; vertical-align: top; padding: 0 3px; }
        .signatures strong { display: block; font-size: 8pt; }
        .signatures small { display: block; color: #64748b; font-size: 7pt; }
        .signature-space { height: 35px; }
        .stamp { position: absolute; top: 32%; left: 8%; right: 8%; text-align: center; transform: rotate(-25deg); font-size: 25pt; font-weight: 800; color: rgba(160,55,55,.16); }
        .footer { margin-top: 10px; font-size: 7pt; color: #64748b; }
        .pdf-export { background: white; }
        .pdf-export .paper { position: static; width: auto; min-height: 0; padding: 0; margin: 0; box-shadow: none; }
        .pdf-export .head, .pdf-export .line, .pdf-export .signatures { display: table; }
        .pdf-export .signatures > div { display: table-cell; }
        .pdf-export .amount, .pdf-export .signatures { page-break-inside: avoid; }
        @media print {
            html, body { background: white; margin: 0; padding: 0; }
            .toolbar { display: none !important; }
            .paper { width: auto; min-height: 0; padding: 0; margin: 0; box-shadow: none; }
            .amount, .signatures { page-break-inside: avoid; }
        }
        @media screen and (max-width: 560px) {
            .toolbar { padding: 0 12px; }
            .screen-preview .paper { max-width: 100%; min-height: {{ $paperHeight }}mm; padding: {{ $printSettings['margin_mm'] }}mm; }
        }
    </style>
</head>
<body class="{{ $pdfMode ? 'pdf-export' : 'screen-preview' }}">
    @unless($pdfMode)
    <nav class="toolbar" aria-label="Thao tác in">
        <a href="{{ route('admin.finance.index') }}">← Sổ chứng từ</a>
        <div><a href="{{ route('admin.finance.vouchers.pdf', ['voucher' => $voucher->id]) }}">Tải PDF</a> <button type="button" onclick="window.print()">In phiếu {{ $printSettings['paper'] }}</button></div>
    </nav>
    @endunless
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
        <div class="details">
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
