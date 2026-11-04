@extends('ClientPortal::layouts.application')
@section('title', 'Cấu hình Excel kho')
@section('content')
@php
    $back = $report === 'stock' ? 'client.pharma.inventory' : 'client.pharma.inventory.movements';
    $values = $profile?->columns ?? array_keys($columns);
    $settings = $profile?->settings ?? [];
@endphp
<div class="fixed inset-0 z-40 overflow-hidden bg-slate-950/60 p-3 sm:p-6" id="inventory-excel-designer">
    <div class="mx-auto flex h-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <header class="flex shrink-0 items-center justify-between border-b px-5 py-4">
            <div><p class="text-[10px] font-bold uppercase tracking-widest text-indigo-600">Excel Designer · Pharma Inventory</p>
                <h1 class="text-xl font-extrabold text-slate-900">Bố cục xuất {{ $report === 'stock' ? 'Tồn kho' : 'Xuất – Nhập – Tồn' }}</h1>
                <p class="text-xs text-slate-500">Thiết kế dữ liệu, thông tin doanh nghiệp và trang in.</p>
            </div>
            <a href="{{ route($back) }}" aria-label="Đóng cấu hình" class="rounded-xl border px-4 py-2 text-sm font-semibold hover:bg-slate-50">✕</a>
        </header>
        <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50 px-4 py-4 sm:px-6">
    @if(session('status'))<div class="rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded-xl bg-rose-50 p-3 text-rose-800">{{ $errors->first() }}</div>@endif
    <div class="flex flex-wrap items-center gap-3 rounded-xl border bg-white p-3 shadow-sm">
        <label class="text-sm font-semibold">Chọn mẫu
            <select onchange="window.location.href=this.value" class="ml-2 rounded-lg border p-2">
                <option value="{{ route('client.pharma.inventory.excel-config', ['report'=>$report]) }}">Mẫu mặc định / Tạo mới</option>
                @foreach($profiles as $item)
                    <option value="{{ route('client.pharma.inventory.excel-config', ['report'=>$report,'profile_id'=>$item->id]) }}" @selected($profile?->id === $item->id)>{{ $item->name }} {{ $item->is_default ? '★' : '' }}</option>
                @endforeach
            </select>
        </label>
        <a href="{{ route('client.pharma.inventory.excel-config', ['report'=>$report,'profile_id'=>0]) }}" class="rounded-lg border px-3 py-2 text-sm">+ Tạo mẫu mới</a>
        @if($profile)
            <form method="POST" action="{{ route('client.pharma.inventory.excel-config.delete', ['report'=>$report,'profile'=>$profile->id]) }}" onsubmit="return confirm('Xóa mẫu Excel này?')">@csrf @method('DELETE')<button class="rounded-lg border border-rose-300 px-3 py-2 text-sm text-rose-700">Xóa mẫu</button></form>
        @endif
    </div>
    <form id="inventory-excel-form" method="POST" action="{{ route('client.pharma.inventory.excel-config.save', ['report'=>$report]) }}" class="space-y-4">
        @csrf
        @if($profile)<input type="hidden" name="profile_id" value="{{ $profile->id }}">@endif
        <div class="grid gap-4 lg:grid-cols-[180px_minmax(0,1fr)]"><aside class="lg:sticky lg:top-0 lg:self-start rounded-xl border bg-white p-3"><p class="mb-3 text-xs font-bold uppercase text-slate-400">Bố cục xuất</p><nav class="grid gap-2 text-sm font-semibold"><a href="#excel-brand" class="rounded-lg border px-3 py-2 text-indigo-700 hover:bg-indigo-50">1 · Thương hiệu</a><a href="#excel-columns" class="rounded-lg border px-3 py-2 hover:bg-indigo-50">2 · Cột dữ liệu</a><a href="#excel-print" class="rounded-lg border px-3 py-2 hover:bg-indigo-50">3 · Trang in</a></nav></aside><div class="space-y-4 min-w-0"><section id="excel-print" class="grid gap-4 rounded-2xl border bg-white p-5 md:grid-cols-2">
            <label class="text-sm font-semibold">Tên cấu hình *<input required name="name" value="{{ old('name', $profile?->name ?? 'Mẫu báo cáo kho') }}" class="mt-1 w-full rounded-lg border p-2"></label>
            <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $profile?->is_default ?? true))> Đặt làm mẫu mặc định</label>
            <label class="text-sm">Tên công ty<input name="settings[company_name]" value="{{ $settings['company_name'] ?? '' }}" class="mt-1 w-full rounded-lg border p-2"></label>
            <label class="text-sm">Tiêu đề báo cáo<input name="settings[title]" value="{{ $settings['title'] ?? ($report === 'stock' ? 'BÁO CÁO TỒN KHO' : 'BÁO CÁO XUẤT NHẬP TỒN') }}" class="mt-1 w-full rounded-lg border p-2"></label>
            <label class="text-sm">Khổ giấy<select name="settings[paper_size]" class="mt-1 w-full rounded-lg border p-2">@foreach(['A4','A3','LETTER'] as $v)<option @selected(($settings['paper_size'] ?? 'A4') === $v)>{{ $v }}</option>@endforeach</select></label>
            <label class="text-sm">Hướng giấy<select name="settings[orientation]" class="mt-1 w-full rounded-lg border p-2"><option value="landscape" @selected(($settings['orientation'] ?? 'landscape') === 'landscape')>Ngang</option><option value="portrait" @selected(($settings['orientation'] ?? '') === 'portrait')>Dọc</option></select></label>
            <label class="text-sm">Font<select name="settings[font_family]" class="mt-1 w-full rounded-lg border p-2">@foreach(['Times New Roman','Arial','Calibri'] as $v)<option @selected(($settings['font_family'] ?? 'Times New Roman') === $v)>{{ $v }}</option>@endforeach</select></label>
        </section>
        <section id="excel-brand" class="rounded-2xl border bg-white p-5">
            <h2 class="mb-3 font-bold">Thương hiệu & nội dung · Header / Thông tin doanh nghiệp</h2>
            <label class="mb-4 flex items-center gap-2 text-sm"><input type="checkbox" name="settings[report_header][enabled]" value="1" @checked(old('settings.report_header.enabled', $settings['report_header']['enabled'] ?? true))> Hiển thị thông tin doanh nghiệp phía trên tiêu đề báo cáo</label>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach(['company_name'=>'Tên doanh nghiệp','address'=>'Địa chỉ','office'=>'Văn phòng đại diện','tax_code'=>'Mã số thuế','phone'=>'Điện thoại','email'=>'Email','recipient'=>'Kính gửi','intro'=>'Nội dung mở đầu'] as $field=>$label)
                    <label class="text-sm font-semibold">{{ $label }}
                        <input name="settings[report_header][{{ $field }}]" value="{{ old('settings.report_header.'.$field, $settings['report_header'][$field] ?? ($field === 'company_name' ? ($settings['company_name'] ?? '') : '')) }}" class="mt-1 w-full rounded-lg border p-2 text-sm">
                    </label>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-slate-500">Các thông tin này sẽ xuất phía trên tiêu đề báo cáo. Logo và Header/Footer nâng cao sẽ được hoàn thiện trong bước đồng bộ Designer V4.</p>
        </section>
        <section class="rounded-2xl border bg-white p-5">
            <h2 class="mb-3 font-bold">Footer sau bảng dữ liệu</h2>
            <label class="mb-3 flex items-center gap-2 text-sm"><input type="checkbox" name="settings[report_footer][enabled]" value="1" @checked(old('settings.report_footer.enabled', $settings['report_footer']['enabled'] ?? false))> Bật Footer tùy chỉnh</label>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach(['date_text'=>'Ngày, địa điểm ký','signer_title'=>'Chức danh','signer_name'=>'Người ký'] as $field=>$label)
                    <label class="text-sm font-semibold">{{ $label }}<input name="settings[report_footer][{{ $field }}]" value="{{ old('settings.report_footer.'.$field, $settings['report_footer'][$field] ?? '') }}" class="mt-1 w-full rounded-lg border p-2 text-sm"></label>
                @endforeach
            </div>
        </section>
        <section class="rounded-2xl border bg-white p-5">
            <h2 class="mb-3 font-bold">Trang in · Kiểu chữ &amp; lề trang</h2>
            <div class="grid gap-3 md:grid-cols-2">
                <label class="text-sm">Cỡ chữ tiêu đề cột<input type="number" min="8" max="24" name="settings[header_font_size]" value="{{ old('settings.header_font_size', $settings['header_font_size'] ?? 12) }}" class="mt-1 w-full rounded-lg border p-2"></label>
                <label class="text-sm">Cỡ chữ dữ liệu<input type="number" min="8" max="24" name="settings[body_font_size]" value="{{ old('settings.body_font_size', $settings['body_font_size'] ?? 11) }}" class="mt-1 w-full rounded-lg border p-2"></label>
                @foreach(['margin_top'=>'Lề trên (cm)','margin_bottom'=>'Lề dưới (cm)','margin_left'=>'Lề trái (cm)','margin_right'=>'Lề phải (cm)'] as $key=>$label)
                    <label class="text-sm">{{ $label }}<input type="number" step="0.1" min="0" max="5" name="settings[{{ $key }}]" value="{{ old('settings.'.$key, $settings[$key] ?? 0.8) }}" class="mt-1 w-full rounded-lg border p-2"></label>
                @endforeach
                <label class="text-sm">Header trang in (mã Excel: &amp;P số trang)<input name="settings[print_header]" value="{{ old('settings.print_header', $settings['print_header'] ?? '') }}" class="mt-1 w-full rounded-lg border p-2"></label>
                <label class="text-sm">Footer trang in<input name="settings[print_footer]" value="{{ old('settings.print_footer', $settings['print_footer'] ?? '') }}" class="mt-1 w-full rounded-lg border p-2"></label>
            </div>
            <label class="mt-4 flex items-center gap-2 text-sm"><input type="checkbox" name="settings[body_border]" value="1" @checked(old('settings.body_border', $settings['body_border'] ?? true))> Viền mảnh bảng dữ liệu</label>
            <p class="mt-2 text-xs text-slate-500">Excel tự động lặp tiêu đề cột trên mỗi trang và căn vừa chiều ngang giấy. Các ô tiêu đề và dữ liệu được Wrap Text.</p>
        </section>
        <section id="excel-columns" class="rounded-2xl border bg-white p-5">
            <h2 class="mb-3 font-bold">Cột dữ liệu · Chọn, đặt tên, căn lề và độ rộng</h2>
            <p class="mb-4 text-xs text-slate-500">Chọn ít nhất một cột. Độ rộng nhập theo pixel và được giữ nguyên khi xuất XLSX. Thứ tự xuất hiện theo danh sách bên dưới.</p>
            <div class="space-y-2">
                @foreach($columns as $key=>$label)
                    <div class="grid grid-cols-[auto_minmax(100px,1fr)] items-center gap-3 rounded-lg border p-3 lg:grid-cols-[auto_1fr_1fr_100px_120px]">
                        <input type="checkbox" name="columns[]" value="{{ $key }}" @checked(in_array($key, old('columns', $values))) aria-label="Xuất {{ $label }}">
                        <span class="text-sm font-semibold">{{ $label }}</span>
                        <input name="headers[{{ $key }}]" value="{{ old('headers.'.$key, $profile?->headers[$key] ?? $label) }}" class="rounded-lg border p-2 text-sm" aria-label="Tên cột {{ $label }}">
                        <input type="number" min="40" max="400" name="widths[{{ $key }}]" value="{{ $profile?->widths[$key] ?? 130 }}" class="w-full rounded-lg border p-2 text-sm" aria-label="Độ rộng {{ $label }}">
                        <select name="alignments[{{ $key }}]" class="rounded-lg border p-2 text-sm" aria-label="Căn lề {{ $label }}">
                            @foreach(['left'=>'Trái','center'=>'Giữa','right'=>'Phải'] as $v=>$text)<option value="{{ $v }}" @selected(($profile?->alignments[$key] ?? 'left') === $v)>{{ $text }}</option>@endforeach
                        </select>
                    </div>
                @endforeach
            </div>
        </section>
        </div></div>
    </form>
        </div>
        <footer class="flex shrink-0 items-center justify-between gap-3 border-t bg-white px-5 py-3"><span class="text-xs text-slate-500">Mẫu cấu hình dành riêng cho từng loại báo cáo</span><div class="flex gap-2"><a href="{{ route($back) }}" class="rounded-lg border px-4 py-2 text-sm font-semibold">Hủy</a><button type="submit" form="inventory-excel-form" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-bold text-white">Lưu cấu hình</button></div></footer>
    </div>
</div>
@endsection
