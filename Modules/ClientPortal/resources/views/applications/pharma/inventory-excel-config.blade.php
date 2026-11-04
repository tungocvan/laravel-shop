@extends('ClientPortal::layouts.application')
@section('title', 'Cấu hình Excel kho')
@section('content')
@php
    $back = $report === 'stock' ? 'client.pharma.inventory' : 'client.pharma.inventory.movements';
    $values = $profile?->columns ?? array_keys($columns);
    $settings = $profile?->settings ?? [];
@endphp
<div class="mx-auto max-w-6xl space-y-4 px-3 py-4">
    <a href="{{ route($back) }}" class="text-sm font-semibold text-indigo-700">← Quay về báo cáo</a>
    <div class="rounded-2xl bg-slate-950 p-5 text-white">
        <p class="text-xs uppercase tracking-wider text-indigo-200">Excel Designer · Pharma Inventory</p>
        <h1 class="mt-2 text-2xl font-bold">Cấu hình Excel · {{ $report === 'stock' ? 'Tồn kho' : 'Xuất – Nhập – Tồn' }}</h1>
        <p class="mt-1 text-sm text-slate-300">Các mẫu được lưu riêng cho tài khoản và loại báo cáo.</p>
    </div>
    @if(session('status'))<div class="rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded-xl bg-rose-50 p-3 text-rose-800">{{ $errors->first() }}</div>@endif
    <div class="flex flex-wrap items-center gap-3 rounded-xl border bg-white p-3">
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
    <form method="POST" action="{{ route('client.pharma.inventory.excel-config.save', ['report'=>$report]) }}" class="space-y-4">
        @csrf
        @if($profile)<input type="hidden" name="profile_id" value="{{ $profile->id }}">@endif
        <section class="grid gap-4 rounded-2xl border bg-white p-5 md:grid-cols-2">
            <label class="text-sm font-semibold">Tên cấu hình *<input required name="name" value="{{ old('name', $profile?->name ?? 'Mẫu báo cáo kho') }}" class="mt-1 w-full rounded-lg border p-2"></label>
            <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $profile?->is_default ?? true))> Đặt làm mẫu mặc định</label>
            <label class="text-sm">Tên công ty<input name="settings[company_name]" value="{{ $settings['company_name'] ?? '' }}" class="mt-1 w-full rounded-lg border p-2"></label>
            <label class="text-sm">Tiêu đề báo cáo<input name="settings[title]" value="{{ $settings['title'] ?? ($report === 'stock' ? 'BÁO CÁO TỒN KHO' : 'BÁO CÁO XUẤT NHẬP TỒN') }}" class="mt-1 w-full rounded-lg border p-2"></label>
            <label class="text-sm">Khổ giấy<select name="settings[paper_size]" class="mt-1 w-full rounded-lg border p-2">@foreach(['A4','A3','LETTER'] as $v)<option @selected(($settings['paper_size'] ?? 'A4') === $v)>{{ $v }}</option>@endforeach</select></label>
            <label class="text-sm">Hướng giấy<select name="settings[orientation]" class="mt-1 w-full rounded-lg border p-2"><option value="landscape" @selected(($settings['orientation'] ?? 'landscape') === 'landscape')>Ngang</option><option value="portrait" @selected(($settings['orientation'] ?? '') === 'portrait')>Dọc</option></select></label>
            <label class="text-sm">Font<select name="settings[font_family]" class="mt-1 w-full rounded-lg border p-2">@foreach(['Times New Roman','Arial','Calibri'] as $v)<option @selected(($settings['font_family'] ?? 'Times New Roman') === $v)>{{ $v }}</option>@endforeach</select></label>
        </section>
        <section class="rounded-2xl border bg-white p-5">
            <h2 class="mb-3 font-bold">Header / Thông tin doanh nghiệp</h2>
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
            <h2 class="mb-3 font-bold">Cột dữ liệu · Chọn, đặt tên, căn lề và độ rộng</h2>
            <p class="mb-4 text-xs text-slate-500">Chọn ít nhất một cột. Thứ tự xuất hiện theo danh sách bên dưới.</p>
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
        <div class="sticky bottom-2 flex justify-end rounded-xl border bg-white p-3 shadow-lg"><button class="rounded-xl bg-indigo-600 px-6 py-3 font-bold text-white">Lưu cấu hình Excel</button></div>
    </form>
</div>
@endsection
