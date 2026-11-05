@extends('ClientPortal::layouts.application')
@section('title', 'Tạo SKU')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Medicine Master · SKU')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('content')
<div class="mx-auto max-w-3xl space-y-5">
    <a href="{{ route('client.pharma.products') }}" class="text-sm font-bold text-indigo-700">← Danh mục thuốc</a>
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Tạo SKU đầu tiên</h1>
        <p class="mt-2 text-sm text-slate-600">Thuốc: <strong>{{ $medicine->name }}</strong> · {{ $medicine->medicine_code }}</p>
        <p class="mt-1 text-sm text-slate-500">SKU sẽ được cấp tự động theo mã thuốc. Chỉ tạo sau khi xác nhận hàm lượng, quy cách và đơn vị tính.</p>
    </div>
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('client.pharma.products.variants.store', $medicine->id) }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <label class="block text-sm font-semibold text-slate-700">Hàm lượng / Nồng độ <span class="text-rose-600">*</span>
                <input required name="strength_text" maxlength="255" value="{{ old('strength_text', $medicine->concentration) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3" placeholder="Ví dụ: 140mg">
                @error('strength_text')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror
            </label>
            <label class="block text-sm font-semibold text-slate-700">Đơn vị tính <span class="text-rose-600">*</span>
                <input required name="base_unit" maxlength="100" value="{{ old('base_unit', $medicine->unit) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3" placeholder="Ví dụ: Viên">
                @error('base_unit')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror
            </label>
            <label class="block text-sm font-semibold text-slate-700 sm:col-span-2">Quy cách / Dạng trình bày <span class="text-rose-600">*</span>
                <input required name="presentation_text" maxlength="255" value="{{ old('presentation_text', $medicine->packaging_specification) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3" placeholder="Ví dụ: Hộp 3 vỉ x 10 viên">
                @error('presentation_text')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror
            </label>
            <label class="block text-sm font-semibold text-slate-700 sm:col-span-2">Giá kê khai (không bắt buộc)
                <input name="declared_price" type="number" min="0" step="0.01" value="{{ old('declared_price', $medicine->declared_price) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3">
            </label>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Tạo SKU không tự kích hoạt bán hàng, không tạo tồn kho hoặc bảng giá. Quy trình đồng bộ Product vẫn tuân theo cấu hình hiện hành.</div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('client.pharma.products') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold">Hủy</a>
            <button type="submit" class="rounded-xl bg-indigo-700 px-5 py-3 text-sm font-bold text-white">Xác nhận tạo SKU</button>
        </div>
    </form>
</div>
@endsection
