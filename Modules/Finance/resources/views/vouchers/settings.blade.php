<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cấu hình mẫu in Finance</title>
<style>body{font:15px/1.5 Arial,sans-serif;background:#f3f6fa;color:#172033;margin:0}main{max-width:850px;margin:30px auto;padding:24px;background:#fff;border:1px solid #dce2eb;border-radius:12px}h1{margin-top:0}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}label{display:block;font-weight:600;font-size:14px}input,select{display:block;width:100%;box-sizing:border-box;margin-top:5px;padding:11px;border:1px solid #bfc8d5;border-radius:7px;background:#fff}input[type=checkbox]{display:inline;width:auto;margin-right:8px}button,.link{display:inline-block;background:#4f46e5;color:white;padding:10px 16px;border:0;border-radius:7px;text-decoration:none;cursor:pointer}.link{background:#e9edf4;color:#172033}.actions{display:flex;gap:10px;margin-top:24px}.errors{color:#b91c1c}.ok{color:#047857}@media(max-width:650px){main{margin:0;border-radius:0;padding:16px}.grid{grid-template-columns:1fr}}</style>
</head><body><main>
<a class="link" href="{{ route('admin.finance.index') }}">← Quay lại Finance</a>
<h1>Cấu hình mẫu phiếu thu / chi</h1>
<p>Thiết lập áp dụng cho cả bản xem trước, bản in và PDF tải về. Mặc định A5 dọc.</p>
@if(session('finance_success'))<p class="ok" role="status">{{ session('finance_success') }}</p>@endif
@if($errors->any())<div class="errors" role="alert">{{ implode(' · ', $errors->all()) }}</div>@endif
<form method="post" action="{{ route('admin.finance.print.settings.update') }}">@csrf
<div class="grid">
<label>Tên đơn vị<input name="organization" required maxlength="150" value="{{ old('organization', $settings['organization']) }}"></label>
<label>Dòng mô tả<input name="subtitle" required maxlength="180" value="{{ old('subtitle', $settings['subtitle']) }}"></label>
<label>Khổ giấy<select name="paper"><option value="A5" @selected(old('paper', $settings['paper']) === 'A5')>A5</option><option value="A4" @selected(old('paper', $settings['paper']) === 'A4')>A4</option></select></label>
<label>Hướng giấy<select name="orientation"><option value="portrait" @selected(old('orientation', $settings['orientation']) === 'portrait')>Dọc</option><option value="landscape" @selected(old('orientation', $settings['orientation']) === 'landscape')>Ngang</option></select></label>
<label>Lề giấy (mm)<input type="number" min="5" max="20" name="margin_mm" required value="{{ old('margin_mm', $settings['margin_mm']) }}"></label>
<label>Cỡ chữ (pt)<input type="number" min="9" max="14" name="font_size" required value="{{ old('font_size', $settings['font_size']) }}"></label>
<label>Chữ ký người lập<input name="signature_creator" required maxlength="80" value="{{ old('signature_creator', $settings['signature_creator']) }}"></label>
<label>Chữ ký người duyệt<input name="signature_approver" required maxlength="80" value="{{ old('signature_approver', $settings['signature_approver']) }}"></label>
</div>
<p><label><input type="checkbox" name="show_reference" value="1" @checked(old('show_reference', $settings['show_reference']))>Hiện mã tham chiếu</label></p>
<p><label><input type="checkbox" name="show_bank" value="1" @checked(old('show_bank', $settings['show_bank']))>Hiện thông tin ngân hàng</label></p>
<p><label><input type="checkbox" name="show_signatures" value="1" @checked(old('show_signatures', $settings['show_signatures']))>Hiện khu vực chữ ký</label></p>
<div class="actions"><button type="submit">Lưu cấu hình</button><a class="link" href="{{ route('admin.finance.index') }}">Hủy</a></div>
</form></main></body></html>
