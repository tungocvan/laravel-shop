@extends('ClientPortal::layouts.application')
@section('title', 'Chi tiết thuốc trúng thầu')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('content')
@php
    $winning = $item['winningName'] ?? null;
    $winning = is_array($winning) ? implode(', ', array_filter($winning, 'is_scalar')) : (is_scalar($winning) ? (string) $winning : '');
    $fields = [
        'Nhóm thuốc' => 'nhomThuoc',
        'Đường dùng' => 'duongDung',
        'Dạng bào chế' => 'dangBaoChe',
        'Đơn vị tính' => 'donViTinh',
        'Quy cách đóng gói' => 'quyCachDongGoi',
        'Hạn dùng' => 'hanDung',
        'Giá trúng thầu' => 'donGia',
        'Số lượng' => 'soLuong',
        'Mã nhà thầu trúng' => 'winningCode',
        'Chủ đầu tư / bên mời thầu' => 'tenCdtBmt',
        'Mã TBMT' => 'maTbmt',
        'Số quyết định' => 'soQuyetDinh',
        'Ngày quyết định' => 'ngayBanHanhQuyetDinh',
        'Ngày đăng KQLCNT' => 'ngayDangTaiKqlcnt',
        'Cơ sở sản xuất' => 'tenCoSoSanXuat',
        'Nước sản xuất' => 'nuocSanXuat',
        'GĐKLH / GPNK' => 'gdklh_GPNK',
    ];
@endphp
<div class="mx-auto max-w-5xl space-y-4">
    <a class="inline-flex min-h-10 items-center text-sm font-semibold text-slate-700" href="{{ route('client.pharma.award-prices', ['keyword' => $keyword, 'page' => $page]) }}">← Quay lại kết quả</a>
    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <header class="border-b border-slate-200 bg-slate-50 p-5 sm:p-6">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Chi tiết kết quả trúng thầu · Pharma</p>
            <h1 class="mt-2 text-2xl font-black text-slate-950">{{ $item['tenThuoc'] ?? '—' }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $item['tenHoatChat'] ?? '—' }} · {{ $item['nongDo'] ?? '—' }}</p>
        </header>
        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($fields as $label => $field)
                <div class="min-w-0 border-b border-r border-slate-100 p-4">
                    <dt class="text-xs font-bold uppercase text-slate-500">{{ $label }}</dt>
                    <dd class="mt-1 break-words text-sm font-semibold text-slate-900">
                        @if($field === 'donGia' && is_numeric($item[$field] ?? null))
                            {{ number_format((float) $item[$field], 0, ',', '.') }} đ
                        @else
                            {{ is_array($item[$field] ?? null) ? (implode('; ', array_filter($item[$field], 'is_scalar')) ?: '—') : (is_scalar($item[$field] ?? null) && trim((string) $item[$field]) !== '' ? $item[$field] : '—') }}
                        @endif
                    </dd>
                </div>
                @if($field === 'soLuong')
                    <div class="min-w-0 border-b border-r border-slate-100 p-4">
                        <dt class="text-xs font-bold uppercase text-slate-500">Đơn vị trúng thầu</dt>
                        <dd class="mt-1 break-words text-sm font-semibold">{{ $winning ?: '—' }}</dd>
                    </div>
                @endif
            @endforeach
        </dl>
        <footer class="space-y-3 border-t border-slate-200 bg-slate-50 p-5">
            @if($match['status'] === 'review')
                <p role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-900">Cần đối chiếu thủ công: {{ $match['reason'] }} Không tự động tạo hoặc ghép thuốc.</p>
            @elseif($match['status'] === 'existing')
                <p class="text-sm text-emerald-800">Đã xác định thuốc #{{ $match['medicine_id'] }} trong danh mục. Chỉ cập nhật nguồn trúng thầu.</p>
            @else
                <p class="text-sm text-indigo-800">Chưa có thuốc trùng bộ ba định danh. Bước tiếp theo sẽ yêu cầu xác nhận tạo mới ở trạng thái Cần rà soát.</p>
            @endif
            @if($match['status'] === 'new' && !($match['registration_verified'] ?? false))
                <p class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Số GĐKLH/GPNK chưa được xác minh. Nếu xác nhận đồng bộ, hệ thống sẽ tạo thuốc mới cần rà soát và lưu mã nguồn trong lịch sử trúng thầu, không ghi vào số đăng ký chính thức.</p>
            @endif
            @if($canSync && $match['status'] !== 'review')
                <form method="POST" action="{{ route('client.pharma.award-prices.sync.preview') }}">
                    @csrf
                    <input type="hidden" name="keyword" value="{{ $keyword }}">
                    <input type="hidden" name="page" value="{{ $page }}">
                    <input type="hidden" name="selected[]" value="{{ $sourceId }}">
                    <button type="submit" class="min-h-11 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white">Đồng bộ bản ghi này vào danh mục thuốc</button>
                </form>
            @elseif(!$canSync)
                <p class="text-sm text-slate-600">Tài khoản chưa có quyền tạo/đồng bộ danh mục thuốc.</p>
            @endif
            <p class="text-xs text-slate-500">Nguồn dữ liệu công khai, chỉ để tham khảo. Không ghi đè giá kê khai, giá bán hoặc tồn kho.</p>
        </footer>
    </article>
</div>
@endsection
