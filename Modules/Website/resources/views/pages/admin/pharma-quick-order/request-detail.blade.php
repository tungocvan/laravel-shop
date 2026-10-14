@extends('Admin::layouts.master')

@section('title', 'Chi tiết yêu cầu đặt hàng Pharma')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-6">
    <a class="text-sm text-emerald-700 underline" href="{{ route('admin.website.pharma-quick-order.requests.index') }}">← Danh sách yêu cầu</a>
    <h1 class="mt-3 text-2xl font-semibold">Yêu cầu #{{ $quickOrderRequest->id }}</h1>
    <p class="mt-2 text-sm text-gray-600">Trạng thái: <strong>{{ ['pending_review' => 'Chờ xét duyệt', 'accepted' => 'Đã chấp nhận', 'rejected' => 'Đã từ chối'][$quickOrderRequest->status] ?? $quickOrderRequest->status }}</strong></p>
    @if(session('success'))<div role="status" class="mt-4 rounded-lg bg-green-50 p-3 text-green-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="mt-4 rounded-lg bg-red-50 p-3 text-red-800">{{ $errors->first() }}</div>@endif

    <div class="mt-5 grid gap-4 md:grid-cols-2">
        <section class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold">Thông tin người mua và nhận hàng</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div><dt class="font-medium">Loại người mua</dt><dd>{{ $quickOrderRequest->buyer_type === 'company' ? 'Doanh nghiệp / cơ sở' : 'Cá nhân' }}</dd></div>
                <div><dt class="font-medium">Doanh nghiệp / cơ sở</dt><dd>{{ $quickOrderRequest->company_name ?: 'Chưa cung cấp' }}</dd></div>
                <div><dt class="font-medium">Mã số thuế</dt><dd>{{ $quickOrderRequest->tax_code ?: 'Chưa cung cấp' }}</dd></div>
                <div><dt class="font-medium">Người nhận</dt><dd>{{ $quickOrderRequest->recipient_name ?: 'Chưa cung cấp' }}</dd></div>
                <div><dt class="font-medium">Số điện thoại</dt><dd>{{ $quickOrderRequest->phone ?: 'Chưa cung cấp' }}</dd></div>
                <div><dt class="font-medium">Địa chỉ nhận hàng</dt><dd class="whitespace-pre-wrap">{{ $quickOrderRequest->shipping_address ?: 'Chưa cung cấp' }}</dd></div>
                <div><dt class="font-medium">Ghi chú</dt><dd class="whitespace-pre-wrap">{{ $quickOrderRequest->notes ?: 'Không có' }}</dd></div>
            </dl>
        </section>
        <section class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold">Sản phẩm yêu cầu</h2>
            <p class="mt-2 text-xs text-amber-700">Danh sách chỉ là số lượng khách yêu cầu. Cần đối chiếu sản phẩm, bảng giá, VAT và tư cách người mua trước khi tạo phiếu xuất.</p>
            <table class="mt-3 w-full text-sm">
                <thead><tr class="border-b"><th class="py-2 text-left">Mã sản phẩm Website</th><th class="py-2 text-right">Số lượng</th></tr></thead>
                <tbody>
                    @foreach($quickOrderRequest->requested_items ?? [] as $line)
                        <tr class="border-b"><td class="py-2">#{{ $line['product_id'] ?? '?' }}</td><td class="py-2 text-right">{{ $line['quantity'] ?? '?' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>

    @if($quickOrderRequest->status === 'pending_review')
        <form method="POST" action="{{ route('admin.website.pharma-quick-order.requests.review', $quickOrderRequest) }}" class="mt-5 space-y-4 rounded-xl border bg-white p-4">
            @csrf
            <h2 class="font-semibold">Xét duyệt yêu cầu</h2>
            <label class="block text-sm font-medium" for="quick-order-review-note">Ghi chú xét duyệt (bắt buộc)</label>
            <textarea id="quick-order-review-note" name="note" required maxlength="2000" rows="3" class="w-full rounded-lg border p-3 text-sm">{{ old('note') }}</textarea>
            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" name="eligibility_confirmed" value="1" class="mt-1">
                <span>Tôi đã kiểm tra thông tin người mua và xác nhận đủ điều kiện tiếp nhận yêu cầu mua dược phẩm. Việc chấp nhận chưa đồng nghĩa được xuất kho.</span>
            </label>
            <div class="flex flex-wrap gap-3">
                <button type="submit" name="decision" value="accepted" class="rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white">Chấp nhận yêu cầu</button>
                <button type="submit" name="decision" value="rejected" class="rounded-lg border border-red-300 px-4 py-2.5 font-semibold text-red-700">Từ chối yêu cầu</button>
            </div>
        </form>
    @endif

    <section class="mt-5 rounded-xl border bg-white p-4">
        <h2 class="font-semibold">Lịch sử xét duyệt</h2>
        @forelse($reviews as $review)
            <div class="mt-3 border-t pt-3 text-sm">
                {{ \Illuminate\Support\Carbon::parse($review->created_at)->format('d/m/Y H:i') }}
                · Admin #{{ $review->admin_id }} · {{ $review->from_status }} → {{ $review->to_status }}
                <p class="mt-1 whitespace-pre-wrap">{{ $review->note }}</p>
            </div>
        @empty
            <p class="mt-2 text-sm text-gray-500">Chưa có thao tác xét duyệt.</p>
        @endforelse
    </section>
</div>
@endsection
