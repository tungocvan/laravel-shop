@extends('ClientPortal::layouts.application')

@section('title', 'Cấu hình Excel danh mục thuốc')
@section('app-name', 'Pharma')
@section('app-subtitle', 'Cấu hình mẫu Excel')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="mx-auto max-w-5xl space-y-5 p-3 sm:p-6">
    <a href="{{ route('client.pharma.products') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-indigo-700">← Danh mục thuốc</a>
    <h1 class="text-2xl font-black text-slate-950">Cấu hình Excel</h1>
    <p class="text-sm text-slate-600">Tạo mẫu xuất riêng cho tài khoản ClientPortal. Thứ tự cột được giữ theo danh sách chọn bên dưới.</p>
    @if(session('status')) <div role="status" class="rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div> @endif
    @if($errors->any()) <div role="alert" class="rounded-xl bg-red-50 p-3 text-red-700">{{ $errors->first() }}</div> @endif
    <form method="GET" action="{{ route('client.pharma.products.excel-config') }}" class="flex flex-wrap gap-2">
        <select name="profile_id" aria-label="Chọn mẫu Excel" class="min-h-11 flex-1 rounded-xl border border-slate-300 px-3">
            <option value="">Mẫu mặc định / tạo mới</option>
            @foreach($profiles as $item)
                <option value="{{ $item['id'] }}" @selected((int) ($profile['id'] ?? 0) === (int) $item['id'])>{{ $item['name'] }} {{ $item['is_default'] ? '★' : '' }}</option>
            @endforeach
        </select>
        <button class="min-h-11 rounded-xl border border-slate-300 px-4 font-bold">Mở mẫu</button>
        <a href="{{ route('client.pharma.products.excel-config') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 font-bold">Tạo mới</a>
    </form>
    <form method="POST" action="{{ route('client.pharma.products.excel-config.save') }}" class="space-y-5">
        @csrf
        @if($profile['id']) <input type="hidden" name="profile_id" value="{{ $profile['id'] }}"> @endif
        <section class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2">
            <label class="text-sm font-bold">Tên mẫu<input required maxlength="120" name="name" value="{{ old('name', $profile['name']) }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $profile['is_default']))> Đặt làm mẫu mặc định</label>
            <label class="text-sm">Tên công ty<input name="settings[company_name]" value="{{ old('settings.company_name', $profile['settings']['company_name']) }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
            <label class="text-sm">Tiêu đề báo cáo<input name="settings[title]" value="{{ old('settings.title', $profile['settings']['title']) }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
            <label class="text-sm">Font chữ<select name="settings[font_family]" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3">@foreach(['Times New Roman','Arial','Calibri'] as $font)<option @selected($profile['settings']['font_family'] === $font)>{{ $font }}</option>@endforeach</select></label>
            <label class="text-sm">Khổ giấy<select name="settings[paper_size]" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3">@foreach(['A4','A3','LETTER'] as $size)<option @selected($profile['settings']['paper_size'] === $size)>{{ $size }}</option>@endforeach</select></label>
            <label class="text-sm">Hướng giấy<select name="settings[orientation]" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3"><option value="landscape" @selected($profile['settings']['orientation'] === 'landscape')>Ngang</option><option value="portrait" @selected($profile['settings']['orientation'] === 'portrait')>Dọc</option></select></label>
            <label class="text-sm">Màu tiêu đề<select name="settings[header_fill]" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3">@foreach(['EFF4FA','F1F5F9','FFFFFF','EDE9FE'] as $color)<option @selected($profile['settings']['header_fill'] === $color)>{{ $color }}</option>@endforeach</select></label>
            <label class="text-sm">Cỡ chữ tiêu đề<input type="number" min="8" max="20" name="settings[header_font_size]" value="{{ $profile['settings']['header_font_size'] }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
            <label class="text-sm">Cỡ chữ dữ liệu<input type="number" min="8" max="20" name="settings[body_font_size]" value="{{ $profile['settings']['body_font_size'] }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="header_enabled" value="1" @checked($profile['settings']['header_enabled'])> Hiển thị tiêu đề</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="body_border" value="1" @checked($profile['settings']['body_border'])> Viền dữ liệu</label>
        </section>
        <section class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4">
            <h2 class="font-bold">Các cột xuất Excel</h2>
            <p class="text-xs text-slate-500">Chọn cột và kéo thả bằng nút lên/xuống để thay đổi thứ tự.</p>
            <div id="excel-column-list" class="space-y-2">
                @php
                    $orderedKeys = array_values(array_unique(array_merge(
                        array_values(array_intersect($profile['columns'], array_keys($availableColumns))),
                        array_keys($availableColumns)
                    )));
                @endphp
                @foreach($orderedKeys as $key)
                    @php $label = $availableColumns[$key]; @endphp
                    <div data-excel-column class="grid grid-cols-[auto_1fr_auto] items-center gap-2 rounded-xl border border-slate-200 p-2">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="columns[]" value="{{ $key }}" @checked(in_array($key, old('columns', $profile['columns'])))><span>{{ $label }}</span></label>
                        <input aria-label="Tiêu đề {{ $label }}" name="headers[{{ $key }}]" value="{{ $profile['headers'][$key] ?? $label }}" class="min-w-0 rounded-lg border border-slate-300 px-2 py-2 text-sm">
                        <div class="flex gap-1"><button type="button" data-move="-1" aria-label="Lên" class="rounded border px-2 py-2">↑</button><button type="button" data-move="1" aria-label="Xuống" class="rounded border px-2 py-2">↓</button></div>
                        <label class="col-span-2 text-xs">Rộng (px)<input type="number" name="widths[{{ $key }}]" min="40" max="400" value="{{ $profile['widths'][$key] ?? 130 }}" class="ml-2 w-20 rounded border px-2 py-1"></label>
                        <select name="alignments[{{ $key }}]" aria-label="Căn lề {{ $label }}" class="rounded border px-2 py-1 text-xs">@foreach(['left'=>'Trái','center'=>'Giữa','right'=>'Phải'] as $value=>$text)<option value="{{ $value }}" @selected(($profile['alignments'][$key] ?? 'left') === $value)>{{ $text }}</option>@endforeach</select>
                    </div>
                @endforeach
            </div>
        </section>
        <button class="min-h-11 rounded-xl bg-indigo-600 px-6 font-bold text-white">Lưu mẫu Excel</button>
    </form>
    @if($profile['id'])
        <form method="POST" action="{{ route('client.pharma.products.excel-config.delete', $profile['id']) }}" onsubmit="return confirm('Xóa mẫu Excel này?')">@csrf @method('DELETE')<button class="min-h-11 rounded-xl border border-red-300 px-4 text-red-700">Xóa mẫu</button></form>
    @endif
</div>
@endsection

@push('application-scripts')
<script>
document.querySelectorAll('#excel-column-list [data-move]').forEach(button => {
    button.addEventListener('click', () => {
        const item = button.closest('[data-excel-column]');
        const sibling = button.dataset.move === '-1' ? item.previousElementSibling : item.nextElementSibling;
        if (!sibling) return;
        if (button.dataset.move === '-1') sibling.before(item); else sibling.after(item);
    });
});
</script>
@endpush
