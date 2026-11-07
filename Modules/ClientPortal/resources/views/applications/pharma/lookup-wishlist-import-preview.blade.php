@extends('ClientPortal::layouts.application')
@section('title', 'Xem trước đồng bộ thuốc công bố')
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <a href="{{ route('client.pharma.lookups.wishlist.show', ['sourceType' => $favorite->source_type, 'sourceId' => $favorite->source_id]) }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-indigo-700">← Thuốc đã quan tâm</a>
    <section class="rounded-3xl border border-slate-200 bg-white p-5">
        <h1 class="text-2xl font-black">Xem trước đồng bộ thuốc công bố</h1>
        <p class="mt-2 text-sm text-slate-600">Đã truy vấn lại dữ liệu từ nguồn công bố. Chỉ tạo thuốc mới sau khi xác nhận; không tự gán giá, lô hoặc giá bán.</p>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            @foreach(['Tên thuốc' => 'name', 'Số đăng ký' => 'registration', 'Hoạt chất / dược liệu' => 'ingredient', 'Hàm lượng' => 'strength', 'Dạng bào chế' => 'dosage_form', 'Quy cách đóng gói' => 'packaging', 'Nhà sản xuất' => 'manufacturer', 'Công ty đăng ký' => 'registrant'] as $label => $key)
                <div><dt class="text-xs font-bold text-slate-500">{{ $label }}</dt><dd class="mt-1 whitespace-pre-line break-words text-sm">{{ $candidate[$key] ?: 'Chưa công bố' }}</dd></div>
            @endforeach
        </dl>
    </section>
    @if($errors->any())<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
    @if($match['status'] === 'existing')
        <a href="{{ route('client.pharma.products.edit', $match['medicine_id']) }}" class="inline-flex min-h-12 items-center rounded-xl bg-emerald-700 px-5 font-bold text-white">Thuốc đã tồn tại — xem Danh mục</a>
    @elseif($match['status'] !== 'new')
        <div role="alert" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Thuốc thiếu thông tin hoặc có nguy cơ trùng. Cần đối chiếu thủ công trước khi đồng bộ.</div>
    @else
        <form method="POST" action="{{ route('client.pharma.lookups.wishlist.import.store', ['sourceType' => $favorite->source_type, 'sourceId' => $favorite->source_id]) }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5">
            @csrf
            <label class="block text-sm font-bold text-slate-700">Đơn vị tính trong Danh mục thuốc <span class="text-rose-700">*</span>
                <select name="unit" required class="mt-2 min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3">
                    <option value="">Chọn đơn vị tính</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit }}" @selected(old('unit') === $unit)>{{ $unit }}</option>
                    @endforeach
                </select>
            </label>
            @if(empty($units))<p class="text-sm text-amber-800">Chưa có đơn vị tính nào trong Danh mục thuốc. Vui lòng bổ sung dữ liệu chuẩn trước khi đồng bộ.</p>@endif
            <label class="flex items-start gap-3 text-sm text-slate-700">
                <input type="checkbox" name="confirmed" value="1" required class="mt-1">
                Tôi đã kiểm tra tên thuốc, số đăng ký, đơn vị tính, quy cách và đồng ý tạo thuốc mới.
            </label>
            <button type="submit" @disabled(empty($units)) class="min-h-12 w-full rounded-xl bg-indigo-700 px-5 font-bold text-white disabled:cursor-not-allowed disabled:opacity-50">Xác nhận đồng bộ vào Danh mục</button>
        </form>
    @endif
</div>
@endsection
