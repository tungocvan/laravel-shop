@extends('ClientPortal::layouts.application')
@section('title', 'Xác nhận đồng bộ thuốc trúng thầu')
@section('app-name', 'Pharma')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <section class="rounded-3xl bg-slate-950 p-6 text-white">
        <h1 class="text-2xl font-black">Xác nhận đồng bộ thuốc trúng thầu</h1>
        <p class="mt-2 text-sm text-slate-200">Đối chiếu tên thuốc + số đăng ký + ĐVT. Số đăng ký chưa xác minh có thể tạo thuốc mới ở trạng thái Cần rà soát sau khi xác nhận; mã nguồn được lưu riêng, không ghi vào số đăng ký chính thức. Giá trúng thầu chỉ lưu trong nguồn, không ghi đè giá kê khai hoặc giá bán.</p>
    </section>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto"><table class="w-full min-w-[700px] text-left text-sm">
            <thead class="bg-slate-50 text-slate-600"><tr><th class="p-3">Thuốc / SĐK / ĐVT</th><th class="p-3">Giá trúng thầu</th><th class="p-3">Đối chiếu</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @foreach($rows as $row)
                <tr><td class="p-3"><strong>{{ $row['item']['tenThuoc'] ?? '—' }}</strong><p>{{ $row['item']['gdklh_GPNK'] ?? 'Chưa có SĐK' }} · {{ $row['item']['donViTinh'] ?? '—' }}</p></td>
                <td class="p-3">{{ is_numeric($row['item']['donGia'] ?? null) ? number_format((float) $row['item']['donGia'], 0, ',', '.').' đ' : '—' }}</td>
                <td class="p-3">@if($row['match']['status'] === 'existing')<span class="font-semibold text-emerald-700">Cập nhật thông tin nguồn · #{{ $row['match']['medicine_id'] }}</span>
                @elseif($row['match']['status'] === 'new')<span class="font-semibold text-indigo-700">Tạo thuốc mới (Cần rà soát) @unless($row['match']['registration_verified'] ?? false) · SĐK chưa xác minh, chỉ lưu mã nguồn @endunless</span>
                @else<span class="font-semibold text-amber-800">Cần đối chiếu thủ công: {{ $row['match']['reason'] }}</span>@endif</td></tr>
            @endforeach
            </tbody>
        </table></div>
    </div>
    @php $hasReview = collect($rows)->contains(fn ($row) => $row['match']['status'] === 'review'); @endphp
    @if($hasReview)<div role="alert" class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-900">Có bản ghi chưa đủ điều kiện. Hãy quay lại và bỏ chọn các bản ghi cần xác minh trước khi đồng bộ.</div>@endif
    @if($errors->any())<div role="alert" class="rounded-2xl bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('client.pharma.award-prices', ['keyword' => $keyword]) }}" class="min-h-11 rounded-xl border border-slate-300 px-5 py-3 font-semibold">← Quay lại</a>
        @unless($hasReview)
            <form action="{{ route('client.pharma.award-prices.sync.store') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required class="mt-1">Tôi xác nhận tạo thuốc mới ở trạng thái Cần rà soát, giữ SĐK chưa xác minh trong nguồn trúng thầu, và chỉ cập nhật thông tin nguồn cho thuốc đã tồn tại.</label>
                <button type="submit" class="min-h-11 rounded-xl bg-emerald-600 px-6 font-bold text-white">Xác nhận đồng bộ {{ count($rows) }} bản ghi</button>
            </form>
        @endunless
    </div>
</div>
@endsection
