@extends('Admin::layouts.master')

@section('title', 'Thiết lập đặt hàng nhanh Pharma')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-semibold text-gray-900">Đặt hàng nhanh Pharma</h1>
    <p class="mt-2 text-sm text-gray-600">Chỉ bảng giá đang hoạt động và được chọn tại đây mới có thể trở thành nguồn đặt hàng. Quyền người dùng và phạm vi khách hàng vẫn phải được kiểm tra ở phía máy chủ.</p>

    @if(session('success'))
        <div role="status" class="mt-4 rounded-lg bg-green-50 p-3 text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="mt-4 rounded-lg bg-red-50 p-3 text-red-800">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.website.pharma-quick-order.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('PUT')
        <input type="hidden" name="enabled" value="0">
        <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4">
            <input type="checkbox" name="enabled" value="1" @checked((bool) old('enabled', $configuration['enabled'])) class="h-5 w-5 rounded border-gray-300">
            <span class="font-medium">Bật đặt hàng nhanh Pharma</span>
        </label>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h2 class="font-semibold">Nguồn bảng giá được phép sử dụng</h2>
            <p class="mt-1 text-sm text-gray-500">Bảng giá hết hiệu lực theo ngày sẽ tự bị loại tại thời điểm đặt hàng. Nếu chưa chọn bảng giá, hệ thống không trả về sản phẩm để đặt hàng.</p>
            <div class="mt-4 max-h-[460px] space-y-2 overflow-y-auto">
                @forelse($priceLists as $priceList)
                    @php($active = $priceList->status === \Modules\Pharma\Models\PriceList::STATUS_ACTIVE)
                    <label class="flex gap-3 rounded-lg border border-gray-100 p-3 {{ $active ? '' : 'opacity-50' }}">
                        <input type="checkbox" name="price_list_ids[]" value="{{ $priceList->id }}"
                            @checked(in_array($priceList->id, array_map('intval', old('price_list_ids', $configuration['price_list_ids'])), true))
                            @disabled(!$active) class="mt-1 h-4 w-4">
                        <span class="text-sm">
                            <strong>{{ $priceList->code }} — {{ $priceList->name }}</strong>
                            <span class="block text-gray-500">{{ $priceList->type }} · {{ $priceList->status }} · {{ $priceList->effective_from?->format('d/m/Y') ?? 'Không giới hạn' }} – {{ $priceList->effective_to?->format('d/m/Y') ?? 'Không giới hạn' }}</span>
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-gray-500">Chưa có bảng giá Pharma.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <label for="quick-order-notification-email" class="block font-semibold text-gray-900">Email nhận yêu cầu đặt hàng mới</label>
            <p class="mt-1 text-sm text-gray-500">Khi khách gửi yêu cầu thành công, hệ thống sẽ gửi thông báo tới địa chỉ này. Để trống nếu không cần thông báo email. Yêu cầu vẫn được lưu nếu email gặp lỗi.</p>
            <input id="quick-order-notification-email" type="email" name="notification_email" maxlength="254" autocomplete="email"
                value="{{ old('notification_email', $configuration['notification_email'] ?? '') }}"
                placeholder="donhang@example.com"
                class="mt-3 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
            @error('notification_email')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-3 font-medium text-white hover:bg-blue-700 active:scale-[0.985]">Lưu thiết lập</button>
    </form>
</div>
@endsection
