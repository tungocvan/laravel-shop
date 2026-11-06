@extends('ClientPortal::layouts.application')
@section('title', 'Xem trước đồng bộ DAV')
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <a class="inline-flex min-h-11 items-center text-sm font-semibold text-indigo-700" href="{{ route('client.pharma.dav-prices', ['q' => $input['q'], 'page' => $input['page']]) }}">← Trở lại kết quả DAV</a>
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <h1 class="text-2xl font-black text-slate-950">Xem trước đồng bộ thuốc DAV</h1>
        <p class="mt-2 text-sm text-slate-600">Chỉ tạo thuốc mới sau khi bạn xác nhận. Giá DAV không tự động trở thành giá bán nội bộ.</p>
        <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
            @foreach(['Tên thuốc' => 'name', 'Số đăng ký' => 'registration', 'Hoạt chất' => 'ingredient', 'Hàm lượng' => 'strength', 'Dạng bào chế' => 'dosage', 'Nước sản xuất' => 'country', 'Đơn vị tính' => 'unit', 'Nhà sản xuất' => 'manufacturer', 'Đơn vị kê khai' => 'declarant', 'Quy cách đóng gói' => 'package'] as $label => $key)
                <div><dt class="text-xs font-semibold text-slate-500">{{ $label }}</dt><dd class="mt-1 break-words text-slate-900">{{ $candidate[$key] ?: 'Chưa có dữ liệu' }}</dd></div>
            @endforeach
            <div><dt class="text-xs font-semibold text-slate-500">Giá bán buôn DAV (tham khảo)</dt><dd class="mt-1 font-bold text-indigo-700">{{ $candidate['wholesale'] === null ? 'Chưa công bố' : number_format($candidate['wholesale'], 0, ',', '.').' đ / '.$candidate['unit'] }}</dd></div>
        </dl>
    </section>
    @if($errors->any())<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
    @if($match['status'] === 'new')
        <form method="POST" action="{{ route('client.pharma.dav-prices.import.store') }}" class="rounded-2xl border border-slate-200 bg-white p-5">
            @csrf
            <input type="hidden" name="q" value="{{ $input['q'] }}">
            <input type="hidden" name="page" value="{{ $input['page'] }}">
            <input type="hidden" name="source_id" value="{{ $input['source_id'] }}">
            <label class="flex items-start gap-3 text-sm text-slate-700"><input type="checkbox" name="confirmed" value="1" required class="mt-1">Tôi đã kiểm tra tên thuốc, số đăng ký, quy cách và đồng ý tạo thuốc mới trong Medicine Master.</label>
            <button type="submit" class="mt-4 min-h-12 w-full rounded-xl bg-indigo-700 px-5 font-bold text-white">Xác nhận tạo thuốc</button>
        </form>
    @elseif($match['status'] === 'existing')
        <a class="inline-flex min-h-12 items-center rounded-xl bg-emerald-700 px-5 font-bold text-white" href="{{ route('client.pharma.products.edit', $match['medicine_id']) }}">Thuốc đã tồn tại — xem trong danh mục</a>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Phát hiện dữ liệu trùng hoặc thiếu định danh. Vui lòng kiểm tra thủ công trong danh mục trước khi tạo.</div>
    @endif
</div>
@endsection
