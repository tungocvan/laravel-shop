@extends('ClientPortal::layouts.application')
@section('title', 'Chi tiết thuốc trúng thầu')
@section('app-name', 'Mua sắm công')
@section('app-dashboard-route', route('client.muasamcong.dashboard'))
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ route('client.muasamcong.drug-pricing', ['keyword' => $keyword]) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">← Quay lại kết quả</a>
    @if($synced)<span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">✓ Đã đồng bộ</span>@endif
</div>
<section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 bg-slate-50 px-5 py-5 sm:px-7"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Chi tiết kết quả Mua sắm công</p><h1 class="mt-2 text-2xl font-bold text-slate-950">{{ $item['tenThuoc'] ?? 'Không có tên thuốc' }}</h1><p class="mt-2 text-sm text-slate-600">{{ $item['tenHoatChat'] ?? '—' }}{{ !empty($item['nongDo']) ? ' · '.$item['nongDo'] : '' }}</p></div>
    @php($fields = [
        'Nhóm thuốc' => $item['nhomThuoc'] ?? $item['groupMedicine'] ?? null,
        'Đường dùng' => $item['duongDung'] ?? null,
        'Dạng bào chế' => $item['dangBaoChe'] ?? null,
        'Đơn vị tính' => $item['donViTinh'] ?? null,
        'Quy cách đóng gói' => $item['quyCachDongGoi'] ?? null,
        'Hạn dùng' => $item['hanDung'] ?? null,
        'Giá trúng thầu' => is_numeric($item['donGia'] ?? null) ? number_format((float)$item['donGia'], 0, ',', '.').' đ' : null,
        'Số lượng' => is_numeric($item['soLuong'] ?? null) ? number_format((float)$item['soLuong'], 0, ',', '.') : null,
        'Đơn vị trúng thầu' => implode('; ', array_map('strval', (array)($item['winningName'] ?? []))),
        'Mã nhà thầu trúng' => implode('; ', array_map('strval', (array)($item['winningCode'] ?? []))),
        'Chủ đầu tư / Bên mời thầu' => $item['tenCdtBmt'] ?? null,
        'Mã TBMT' => $item['maTbmt'] ?? null,
        'Số quyết định' => $item['soQuyetDinh'] ?? null,
        'Ngày quyết định' => $item['ngayBanHanhQuyetDinh'] ?? null,
        'Ngày đăng KQLCNT' => $item['ngayDangTaiKqlcnt'] ?? null,
        'Cơ sở sản xuất' => $item['tenCoSoSanXuat'] ?? null,
        'Nước sản xuất' => $item['nuocSanXuat'] ?? null,
        'GĐKLH / GPNK' => $item['gdklh_GPNK'] ?? null,
    ])
    <dl class="grid gap-0 md:grid-cols-2 xl:grid-cols-3">@foreach($fields as $label => $value)<div class="border-b border-slate-100 px-5 py-4 md:border-r xl:px-6"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</dt><dd class="mt-1.5 break-words text-sm font-medium text-slate-800">{{ ($value !== null && $value !== '') ? $value : '—' }}</dd></div>@endforeach</dl>
    @if($canSync && !$synced && !empty($item['id']))<div class="border-t border-slate-200 bg-slate-50 px-5 py-5 sm:px-7"><form method="POST" action="{{ route('client.muasamcong.drug-pricing.sync') }}">@csrf<input type="hidden" name="keyword" value="{{ $keyword }}"><input type="hidden" name="selected_ids[]" value="{{ $item['id'] }}"><button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white">Đồng bộ bản ghi này qua Queue</button></form></div>@endif
</section>
@endsection
